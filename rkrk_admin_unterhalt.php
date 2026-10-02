<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');
include_once('scripts/rkrk_catalog_backup.php');
include_once('scripts/rkrk_site_backup.php');
include_once('scripts/rkrk_code_sync.php');

CheckIPandExit();

$user = rkrk_unterhalt_user();
$uhAction = (string)($_POST['uh_action'] ?? $_GET['uh_action'] ?? '');
$okMsg = isset($_GET['ok']) ? (string)$_GET['ok'] : '';
$errMsg = isset($_GET['error']) ? (string)$_GET['error'] : '';
$syncPlan = null;
$syncRouteId = (string)($_POST['route'] ?? $_GET['route'] ?? '');
$syncMode = function_exists('rkrk_code_sync_mode')
	? rkrk_code_sync_mode($_POST['sync_mode'] ?? $_GET['sync_mode'] ?? 'mirror')
	: 'mirror';
$needsSilentAuth = ($uhAction === 'download_catalog' || $uhAction === 'download_site' || $_SERVER['REQUEST_METHOD'] === 'POST');

if ($needsSilentAuth) {
	$GLOBALS['RKRK_AJAX_JSON_MODE'] = true;
	GrantAccess();
	unset($GLOBALS['RKRK_AJAX_JSON_MODE']);
	if (empty($LoggedOn) || !GetAccess('TestOnly')) {
		if ($uhAction === 'download_catalog' || $uhAction === 'download_site' || $_SERVER['REQUEST_METHOD'] === 'POST') {
			header('HTTP/1.1 403 Forbidden');
			echo 'Kein Zugriff.';
			exit;
		}
	}
}

if (!function_exists('rkrk_unterhalt_go')) {
	function rkrk_unterhalt_go($ok, $error = '', $extra = array()) {
		$q = array();
		if ($ok !== '') {
			$q['ok'] = $ok;
		}
		if ($error !== '') {
			$q['error'] = $error;
		}
		foreach ($extra as $k => $v) {
			$q[$k] = $v;
		}
		header('Location: rkrk_admin_unterhalt.php' . ($q ? ('?' . http_build_query($q)) : ''));
		exit;
	}
}

if (!function_exists('rkrk_unterhalt_echo_pill')) {
	function rkrk_unterhalt_echo_pill($type, $name, $value, $checked, $label, $extraClass = '', $title = '') {
		$h = 'rkrk_unterhalt_h';
		$chk = $checked ? ' checked' : '';
		$cls = 'rkrk-sel rkrk-reg-pill';
		if ($extraClass !== '') {
			$cls .= ' ' . $extraClass;
		}
		$titleAttr = $title !== '' ? (" title='" . $h($title) . "'") : '';
		echo "<label class='" . $cls . "'" . $titleAttr . "><input type='" . $h($type) . "' name='" . $h($name) . "' value='" . $h($value) . "'{$chk}> " . $h($label) . "</label>";
	}
}

if (!function_exists('rkrk_unterhalt_sync_redirect')) {
	function rkrk_unterhalt_sync_redirect($routeId, $mode, $applied = false) {
		$extra = array('route' => $routeId, 'sync_mode' => $mode);
		if ($applied) {
			$extra['applied'] = '1';
		}
		$mods = function_exists('rkrk_code_sync_selected_rkrs_folders')
			? rkrk_code_sync_selected_rkrs_folders()
			: array();
		if ($mods !== array()) {
			$extra['rkrs_mod'] = array_values($mods);
		}
		return $extra;
	}
}

if (!function_exists('rkrk_unterhalt_echo_status_pill')) {
	function rkrk_unterhalt_echo_status_pill($ok, $okLabel = 'vorhanden', $badLabel = 'fehlt') {
		$h = 'rkrk_unterhalt_h';
		$cls = 'rkrk-status-pill' . ($ok ? ' is-ok' : ' is-missing');
		echo "<span class='" . $cls . "'>" . $h($ok ? $okLabel : $badLabel) . "</span>";
	}
}

