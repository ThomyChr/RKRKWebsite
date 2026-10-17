<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_jg_komm.php');
	include_once('rkrk_access_control.php');

	CheckIPandExit();
	GrantAccess();
	headerpage('Jagdgebietsseite', false);
	CreateTopMenu();

	$reg = rkrk_jg_komm_norm_reg(GetURLwDefault('Reg', 'Reg', 'Reg', '26'));
	if ($reg === '') {
		$reg = '26';
	}
	$previewDate = date('Y-m-d');
	$message = '';
	$messageKind = '';
	$jgkNavReg = '';
	$published = rkrk_jg_komm_read_store();
	$draft = isset($_SESSION['jg_komm_draft']) && is_array($_SESSION['jg_komm_draft'])
		? $_SESSION['jg_komm_draft']
		: rkrk_jg_komm_merge_draft_area($published, $reg);
	if (!isset($draft['areas'][$reg])) {
		$draft = rkrk_jg_komm_merge_draft_area($draft, $reg);
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$reg = rkrk_jg_komm_norm_reg($_POST['Reg'] ?? $reg);
		if ($reg === '') {
			$reg = '26';
		}
		$previewDate = (string)($_POST['preview_date'] ?? $previewDate);
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $previewDate)) {
			$previewDate = date('Y-m-d');
		}
		$payload = rkrk_jg_komm_payload_from_post($reg);
		$action = (string)($_POST['action'] ?? '');
		if ($action === 'preview') {
			$_SESSION['jg_komm_draft'] = $payload;
			$_SESSION['jg_komm_preview_hash'] = rkrk_jg_komm_hash($payload);
			$_SESSION['jg_komm_preview_date'] = $previewDate;
			unset($_SESSION['jg_komm_preview_reg']);
			$draft = $payload;
			$message = 'Interne Vorschau für alle Seiten ist bereit. Sie ist nicht nach aussen sichtbar.';
			$messageKind = 'preview';
		} elseif ($action === 'publish') {
			$postedHash = rkrk_jg_komm_hash($payload);
			$seen = (string)($_SESSION['jg_komm_preview_hash'] ?? '');
			if ($seen === '' || $seen !== $postedHash) {
				$message = 'Zuerst die interne Vorschau erstellen. Publiziert wird das ganze Set, nur was in der Vorschau steht.';
				$messageKind = 'warn';
				$draft = $payload;
			} elseif (!rkrk_jg_komm_write_store($payload)) {
				$message = 'Speichern der publizierten Fassung ist fehlgeschlagen.';
				$messageKind = 'warn';
				$draft = $payload;
			} else {
				$published = $payload;
				$draft = $payload;
				$_SESSION['jg_komm_draft'] = $payload;
				$message = 'Alle Seiten und die Nachrichtenliste sind jetzt nach aussen sichtbar.';
				$messageKind = 'live';
			}
		} elseif (trim((string)($_POST['jgk_nav'] ?? '')) !== '') {
			$next = rkrk_jg_komm_norm_reg($_POST['jgk_nav']);
			if ($next !== '') {
				$_SESSION['jg_komm_draft'] = $payload;
				$_SESSION['jg_komm_preview_date'] = $previewDate;
				$draft = $payload;
				if (!isset($draft['areas'][$next]) || !is_array($draft['areas'][$next])) {
					$draft = rkrk_jg_komm_merge_draft_area($draft, $next);
					$_SESSION['jg_komm_draft'] = $draft;
				}
				$reg = $next;
				$jgkNavReg = $next;
			}
		}
	}

	$area = $draft['areas'][$reg];
	$publishedArea = (isset($published['areas'][$reg]) && is_array($published['areas'][$reg])) ? $published['areas'][$reg] : null;
	$jgkHasDraft = isset($_SESSION['jg_komm_draft']) && is_array($_SESSION['jg_komm_draft']);
	$previewReady = isset($_SESSION['jg_komm_preview_hash'])
		&& (string)$_SESSION['jg_komm_preview_hash'] === rkrk_jg_komm_hash($draft);
	if (isset($_SESSION['jg_komm_preview_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_SESSION['jg_komm_preview_date'])) {
		$previewDate = (string)$_SESSION['jg_komm_preview_date'];
	}
	$view = (isset($_GET['view']) && $_GET['view'] === 'live') ? 'live' : 'draft';
	if ($messageKind === 'live') {
		$view = 'live';
	}
	if ($messageKind === 'preview') {
		$view = 'draft';
	}

	$csvDesc = ';';
	$contacts = csv_to_array($csvDocLocation . $RKRKContactFile, $csvDesc);

	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = (string)($_SERVER['HTTP_HOST'] ?? '');
	$base = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
	$publicBase = preg_replace('#/test27$#', '/pub', $base);
	$pageQuery = 'rkrk_jg_summary.php?Reg=' . rawurlencode($reg) . '&Color=white&GA=gast';
	$publicUrl = $scheme . '://' . $host . $publicBase . '/' . $pageQuery;
	$draftUrl = $scheme . '://' . $host . $base . '/' . $pageQuery;
	if ($view === 'live') {
		$iframeUrl = $publicUrl;
	} else {
		$iframeUrl = $draftUrl;
		if ($previewReady) {
			$iframeUrl .= '&Preview=1&PreviewDate=' . rawurlencode($previewDate);
		}
	}
	$qr = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&ecc=M&data=' . rawurlencode($publicUrl);

	function rkrk_jg_komm_checked($on) {
		return $on ? ' checked' : '';
	}
	function rkrk_jg_komm_h($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
	function rkrk_jg_komm_kind_ink($hex) {
		$hex = ltrim((string)$hex, '#');
		if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
			return '#fff';
		}
		$lin = function ($channel) {
			$c = $channel / 255;
			return $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
		};
		$l = 0.2126 * $lin(hexdec(substr($hex, 0, 2)))
			+ 0.7152 * $lin(hexdec(substr($hex, 2, 2)))
			+ 0.0722 * $lin(hexdec(substr($hex, 4, 2)));
		$onWhite = 1.05 / ($l + 0.05);
		$onDark = ($l + 0.05) / 0.05;
		return $onWhite >= $onDark ? '#fff' : '#111';
	}
	function rkrk_jg_komm_notice_state($notice, $publishedNotices) {
		$id = (string)($notice['id'] ?? '');
		foreach ($publishedNotices as $item) {
			if (is_array($item) && (string)($item['id'] ?? '') === $id) {
				$same = (string)($item['text'] ?? '') === (string)($notice['text'] ?? '')
					&& (string)($item['from'] ?? '') === (string)($notice['from'] ?? '')
					&& (string)($item['to'] ?? '') === (string)($notice['to'] ?? '')
					&& (string)($item['scope'] ?? '') === (string)($notice['scope'] ?? '')
					&& (string)($item['title'] ?? '') === (string)($notice['title'] ?? '')
					&& (string)($item['kind'] ?? 'info') === (string)($notice['kind'] ?? 'info')
					&& !empty($item['yearly']) === !empty($notice['yearly']);
				return $same ? 'live' : 'changed';
			}
		}
		return 'draft';
	}
	function rkrk_jg_komm_ui_leaf_pick($draft, $published, $contacts, $leafReg) {
		$area = (isset($draft['areas'][$leafReg]) && is_array($draft['areas'][$leafReg])) ? $draft['areas'][$leafReg] : array();
		$publishedArea = (isset($published['areas'][$leafReg]) && is_array($published['areas'][$leafReg])) ? $published['areas'][$leafReg] : null;
		$explicit = is_array($publishedArea) || !empty($area['assigned']);
		if ($explicit) {
			$selected = array(
				'jaeger' => rkrk_jg_komm_norm_list($area['jaeger'] ?? array()),
				'piloten' => rkrk_jg_komm_norm_list($area['piloten'] ?? array()),
			);
		} else {
			$selected = array(
				'jaeger' => (!empty($area['jaeger']) || !empty($area['active'])) ? rkrk_jg_komm_norm_list($area['jaeger'] ?? array()) : array_keys(rkrk_jg_komm_people_for_reg($contacts, $leafReg, 'jaeger')),
				'piloten' => (!empty($area['piloten']) || !empty($area['active'])) ? rkrk_jg_komm_norm_list($area['piloten'] ?? array()) : array_keys(rkrk_jg_komm_people_for_reg($contacts, $leafReg, 'pilot')),
			);
			if (empty($area['jaeger']) && empty($area['active']) && isset($_SESSION['jg_komm_draft']['areas'][$leafReg])) {
				$selected['jaeger'] = rkrk_jg_komm_norm_list($area['jaeger'] ?? array());
				$selected['piloten'] = rkrk_jg_komm_norm_list($area['piloten'] ?? array());
			}
		}
		return array(
			'jaeger' => $selected['jaeger'],
			'piloten' => $selected['piloten'],
			'jaeger_stv' => rkrk_jg_komm_stv_names($selected['jaeger'], array(), $area['jaeger_stv'] ?? array(), false),
			'piloten_stv' => rkrk_jg_komm_stv_names($selected['piloten'], array(), $area['piloten_stv'] ?? array(), false),
		);
	}
?>
<style>
.jgk { display:flex; align-items:stretch; height:calc(100vh - 8px); min-height:0; overflow:hidden; }
form.jgk-edit { width:440px; min-width:280px; overflow:auto; padding:12px 14px 24px; box-sizing:border-box; background:#f7f7f4; flex:0 0 auto; }
.jgk-split { flex:0 0 8px; cursor:col-resize; background:#c8c8c2; touch-action:none; }
.jgk-split:hover, .jgk-split.drag { background:#6b6b66; }
.jgk-view { flex:1; min-width:0; min-height:0; height:100%; overflow:auto; background:#e6e6e1; }
.jgk-sheet { width:1000px; height:100%; min-height:0; display:flex; flex-direction:column; background:#fff; box-shadow:0 0 0 1px #ccc; }
.jgk-banner { padding:10px 14px; font-weight:bold; flex:0 0 auto; }
.jgk-banner.draft { background:#f59e0b; color:#1a1a1a; }
.jgk-banner.live { background:#166534; color:#fff; }
.jgk-sheet > p { flex:0 0 auto; margin:8px 14px; }
.jgk-view iframe { width:1000px; flex:1 1 auto; height:0; min-height:0; border:0; background:#fff; display:block; }
.jgk h2 { margin:8px 0; font-size:18px; }
.jgk label { display:block; margin:8px 0 2px; font-weight:bold; }
.jgk input[type=text], .jgk input[type=date], .jgk textarea, .jgk select { width:100%; box-sizing:border-box; }
.jgk textarea { min-height:70px; }
.jgk .row { display:flex; gap:8px; }
.jgk .row > * { flex:1; }
.jgk .people { max-height:140px; overflow:auto; border:1px solid #ddd; background:#fff; padding:4px 8px; }
.jgk .note { border:1px solid #ddd; background:#fff; padding:8px; margin:8px 0; }
.jgk .note > summary { cursor:pointer; font-weight:bold; }
.jgk .note > summary .badge { font-weight:bold; margin-left:6px; }
.jgk .badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:12px; font-weight:bold; }
.jgk .badge.draft { background:#f59e0b; }
.jgk .badge.live { background:#166534; color:#fff; }
.jgk .badge.changed { background:#b45309; color:#fff; }
.jgk button { margin-top:8px; padding:8px 12px; }
.jgk .msg { padding:8px; margin:8px 0; }
.jgk .msg.preview { background:#ffedd5; }
.jgk .msg.live { background:#dcfce7; }
.jgk .msg.warn { background:#fee2e2; }
.jgk-plan { width:100%; max-width:100%; table-layout:fixed; border-collapse:collapse; font-size:12px; background:#fff; margin:6px 0 10px; }
.jgk-plan th, .jgk-plan td { border:1px solid #ddd; padding:2px 4px; vertical-align:top; overflow-wrap:anywhere; }
.jgk-plan th.jgk-flag, .jgk-plan td.jgk-flag { width:7.6em; white-space:nowrap; overflow:hidden; overflow-wrap:normal; word-break:keep-all; text-align:center; }
.jgk-plan tr.jgk-edit > td { width:100%; }
.jgk-plan th { background:#eee; text-align:left; }
.jgk .jgk-toggle { margin:0; padding:0; border:0; background:transparent; font-weight:bold; text-align:left; cursor:pointer; color:inherit; text-decoration:underline; }
.jgk-plan tr.jgk-open td { background:#dceadf; }
.jgk-plan tr.jgk-open .jgk-toggle { text-decoration:none; }
.jgk-plan tr.jgk-edit[hidden], .jgk-plan tr[hidden] { display:none; }
.jgk-plan td.jgk-ja { color:#166534; font-weight:bold; }
.jgk-plan td.jgk-aus { color:#7f1d1d; font-weight:bold; }
.jgk-plan td.jgk-nein { color:#444; }
.jgk-edit td { background:#fafafa; }
.jgk-edit label { margin:4px 0 1px; font-size:11px; }
.jgk-edit textarea { min-height:48px; }
.jgk select.jgk-kind { color-scheme: light; font-weight: 700; }
.jgk select[multiple] { height:6.5em; }
.jgk select.jgk-targets { height:18em; width:100%; max-width:100%; display:block; box-sizing:border-box; }
.jgk-assign { margin:4px 0 10px; }
.jgk-sum { margin:2px 0 8px; }
.jgk-modal { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; }
.jgk-modal[hidden] { display:none; }
.jgk-dialog { width:min(1280px, calc(100vw - 12px)); max-height:calc(100vh - 12px); background:#fff; padding:12px; display:flex; flex-direction:column; overflow:hidden; box-sizing:border-box; }
.jgk-modes { margin:0 0 8px; }
.jgk-modes button { margin:0 6px 0 0; }
.jgk-boards { display:flex; gap:8px; min-height:0; flex:1; align-items:stretch; }
.jgk-pool { flex:0 0 220px; min-width:180px; display:flex; flex-direction:column; }
.jgk-cols { flex:1; min-width:0; display:flex; gap:8px; overflow-x:auto; overflow-y:hidden; }
.jgk-col { flex:0 0 168px; display:flex; flex-direction:column; min-width:168px; }
.jgk-col.here > b { color:#166534; }
.jgk-col small { display:block; color:#555; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.jgk-col.over .jgk-board { outline:2px solid #166534; }
.jgk-board { min-height:240px; max-height:62vh; overflow:auto; border:1px solid #ccc; background:#f7f7f4; padding:6px; }
.jgk-card { background:#fff; border:1px solid #ccc; padding:3px 4px; margin:0 0 4px; cursor:grab; display:flex; align-items:flex-start; gap:3px; }
.jgk-card .jgk-name { flex:1; min-width:0; font-size:12px; line-height:1.3; }
.jgk-dialog button.jgk-mini { margin:0; padding:0 4px; font-size:11px; line-height:1.5; flex:0 0 auto; }
.jgk-dialog button.jgk-stv.on { background:#111; color:#fff; }
.jgk-alerts { border:1px solid #ddd; background:#fff; padding:6px 8px; margin:6px 0 10px; }
.jgk-alerts label { font-weight:normal; margin:4px 0; }
.jgk-alerts input { width:auto; margin:0 6px 0 0; }
.jgk-alerts .win { color:#555; font-size:12px; }
.jgk-alerts .closed { color:#D11111; }
</style>
<div class="jgk">
<form class="jgk-edit" method="post" action="rkrk_jg_komm.php?Reg=<?php echo rkrk_jg_komm_h($reg); ?>" id="jgkForm">
	<label>Seite</label>
	<select id="jgkPage">
	<?php
	$navReg = rkrk_jg_komm_norm_reg($reg);
	foreach (rkrk_jg_komm_pages() as $page) {
		$pageReg = rkrk_jg_komm_norm_reg($page['reg']);
		if (!empty($page['group'])) {
			echo '<optgroup label="' . rkrk_jg_komm_h($page['label']) . '">';
			$live = (isset($published['areas'][$pageReg]) && is_array($published['areas'][$pageReg]) && !empty($published['areas'][$pageReg]['active']));
			echo '<option value="' . rkrk_jg_komm_h($pageReg) . '"' . ($pageReg === $navReg ? ' selected' : '') . '>' . rkrk_jg_komm_h($page['label']) . ($live ? ' · aussen' : '') . '</option>';
			foreach ($page['members'] as $member) {
				$memberReg = rkrk_jg_komm_norm_reg($member['reg']);
				$memberLive = (isset($published['areas'][$memberReg]) && is_array($published['areas'][$memberReg]) && !empty($published['areas'][$memberReg]['active']));
				echo '<option value="' . rkrk_jg_komm_h($memberReg) . '"' . ($memberReg === $navReg ? ' selected' : '') . '>' . rkrk_jg_komm_h($member['label']) . ($memberLive ? ' · aussen' : '') . '</option>';
			}
			echo '</optgroup>';
		} else {
			$only = $page['members'][0];
			$onlyReg = rkrk_jg_komm_norm_reg($only['reg']);
			$live = (isset($published['areas'][$onlyReg]) && is_array($published['areas'][$onlyReg]) && !empty($published['areas'][$onlyReg]['active']));
			echo '<option value="' . rkrk_jg_komm_h($onlyReg) . '"' . ($onlyReg === $navReg ? ' selected' : '') . '>' . rkrk_jg_komm_h($only['label']) . ($live ? ' · aussen' : '') . '</option>';
		}
	}
	?>
	</select>
	<h2>Entwurf</h2>
	<p>Die Vorschau ist intern. Ein Klick speichert oder publiziert alle Seiten und die Nachrichtenliste.</p>
	<?php if ($message !== '') { ?>
		<div class="msg <?php echo rkrk_jg_komm_h($messageKind); ?>"><?php echo rkrk_jg_komm_h($message); ?></div>
	<?php } ?>
	<input type="hidden" name="Reg" value="<?php echo rkrk_jg_komm_h($reg); ?>">
	<input type="hidden" name="jgk_nav" id="jgkNav" value="">
	<p><b><?php echo rkrk_jg_komm_h($reg); ?></b></p>
	<label><input type="checkbox" name="active"<?php echo rkrk_jg_komm_checked(!empty($area['active'])); ?>> Öffentliche Infoseite aktiv</label>
	<label>Titel</label>
	<input type="text" name="title" value="<?php echo rkrk_jg_komm_h($area['title'] ?? ''); ?>">
	<label>Untertitel</label>
	<input type="text" name="subtitle" value="<?php echo rkrk_jg_komm_h($area['subtitle'] ?? ''); ?>">
	<label>Informationen zum Betrieb</label>
	<textarea name="info"><?php echo rkrk_jg_komm_h($area['info'] ?? ''); ?></textarea>
	<label><input type="checkbox" name="show_phones"<?php echo rkrk_jg_komm_checked(!isset($area['show_phones']) || !empty($area['show_phones'])); ?>> Telefonnummern anzeigen</label>
	<label><input type="checkbox" name="show_map"<?php echo rkrk_jg_komm_checked(!isset($area['show_map']) || !empty($area['show_map'])); ?>> Karte anzeigen</label>
	<label><input type="checkbox" name="show_geflogen"<?php echo rkrk_jg_komm_checked(!isset($area['show_geflogen']) || !empty($area['show_geflogen'])); ?>> Tab Geflogene Felder</label>
	<label><input type="checkbox" name="sms"<?php echo rkrk_jg_komm_checked(!empty($area['sms'])); ?>> SMS-Link</label>
	<label><input type="checkbox" name="whatsapp"<?php echo rkrk_jg_komm_checked(!empty($area['whatsapp'])); ?>> WhatsApp-Link</label>

	<h2>Kontakte</h2>
	<p>Ziehen zum Zuordnen. Stv setzt das Gebiet in Klammern.</p>
	<?php
	$peopleCatalog = array(
		'jaeger' => rkrk_jg_komm_hunter_catalog($contacts),
		'piloten' => rkrk_jg_komm_pilot_catalog($contacts),
	);
	$peopleTitles = array('jaeger' => 'Jäger', 'piloten' => 'Piloten');
	$peopleSources = array('jaeger' => 'Kontaktadressen', 'piloten' => 'RKRK-Pilotenliste');
	$leafUi = array();
	$leafSeen = array();
	$leafByKind = array('jaeger' => array(), 'piloten' => array());
	foreach (rkrk_jg_komm_pages() as $page) {
		foreach ($page['members'] as $member) {
			$leafReg = rkrk_jg_komm_norm_reg($member['reg']);
			if ($leafReg === '' || isset($leafSeen[$leafReg])) {
				continue;
			}
			$leafSeen[$leafReg] = true;
			$leafUi[] = array('reg' => $leafReg, 'label' => (string)$member['label']);
			$pick = rkrk_jg_komm_ui_leaf_pick($draft, $published, $contacts, $leafReg);
			$leafByKind['jaeger'][$leafReg] = array('selected' => $pick['jaeger'], 'stv' => $pick['jaeger_stv']);
			$leafByKind['piloten'][$leafReg] = array('selected' => $pick['piloten'], 'stv' => $pick['piloten_stv']);
		}
	}
	foreach ($peopleCatalog as $key => $items) {
		$known = array();
		foreach ($items as $item) {
			$known[$item['name']] = true;
		}
		foreach ($leafByKind[$key] as $leafPick) {
			foreach ($leafPick['selected'] as $name) {
				if (!isset($known[$name])) {
					$known[$name] = true;
					$peopleCatalog[$key][] = array('name' => $name, 'label' => $name, 'code' => '', 'jg' => '');
				}
			}
		}
		echo '<label>' . rkrk_jg_komm_h($peopleTitles[$key]) . '</label>';
		echo '<div class="jgk-assign" id="jgkText-' . rkrk_jg_komm_h($key) . '"></div>';
		echo '<button type="button" data-pick="' . rkrk_jg_komm_h($key) . '">' . rkrk_jg_komm_h($peopleTitles[$key]) . ' zuordnen</button>';
	}
	echo '<div id="jgkAssignFields"></div>';
	$peopleUi = array('reg' => $reg, 'leaves' => $leafUi, 'groups' => array());
	foreach ($peopleCatalog as $key => $items) {
		$peopleUi['groups'][$key] = array(
			'title' => $peopleTitles[$key],
			'source' => $peopleSources[$key],
			'items' => $items,
			'byReg' => $leafByKind[$key],
		);
	}
	echo '<script type="application/json" id="jgkPeople">' . json_encode($peopleUi, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . '</script>';
	?>

	<h2>Nachrichtenplan</h2>
	<p>Titel antippen. Jetzt: angezeigt, geplant, vorbei, aus oder andere.</p>
	<label>Stichtag der Vorschau</label>
	<input type="date" name="preview_date" id="jgkPreviewDate" value="<?php echo rkrk_jg_komm_h($previewDate); ?>">
	<h2>Flugeinschränkungen</h2>
	<p>Nur aktive Zonen. <a href="rkrk_kml2xml.php">Aktivieren unter Layer verwalten</a>.</p>
	<?php
	$alertCatalog = rkrk_jg_komm_alert_catalog();
	$restrictionOn = array();
	foreach ((isset($area['restrictions']) && is_array($area['restrictions'])) ? $area['restrictions'] : array() as $restrictionItem) {
		$restrictionFile = is_array($restrictionItem) ? (string)($restrictionItem['file'] ?? '') : (string)$restrictionItem;
		if ($restrictionFile !== '') {
			$restrictionOn[$restrictionFile] = true;
		}
	}
	$noticeAlertOn = array();
	if (function_exists('rkrk_jg_komm_notice_alert_zones')) {
		foreach (rkrk_jg_komm_notice_alert_zones(isset($draft) ? $draft : array(), $reg, $previewDate) as $noticeZone) {
			$noticeZoneName = is_array($noticeZone) ? (string)($noticeZone['name'] ?? '') : '';
			if ($noticeZoneName !== '') {
				$noticeAlertOn[$noticeZoneName] = true;
			}
		}
	}
	if ($alertCatalog === array()) {
		echo '<p>Keine Alert-Zone im Katalog.</p>';
	} else {
		echo '<input type="hidden" name="restrictions_set" value="1">';
		$alertLabels = '';
		foreach ($alertCatalog as $alertRow) {
			if (!is_array($alertRow)) {
				continue;
			}
			$alertName = (string)($alertRow['name'] ?? '');
			if ($alertName === '') {
				continue;
			}
			if (empty($alertRow['aktiv'])) {
				if (isset($restrictionOn[$alertName])) {
					echo '<input type="hidden" name="restriction_file[]" value="' . rkrk_jg_komm_h($alertName) . '">';
				}
				continue;
			}
			$alertOn = function_exists('ref_layers_alert_row_on') && ref_layers_alert_row_on($alertRow, $previewDate);
			$viaNotice = isset($noticeAlertOn[$alertName]);
			if ($viaNotice) {
				$alertState = 'am Stichtag über Nachricht';
				$alertOn = true;
			} elseif (!$alertOn) {
				$alertState = 'Zeitraum zu';
			} else {
				$alertState = 'am Stichtag eingeschaltet';
			}
			$alertLabels .= '<label><input type="checkbox" name="restriction_file[]" value="' . rkrk_jg_komm_h($alertName) . '"' . rkrk_jg_komm_checked(isset($restrictionOn[$alertName])) . '> ';
			$alertLabels .= '<b>' . rkrk_jg_komm_h($alertName) . '</b> ';
			$alertLabels .= '<span class="win' . ($alertOn ? '' : ' closed') . '">' . rkrk_jg_komm_h(rkrk_jg_komm_alert_window_text($alertRow)) . ' · ' . rkrk_jg_komm_h($alertState) . '</span></label>';
		}
		if ($alertLabels !== '') {
			echo '<div class="jgk-alerts">' . $alertLabels . '</div>';
		}
	}
	?>
	<?php
	$noticeRows = isset($draft['notices']) && is_array($draft['notices']) ? $draft['notices'] : array();
	$planIsLegacy = count($noticeRows) === 0;
	if ($planIsLegacy) {
		$noticeRows = rkrk_jg_komm_legacy_notices();
	}
	$targetNames = array('all' => 'alle');
	foreach (rkrk_jg_komm_pages() as $page) {
		if (!empty($page['group'])) {
			$targetNames[rkrk_jg_komm_norm_reg($page['reg'])] = (string)$page['label'];
		}
		foreach ($page['members'] as $member) {
			$targetNames[rkrk_jg_komm_norm_reg($member['reg'])] = (string)$member['label'];
		}
	}
	$kinds = rkrk_jg_komm_notice_kinds();
	$noticeRows[] = array('id' => '', 'scope' => $reg, 'targets' => array(rkrk_jg_komm_norm_reg($reg)), 'from' => $previewDate, 'to' => $previewDate, 'text' => '', 'title' => '', 'kind' => 'info', 'yearly' => false, 'link_label' => '', 'link_url' => '', 'enabled' => true);
	$publishedNotices = isset($published['notices']) && is_array($published['notices']) ? $published['notices'] : array();
	echo '<table class="jgk-plan" id="jgkNoticePlan" data-page="' . rkrk_jg_komm_h($reg) . '"><tr><th>Titel</th><th>Zeitraum</th><th>Ziel</th><th class="jgk-flag">Aktiv</th><th class="jgk-flag">Jetzt</th></tr>';
	$rowIndex = 0;
	foreach ($noticeRows as $notice) {
		$isNew = trim((string)($notice['text'] ?? '') . (string)($notice['title'] ?? '')) === '';
		$targets = (isset($notice['targets']) && is_array($notice['targets'])) ? $notice['targets'] : (((string)($notice['scope'] ?? '') === 'all') ? array('all') : array());
		$targetNorm = array();
		$names = array();
		foreach ($targets as $target) {
			$key = ((string)$target === 'all') ? 'all' : rkrk_jg_komm_norm_reg($target);
			$targetNorm[] = $key;
			$names[] = (string)($targetNames[$key] ?? $key);
		}
		if (in_array('all', $targetNorm, true)) {
			$targetShort = 'alle';
		} elseif (count($names) > 2) {
			$targetShort = $names[0] . ', ' . $names[1] . ' +' . (count($names) - 2);
		} else {
			$targetShort = implode(', ', $names);
		}
		$fromRaw = (string)($notice['from'] ?? '');
		$toRaw = (string)($notice['to'] ?? '');
		if (!empty($notice['yearly']) && preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $fromRaw, $fromMd) && preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $toRaw, $toMd)) {
			$when = $fromMd[2] . '.' . $fromMd[1] . '.–' . $toMd[2] . '.' . $toMd[1] . '.';
		} else {
			$when = $fromRaw . '–' . $toRaw;
		}
		$noteTitle = trim((string)($notice['title'] ?? ''));
		if ($noteTitle === '') {
			$noteTitle = $isNew ? 'Neue Nachricht' : 'ohne Titel';
		}
		$noticeOn = rkrk_jg_komm_notice_enabled($notice);
		$aktivText = $noticeOn ? 'ja' : 'aus';
		$jetztText = '';
		$jetztClass = '';
		if (!$isNew) {
			$phaseKey = rkrk_jg_komm_notice_phase($notice, $previewDate);
			if ($noticeOn && rkrk_jg_komm_notice_matches($notice, $reg, $previewDate)) {
				$jetztText = 'angezeigt';
				$jetztClass = ' jgk-ja';
			} elseif (!$noticeOn) {
				$jetztText = 'aus';
			} elseif ($phaseKey === 'plan') {
				$jetztText = 'geplant';
			} elseif ($phaseKey === 'past') {
				$jetztText = 'vorbei';
			} else {
				$jetztText = 'andere';
			}
		}
		$editId = 'jgkEdit' . $rowIndex;
		$noticeAttrs = ' data-from="' . rkrk_jg_komm_h($fromRaw) . '" data-to="' . rkrk_jg_komm_h($toRaw) . '" data-yearly="' . (!empty($notice['yearly']) ? '1' : '0') . '" data-on="' . ($noticeOn ? '1' : '0') . '" data-targets="' . rkrk_jg_komm_h(implode(',', $targetNorm)) . '" data-new="' . ($isNew ? '1' : '0') . '"';
		echo '<tr' . ($isNew ? ' class="jgk-new"' : '') . $noticeAttrs . '><td><button type="button" class="jgk-toggle" id="jgkSum' . $rowIndex . '" aria-controls="' . $editId . '" aria-expanded="' . ($isNew ? 'true' : 'false') . '">' . rkrk_jg_komm_h($noteTitle) . '</button></td>';
		echo '<td>' . rkrk_jg_komm_h($isNew ? '' : $when) . '</td><td>' . rkrk_jg_komm_h($isNew ? '' : $targetShort) . '</td>';
		echo '<td class="jgk-flag">' . rkrk_jg_komm_h($aktivText) . '</td><td class="jgk-flag jgk-jetzt' . $jetztClass . '">' . rkrk_jg_komm_h($jetztText) . '</td></tr>';
		echo '<tr class="jgk-edit" id="' . $editId . '"' . ($isNew ? '' : ' hidden') . '><td colspan="5">';
		echo '<input type="hidden" name="notice_id[]" value="' . rkrk_jg_komm_h($notice['id'] ?? '') . '">';
		echo '<label><input type="hidden" name="notice_enabled[' . $rowIndex . ']" value="0"><input type="checkbox" name="notice_enabled[' . $rowIndex . ']" value="1"' . ($noticeOn ? ' checked' : '') . '> Aktiv</label>';
		echo '<label>Zielseiten</label><select class="jgk-targets" name="notice_targets[' . $rowIndex . '][]" multiple>';
		echo '<option value="all"' . (in_array('all', $targetNorm, true) ? ' selected' : '') . '>alle</option>';
		foreach (rkrk_jg_komm_pages() as $page) {
			if (!empty($page['group'])) {
				$groupReg = rkrk_jg_komm_norm_reg($page['reg']);
				echo '<optgroup label="' . rkrk_jg_komm_h($page['label']) . '">';
				echo '<option value="' . rkrk_jg_komm_h($groupReg) . '"' . (in_array($groupReg, $targetNorm, true) ? ' selected' : '') . '>' . rkrk_jg_komm_h($page['label']) . '</option>';
				foreach ($page['members'] as $member) {
					$memberReg = rkrk_jg_komm_norm_reg($member['reg']);
					echo '<option value="' . rkrk_jg_komm_h($memberReg) . '"' . (in_array($memberReg, $targetNorm, true) ? ' selected' : '') . '>' . rkrk_jg_komm_h($member['label']) . '</option>';
				}
				echo '</optgroup>';
			} else {
				$onlyReg = rkrk_jg_komm_norm_reg($page['members'][0]['reg']);
				echo '<option value="' . rkrk_jg_komm_h($onlyReg) . '"' . (in_array($onlyReg, $targetNorm, true) ? ' selected' : '') . '>' . rkrk_jg_komm_h($page['members'][0]['label']) . '</option>';
			}
		}
		echo '</select>';
		$kindNow = (string)($notice['kind'] ?? 'info');
		if (!isset($kinds[$kindNow])) {
			$kindNow = 'info';
		}
		$kindNowColor = (string)($kinds[$kindNow]['color'] ?? '#527642');
		$kindNowInk = rkrk_jg_komm_kind_ink($kindNowColor);
		echo '<div class="row"><div><label>Art</label><select class="jgk-kind" name="notice_kind[]" style="background-color:' . rkrk_jg_komm_h($kindNowColor) . ';color:' . rkrk_jg_komm_h($kindNowInk) . '">';
		foreach ($kinds as $kindKey => $kindInfo) {
			$kindColor = (string)($kindInfo['color'] ?? '');
			$kindInk = rkrk_jg_komm_kind_ink($kindColor);
			echo '<option value="' . rkrk_jg_komm_h($kindKey) . '" style="background-color:' . rkrk_jg_komm_h($kindColor) . ';color:' . rkrk_jg_komm_h($kindInk) . '"' . ($kindNow === $kindKey ? ' selected' : '') . '>' . rkrk_jg_komm_h($kindInfo['label']) . '</option>';
		}
		echo '</select></div><div><label>Wiederholung</label><select name="notice_yearly[]">';
		echo '<option value="0"' . (empty($notice['yearly']) ? ' selected' : '') . '>einmal</option>';
		echo '<option value="1"' . (!empty($notice['yearly']) ? ' selected' : '') . '>jedes Jahr</option>';
		echo '</select></div></div>';
		echo '<div class="row"><div><label>Von</label><input type="date" name="notice_from[]" value="' . rkrk_jg_komm_h($notice['from'] ?? '') . '"></div>';
		echo '<div><label>Bis</label><input type="date" name="notice_to[]" value="' . rkrk_jg_komm_h($notice['to'] ?? '') . '"></div></div>';
		echo '<label>Titel</label><input type="text" name="notice_title[]" value="' . rkrk_jg_komm_h($notice['title'] ?? '') . '" data-sum="jgkSum' . $rowIndex . '">';
		echo '<label>Text</label><textarea name="notice_text[]">' . rkrk_jg_komm_h($notice['text'] ?? '') . '</textarea>';
		echo '<div class="row"><div><label>Linktext</label><input type="text" name="notice_link_label[]" value="' . rkrk_jg_komm_h($notice['link_label'] ?? '') . '"></div>';
		echo '<div><label>Link</label><input type="text" name="notice_link_url[]" value="' . rkrk_jg_komm_h($notice['link_url'] ?? '') . '"></div></div>';
		$noticeAlert = (string)($notice['alert'] ?? '');
		echo '<label>Alert-Zone</label><select name="notice_alert[]">';
		echo '<option value="">keine</option>';
		foreach ($alertCatalog as $alertChoice) {
			if (!is_array($alertChoice)) {
				continue;
			}
			$alertChoiceName = (string)($alertChoice['name'] ?? '');
			if ($alertChoiceName === '') {
				continue;
			}
			echo '<option value="' . rkrk_jg_komm_h($alertChoiceName) . '"' . ($noticeAlert === $alertChoiceName ? ' selected' : '') . '>' . rkrk_jg_komm_h($alertChoiceName) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';
		$rowIndex++;
	}
	echo '</table>';
	if ($planIsLegacy) {
		echo '<p>Das sind die bisherigen Meldungen. Sie werden erst nach der internen Vorschau und dem Publizieren gespeichert.</p>';
	}
	?>

	<button type="submit" name="action" value="preview">1. Speichern Vorschau</button>
	<button type="submit" name="action" value="publish"<?php echo $previewReady ? '' : ' disabled'; ?>>2. Publizieren</button>
	<p><?php echo $previewReady ? 'Die Vorschau gilt für alle Seiten und die Nachrichtenliste. Publizieren gibt das ganze Set nach aussen.' : 'Publizieren ist gesperrt, bis die interne Vorschau für das ganze Set erstellt wurde.'; ?></p>

	<h2>Nach aussen sichtbar</h2>
	<p>Zum Stichtag geplant oder angezeigt, aktiv, für diese Seite oder alle.</p>
	<?php
	$liveArea = is_array($publishedArea) && !empty($publishedArea['active']);
	echo $liveArea ? '<span class="badge live">Infoseite aktiv</span>' : '<span class="badge draft">keine publizierte Infoseite</span>';
	echo '<table class="jgk-plan" id="jgkLivePlan" data-page="' . rkrk_jg_komm_h($reg) . '"><tr><th>Titel</th><th>Zeitraum</th><th>Ziel</th></tr>';
	$liveKinds = rkrk_jg_komm_notice_kinds();
	$anyShown = false;
	$livePage = rkrk_jg_komm_norm_reg($reg);
	foreach ($publishedNotices as $notice) {
		if (!is_array($notice)) {
			continue;
		}
		$targets = (isset($notice['targets']) && is_array($notice['targets'])) ? $notice['targets'] : array();
		if (count($targets) === 0) {
			$scope = trim((string)($notice['scope'] ?? ''));
			$targets = ($scope === '' || $scope === 'all') ? array('all') : preg_split('/[\s,;]+/', $scope);
		}
		$names = array();
		$liveKeys = array();
		$seenAll = false;
		$liveHere = false;
		foreach ($targets as $target) {
			$raw = trim((string)$target);
			if ($raw === '') {
				continue;
			}
			$parts = (strpos($raw, ',') !== false || strpos($raw, ';') !== false) ? preg_split('/[\s,;]+/', $raw) : array($raw);
			foreach ($parts as $part) {
				$part = trim((string)$part);
				if ($part === '') {
					continue;
				}
				if ($part === 'all') {
					$seenAll = true;
					$liveHere = true;
					if (!in_array('all', $liveKeys, true)) {
						$liveKeys[] = 'all';
					}
					continue;
				}
				$key = rkrk_jg_komm_norm_reg($part);
				$names[] = (string)($targetNames[$key] ?? $key);
				if (!in_array($key, $liveKeys, true)) {
					$liveKeys[] = $key;
				}
				if ($livePage !== '' && $key === $livePage) {
					$liveHere = true;
				}
			}
		}
		if ($seenAll) {
			$targetShort = 'alle';
		} elseif (count($names) > 2) {
			$targetShort = $names[0] . ', ' . $names[1] . ' +' . (count($names) - 2);
		} else {
			$targetShort = implode(', ', $names);
		}
		$fromRaw = (string)($notice['from'] ?? '');
		$toRaw = (string)($notice['to'] ?? '');
		$fromOk = preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $fromRaw, $fromMd);
		$toOk = preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $toRaw, $toMd);
		if (!empty($notice['yearly']) && $fromOk && $toOk) {
			$when = $fromMd[2] . '.' . $fromMd[1] . '.–' . $toMd[2] . '.' . $toMd[1] . '.';
		} elseif ($fromOk && $toOk) {
			$when = $fromMd[2] . '.' . $fromMd[1] . '.' . substr($fromRaw, 0, 4) . '–' . $toMd[2] . '.' . $toMd[1] . '.' . substr($toRaw, 0, 4);
		} else {
			$when = $fromRaw . '–' . $toRaw;
		}
		$noteTitle = trim((string)($notice['title'] ?? ''));
		if ($noteTitle === '') {
			$plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)($notice['text'] ?? ''))));
			if ($plain === '') {
				$noteTitle = 'ohne Titel';
			} elseif (function_exists('mb_strlen') && mb_strlen($plain, 'UTF-8') > 60) {
				$noteTitle = rtrim(mb_substr($plain, 0, 60, 'UTF-8')) . '…';
			} elseif (strlen($plain) > 60) {
				$noteTitle = rtrim(substr($plain, 0, 60)) . '…';
			} else {
				$noteTitle = $plain;
			}
		}
		$kindNow = (string)($notice['kind'] ?? 'info');
		if (!isset($liveKinds[$kindNow])) {
			$kindNow = 'info';
		}
		$kindColor = (string)($liveKinds[$kindNow]['color'] ?? '#527642');
		$liveOn = rkrk_jg_komm_notice_enabled($notice);
		$livePhase = rkrk_jg_komm_notice_phase($notice, $previewDate);
		$liveShow = $liveOn && $liveHere && ($livePhase === 'plan' || $livePhase === 'now');
		if ($liveShow) {
			$anyShown = true;
		}
		echo '<tr class="jgk-live"' . ($liveShow ? '' : ' hidden') . ' data-from="' . rkrk_jg_komm_h($fromRaw) . '" data-to="' . rkrk_jg_komm_h($toRaw) . '" data-yearly="' . (!empty($notice['yearly']) ? '1' : '0') . '" data-on="' . ($liveOn ? '1' : '0') . '" data-targets="' . rkrk_jg_komm_h(implode(',', $liveKeys)) . '"><td style="color:' . rkrk_jg_komm_h($kindColor) . ';border-left:4px solid ' . rkrk_jg_komm_h($kindColor) . ';font-weight:bold">' . rkrk_jg_komm_h($noteTitle) . '</td>';
		echo '<td>' . rkrk_jg_komm_h($when) . '</td><td>' . rkrk_jg_komm_h($targetShort) . '</td></tr>';
	}
	echo '<tr class="jgk-live-empty"' . ($anyShown ? ' hidden' : '') . '><td colspan="3">Keine geplanten Hinweise für diesen Stichtag.</td></tr>';
	echo '</table>';
	echo '<p>Öffentliche Adresse:<br><a href="' . rkrk_jg_komm_h($publicUrl) . '" target="_blank">' . rkrk_jg_komm_h($publicUrl) . '</a></p>';
	echo '<img src="' . rkrk_jg_komm_h($qr) . '" alt="QR-Code der öffentlichen Seite" width="140" height="140">';
	?>
</form>
<div class="jgk-split" id="jgkSplit" title="Menübreite ziehen"></div>
<section class="jgk-view">
	<div class="jgk-sheet">
	<?php if ($view === 'draft' && $previewReady) { ?>
		<div class="jgk-banner draft" id="jgkBanner">Interne Vorschau für <span id="jgkBannerDate"><?php echo rkrk_jg_komm_h($previewDate); ?></span> – nicht nach aussen sichtbar. Format 1000 px, wie die öffentliche Seite.</div>
	<?php } elseif ($view === 'draft') { ?>
		<div class="jgk-banner stale" id="jgkBanner">Noch keine interne Vorschau für das ganze Set. Rechts steht die Testseite, nicht die öffentliche Fassung. Format 1000 px.</div>
	<?php } else { ?>
		<div class="jgk-banner live">Sichtbar nach aussen, Format 1000 px</div>
	<?php } ?>
	<p style="margin:8px 14px;">
		<a href="rkrk_jg_komm.php?Reg=<?php echo rkrk_jg_komm_h($reg); ?>&view=draft">Entwurf ansehen</a>
		&nbsp;|&nbsp;
		<a href="rkrk_jg_komm.php?Reg=<?php echo rkrk_jg_komm_h($reg); ?>&view=live">Wie aussen ansehen</a>
	</p>
	<iframe id="jgkFrame" data-view="<?php echo $view === 'live' ? 'live' : 'draft'; ?>" data-base="<?php echo rkrk_jg_komm_h($draftUrl); ?>" data-has-draft="<?php echo $jgkHasDraft ? '1' : '0'; ?>" title="<?php echo $view === 'live' ? 'Öffentliche Seite' : 'Interne Vorschau'; ?>" width="1000" src="<?php echo rkrk_jg_komm_h($iframeUrl); ?>"></iframe>
	</div>
</section>
</div>
<div class="jgk-modal" id="jgkModal" hidden>
	<div class="jgk-dialog" role="dialog" aria-modal="true" aria-labelledby="jgkPickTitle">
		<h2 id="jgkPickTitle">Zuordnen</h2>
		<p class="jgk-modes"><button type="button" data-mode="jaeger">Jäger</button><button type="button" data-mode="piloten">Piloten</button></p>
		<p id="jgkPickSource"></p>
		<input type="search" id="jgkPickSearch" placeholder="Suchen">
		<div class="jgk-boards">
			<div class="jgk-pool"><b id="jgkPoolTitle">Liste</b><div class="jgk-board" id="jgkPool"></div></div>
			<div class="jgk-cols" id="jgkCols"></div>
		</div>
		<p><button type="button" id="jgkPickClose">Schliessen</button></p>
	</div>
</div>
<script>
document.getElementById('jgkForm').addEventListener('input', function (e) {
	if (e.target && e.target.name === 'preview_date') return;
	var publish = document.querySelector('button[value="publish"]');
	if (publish) publish.disabled = true;
});
(function () {
	var input = document.getElementById('jgkPreviewDate');
	if (!input) return;
	function dateActive(from, to, onDate, yearly) {
		if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to) || !/^\d{4}-\d{2}-\d{2}$/.test(onDate)) return false;
		if (!yearly) return onDate >= from && onDate <= to;
		var fromMd = from.slice(5), toMd = to.slice(5), onMd = onDate.slice(5);
		if (fromMd <= toMd) return onMd >= fromMd && onMd <= toMd;
		return onMd >= fromMd || onMd <= toMd;
	}
	function phaseOf(from, to, onDate, yearly) {
		if (dateActive(from, to, onDate, yearly)) return 'now';
		if (yearly) return 'plan';
		if (from !== '' && onDate < from) return 'plan';
		return 'past';
	}
	function targetHere(targets, page) {
		var list = String(targets || '').split(',');
		for (var i = 0; i < list.length; i++) {
			if (list[i] === 'all' || (list[i] !== '' && list[i] === page)) return true;
		}
		return false;
	}
	function paintJetzt(onDate) {
		var table = document.getElementById('jgkNoticePlan');
		if (!table) return;
		var page = table.getAttribute('data-page') || '';
		var rows = table.querySelectorAll('tr[data-from]');
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i];
			var cell = row.querySelector('.jgk-jetzt');
			if (!cell || row.getAttribute('data-new') === '1') continue;
			var from = row.getAttribute('data-from') || '';
			var to = row.getAttribute('data-to') || '';
			var yearly = row.getAttribute('data-yearly') === '1';
			var on = row.getAttribute('data-on') === '1';
			var phase = phaseOf(from, to, onDate, yearly);
			var text = 'andere';
			var mark = false;
			if (on && dateActive(from, to, onDate, yearly) && targetHere(row.getAttribute('data-targets'), page)) {
				text = 'angezeigt';
				mark = true;
			} else if (!on) {
				text = 'aus';
			} else if (phase === 'plan') {
				text = 'geplant';
			} else if (phase === 'past') {
				text = 'vorbei';
			}
			cell.textContent = text;
			cell.className = 'jgk-flag jgk-jetzt' + (mark ? ' jgk-ja' : '');
		}
	}
	function paintLive(onDate) {
		var table = document.getElementById('jgkLivePlan');
		if (!table) return;
		var page = table.getAttribute('data-page') || '';
		var rows = table.querySelectorAll('tr.jgk-live');
		var shown = 0;
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i];
			var from = row.getAttribute('data-from') || '';
			var to = row.getAttribute('data-to') || '';
			var yearly = row.getAttribute('data-yearly') === '1';
			var on = row.getAttribute('data-on') === '1';
			var phase = phaseOf(from, to, onDate, yearly);
			var show = on && targetHere(row.getAttribute('data-targets'), page) && (phase === 'plan' || phase === 'now');
			row.hidden = !show;
			if (show) shown++;
		}
		var empty = table.querySelector('tr.jgk-live-empty');
		if (empty) empty.hidden = shown > 0;
	}
	function paintDate() {
		var onDate = input.value;
		if (!/^\d{4}-\d{2}-\d{2}$/.test(onDate)) return;
		paintJetzt(onDate);
		paintLive(onDate);
		var bannerDate = document.getElementById('jgkBannerDate');
		if (bannerDate) bannerDate.textContent = onDate;
		var frame = document.getElementById('jgkFrame');
		if (!frame || frame.getAttribute('data-view') !== 'draft') return;
		if (frame.getAttribute('data-has-draft') !== '1' && frame.src.indexOf('Preview=1') < 0) return;
		var base = frame.getAttribute('data-base') || '';
		if (!base) return;
		var next = base + '&Preview=1&PreviewDate=' + encodeURIComponent(onDate);
		if (frame.getAttribute('src') !== next) frame.src = next;
	}
	input.addEventListener('input', paintDate);
	input.addEventListener('change', paintDate);
	var pageSel = document.getElementById('jgkPage');
	if (pageSel) {
		pageSel.addEventListener('change', function () {
			var next = this.value;
			if (!next) return;
			var nav = document.getElementById('jgkNav');
			if (!nav) return;
			nav.value = next;
			document.getElementById('jgkForm').submit();
		});
	}
})();
<?php if ($jgkNavReg !== '') { ?>
history.replaceState(null, '', 'rkrk_jg_komm.php?Reg=<?php echo rawurlencode($jgkNavReg); ?>&view=draft');
<?php } ?>
(function () {
	var plan = document.querySelector('.jgk-plan');
	if (!plan) return;
	var toggles = plan.querySelectorAll('.jgk-toggle');
	function summaryRow(btn) {
		return btn.closest('tr');
	}
	function editorRow(btn) {
		var id = btn.getAttribute('aria-controls') || '';
		return id ? document.getElementById(id) : null;
	}
	function isNew(btn) {
		var row = summaryRow(btn);
		return !!(row && row.classList.contains('jgk-new'));
	}
	function mark() {
		for (var i = 0; i < toggles.length; i++) {
			var sum = summaryRow(toggles[i]);
			var ed = editorRow(toggles[i]);
			if (!sum || !ed) continue;
			var open = !ed.hidden;
			toggles[i].setAttribute('aria-expanded', open ? 'true' : 'false');
			if (isNew(toggles[i])) continue;
			if (open) sum.classList.add('jgk-open');
			else sum.classList.remove('jgk-open');
		}
	}
	function openNotice(btn) {
		var target = editorRow(btn);
		if (!target) return;
		if (!target.hidden) {
			target.hidden = true;
			mark();
			return;
		}
		for (var i = 0; i < toggles.length; i++) {
			if (isNew(toggles[i])) continue;
			var ed = editorRow(toggles[i]);
			if (!ed) continue;
			ed.hidden = toggles[i] !== btn;
		}
		target.hidden = false;
		mark();
		var title = target.querySelector('input[name="notice_title[]"]');
		if (title) title.focus();
	}
	for (var t = 0; t < toggles.length; t++) {
		toggles[t].addEventListener('click', function (e) {
			e.preventDefault();
			openNotice(this);
		});
	}
	var titles = plan.querySelectorAll('input[data-sum]');
	for (var n = 0; n < titles.length; n++) {
		titles[n].addEventListener('input', function () {
			var btn = document.getElementById(this.getAttribute('data-sum') || '');
			if (!btn) return;
			var text = this.value.replace(/^\s+|\s+$/g, '');
			var sum = summaryRow(btn);
			var fallback = (sum && sum.classList.contains('jgk-new')) ? 'Neue Nachricht' : 'ohne Titel';
			btn.textContent = text || fallback;
		});
	}
	mark();
})();
(function () {
	function paintKind(sel) {
		var opt = sel.options[sel.selectedIndex];
		if (!opt) return;
		sel.style.backgroundColor = opt.style.backgroundColor;
		sel.style.color = opt.style.color;
	}
	var kinds = document.querySelectorAll('select.jgk-kind');
	for (var i = 0; i < kinds.length; i++) {
		kinds[i].addEventListener('change', function () { paintKind(this); });
		paintKind(kinds[i]);
	}
})();
(function () {
	var edit = document.getElementById('jgkForm');
	var split = document.getElementById('jgkSplit');
	var box = edit.parentNode;
	var key = 'jgk-edit-width';
	var saved = parseInt(localStorage.getItem(key) || '', 10);
	if (saved >= 280) edit.style.width = saved + 'px';
	var dragging = false;
	function frames(off) {
		var list = box.querySelectorAll('iframe');
		for (var i = 0; i < list.length; i++) list[i].style.pointerEvents = off ? 'none' : '';
	}
	function end() {
		if (!dragging) return;
		dragging = false;
		split.className = 'jgk-split';
		document.body.style.cursor = '';
		frames(false);
		localStorage.setItem(key, String(parseInt(edit.style.width, 10) || 440));
	}
	split.addEventListener('pointerdown', function (e) {
		dragging = true;
		split.className = 'jgk-split drag';
		split.setPointerCapture(e.pointerId);
		document.body.style.cursor = 'col-resize';
		frames(true);
	});
	split.addEventListener('pointermove', function (e) {
		if (!dragging) return;
		var w = Math.round(e.clientX - box.getBoundingClientRect().left);
		var max = Math.max(280, box.clientWidth - 120);
		if (w < 280) w = 280;
		if (w > max) w = max;
		edit.style.width = w + 'px';
	});
	split.addEventListener('pointerup', end);
	split.addEventListener('pointercancel', end);
	function fitPreview() {
		var top = box.getBoundingClientRect().top;
		var h = window.innerHeight - top;
		if (h < 240) h = 240;
		box.style.height = h + 'px';
	}
	window.addEventListener('resize', fitPreview);
	fitPreview();
})();
(function () {
	var raw = document.getElementById('jgkPeople');
	var pool = document.getElementById('jgkPool');
	var cols = document.getElementById('jgkCols');
	var fields = document.getElementById('jgkAssignFields');
	if (!raw || !pool || !cols || !fields) return;
	var data = JSON.parse(raw.textContent || '{}');
	var groups = data.groups || {};
	var openKey = '';
	var dragName = '';
	var dragFrom = '';
	function leaves() {
		return data.leaves || [];
	}
	function hereReg() {
		var reg = String(data.reg || '');
		var list = leaves();
		for (var i = 0; i < list.length; i++) {
			if (list[i].reg === reg) return reg;
		}
		return '';
	}
	function itemsOf(key) {
		return (groups[key] && groups[key].items) ? groups[key].items : [];
	}
	function findItem(key, name) {
		var items = itemsOf(key);
		for (var i = 0; i < items.length; i++) {
			if (items[i].name === name) return items[i];
		}
		return { name: name, label: name, code: '', jg: '' };
	}
	function line(item) {
		var text = item.label || item.name;
		if (item.code) text += ' (' + item.code + ')';
		if (item.jg) text += ', ' + item.jg;
		return text;
	}
	function stateOf(key, reg) {
		if (!groups[key]) return { selected: [], stv: [] };
		if (!groups[key].byReg) groups[key].byReg = {};
		if (!groups[key].byReg[reg]) groups[key].byReg[reg] = { selected: [], stv: [] };
		var row = groups[key].byReg[reg];
		if (!row.selected) row.selected = [];
		if (!row.stv) row.stv = [];
		return row;
	}
	function isStv(key, reg, name) {
		return stateOf(key, reg).stv.indexOf(name) >= 0;
	}
	function setStv(key, reg, name, on) {
		var row = stateOf(key, reg);
		var next = [];
		for (var i = 0; i < row.stv.length; i++) {
			if (row.stv[i] !== name) next.push(row.stv[i]);
		}
		if (on && row.selected.indexOf(name) >= 0) next.push(name);
		row.stv = next;
	}
	function keepStv(key, reg) {
		var row = stateOf(key, reg);
		var allow = {};
		for (var i = 0; i < row.selected.length; i++) allow[row.selected[i]] = true;
		var next = [];
		for (var j = 0; j < row.stv.length; j++) {
			if (allow[row.stv[j]]) next.push(row.stv[j]);
		}
		row.stv = next;
	}
	function addHidden(name, value) {
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		fields.appendChild(input);
	}
	function syncFields() {
		fields.innerHTML = '';
		var list = leaves();
		var kinds = ['jaeger', 'piloten'];
		for (var i = 0; i < list.length; i++) {
			var reg = list[i].reg;
			addHidden('assign_regs[]', reg);
			for (var k = 0; k < kinds.length; k++) {
				var key = kinds[k];
				var row = stateOf(key, reg);
				keepStv(key, reg);
				addHidden('assign_' + key + '_stv_set[]', reg);
				for (var n = 0; n < row.selected.length; n++) {
					addHidden('assign_' + key + '[' + reg + '][]', row.selected[n]);
				}
				for (var s = 0; s < row.stv.length; s++) {
					addHidden('assign_' + key + '_stv[' + reg + '][]', row.stv[s]);
				}
			}
		}
	}
	function paintSummary(key) {
		var box = document.getElementById('jgkText-' + key);
		if (!box) return;
		box.innerHTML = '';
		var focus = hereReg();
		var list = leaves();
		var any = false;
		for (var i = 0; i < list.length; i++) {
			var leaf = list[i];
			if (focus && leaf.reg !== focus) continue;
			keepStv(key, leaf.reg);
			var row = stateOf(key, leaf.reg);
			if (!row.selected.length) continue;
			any = true;
			var bits = [];
			for (var n = 0; n < row.selected.length; n++) {
				var text = line(findItem(key, row.selected[n]));
				if (row.stv.indexOf(row.selected[n]) >= 0) text += ' Stv';
				bits.push(text);
			}
			var p = document.createElement('p');
			p.textContent = (focus ? '' : (leaf.label + ': ')) + bits.join(', ');
			box.appendChild(p);
		}
		if (!any) {
			var empty = document.createElement('p');
			empty.textContent = 'Keine Zuordnung.';
			box.appendChild(empty);
		}
	}
	function touchForm() {
		paintSummary('jaeger');
		paintSummary('piloten');
		syncFields();
		if (openKey) paintModal();
		var form = document.getElementById('jgkForm');
		if (form) form.dispatchEvent(new Event('input', { bubbles: true }));
	}
	function addTo(reg, name) {
		if (!openKey || !reg || !name) return false;
		var row = stateOf(openKey, reg);
		if (row.selected.indexOf(name) >= 0) return false;
		row.selected.push(name);
		return true;
	}
	function removeFrom(reg, name) {
		if (!openKey || !reg || !name) return false;
		var row = stateOf(openKey, reg);
		var next = [];
		var found = false;
		for (var i = 0; i < row.selected.length; i++) {
			if (row.selected[i] === name) found = true;
			else next.push(row.selected[i]);
		}
		if (!found) return false;
		row.selected = next;
		setStv(openKey, reg, name, false);
		return true;
	}
	function stvButton(key, reg, name) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'jgk-mini jgk-stv' + (isStv(key, reg, name) ? ' on' : '');
		btn.textContent = 'Stv';
		btn.title = 'Stellvertretung';
		btn.addEventListener('mousedown', function (e) { e.stopPropagation(); });
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			setStv(key, reg, name, !isStv(key, reg, name));
			touchForm();
		});
		return btn;
	}
	function card(item, reg) {
		var el = document.createElement('div');
		el.className = 'jgk-card';
		el.draggable = true;
		el.dataset.name = item.name;
		if (reg) el.dataset.reg = reg;
		var title = document.createElement('div');
		title.className = 'jgk-name';
		title.textContent = line(item);
		el.appendChild(title);
		if (reg) el.appendChild(stvButton(openKey, reg, item.name));
		el.addEventListener('dragstart', function (e) {
			if (e.target.closest && e.target.closest('button')) {
				e.preventDefault();
				return;
			}
			dragName = item.name;
			dragFrom = reg || '';
			if (e.dataTransfer) {
				e.dataTransfer.setData('text/plain', item.name);
				e.dataTransfer.effectAllowed = 'copy';
			}
		});
		el.addEventListener('dblclick', function (e) {
			if (e.target.closest && e.target.closest('button')) return;
			var changed = reg ? removeFrom(reg, item.name) : addTo(hereReg(), item.name);
			if (changed) touchForm();
		});
		return el;
	}
	function fillBoard(board, names, query, reg) {
		board.innerHTML = '';
		var q = (query || '').toLowerCase();
		var shown = 0;
		for (var i = 0; i < names.length; i++) {
			var item = findItem(openKey, names[i]);
			if (q && line(item).toLowerCase().indexOf(q) < 0) continue;
			board.appendChild(card(item, reg));
			shown++;
		}
		if (shown === 0) {
			var empty = document.createElement('p');
			empty.textContent = 'Keine Einträge.';
			board.appendChild(empty);
		}
	}
	function clearOver() {
		var marked = cols.querySelectorAll('.jgk-col.over');
		for (var i = 0; i < marked.length; i++) marked[i].classList.remove('over');
	}
	function paintModal() {
		if (!openKey || !groups[openKey]) return;
		var query = document.getElementById('jgkPickSearch').value || '';
		var names = [];
		var items = itemsOf(openKey);
		for (var i = 0; i < items.length; i++) names.push(items[i].name);
		fillBoard(pool, names, query, '');
		cols.innerHTML = '';
		var focus = hereReg();
		var list = leaves();
		for (var c = 0; c < list.length; c++) {
			var leaf = list[c];
			var col = document.createElement('div');
			col.className = 'jgk-col' + (leaf.reg === focus ? ' here' : '');
			var title = document.createElement('b');
			title.textContent = leaf.label || leaf.reg;
			var sub = document.createElement('small');
			sub.textContent = leaf.reg;
			var board = document.createElement('div');
			board.className = 'jgk-board';
			col.appendChild(title);
			col.appendChild(sub);
			col.appendChild(board);
			fillBoard(board, stateOf(openKey, leaf.reg).selected, query, leaf.reg);
			(function (reg, column) {
				column.addEventListener('dragover', function (e) {
					e.preventDefault();
					column.classList.add('over');
				});
				column.addEventListener('drop', function (e) {
					e.preventDefault();
					clearOver();
					if (dragName && addTo(reg, dragName)) touchForm();
					dragName = '';
					dragFrom = '';
				});
			})(leaf.reg, col);
			cols.appendChild(col);
		}
	}
	pool.addEventListener('dragover', function (e) { e.preventDefault(); });
	pool.addEventListener('drop', function (e) {
		e.preventDefault();
		clearOver();
		if (dragName && dragFrom && removeFrom(dragFrom, dragName)) touchForm();
		dragName = '';
		dragFrom = '';
	});
	document.addEventListener('dragend', function () {
		dragName = '';
		dragFrom = '';
		clearOver();
	});
	var search = document.getElementById('jgkPickSearch');
	if (search) search.addEventListener('input', paintModal);
	var closeBtn = document.getElementById('jgkPickClose');
	if (closeBtn) closeBtn.addEventListener('click', function () {
		document.getElementById('jgkModal').hidden = true;
		openKey = '';
	});
	var modal = document.getElementById('jgkModal');
	if (modal) modal.addEventListener('click', function (e) {
		if (e.target === this) {
			this.hidden = true;
			openKey = '';
		}
	});
	function setMode(key) {
		openKey = key;
		var modes = document.querySelectorAll('#jgkModal button[data-mode]');
		for (var i = 0; i < modes.length; i++) {
			modes[i].setAttribute('aria-pressed', modes[i].getAttribute('data-mode') === key ? 'true' : 'false');
		}
		var group = groups[key];
		if (!group) return;
		document.getElementById('jgkPickTitle').textContent = group.title + ' zuordnen';
		document.getElementById('jgkPickSource').textContent = group.source + '. Person auf eine Spalte ziehen. Dieselbe Person kann auf mehreren Jagdgebieten liegen. Stv setzt das Jagdgebiet in Klammern.';
		document.getElementById('jgkPoolTitle').textContent = group.source;
		paintModal();
		var here = cols.querySelector('.jgk-col.here');
		if (here && here.scrollIntoView) here.scrollIntoView({ inline: 'center', block: 'nearest' });
	}
	var modes = document.querySelectorAll('#jgkModal button[data-mode]');
	for (var m = 0; m < modes.length; m++) {
		modes[m].addEventListener('click', function () {
			var key = this.getAttribute('data-mode') || '';
			if (!groups[key]) return;
			setMode(key);
		});
	}
	var buttons = document.querySelectorAll('button[data-pick]');
	for (var b = 0; b < buttons.length; b++) {
		buttons[b].addEventListener('click', function () {
			var key = this.getAttribute('data-pick') || '';
			if (!groups[key]) return;
			if (search) search.value = '';
			document.getElementById('jgkModal').hidden = false;
			setMode(key);
		});
	}
	paintSummary('jaeger');
	paintSummary('piloten');
	syncFields();
})();
</script>