if ($uhAction === 'download_catalog' && isset($_GET['id'])) {
	$err = rkrk_catalog_backup_download((string)$_GET['id']);
	if (is_string($err) && $err !== '') {
		$errMsg = $err;
	}
}
if ($uhAction === 'download_site' && isset($_GET['id'])) {
	$err = rkrk_site_backup_download((string)$_GET['id']);
	if (is_string($err) && $err !== '') {
		$errMsg = $err;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$cfgPost = rkrk_unterhalt_load();
	if (!rkrk_unterhalt_is_control_site($cfgPost)) {
		rkrk_unterhalt_go('', 'Unterhalt schreibt nur von test27 oder test aus.');
	}
	if ($uhAction === 'save_dirs') {
		$cfg = rkrk_unterhalt_from_post($_POST);
		$msg = rkrk_unterhalt_save($cfg);
		if ($msg === '') {
			rkrk_unterhalt_go('Verzeichnisse und Ordner-Matrix gespeichert.');
		}
		rkrk_unterhalt_go('', $msg);
	}
	if ($uhAction === 'catalog_create') {
		$res = rkrk_catalog_backup_create($user, trim((string)($_POST['note'] ?? '')), 'manual');
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Katalog-Sicherung ' . ($res['id'] ?? '') . ' erstellt.');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Sicherung fehlgeschlagen.');
	}
	if ($uhAction === 'catalog_restore') {
		$res = rkrk_catalog_backup_restore((string)($_POST['id'] ?? ''), $user);
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Katalog wiederhergestellt (' . (int)($res['restored'] ?? 0) . ' Dateien). Sicherheit: ' . ($res['safety'] ?? ''));
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Wiederherstellen fehlgeschlagen.');
	}
	if ($uhAction === 'catalog_delete') {
		$res = rkrk_catalog_backup_delete((string)($_POST['id'] ?? ''));
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Katalog-Sicherung gelöscht.');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Löschen fehlgeschlagen.');
	}
	if ($uhAction === 'catalog_upload') {
		$res = rkrk_catalog_backup_restore_upload($_FILES['backup_zip'] ?? array(), $user);
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('ZIP eingespielt (' . (int)($res['restored'] ?? 0) . ' Dateien).');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Upload fehlgeschlagen.');
	}
	if ($uhAction === 'catalog_history_restore') {
		$res = rkrk_catalog_history_restore((string)($_POST['hist_dir'] ?? ''), (string)($_POST['hist_name'] ?? ''), $user);
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Datei zurückgesetzt: ' . ($res['restored'] ?? ''));
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Zurücksetzen fehlgeschlagen.');
	}
	if ($uhAction === 'site_create') {
		$res = rkrk_site_backup_create($user, trim((string)($_POST['note'] ?? '')), 'manual');
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Website-Sicherung ' . ($res['id'] ?? '') . ' erstellt.');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Website-Sicherung fehlgeschlagen.');
	}
	if ($uhAction === 'site_restore') {
		$res = rkrk_site_backup_restore((string)($_POST['id'] ?? ''), $user);
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Website wiederhergestellt (' . (int)($res['restored'] ?? 0) . ' Dateien). Sicherheit: ' . ($res['safety'] ?? ''));
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Website-Restore fehlgeschlagen.');
	}
	if ($uhAction === 'site_delete') {
		$res = rkrk_site_backup_delete((string)($_POST['id'] ?? ''));
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Website-Sicherung gelöscht.');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Löschen fehlgeschlagen.');
	}
	if ($uhAction === 'site_upload') {
		$res = rkrk_site_backup_restore_upload($_FILES['site_backup_zip'] ?? array(), $user);
		if (!empty($res['ok'])) {
			rkrk_unterhalt_go('Website-ZIP eingespielt (' . (int)($res['restored'] ?? 0) . ' Dateien).');
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Upload fehlgeschlagen.');
	}
	if ($uhAction === 'sync_apply') {
		$res = rkrk_code_sync_apply($syncRouteId, null, $syncMode);
		if (!empty($res['ok'])) {
			$fail = !empty($res['failed']) ? (' Fehlgeschlagen: ' . implode(', ', $res['failed'])) : '';
			$destN = isset($res['dest_only']) ? count($res['dest_only']) : 0;
			$deleted = (int)($res['deleted'] ?? 0);
			$hopN = isset($res['hops']) && is_array($res['hops']) ? count($res['hops']) : 0;
			$msg = (int)($res['copied'] ?? 0) . ' Dateien kopiert.';
			if ($hopN > 1) {
				$msg = $hopN . ' Stufen. ' . $msg;
			}
			if ($syncMode === 'mirror') {
				$msg .= ' Mirror: ' . $deleted . ' überzählige Dateien gelöscht.';
			} elseif ($hopN <= 1) {
				$msg .= ' Nur im Ziel (nicht gelöscht): ' . $destN . '.';
			}
			rkrk_unterhalt_go($msg . $fail, '', rkrk_unterhalt_sync_redirect($syncRouteId, $syncMode, true));
		}
		rkrk_unterhalt_go('', $res['error'] ?? 'Sync fehlgeschlagen.', rkrk_unterhalt_sync_redirect($syncRouteId, $syncMode, false));
	}
	if ($uhAction === 'sync_preview') {
		$syncPlan = rkrk_code_sync_plan($syncRouteId);
	}
}

if (!rkrk_admin_boot('Unterhalt')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_unterhalt.php');

$cfgEarly = rkrk_unterhalt_load();
if (rkrk_unterhalt_is_control_site($cfgEarly)) {
	rkrk_catalog_backup_maybe_auto_daily($user);
}

$cfg = rkrk_unterhalt_load();
$siteKey = rkrk_unterhalt_current_site_key($cfg);
$siteLabel = (string)($cfg['sites'][$siteKey]['label'] ?? $siteKey);
$catalogSnaps = rkrk_catalog_backup_list();
$siteSnaps = rkrk_site_backup_list($siteKey);
$history = rkrk_catalog_history_list(150);
$routes = rkrk_code_sync_routes($cfg);
$hasZip = class_exists('ZipArchive');
$h = 'rkrk_unterhalt_h';

if ($syncRouteId === '' && isset($_GET['route']) && isset($routes[(string)$_GET['route']])) {
	$syncRouteId = (string)$_GET['route'];
}
if ($syncRouteId === '' || !isset($routes[$syncRouteId])) {
	$syncRouteId = rkrk_code_sync_default_route($cfg);
}
if ($syncPlan === null && !empty($_GET['applied']) && $syncRouteId !== '') {
	$syncPlan = rkrk_code_sync_plan($syncRouteId);
}

echo "<link rel='stylesheet' href='template/rkrk_reg_pills.css?v=20260911status'>\n";
echo "<link rel='stylesheet' href='template/rkrk_page_help.css?v=20260912uh'>\n";
echo "<style>
.uh-card{background:#fff;border:1px solid #d7ddd7;border-radius:10px;padding:1rem 1.1rem;margin:1rem 0;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.uh-card h2{margin:0 0 .5rem}
.uh-muted{color:#5a675a;font-size:.95em}
.uh-row{display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end;margin:.6rem 0}
.uh-row label:not(.rkrk-sel):not(.rkrk-reg-pill){display:flex;flex-direction:column;font-size:.9em;gap:.2rem}
.uh-scroll{overflow:auto;max-height:22rem}
.uh-actions form,.uh-actions a{display:inline-block;margin:0 .2rem .2rem 0}
.uh-ok{color:#1b5e20;font-weight:600}
.uh-err{color:#b71c1c;font-weight:600}
.uh-matrix td,.uh-matrix th{text-align:center}
.uh-matrix td:first-child,.uh-matrix th:first-child{text-align:left}
.uh-matrix .rkrk-reg-pill,.uh-matrix .rkrk-sel,.uh-matrix .rkrk-status-pill{font-size:11px;padding:1px 8px;min-height:20px}
.uh-warn{background:#fff8e1;border:1px solid #ffe082;padding:.5rem .7rem;border-radius:6px}
.uh-phase{background:#e8f4fc;border:1px solid #90caf9;padding:.6rem .8rem;border-radius:6px;margin:0 0 1rem}
.uh-field{display:flex;flex-direction:column;gap:.25rem;font-size:.9em}
.uh-pills{display:inline-flex;flex-wrap:wrap;align-items:center;gap:5px 6px;vertical-align:middle}
.uh-rkrs-mod{display:inline-flex;flex-direction:column;align-items:flex-start;gap:1px;margin:0 8px 4px 0}
.uh-rkrs-mod .uh-muted{font-size:.8em}
.rkrk-help-fab{top:72px!important;z-index:400!important}
</style>\n";

$onControl = rkrk_unterhalt_is_control_site($cfg);
$syncApplyOk = rkrk_unterhalt_sync_apply_allowed($cfg);
$siteBackupOk = rkrk_unterhalt_site_backup_allowed($siteKey, $cfg);

if ($okMsg !== '') {
	echo "<p class='uh-ok'>" . $h($okMsg) . "</p>\n";
}
if ($errMsg !== '') {
	echo "<p class='uh-err'>" . $h($errMsg) . "</p>\n";
}

echo "<div class='uh-phase' data-uh-help='unterhalt.overview'><b>Steuerung: test27 oder test.</b> Sichern und Synchronisieren von dieser Instanz weiter in der Kette: von <code>test27</code> nach <code>test</code>, von <code>test</code> nach <code>v2</code>, zuletzt <code>v2 → pub</code>. <b>Alle Ebenen |</b> nimmt die restlichen Stufen ab hier, ohne rkrs. Modus Ergänzen löscht nichts; Mirror löscht überzählige Dateien in den gewählten Ordnern. RoboCopy v2 → pub erst abschalten, wenn Mirror auf dieser Route mehrfach geklappt hat.</div>\n";
if (!$onControl) {
	echo "<p class='uh-err'>Aktuelle Instanz ist <code>" . $h($siteKey) . "</code>. Unterhalt schreibt nur von test27 oder test aus.</p>\n";
}
echo "<p class='uh-muted'>Code-Sync, Daten-Snapshots und Website-Snapshots. Katalog und Website sind getrennte Wiederherstellungen. Nur Stufe <b>test</b>.</p>\n";

echo "<div class='uh-card' id='uh-sync' data-uh-help='unterhalt.sync'>\n";
echo "<h2>Code-Sync</h2>\n";
echo "<p class='uh-muted'>Neue oder neuere Dateien (mtime + SHA1). Standard ist <b>Mirror</b>: Spiegelkopie der gewählten Ordner, überzählige Dateien im Ziel werden gelöscht (nur Sync-Matrix, nicht Katalog/Missionen). <b>Ergänzen</b> kopiert nur und löscht nichts. Von <b>test27</b> oder <b>test</b> aus. <b>Alle Ebenen |</b> läuft ab der aktuellen Instanz weiter (ohne rkrs, ohne Kopie auf sich selbst). Route nach rkrs nur wenn Site und Ordner aktiviert sind, dann ein Modul wählen. Assess geht nach <code>rkrs/assess/public/</code> (nicht <code>assess/</code>). Die Vorschau der späteren Stufen zeigt den Ist-Stand; Apply spielt die Stufen der Reihe nach. jsonfiles, Missionen-Medien, rkrs <code>data/</code>, <code>results/</code> und Live-Personel-JSON (geführt von rehkitzrettung.ch) werden nicht kopiert. RoboCopy v2 → pub erst abschalten, wenn Mirror auf dieser Route mehrfach geklappt hat.</p>\n";
echo "<form method='post' action='rkrk_admin_unterhalt.php' class='uh-row' id='uh_sync_form'>";
echo "<div class='uh-field'><span>Route</span><span class='rkrk-sels rkrk-reg-pills uh-pills'>";
foreach ($routes as $rid => $route) {
	if ($rid === 'to_rkrs') {
		echo "<span class='uh-muted' title='Alle Ebenen enthält test27 → rkrs nicht'>|</span>";
	}
	$short = ($rid === 'all_levels')
		? (isset($routes['to_rkrs']) ? 'Alle Ebenen |' : 'Alle Ebenen')
		: ((string)($route['from'] ?? '') . ' → ' . (string)($route['to'] ?? $rid));
	rkrk_unterhalt_echo_pill('radio', 'route', $rid, $syncRouteId === $rid, $short, '', (string)($route['label'] ?? $rid));
}
echo "</span></div>";
$rkrsMods = rkrk_code_sync_rkrs_modules($cfg);
$rkrsSel = rkrk_code_sync_selected_rkrs_folders($cfg);
$rkrsOn = function_exists('rkrk_code_sync_rkrs_enabled') && rkrk_code_sync_rkrs_enabled($cfg);
$rkrsShow = ($rkrsOn && $syncRouteId === 'to_rkrs') ? '' : ' style="display:none"';
if ($rkrsOn) {
	echo "<div class='uh-field' id='uh_rkrs_mods'" . $rkrsShow . "><span>rkrs-Modul</span><span class='rkrk-sels rkrk-reg-pills uh-pills'>";
	foreach ($rkrsMods as $fid => $mod) {
		$when = rkrk_code_sync_rkrs_last_label($fid, $cfg);
		$title = (string)$mod['label'] . ' → ' . (string)$mod['dest'] . ' · ' . ($when === 'noch nie' ? 'noch nie' : ('zuletzt ' . $when));
		echo "<span class='uh-rkrs-mod'>";
		rkrk_unterhalt_echo_pill('checkbox', 'rkrs_mod[]', $fid, isset($rkrsSel[$fid]), (string)$mod['label'], '', $title);
		echo "<span class='uh-muted'>" . $h((string)$mod['dest']) . "<br>" . $h($when === 'noch nie' ? 'noch nie' : ('zuletzt ' . $when)) . "</span></span>";
	}
	echo "</span><span class='uh-muted'>Nur bei Route test27 → rkrs. Mindestens ein Modul wählen.</span></div>";
}
echo "<div class='uh-field'><span>Modus</span><span class='rkrk-sels rkrk-reg-pills uh-pills'>";
rkrk_unterhalt_echo_pill('radio', 'sync_mode', 'mirror', $syncMode === 'mirror', 'Mirror', '', 'Spiegelkopie, Extra-Dateien löschen');
rkrk_unterhalt_echo_pill('radio', 'sync_mode', 'copy', $syncMode === 'copy', 'Ergänzen', '', 'Nur kopieren, nichts löschen');
echo "</span></div>";
echo "<button type='submit' name='uh_action' value='sync_preview'>Vorschau</button>";
if ($syncApplyOk) {
	echo "<button type='submit' name='uh_action' value='sync_apply' onclick=\"var f=this.form; function uhR(n){var m=f[n]; if(!m) return ''; if(!m.length) return m.value; for(var i=0;i<m.length;i++) if(m[i].checked) return m[i].value; return '';} function uhMods(){var out=[], els=f.querySelectorAll('input[name=\\'rkrs_mod[]\\']'); for(var i=0;i<els.length;i++) if(els[i].checked) out.push(els[i].value); return out;} var r=uhR('route'); var m=uhR('sync_mode'); if(r==='to_rkrs'){ var mods=uhMods(); if(!mods.length){ alert('Bitte ein rkrs-Modul wählen.'); return false; } return confirm((m==='mirror' ? 'Mirror auf rkrs-Modul ' : 'rkrs-Modul ') + mods.join(', ') + (m==='mirror' ? '. Extra-Dateien in diesen Ordnern werden gelöscht. Weiter?' : ' jetzt kopieren (nichts löschen). Weiter?')); } if(r==='all_levels'){ return confirm(m==='mirror' ? 'Alle Ebenen | ohne rkrs (Mirror). Extra-Dateien auf jeder Stufe werden gelöscht. Weiter?' : 'Alle Ebenen | ohne rkrs kopieren (nichts löschen). Weiter?'); } if(m==='mirror'){ return confirm('MIRROR: Überzählige Dateien auf dem Ziel werden gelöscht. Zuerst Vorschau prüfen. Weiter?'); } return confirm('Diese Route jetzt kopieren? Auf dem Ziel wird nichts gelöscht.');\">Synchronisieren</button>";
} else {
	echo "<button type='button' disabled>Synchronisieren (aus)</button>";
}
echo "</form>\n";
if (!$syncApplyOk) {
	echo "<p class='uh-muted'>Synchronisieren nur von test27 oder test, wenn Apply freigegeben ist. Vorschau zeigt, was kopiert würde.</p>\n";
}

if (is_array($syncPlan)) {
	if (empty($syncPlan['ok'])) {
		echo "<p class='uh-err'>" . $h((string)($syncPlan['error'] ?? 'Vorschau fehlgeschlagen.')) . "</p>\n";
	} else {
		$items = $syncPlan['items'] ?? array();
		$destOnly = $syncPlan['dest_only'] ?? array();
		$showHop = false;
		foreach ($items as $item) {
			if (!empty($item['hop'])) {
				$showHop = true;
				break;
			}
		}
		if (!$showHop) {
			foreach ($destOnly as $drow) {
				if (!empty($drow['hop'])) {
					$showHop = true;
					break;
				}
			}
		}
		echo "<p class='uh-muted'>" . count($items) . " Dateien zum Kopieren · Modus " . $h($syncMode === 'mirror' ? 'Mirror' : 'Ergänzen') . " · " . $h((string)($syncPlan['route']['label'] ?? '')) . "</p>\n";
		$colspan = $showHop ? 7 : 6;
		echo "<div class='uh-scroll'><table class='big'><tr>";
		if ($showHop) {
			echo "<th>Stufe</th>";
		}
		echo "<th>Datei</th><th>Art</th><th>Quelle</th><th>Quelle-Zeit</th><th>Ziel-Zeit</th><th>Grösse</th></tr>\n";
		if ($items === array()) {
			echo "<tr><td colspan='" . (int)$colspan . "' class='uh-muted'>Nichts zu kopieren — Quelle und Ziel sind gleich (oder Ordner nicht aktiv).</td></tr>\n";
		}
		foreach ($items as $item) {
			echo "<tr>";
			if ($showHop) {
				echo "<td>" . $h((string)($item['hop'] ?? '')) . "</td>";
			}
			echo "<td><code>" . $h((string)$item['rel']) . "</code></td><td>" . $h((string)$item['kind']) . "</td>";
			echo "<td>" . $h((string)($item['source'] ?? '')) . "</td>";
			echo "<td>" . ($item['from_mtime'] ? $h(date('d.m.Y H:i', (int)$item['from_mtime'])) : '') . "</td>";
			echo "<td>" . ($item['to_mtime'] ? $h(date('d.m.Y H:i', (int)$item['to_mtime'])) : '—') . "</td>";
			echo "<td>" . $h(rkrk_unterhalt_format_bytes((int)($item['bytes'] ?? 0))) . "</td></tr>\n";
		}
		echo "</table></div>\n";
		if ($destOnly !== array()) {
			if ($syncMode === 'mirror') {
				echo "<div class='uh-warn'><b>Mirror: nur im Ziel</b> (" . count($destOnly) . ") — diese Dateien werden beim Synchronisieren <b>gelöscht</b>:</div>\n";
			} else {
				echo "<div class='uh-warn'><b>Nur im Ziel vorhanden</b> (" . count($destOnly) . ") — bleiben liegen (Ergänzen). Mit Mirror würden sie gelöscht:</div>\n";
			}
			echo "<div class='uh-scroll'><table class='big'><tr>";
			if ($showHop) {
				echo "<th>Stufe</th>";
			}
			echo "<th>Datei</th><th>Zeit</th><th>Grösse</th></tr>\n";
			$shown = 0;
			foreach ($destOnly as $drow) {
				if ($shown >= 80) {
					echo "<tr><td colspan='" . ($showHop ? 4 : 3) . "' class='uh-muted'>… " . (count($destOnly) - 80) . " weitere</td></tr>\n";
					break;
				}
				echo "<tr>";
				if ($showHop) {
					echo "<td>" . $h((string)($drow['hop'] ?? '')) . "</td>";
				}
				echo "<td><code>" . $h((string)$drow['rel']) . "</code></td><td>" . ($drow['mtime'] ? $h(date('d.m.Y H:i', (int)$drow['mtime'])) : '') . "</td>";
				echo "<td>" . $h(rkrk_unterhalt_format_bytes((int)($drow['bytes'] ?? 0))) . "</td></tr>\n";
				$shown++;
			}
			echo "</table></div>\n";
		}
	}
}
echo "</div>\n";

echo "<div class='uh-card' data-uh-help='unterhalt.dirs'>\n";
echo "<h2>Verzeichnisse und Ordner</h2>\n";
echo "<p class='uh-muted'>Pfade relativ zu <code>" . $h(rkrk_unterhalt_rkropen_root()) . "</code>. rkrs-Quelle nur test27 oder test. Ordner einzeln pro Ziel aktivieren.</p>\n";
echo "<form method='post'>\n";
echo "<input type='hidden' name='uh_action' value='save_dirs'>\n";
echo "<table class='big'><tr><th>Site</th><th>Label</th><th>Ordner</th><th>Aktiv</th><th>Absoluter Pfad</th></tr>\n";
foreach ($cfg['sites'] as $key => $row) {
	$abs = rkrk_unterhalt_site_abs($key, $cfg);
	echo "<tr><td><code>" . $h($key) . "</code></td>";
	echo "<td><input name='sites[" . $h($key) . "][label]' value='" . $h($row['label'] ?? '') . "' size='14'></td>";
	echo "<td><input name='sites[" . $h($key) . "][path]' value='" . $h($row['path'] ?? '') . "' size='16'></td>";
	echo "<td>";
	rkrk_unterhalt_echo_pill('checkbox', 'sites[' . $key . '][active]', '1', !empty($row['active']), 'aktiv');
	echo "</td>";
	echo "<td><code>" . $h($abs !== '' && is_dir($abs) ? $abs : ($abs . ' (fehlt)')) . "</code></td></tr>\n";
}
echo "</table>\n";
$probe = rkrk_unterhalt_data_probe($cfg);
echo "<p>Gemeinsamer Datenpfad (neben den Webkopien, nicht pro Instanz): <code>" . $h($probe['from_web']) . "</code>";
echo " → Ordnername <input name='data_path' value='" . $h($cfg['data_path'] ?? 'rkrk') . "' size='12'> relativ zu rkropen.</p>\n";
echo "<p class='uh-muted'>Aufgelöst: <code>" . $h($probe['root']) . "</code> ";
echo !empty($probe['exists']) ? "— vorhanden." : "— fehlt.";
echo " Legacy-Variable <code>\$RKRKRelRKRKDataLocation</code> = <code>" . $h(rtrim(str_replace('\\', '/', (string)($RKRKRelRKRKDataLocation ?? '')), '/')) . "</code> (cwd-abhängig, nur Fallback).</p>\n";
echo "<p>In Katalog-Sicherung: <span class='rkrk-sels rkrk-reg-pills uh-pills'>";
$incNow = isset($cfg['data_includes']) && is_array($cfg['data_includes']) ? $cfg['data_includes'] : array();
foreach (array(
	'webdaten' => 'webdaten',
	'config' => 'config',
	'webcsv' => 'webcsv',
	'data' => 'data',
) as $incKey => $incLab) {
	rkrk_unterhalt_echo_pill('checkbox', 'data_includes[]', $incKey, in_array($incKey, $incNow, true), $incLab);
}
echo "</span></p>\n";
echo "<table class='big'><tr><th>Unterordner</th><th>Status</th></tr>\n";
foreach ($probe['rows'] as $prow) {
	echo "<tr><td><code>" . $h($prow['rel']) . "</code><div class='uh-muted'>" . $h($prow['abs']) . "</div></td>";
	echo "<td>";
	rkrk_unterhalt_echo_status_pill(!empty($prow['exists']));
	echo "</td></tr>\n";
}
echo "</table>\n";
echo "<input type='hidden' name='mission_includes_posted' value='1'>\n";
echo "<p>Missionsbibliotheken pro Drohnentyp. Pfade (<code>MissionLocation</code>) sind fest. Sicherung: JSON/KML der Felder plus restliche <code>jsonfiles/</code>, ohne JPG/KMZ.</p>\n";
echo "<table class='big'><tr><th>MDB</th><th>Name</th><th>Missionen</th><th>Docs</th><th>In Sicherung</th><th>Ordner</th></tr>\n";
foreach (rkrk_unterhalt_mission_libs($cfg) as $lib) {
	$mdbH = $h($lib['mdb']);
	echo "<tr><td><code>{$mdbH}</code></td>";
	echo "<td>" . $h($lib['name']) . (!empty($lib['active']) ? '' : ' <span class=\'uh-muted\'>(inaktiv)</span>') . "</td>";
	echo "<td><code>" . $h($lib['path']) . "</code></td>";
	echo "<td><code>" . $h($lib['doc']) . "</code></td>";
	echo "<td>";
	rkrk_unterhalt_echo_pill('checkbox', 'mission_includes[' . $lib['mdb'] . ']', '1', !empty($lib['include']), 'an');
	echo "</td>";
	echo "<td>";
	rkrk_unterhalt_echo_status_pill(!empty($lib['exists']));
	echo "<div class='uh-muted'><code>" . $h($lib['abs']) . "</code></div></td></tr>\n";
}
echo "</table>\n";
echo "<p>rkrs-Quelle (global): <span class='rkrk-sels rkrk-reg-pills uh-pills'>";
foreach (array('test27', 'test') as $opt) {
	rkrk_unterhalt_echo_pill('radio', 'rkrs_source', $opt, (($cfg['rkrs_source'] ?? 'test27') === $opt), $opt);
}
echo "</span></p>\n";
echo "<table class='big uh-matrix'><tr><th>Ordner</th><th>Aktiv</th><th>→ test</th><th>→ v2</th><th>→ pub</th><th>→ rkrs</th><th>rkrs-Quelle</th></tr>\n";
foreach ($cfg['folders'] as $fid => $folder) {
	$relShow = (string)($folder['rel'] !== '' ? $folder['rel'] : ($folder['kind'] ?? ''));
	$rkrsDest = function_exists('rkrk_unterhalt_rkrs_dest_for')
		? rkrk_unterhalt_rkrs_dest_for($fid, $folder)
		: (string)($folder['rkrs_dest'] ?? '');
	$relNote = $relShow;
	if ($rkrsDest !== '' && $rkrsDest !== $relShow) {
		$relNote .= ' → rkrs:' . $rkrsDest;
	}
	echo "<tr><td>" . $h($folder['label'] ?? $fid) . "<br><span class='uh-muted'><code>" . $h($relNote) . "</code></span></td>";
	echo "<td>";
	rkrk_unterhalt_echo_pill('checkbox', 'folders[' . $fid . '][active]', '1', !empty($folder['active']), 'an');
	echo "</td>";
	foreach (array('test', 'v2', 'pub', 'rkrs') as $t) {
		echo "<td>";
		rkrk_unterhalt_echo_pill('checkbox', 'folders[' . $fid . '][targets][' . $t . ']', '1', !empty($folder['targets'][$t]), $t);
		echo "</td>";
	}
	$fs = (string)($folder['rkrs_source'] ?? '');
	echo "<td><span class='rkrk-sels rkrk-reg-pills uh-pills'>";
	rkrk_unterhalt_echo_pill('radio', 'folders[' . $fid . '][rkrs_source]', '', $fs === '', 'global');
	rkrk_unterhalt_echo_pill('radio', 'folders[' . $fid . '][rkrs_source]', 'test27', $fs === 'test27', 'test27');
	rkrk_unterhalt_echo_pill('radio', 'folders[' . $fid . '][rkrs_source]', 'test', $fs === 'test', 'test');
	echo "</span></td></tr>\n";
}
echo "</table>\n";
echo "<p><button type='submit'>Verzeichnisse speichern</button></p>\n";
echo "</form></div>\n";

echo "<div class='uh-card' data-uh-help='unterhalt.catalog'>\n";
echo "<h2>Katalog-Sicherungen</h2>\n";
echo "<p class='uh-muted'>Gemeinsamer Baum <code>../rkrk</code> plus Missions-JSON pro Drohnentyp (<code>Missionen/</code>, <code>Missionen Matrice 4TD/</code>, …). Keine Feld-JPG/KMZ. Restore legt zuerst eine Sicherheitssicherung an. Täglicher Auto-Snapshot beim Öffnen dieser Seite. Code-Sync kopiert diese Ordner nicht.</p>\n";
echo "<form method='post' class='uh-row'><input type='hidden' name='uh_action' value='catalog_create'>";
echo "<label>Notiz <input type='text' name='note' maxlength='120' placeholder='optional'></label>";
echo "<button type='submit'>Katalog jetzt sichern</button></form>\n";
echo "<div class='uh-scroll'><table class='big'><tr><th>Sicherung</th><th>Art</th><th>Von</th><th>Dateien</th><th>Grösse</th><th></th></tr>\n";
if ($catalogSnaps === array()) {
	echo "<tr><td colspan='6' class='uh-muted'>Noch keine Sicherung.</td></tr>\n";
}
foreach ($catalogSnaps as $snap) {
	$sid = (string)($snap['id'] ?? '');
	echo "<tr><td><b>" . $h($sid) . "</b><div class='uh-muted'>" . $h((string)($snap['created_at'] ?? ''));
	if (($snap['note'] ?? '') !== '') {
		echo ' · ' . $h((string)$snap['note']);
	}
	echo "</div></td><td>" . $h((string)($snap['kind'] ?? '')) . "</td><td>" . $h((string)($snap['user'] ?? '')) . "</td>";
	echo "<td>" . (int)($snap['files'] ?? 0) . "</td><td>" . $h(rkrk_unterhalt_format_bytes((int)($snap['bytes'] ?? 0))) . "</td><td class='uh-actions'>";
	echo "<form method='post' onsubmit=\"return confirm('Katalog-Datenstand wirklich ersetzen?');\"><input type='hidden' name='uh_action' value='catalog_restore'><input type='hidden' name='id' value='" . $h($sid) . "'><button type='submit'>Wiederherstellen</button></form>";
	if ($hasZip) {
		echo "<a href='rkrk_admin_unterhalt.php?uh_action=download_catalog&amp;id=" . rawurlencode($sid) . "'>ZIP</a>";
	}
	echo "<form method='post' onsubmit=\"return confirm('Sicherung löschen?');\"><input type='hidden' name='uh_action' value='catalog_delete'><input type='hidden' name='id' value='" . $h($sid) . "'><button type='submit'>Löschen</button></form>";
	echo "</td></tr>\n";
}
echo "</table></div>\n";
if ($hasZip) {
	echo "<form method='post' enctype='multipart/form-data' class='uh-row'><input type='hidden' name='uh_action' value='catalog_upload'>";
	echo "<label>ZIP einspielen <input type='file' name='backup_zip' accept='.zip' required></label>";
	echo "<button type='submit'>Hochladen und wiederherstellen</button></form>\n";
} else {
	echo "<p class='uh-muted'>ZIP nicht verfügbar (keine ZipArchive-Erweiterung).</p>\n";
}
echo "</div>\n";

echo "<div class='uh-card' data-uh-help='unterhalt.history'>\n";
echo "<h2>Datei-Historie (JSON)</h2>\n";
echo "<p class='uh-muted'>Kopien vor jedem Katalog-/Config-Speichern (<code>_history/</code>). XML-Recovery ist nach der JSON-Umstellung nicht mehr gültig.</p>\n";
echo "<div class='uh-scroll'><table class='big'><tr><th>Datei</th><th>Stand</th><th>Grösse</th><th></th></tr>\n";
if ($history === array()) {
	echo "<tr><td colspan='4' class='uh-muted'>Keine JSON-History-Dateien.</td></tr>\n";
}
foreach ($history as $hrow) {
	echo "<tr><td>" . $h((string)($hrow['original'] ?? '')) . "</td><td>" . $h((string)($hrow['label'] ?? '')) . "</td>";
	echo "<td>" . $h(rkrk_unterhalt_format_bytes((int)($hrow['bytes'] ?? 0))) . "</td><td>";
	echo "<form method='post' class='uh-actions' onsubmit=\"return confirm('Diese Version zurückspielen?');\">";
	echo "<input type='hidden' name='uh_action' value='catalog_history_restore'>";
	echo "<input type='hidden' name='hist_dir' value='" . $h((string)($hrow['dir'] ?? '')) . "'>";
	echo "<input type='hidden' name='hist_name' value='" . $h((string)($hrow['name'] ?? '')) . "'>";
	echo "<button type='submit'>Zurücksetzen</button></form></td></tr>\n";
}
echo "</table></div></div>\n";

echo "<div class='uh-card' data-uh-help='unterhalt.site'>\n";
echo "<h2>Website-Sicherung " . $h($siteLabel) . " (<code>" . $h($siteKey) . "</code>)</h2>\n";
echo "<p class='uh-muted'>Programmdateien nur dieser Instanz nach <code>rkrk/SiteBackups/" . $h($siteKey) . "</code>. Ohne Katalog-JSON, Missionen-Medien, DAV, vendor, Kopien. Restore nur in dieselbe Instanz. Vor dem Sync diese Instanz sichern.</p>\n";
echo "<form method='post' class='uh-row'><input type='hidden' name='uh_action' value='site_create'>";
echo "<label>Notiz <input type='text' name='note' maxlength='120' placeholder='optional'></label>";
echo "<button type='submit'>Website jetzt sichern</button></form>\n";
echo "<div class='uh-scroll'><table class='big'><tr><th>Sicherung</th><th>Art</th><th>Von</th><th>Dateien</th><th>Grösse</th><th></th></tr>\n";
if ($siteSnaps === array()) {
	echo "<tr><td colspan='6' class='uh-muted'>Noch keine Website-Sicherung.</td></tr>\n";
}
foreach ($siteSnaps as $snap) {
	$sid = (string)($snap['id'] ?? '');
	echo "<tr><td><b>" . $h($sid) . "</b><div class='uh-muted'>" . $h((string)($snap['created_at'] ?? ''));
	if (($snap['note'] ?? '') !== '') {
		echo ' · ' . $h((string)$snap['note']);
	}
	echo "</div></td><td>" . $h((string)($snap['kind'] ?? '')) . "</td><td>" . $h((string)($snap['user'] ?? '')) . "</td>";
	echo "<td>" . (int)($snap['files'] ?? 0) . "</td><td>" . $h(rkrk_unterhalt_format_bytes((int)($snap['bytes'] ?? 0))) . "</td><td class='uh-actions'>";
	echo "<form method='post' onsubmit=\"return confirm('Programmstand dieser Website wirklich ersetzen?');\"><input type='hidden' name='uh_action' value='site_restore'><input type='hidden' name='id' value='" . $h($sid) . "'><button type='submit'>Wiederherstellen</button></form>";
	if ($hasZip) {
		echo "<a href='rkrk_admin_unterhalt.php?uh_action=download_site&amp;id=" . rawurlencode($sid) . "'>ZIP</a>";
	}
	echo "<form method='post' onsubmit=\"return confirm('Website-Sicherung löschen?');\"><input type='hidden' name='uh_action' value='site_delete'><input type='hidden' name='id' value='" . $h($sid) . "'><button type='submit'>Löschen</button></form>";
	echo "</td></tr>\n";
}
echo "</table></div>\n";
if ($hasZip) {
	echo "<form method='post' enctype='multipart/form-data' class='uh-row'><input type='hidden' name='uh_action' value='site_upload'>";
	echo "<label>ZIP einspielen <input type='file' name='site_backup_zip' accept='.zip' required></label>";
	echo "<button type='submit'>Hochladen und wiederherstellen</button></form>\n";
}
echo "</div>\n";

echo "<button type='button' class='rkrk-help-fab' data-rkrk-help title='Hilfe zur aktuellen Sicht (F1 oder ?)' aria-label='Hilfe'>?</button>\n";
echo "<script src='template/rkrk_unterhalt_help_catalog.js?v=20260912rkrsownroute'></script>\n";
echo "<script src='template/rkrk_page_help.js?v=20260912uh'></script>\n";
echo "<script>
(function(){
  var KEY = 'rkrk_uh_viewport';
  function saveView(){
    try {
      sessionStorage.setItem(KEY, JSON.stringify({
        x: window.scrollX || window.pageXOffset || 0,
        y: window.scrollY || window.pageYOffset || 0
      }));
    } catch (e) {}
  }
  function restoreView(){
    var raw = null;
    try { raw = sessionStorage.getItem(KEY); } catch (e) { return; }
    if (!raw) return;
    try { sessionStorage.removeItem(KEY); } catch (e) {}
    var pos;
    try { pos = JSON.parse(raw); } catch (e) { return; }
    var x = Number(pos.x) || 0;
    var y = Number(pos.y) || 0;
    try { if ('scrollRestoration' in history) history.scrollRestoration = 'manual'; } catch (e) {}
    function go(){ window.scrollTo(x, y); }
    go();
    if (window.requestAnimationFrame) {
      requestAnimationFrame(function(){ go(); requestAnimationFrame(go); });
    }
    window.addEventListener('load', go);
    window.addEventListener('pageshow', go);
  }
  var form = document.getElementById('uh_sync_form');
  if (form) {
    form.addEventListener('submit', function(ev){
      var btn = ev.submitter || document.activeElement;
      if (btn && btn.name === 'uh_action' && btn.value === 'sync_preview') saveView();
    });
  }
  restoreView();
})();
(function(){
  var form = document.getElementById('uh_sync_form');
  var box = document.getElementById('uh_rkrs_mods');
  if (!form || !box) return;
  function show(){
    var r = '', m = form.route;
    if (!m) return;
    if (!m.length) r = m.value;
    else for (var i = 0; i < m.length; i++) if (m[i].checked) r = m[i].value;
    box.style.display = (r === 'to_rkrs') ? '' : 'none';
  }
  form.addEventListener('change', show);
  show();
})();
(function(){
  var view = 'unterhalt.overview';
  var ratios = {};
  var cards = document.querySelectorAll('[data-uh-help]');
  function pick(){
    var bestId = 'unterhalt.overview', bestR = -1;
    Object.keys(ratios).forEach(function(id){
      if (ratios[id] > bestR) { bestR = ratios[id]; bestId = id; }
    });
    if (bestR > 0) view = bestId;
    if (window.RkrkHelp) RkrkHelp.setView();
  }
  if (window.IntersectionObserver && cards.length) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){
        var id = e.target.getAttribute('data-uh-help');
        if (id) ratios[id] = e.intersectionRatio;
      });
      pick();
    }, { threshold: [0, 0.15, 0.35, 0.55, 0.75, 1], rootMargin: '-10% 0px -50% 0px' });
    cards.forEach(function(c){ io.observe(c); });
  }
  if (window.RkrkHelp) {
    RkrkHelp.mount({
      page: 'unterhalt',
      getView: function(){ return view; },
      getRole: function(){ return 'admin'; }
    });
  }
})();
</script>\n";

echo "</main>\n";
footer();
