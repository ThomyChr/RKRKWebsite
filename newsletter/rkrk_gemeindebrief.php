<?php
session_start();
$folderoffset = '../';
chdir(dirname(__DIR__));
include_once 'scripts/rkrk_gen.php';
include_once 'scripts/rkrk_csvfiles.php';
include_once 'scripts/rkrk_menu.php';
include_once 'rkrk_access_control.php';
include_once 'newsletter/rkrk_newsletter_lib.php';

CheckIPandExit();
nl_ensure_store();

function gb_redirect($extra = array()) {
	header('Location: rkrk_gemeindebrief.php?' . http_build_query($extra));
	exit;
}

function gb_seed_sections($camp, $letter = array()) {
	if (isset($camp['sections']) && is_array($camp['sections']) && count($camp['sections']) > 0) {
		$sections = $camp['sections'];
	} else {
		$pics = array();
		foreach (array('themeBild1', 'themeBild2') as $key) {
			$rel = trim((string)($camp[$key] ?? ''));
			if ($rel !== '') {
				$pics[] = $rel;
			}
		}
		$sec = nl_blank_section();
		$sec['title'] = trim((string)($camp['themeTitle'] ?? ''));
		$sec['text'] = (string)($camp['themeText'] ?? '');
		$sec['caption'] = trim((string)($camp['themeCap1'] ?? ''));
		$sec['image'] = isset($pics[0]) ? $pics[0] : '';
		$sec['images'] = array_slice($pics, 1);
		$sections = array($sec);
	}
	$hasPic = false;
	foreach ($sections as $section) {
		if (count(nl_section_pic_rels($section)) > 0) {
			$hasPic = true;
			break;
		}
	}
	if (!$hasPic) {
		$rels = array();
		foreach (nl_letter_own_slots($letter) as $slot) {
			if (trim((string)($slot['rel'] ?? '')) !== '') {
				$rels[] = $slot;
			}
		}
		if (count($rels) > 0) {
			$sections[0]['pics'] = array();
			$sections[0]['captions'] = array();
			$sections[0]['images'] = array();
			foreach ($rels as $i => $slot) {
				$sections[0]['pics'][] = array('rel' => $slot['rel'], 'cap' => $slot['cap']);
				$sections[0]['captions'][] = $slot['cap'];
				if ($i === 0) {
					$sections[0]['image'] = $slot['rel'];
					if (trim((string)($sections[0]['caption'] ?? '')) === '') {
						$sections[0]['caption'] = $slot['cap'];
					}
				} else {
					$sections[0]['images'][] = $slot['rel'];
				}
			}
		}
	}
	return $sections;
}

function gb_echo_pic_slot($rel, $isMain, $secId, $cap, $over = array(), $crop = null) {
	$rel = trim((string)$rel);
	$src = nl_media_src($rel);
	if (!is_array($over)) {
		$over = array();
	}
	$ownOn = !empty($over['on']);
	$hideHere = !empty($over['hide']) && !$ownOn;
	echo "<div class='nl-pic" . ($isMain ? " is-main" : "") . ($src === '' ? " is-empty" : "") . ($hideHere ? " is-off" : "") . "'>";
	echo "<span class='nl-pic-handle' title='Ziehen zum Sortieren'>⋮⋮</span>";
	echo "<div class='nl-img-mask'>";
	if ($src !== '') {
		$pix = @getimagesize((string)nl_file_for_web_src($src));
		$dim = (is_array($pix) && !empty($pix[0]) && !empty($pix[1])) ? (" data-nw='" . (int)$pix[0] . "' data-nh='" . (int)$pix[1] . "'") : '';
		echo "<img class='nl-pic-preview' src='" . nl_h($src) . "' alt=''" . $dim . ">";
	}
	echo "</div>";
	if ($secId === null) {
		echo "<input class='nl-pic-path' type='hidden' name='gde_keep[]' value='" . nl_h($rel) . "'>";
		echo "<input class='nl-pic-file' type='file' name='gde_file[]' accept='image/*' hidden>";
		echo "<label>Beschriftung</label><input class='gb-own' type='text' name='gde_cap[]' value='" . nl_h($cap) . "'>";
	} elseif ($secId === 'intro') {
		echo "<input class='nl-pic-path' type='hidden' name='intro_keep[]' value='" . nl_h($rel) . "'>";
		echo "<input class='nl-pic-file' type='file' name='intro_file[]' accept='image/*' hidden>";
	} elseif ($isMain) {
		echo "<input class='nl-pic-path' type='hidden' value='" . nl_h($rel) . "'>";
		echo "<input class='nl-pic-file nl-file-input' type='file' name='sec_file[]' accept='image/*' hidden>";
	} else {
		echo "<input class='nl-pic-path' type='hidden' name='sec_more_keep[" . nl_h($secId) . "][]' value='" . nl_h($rel) . "'>";
		echo "<input class='nl-pic-file' type='file' name='sec_more[" . nl_h($secId) . "][]' accept='image/*' hidden>";
	}
	echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-pic-load'>" . ($src !== '' ? "Bild ändern" : "Bild laden") . "</button>";
	echo "<button type='button' class='nl-icon-btn nl-icon-danger nl-pic-del' title='Bild entfernen' aria-label='Bild entfernen'>" . nl_ico('trash') . "</button>";
	if ($secId !== null && $src !== '') {
		echo "<button type='button' class='nl-btn secondary nl-crop-show'>Ausschnitt wählen</button>";
	}
	echo "</div>";
	if ($secId !== null) {
		if (!is_array($over)) {
			$over = array();
		}
		$seriesCap = (string)$cap;
		$ownRel = trim((string)($over['rel'] ?? ''));
		$ownSrc = nl_media_src($ownRel);
		$cropBox = nl_crop_box(is_array($crop) ? $crop : null);
		$cropJson = $cropBox ? json_encode($cropBox) : '';
		$cropName = ($secId === 'intro') ? 'intro_crop[]' : ('sec_crop[' . $secId . '][]');
		$capName = ($secId === 'intro') ? 'intro_cap[]' : ('sec_cap[' . $secId . '][]');
		echo "<input type='hidden' class='nl-pic-crop' name='" . $cropName . "' value='" . nl_h($cropJson) . "'>";
		echo "<label>Bildbeschriftung</label><input type='text' class='nl-pic-cap' name='" . $capName . "' value='" . nl_h($seriesCap) . "'>";
		static $gbScopeN = 0;
		$gbScopeN++;
		$scope = 'pic-scope-' . $gbScopeN;
		echo "<div class='nl-pic-scope'>";
		echo "<label><input type='radio' class='nl-scope-all' name='" . $scope . "'" . ($ownOn ? "" : " checked") . "> Alle</label>";
		echo "<label><input type='radio' class='nl-scope-own gb-own' name='" . $scope . "'" . ($ownOn ? " checked" : "") . "> Nur diese Gemeinde</label>";
		echo "</div>";
		echo "<input type='hidden' class='nl-own-flag' name='own_on[" . nl_h($secId) . "][]' value='" . ($ownOn ? "1" : "0") . "'>";
		echo "<input type='hidden' class='nl-hide-flag' name='own_hide[" . nl_h($secId) . "][]' value='" . ($hideHere ? "1" : "0") . "'>";
		echo "<button type='button' class='nl-btn secondary nl-pic-hide gb-own'" . ($ownOn ? " hidden" : "") . ">" . ($hideHere ? "Wieder zeigen" : "Hier ausblenden") . "</button>";
		echo "<div class='nl-own-box'" . ($ownOn ? "" : " hidden") . ">";
		if ($ownSrc !== '') {
			$ownPix = @getimagesize((string)nl_file_for_web_src($ownSrc));
			$ownDim = (is_array($ownPix) && !empty($ownPix[0]) && !empty($ownPix[1])) ? (" data-nw='" . (int)$ownPix[0] . "' data-nh='" . (int)$ownPix[1] . "'") : '';
			echo "<img class='nl-own-preview nl-sec-preview' src='" . nl_h($ownSrc) . "' alt=''" . $ownDim . ">";
		}
		echo "<input type='hidden' class='nl-own-path' name='own_keep[" . nl_h($secId) . "][]' value='" . nl_h($ownRel) . "'>";
		echo "<input class='nl-own-file' type='file' name='own_file[" . nl_h($secId) . "][]' accept='image/*' hidden>";
		echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-own-load gb-own'>" . ($ownSrc !== '' ? "Bild hier ändern" : "Eigenes Bild") . "</button>";
		if ($ownSrc !== '') {
			echo "<button type='button' class='nl-btn secondary nl-own-crop-show gb-own'>Ausschnitt wählen</button>";
		}
		echo "</div>";
		$ownCrop = nl_crop_box(isset($over['crop']) && is_array($over['crop']) ? $over['crop'] : null);
		echo "<input type='hidden' class='nl-own-crop' name='own_crop[" . nl_h($secId) . "][]' value='" . nl_h($ownCrop ? json_encode($ownCrop) : '') . "'>";
		echo "<label>Beschriftung nur hier</label><input type='text' class='nl-own-cap gb-own' name='own_cap[" . nl_h($secId) . "][]' value='" . nl_h($over['cap'] ?? '') . "'>";
		echo "</div>";
	}
	echo "</div>";
}

$nlLoggedIn = isset($SessNme_RegisteredUser) && trim((string)($_SESSION[$SessNme_RegisteredUser] ?? '')) !== '';
$nlAllowed = $nlLoggedIn && MenuEntryAccessGranted('Gemeindebrief');
if ($nlAllowed) {
	$freezeCamps = nl_items('newsletter_kampagnen.json');
	$freezeGde = nl_items('newsletter_gemeinden.json');
	if (nl_freeze_letter_recipients($freezeCamps, $freezeGde)) {
		nl_save_items('newsletter_kampagnen.json', $freezeCamps);
	}
}
if ($nlAllowed && !empty($_GET['pdf'])) {
	nl_stream_gemeinde_pdf(
		(string)($_GET['campaign'] ?? ''),
		(string)($_GET['gemeinde'] ?? ''),
		(string)($_GET['rtKat'] ?? ''),
		(string)($_GET['slip'] ?? '')
	);
}
if ($nlAllowed && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	if ($action === 'import_gemeinde') {
		$camp = (string)($_POST['campaign'] ?? '');
		if (!empty($_FILES['xlsx']['tmp_name'])) {
			$result = nl_import_gemeinde_xlsx($_FILES['xlsx']['tmp_name'], $camp);
			$extra = isset($result['error'])
				? array('err' => $result['error'])
				: array('msg' => 'Gemeinden: ' . (int)$result['added'] . ' neu, ' . (int)$result['updated'] . ' aktualisiert.');
		} else {
			$extra = array('err' => 'Keine Datei gewählt.');
		}
		$extra['campaign'] = $camp;
		gb_redirect($extra);
	}
	if ($action === 'save_gemeinde_ui') {
		$cols = nl_gemeinde_columns();
		$visible = !empty($_POST['col_vis_show_all'])
			? array_keys($cols)
			: (isset($_POST['col_visible']) && is_array($_POST['col_visible']) ? $_POST['col_visible'] : array());
		nl_ui_save_hidden('gemeinde_ui.json', $cols, $visible);
		gb_redirect(array('msg' => 'Spalten gespeichert.', 'campaign' => (string)($_POST['campaign'] ?? '')));
	}
	if ($action === 'save_gemeinde_widths') {
		$posted = isset($_POST['widths']) && is_array($_POST['widths']) ? $_POST['widths'] : array();
		nl_ui_save_widths('gemeinde_ui.json', nl_gemeinde_columns(), $posted);
		if (!empty($_POST['ajax'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo '{"ok":true}';
			exit;
		}
		gb_redirect(array('msg' => 'Spaltenbreiten gespeichert.', 'campaign' => (string)($_POST['campaign'] ?? '')));
	}
	if ($action === 'save_gemeinde_favorit') {
		nl_set_favorit('newsletter_gemeinden.json', (string)($_POST['id'] ?? ''), !empty($_POST['favorit']));
		if (!empty($_POST['ajax'])) {
			header('Content-Type: application/json; charset=utf-8');
			echo '{"ok":true}';
			exit;
		}
		gb_redirect(array('campaign' => (string)($_POST['campaign'] ?? '')));
	}
	if ($action === 'save_gemeinde_grid') {
		$cols = nl_gemeinde_columns();
		$posted = nl_posted_grid_rows();
		$delete = nl_posted_grid_deletes();
		$items = nl_merge_grid_rows(nl_items('newsletter_gemeinden.json'), $posted, $cols, $delete);
		foreach ($items as $i => $g) {
			$items[$i]['gdeCode'] = nl_gemeinde_code($g['gemeinde'] ?? '');
		}
		nl_save_items('newsletter_gemeinden.json', $items);
		gb_redirect(array('msg' => count($items) . ' Gemeinde-Adressen gespeichert.', 'campaign' => (string)($_POST['campaign'] ?? '')));
	}
	if ($action === 'new_campaign') {
		$baseId = trim((string)($_POST['base'] ?? ''));
		$newId = preg_replace('/\D+/', '', (string)($_POST['newId'] ?? ''));
		$items = nl_items('newsletter_kampagnen.json');
		$base = nl_find_item($items, $baseId);
		if (!$base || strlen($newId) !== 6) {
			gb_redirect(array('err' => 'Basis und Kennung JJJJMM angeben.', 'campaign' => $baseId));
		}
		if (nl_find_item($items, $newId)) {
			gb_redirect(array('err' => 'Diese Kennung gibt es schon.', 'campaign' => $baseId));
		}
		$copy = $base;
		$copy['id'] = $newId;
		$copy['title'] = 'Gde ' . substr($newId, 0, 4) . '.' . substr($newId, 4, 2);
		nl_apply_brief_season($copy);
		$items[] = $copy;
		nl_save_items('newsletter_kampagnen.json', $items);
		gb_redirect(array('msg' => 'Kampagne angelegt.', 'campaign' => $newId));
	}
	if ($action === 'delete_campaign') {
		$id = preg_replace('/\D+/', '', (string)($_POST['campaign'] ?? ''));
		$items = nl_items('newsletter_kampagnen.json');
		$keep = array();
		$found = false;
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			if ((string)($item['id'] ?? '') === $id) {
				$found = true;
				continue;
			}
			$keep[] = $item;
		}
		if ($id === '' || !$found || !nl_save_items('newsletter_kampagnen.json', $keep)) {
			gb_redirect(array('err' => 'Serie konnte nicht gelöscht werden.', 'campaign' => $id));
		}
		$next = isset($keep[0]['id']) ? (string)$keep[0]['id'] : '';
		gb_redirect(array('msg' => 'Serie gelöscht. Die Gemeinde-Adressen bleiben erhalten.', 'campaign' => $next));
	}
	if ($action === 'save_campaign') {
		$id = trim((string)($_POST['id'] ?? ''));
		if ($id === '') {
			$id = date('Ym');
		}
		$items = nl_items('newsletter_kampagnen.json');
		$current = nl_find_item($items, $id);
		if (!$current) {
			$current = array('id' => $id, 'texts' => array());
		}
		$current['title'] = trim((string)($_POST['title'] ?? ''));
		nl_apply_brief_season($current);
		$current['ortDatum'] = trim((string)($_POST['ortDatum'] ?? 'Knonau'));
		$current['rtKat'] = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)($_POST['rtKat'] ?? 'RK')));
		$seriesSlip = (string)($_POST['seriesSlip'] ?? '');
		if (in_array($seriesSlip, array('betrag', 'beide', 'keine'), true)) {
			$current['slipMode'] = $seriesSlip;
		}
		$folder = 'gemeinde/' . preg_replace('/[^A-Za-z0-9_-]/', '', $id);
		$current['sharedHtml'] = (string)($_POST['sharedHtml'] ?? '');
		$introKeeps = (isset($_POST['intro_keep']) && is_array($_POST['intro_keep'])) ? array_values($_POST['intro_keep']) : array();
		$introCaps = (isset($_POST['intro_cap']) && is_array($_POST['intro_cap'])) ? array_values($_POST['intro_cap']) : array();
		$introCrops = (isset($_POST['intro_crop']) && is_array($_POST['intro_crop'])) ? array_values($_POST['intro_crop']) : array();
		$introFiles = (isset($_FILES['intro_file']['name']) && is_array($_FILES['intro_file']['name'])) ? count($_FILES['intro_file']['name']) : 0;
		$introCount = max(count($introKeeps), count($introCaps), count($introCrops), $introFiles);
		$introPics = array();
		for ($pi = 0; $pi < $introCount; $pi++) {
			$uploadedIntro = nl_store_upload_index('intro_file', $pi, $folder);
			$rel = ($uploadedIntro !== '') ? $uploadedIntro : trim((string)($introKeeps[$pi] ?? ''));
			if ($rel === '') {
				continue;
			}
			$pic = array('rel' => $rel, 'cap' => trim((string)($introCaps[$pi] ?? '')));
			$crop = nl_crop_box(json_decode((string)($introCrops[$pi] ?? ''), true));
			if ($crop) {
				$pic['crop'] = $crop;
			}
			$introPics[] = $pic;
		}
		$current['introPics'] = $introPics;
		$current['dankShared'] = (string)($_POST['dankShared'] ?? '');
		$sections = array();
		$titles = isset($_POST['sec_title']) && is_array($_POST['sec_title']) ? $_POST['sec_title'] : array();
		$texts = isset($_POST['sec_text']) && is_array($_POST['sec_text']) ? $_POST['sec_text'] : array();
		$images = isset($_POST['sec_image']) && is_array($_POST['sec_image']) ? $_POST['sec_image'] : array();
		$ids = isset($_POST['sec_id']) && is_array($_POST['sec_id']) ? $_POST['sec_id'] : array();
		$caps = isset($_POST['sec_caption']) && is_array($_POST['sec_caption']) ? $_POST['sec_caption'] : array();
		foreach ($titles as $i => $title) {
			$image = trim((string)($images[$i] ?? ''));
			$uploaded = nl_store_upload_index('sec_file', $i, $folder);
			if ($uploaded !== '') {
				$image = $uploaded;
			}
			$rowId = trim((string)($ids[$i] ?? '')) !== '' ? trim((string)$ids[$i]) : ('s' . ($i + 1));
			$more = array();
			$keep = (isset($_POST['sec_more_keep'][$rowId]) && is_array($_POST['sec_more_keep'][$rowId])) ? $_POST['sec_more_keep'][$rowId] : array();
			$keepList = array();
			foreach ($keep as $path) {
				$keepList[] = trim((string)$path);
			}
			$fileCount = 0;
			if (isset($_FILES['sec_more']['name'][$rowId]) && is_array($_FILES['sec_more']['name'][$rowId])) {
				$fileCount = count($_FILES['sec_more']['name'][$rowId]);
			}
			$slotCount = max(count($keepList), $fileCount);
			for ($fi = 0; $fi < $slotCount; $fi++) {
				$uploadedMore = nl_store_upload_at('sec_more', $rowId, $fi, $folder);
				$path = isset($keepList[$fi]) ? $keepList[$fi] : '';
				$use = ($uploadedMore !== '') ? $uploadedMore : $path;
				if ($use !== '' && $use !== $image && !in_array($use, $more, true)) {
					$more[] = $use;
				}
			}
			$allCaps = (isset($_POST['sec_cap'][$rowId]) && is_array($_POST['sec_cap'][$rowId])) ? array_values($_POST['sec_cap'][$rowId]) : array();
			$allCrops = (isset($_POST['sec_crop'][$rowId]) && is_array($_POST['sec_crop'][$rowId])) ? array_values($_POST['sec_crop'][$rowId]) : array();
			$rels = array($image);
			foreach ($more as $rel) {
				$rels[] = $rel;
			}
			$pics = array();
			$captions = array();
			$picCount = max(count($rels), count($allCaps), count($allCrops));
			for ($pi = 0; $pi < $picCount; $pi++) {
				$rel = trim((string)($rels[$pi] ?? ''));
				$cap = trim((string)($allCaps[$pi] ?? ''));
				if ($rel === '') {
					continue;
				}
				$crop = nl_crop_box(json_decode((string)($allCrops[$pi] ?? ''), true));
				$pic = array('rel' => $rel, 'cap' => $cap);
				if ($crop) {
					$pic['crop'] = $crop;
				}
				$pics[] = $pic;
				$captions[] = $cap;
			}
			$shows = (isset($_POST['sec_show']) && is_array($_POST['sec_show'])) ? $_POST['sec_show'] : array();
			$sections[] = array(
				'id' => $rowId,
				'title' => trim((string)$title),
				'showTitle' => ((string)($shows[$i] ?? '1') === '1') ? 1 : 0,
				'text' => (string)($texts[$i] ?? ''),
				'image' => isset($pics[0]['rel']) ? $pics[0]['rel'] : '',
				'caption' => isset($captions[0]) ? $captions[0] : '',
				'images' => array_map(function ($pic) {
					return $pic['rel'];
				}, array_slice($pics, 1)),
				'captions' => $captions,
				'pics' => $pics,
			);
		}
		if (count($sections) === 0) {
			$sections[] = nl_blank_section();
		}
		$current['sections'] = $sections;
		$first = $sections[0];
		$firstPics = nl_section_pic_rels($first);
		$current['themeTitle'] = (string)($first['title'] ?? '');
		$current['themeText'] = (string)($first['text'] ?? '');
		$current['themeBild1'] = isset($firstPics[0]) ? $firstPics[0] : '';
		$current['themeBild2'] = isset($firstPics[1]) ? $firstPics[1] : '';
		$current['themeCap1'] = (string)($first['caption'] ?? '');
		$current['themeCap2'] = isset($first['captions'][1]) ? (string)$first['captions'][1] : '';
		$gid = trim((string)($_POST['gid'] ?? ''));
		if (!isset($current['texts']) || !is_array($current['texts'])) {
			$current['texts'] = array();
		}
		if ($gid !== '') {
			$letter = nl_letter_row($current, $gid);
			foreach (array('gemeinde', 'anschrift1', 'anschrift2', 'strasse', 'strasse2', 'plz', 'ort', 'email', 'anrede', 'text', 'dank', 'betrag', 'absender', 'bemerkung') as $field) {
				$letter[$field] = trim((string)($_POST[$field] ?? ''));
			}
			$slip = (string)($_POST['slipMode'] ?? '');
			if ($slip === 'serie' || $slip === '') {
				$letter['slipMode'] = '';
			} elseif (in_array($slip, array('betrag', 'beide', 'keine'), true)) {
				$letter['slipMode'] = $slip;
			}
			$letter['picOver'] = array();
			if (isset($_POST['own_on']) && is_array($_POST['own_on'])) {
				foreach ($_POST['own_on'] as $secKey => $flags) {
					if (!is_array($flags)) {
						continue;
					}
					$secKey = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$secKey);
					if ($secKey === '') {
						continue;
					}
					$ownKeeps = (isset($_POST['own_keep'][$secKey]) && is_array($_POST['own_keep'][$secKey])) ? array_values($_POST['own_keep'][$secKey]) : array();
					$ownCaps = (isset($_POST['own_cap'][$secKey]) && is_array($_POST['own_cap'][$secKey])) ? array_values($_POST['own_cap'][$secKey]) : array();
					$ownCrops = (isset($_POST['own_crop'][$secKey]) && is_array($_POST['own_crop'][$secKey])) ? array_values($_POST['own_crop'][$secKey]) : array();
					$ownHides = (isset($_POST['own_hide'][$secKey]) && is_array($_POST['own_hide'][$secKey])) ? array_values($_POST['own_hide'][$secKey]) : array();
					$fileCount = (isset($_FILES['own_file']['name'][$secKey]) && is_array($_FILES['own_file']['name'][$secKey])) ? count($_FILES['own_file']['name'][$secKey]) : 0;
					$slotCount = max(count($flags), count($ownKeeps), count($ownCaps), count($ownCrops), count($ownHides), $fileCount);
					$rows = array();
					$any = false;
					for ($fi = 0; $fi < $slotCount; $fi++) {
						$on = ((string)($flags[$fi] ?? '0') === '1');
						$hide = !$on && ((string)($ownHides[$fi] ?? '0') === '1');
						$uploaded = nl_store_upload_at('own_file', $secKey, $fi, $folder);
						$rel = ($uploaded !== '') ? $uploaded : trim((string)($ownKeeps[$fi] ?? ''));
						$row = array(
							'on' => $on ? 1 : 0,
							'hide' => $hide ? 1 : 0,
							'rel' => $on ? $rel : '',
							'cap' => $on ? trim((string)($ownCaps[$fi] ?? '')) : '',
						);
						$ownCrop = nl_crop_box(json_decode((string)($ownCrops[$fi] ?? ''), true));
						if ($on && $ownCrop) {
							$row['crop'] = $ownCrop;
						}
						$rows[] = $row;
						if ($on || $hide) {
							$any = true;
						}
					}
					if ($any) {
						$letter['picOver'][$secKey] = $rows;
					}
				}
			}
			if (isset($_POST['gde_keep']) || isset($_FILES['gde_file'])) {
			$keeps = (isset($_POST['gde_keep']) && is_array($_POST['gde_keep'])) ? array_values($_POST['gde_keep']) : array();
			$gdeCaps = (isset($_POST['gde_cap']) && is_array($_POST['gde_cap'])) ? array_values($_POST['gde_cap']) : array();
			$fileCount = (isset($_FILES['gde_file']['name']) && is_array($_FILES['gde_file']['name'])) ? count($_FILES['gde_file']['name']) : 0;
			$slotCount = max(count($keeps), count($gdeCaps), $fileCount);
			$list = array();
			$listCaps = array();
			for ($fi = 0; $fi < $slotCount; $fi++) {
				$uploaded = nl_store_upload_index('gde_file', $fi, $folder);
				$path = isset($keeps[$fi]) ? trim((string)$keeps[$fi]) : '';
				$list[] = ($uploaded !== '') ? $uploaded : $path;
				$listCaps[] = trim((string)($gdeCaps[$fi] ?? ''));
			}
			$letter['bild1'] = isset($list[0]) ? $list[0] : '';
			$letter['cap1'] = isset($listCaps[0]) ? $listCaps[0] : '';
			$letter['bild2'] = isset($list[1]) ? $list[1] : '';
			$letter['cap2'] = isset($listCaps[1]) ? $listCaps[1] : '';
			$letter['images'] = array();
			$letter['imageCaps'] = array();
			for ($fi = 2; $fi < count($list); $fi++) {
				if ($list[$fi] === '') {
					continue;
				}
				$letter['images'][] = $list[$fi];
				$letter['imageCaps'][] = $listCaps[$fi];
			}
			}
			$letter['qr'] = nl_qr_rel($letter['betrag'] ?? '');
			if (!isset($current['letters']) || !is_array($current['letters'])) {
				$current['letters'] = array();
			}
			$current['letters'][$gid] = $letter;
			$current['texts'][$gid] = $letter;
		}
		$found = false;
		foreach ($items as $i => $existing) {
			if ((string)$existing['id'] === $id) {
				$items[$i] = $current;
				$found = true;
				break;
			}
		}
		if (!$found) {
			$items[] = $current;
		}
		nl_save_items('newsletter_kampagnen.json', $items);
		if ($gid !== '') {
			$gemeinden = nl_items('newsletter_gemeinden.json');
			foreach ($gemeinden as $i => $g) {
				if ((string)$g['id'] !== $gid) {
					continue;
				}
				$gemeinden[$i]['aktiv'] = !empty($_POST['aktiv']);
				$gemeinden[$i]['ausloesen'] = !empty($_POST['ausloesen_g']);
				$gemeinden[$i]['gdeCode'] = nl_gemeinde_code($gemeinden[$i]['gemeinde'] ?? '');
				$gemeinden[$i]['statSicht'] = ((string)($_POST['statSicht'] ?? 'gde') === 'jg') ? 'jg' : 'gde';
				$gemeinden[$i]['jgCode'] = trim((string)($_POST['jgCode'] ?? ''));
				$allowedShow = array_keys(nl_stat_metrics());
				$pickedShow = array();
				foreach ((array)($_POST['statShow'] ?? array()) as $one) {
					$one = (string)$one;
					if (in_array($one, $allowedShow, true)) {
						$pickedShow[] = $one;
					}
				}
				$gemeinden[$i]['statShow'] = count($pickedShow) > 0 ? implode(',', $pickedShow) : 'none';
			}
			nl_save_items('newsletter_gemeinden.json', $gemeinden);
		}
		gb_redirect(array('msg' => 'Brief gespeichert.', 'campaign' => $id, 'gemeinde' => $gid));
	}
	if ($action === 'fill_stats_text') {
		$id = (string)($_POST['id'] ?? '');
		$items = nl_items('newsletter_kampagnen.json');
		foreach ($items as $i => $camp) {
			if ((string)$camp['id'] !== $id) {
				continue;
			}
			$stats = nl_flight_bundle($camp);
			foreach (nl_items('newsletter_gemeinden.json') as $g) {
				if (empty($g['aktiv'])) {
					continue;
				}
				$stat = nl_stats_for_gemeinde($stats, $g);
				$letter = nl_letter_row($camp, $g['id']);
				$letter['text'] = nl_suggest_gemeinde_text(nl_with_letter($camp, $g), $stat);
				if (!isset($letter['dank'])) {
					$letter['dank'] = '';
				}
				if (!isset($items[$i]['letters']) || !is_array($items[$i]['letters'])) {
					$items[$i]['letters'] = array();
				}
				$items[$i]['letters'][$g['id']] = $letter;
				$items[$i]['texts'][$g['id']] = $letter;
			}
		}
		nl_save_items('newsletter_kampagnen.json', $items);
		gb_redirect(array('msg' => 'Resultattexte aus der Flugstatistik eingesetzt.', 'campaign' => $id, 'gemeinde' => (string)($_POST['gemeinde'] ?? '')));
	}
	if ($action === 'send_gemeinde') {
		if (empty($_POST['confirm'])) {
			gb_redirect(array('err' => 'Versand nicht bestätigt.', 'campaign' => (string)($_POST['id'] ?? '')));
		}
		$camp = nl_find_item(nl_items('newsletter_kampagnen.json'), (string)($_POST['id'] ?? ''));
		if (!$camp) {
			gb_redirect(array('err' => 'Keine Kampagne.'));
		}
		$postedTo = (isset($_POST['send_to']) && is_array($_POST['send_to'])) ? $_POST['send_to'] : array();
		$wanted = array();
		foreach ($postedTo as $oneId) {
			$oneId = trim((string)$oneId);
			if ($oneId !== '') {
				$wanted[$oneId] = true;
			}
		}
		if (count($wanted) === 0) {
			gb_redirect(array('err' => 'Keine Empfänger markiert.', 'campaign' => (string)$camp['id']));
		}
		$stats = nl_flight_bundle($camp);
		$sent = 0;
		$failed = 0;
		$gemeinden = nl_items('newsletter_gemeinden.json');
		foreach ($gemeinden as $i => $g) {
			if (!isset($wanted[(string)($g['id'] ?? '')])) {
				continue;
			}
			if (empty($g['ausloesen']) || empty($g['aktiv'])) {
				continue;
			}
			$g = nl_with_letter($camp, $g);
			$email = trim((string)($g['email'] ?? ''));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			$stat = nl_stats_for_gemeinde($stats, $g);
			$GLOBALS['nl_for_mail'] = true;
			$html = '<div style="font-family:Calibri,Segoe UI,sans-serif">' . nl_render_letter($camp, $g, $stat) . '</div>';
			$GLOBALS['nl_for_mail'] = false;
			$subject = 'Rehkitzrettung – Aktivitäten in ' . ($g['gemeinde'] ?? '');
			$result = nl_send_mail($email, '', $subject, $html);
			nl_log_mail('gemeinde', $email, $subject, $result['ok'], $result['error']);
			if ($result['ok']) {
				$gemeinden[$i]['ausloesen'] = false;
				$gemeinden[$i]['status'] = 'ok: ' . date('d.m.Y H:i:s');
				$sent++;
			} else {
				$gemeinden[$i]['status'] = 'Fehler: ' . $result['error'];
				$failed++;
				if ($sent === 0) {
					nl_save_items('newsletter_gemeinden.json', $gemeinden);
					gb_redirect(array('err' => $result['error'], 'campaign' => $camp['id']));
				}
			}
		}
		if ($sent === 0 && $failed === 0) {
			gb_redirect(array('err' => 'Keine Empfänger markiert.', 'campaign' => (string)$camp['id']));
		}
		nl_save_items('newsletter_gemeinden.json', $gemeinden);
		gb_redirect(array('msg' => $sent . ' Gemeindebriefe gesendet, ' . $failed . ' Fehler.', 'campaign' => $camp['id']));
	}
	if ($action === 'test_gemeinde') {
		$settings = nl_settings();
		$to = trim((string)($settings['fromEmail'] ?? ''));
		$camp = nl_find_item(nl_items('newsletter_kampagnen.json'), (string)($_POST['id'] ?? ''));
		$gid = (string)($_POST['gemeinde'] ?? '');
		if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
			gb_redirect(array('err' => 'In der Mail-Konfiguration ist keine gültige Absender-Adresse hinterlegt.', 'campaign' => (string)($_POST['id'] ?? ''), 'gemeinde' => $gid));
		}
		if (!$camp) {
			gb_redirect(array('err' => 'Keine Kampagne.'));
		}
		$g = null;
		foreach (nl_items('newsletter_gemeinden.json') as $one) {
			if ((string)$one['id'] === $gid) {
				$g = $one;
				break;
			}
		}
		if (!$g) {
			gb_redirect(array('err' => 'Keine Gemeinde gewählt.', 'campaign' => $camp['id']));
		}
		$stat = nl_stats_for_gemeinde(nl_flight_bundle($camp), $g);
		$GLOBALS['nl_for_mail'] = true;
		$html = '<div style="font-family:Calibri,Segoe UI,sans-serif">' . nl_render_letter($camp, $g, $stat) . '</div>';
		$GLOBALS['nl_for_mail'] = false;
		$subject = '[Test] Rehkitzrettung – Aktivitäten in ' . ($g['gemeinde'] ?? '');
		$result = nl_send_mail($to, '', $subject, $html);
		nl_log_mail('test', $to, $subject, $result['ok'], $result['error']);
		$extra = $result['ok']
			? array('msg' => 'Testmail gesendet an ' . $to, 'campaign' => $camp['id'], 'gemeinde' => $gid)
			: array('err' => $result['error'], 'campaign' => $camp['id'], 'gemeinde' => $gid);
		gb_redirect($extra);
	}
}

$msg = trim((string)($_GET['msg'] ?? ''));
$err = trim((string)($_GET['err'] ?? ''));
$isPrint = !empty($_GET['druck']);

headerpage('Gemeindebriefe', false, $folderoffset);
GrantAccess();
if (!MenuEntryAccessGranted('Gemeindebrief')) {
	echo "<main class='rkrk-shell-main'><p>Diese Seite ist im Menü Intern für die freigegebenen Personen.</p></main>";
	footer();
	exit;
}
if (!$isPrint) {
	CreateTopMenu($folderoffset);
}
echo "<link rel='stylesheet' href='rkrk_newsletter.css?v=20260925z21'>\n";
if (empty($_GET['druck'])) {
	echo "<script src='rkrk_newsletter_view.js?v=20260925slip2' defer></script>\n";
}
echo "<main class='rkrk-shell-main nl-wrap" . ($isPrint ? " nl-sheets" : "") . "'>\n";

$kampagnen = nl_items('newsletter_kampagnen.json');
$campId = (string)($_REQUEST['campaign'] ?? '');
$camp = $campId !== '' ? nl_find_item($kampagnen, $campId) : (isset($kampagnen[0]) ? $kampagnen[0] : null);
$gemeinden = nl_items('newsletter_gemeinden.json');
$stats = $camp ? nl_flight_bundle($camp) : array('season' => array());
$oneId = (string)($_GET['gemeinde'] ?? '');
$current = null;
foreach ($gemeinden as $g) {
	if ($oneId !== '' && (string)$g['id'] === $oneId) {
		$current = $g;
		break;
	}
}
if (!$current && isset($gemeinden[0])) {
	$current = $gemeinden[0];
}

if ($isPrint && $camp) {
	echo "<p class='nl-noprint'><button class='nl-btn' type='button' onclick='window.print()'>Drucken</button> ";
	echo "<a class='nl-btn secondary' href='rkrk_gemeindebrief.php?campaign=" . nl_h($camp['id']) . ($oneId !== '' ? '&gemeinde=' . nl_h($oneId) : '') . "'>Zurück</a></p>";
	foreach ($gemeinden as $g) {
		if (empty($g['aktiv'])) {
			continue;
		}
		if ($oneId !== '' && (string)$g['id'] !== $oneId) {
			continue;
		}
		echo nl_render_letter($camp, $g, nl_stats_for_gemeinde($stats, $g));
	}
	echo "</main>\n";
	echo "<script>\n";
	echo "(function(){\n";
	echo "document.body.classList.add('nl-print-body');\n";
	echo "var probe=document.createElement('div');\n";
	echo "probe.style.cssText='position:absolute;left:-9999px;height:297mm;width:0';\n";
	echo "document.body.appendChild(probe);\n";
	echo "var pageH=probe.getBoundingClientRect().height;\n";
	echo "probe.remove();\n";
	echo "if(!(pageH>0)) return;\n";
	echo "document.querySelectorAll('.nl-a4-doc').forEach(function(doc){\n";
	echo "var letter=doc.querySelector('.nl-a4.nl-letter');\n";
	echo "if(!letter) return;\n";
	echo "var slips=doc.querySelectorAll('.nl-a4.nl-slip-sheet');\n";
	echo "var anchor=slips.length?slips[0]:null;\n";
	echo "var kids=Array.prototype.slice.call(letter.children);\n";
	echo "letter.style.height=pageH+'px';letter.style.maxHeight=pageH+'px';letter.style.overflow='auto';\n";
	echo "function sheet(){var p=letter.cloneNode(false);doc.insertBefore(p,anchor);return p;}\n";
	echo "kids.forEach(function(n){n.remove();});\n";
	echo "var page=letter;\n";
	echo "function place(node,page){\n";
	echo "page.appendChild(node);\n";
	echo "if(page.scrollHeight<=pageH+2) return page;\n";
	echo "if(page.children.length>1){node.remove();page=sheet();return place(node,page);}\n";
	echo "if(node.children&&node.children.length>1){var inner=Array.prototype.slice.call(node.children);node.remove();inner.forEach(function(child){page=place(child,page);});}\n";
	echo "return page;\n";
	echo "}\n";
	echo "kids.forEach(function(node){page=place(node,page);});\n";
	echo "doc.querySelectorAll('.nl-a4.nl-letter').forEach(function(p){if(p.scrollHeight>pageH+2){p.style.height='auto';p.style.maxHeight='none';p.style.overflow='visible';}else{p.style.overflow='hidden';}});\n";
	echo "});\n";
	echo "function nlStamp(list){list.forEach(function(el,i){var mark=el.querySelector('.nl-page-no');if(!mark){mark=document.createElement('div');mark.className='nl-page-no';el.appendChild(mark);}mark.textContent=(i+1)+'/'+list.length;});}\n";
	echo "var seen=[];\n";
	echo "document.querySelectorAll('.nl-a4-doc').forEach(function(doc){var pages=[];doc.querySelectorAll('.nl-a4').forEach(function(el){if(el.style.display==='none')return;pages.push(el);seen.push(el);});if(pages.length)nlStamp(pages);});\n";
	echo "var loose=[];\n";
	echo "document.querySelectorAll('.nl-a4').forEach(function(el){if(el.style.display==='none')return;if(seen.indexOf(el)>=0)return;loose.push(el);});\n";
	echo "if(loose.length)nlStamp(loose);\n";
	echo "})();\n";
	echo "</script>\n";
	exit;
}

echo "<h1 class='rkrk-shell-page-title'>Gemeindebriefe</h1>\n";
echo "<nav class='nl-tabs nl-noprint'><a href='rkrk_newsletter.php'>Newsletter</a><a class='on' href='rkrk_gemeindebrief.php'>Gemeindebriefe</a></nav>\n";
if ($msg !== '') {
	echo "<p class='nl-msg'>" . nl_h($msg) . "</p>";
}
if ($err !== '') {
	echo "<p class='nl-msg err'>" . nl_h($err) . "</p>";
}

if (!$camp) {
	echo "<p>Noch keine Kampagne.</p></main>";
	footer();
	exit;
}

$series = $kampagnen;
usort($series, function ($a, $b) {
	return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
});
echo "<section class='nl-card nl-noprint'>";
echo "<details class='nl-fold' id='gb-series' open><summary class='nl-fold-title'>Serien</summary><div class='nl-fold-body'>";
echo "<p>Texte, Betrag und Bilder gehören zur Serie. Die Adressen bleiben für alle Serien gleich.</p>";
echo "<ul class='nl-issue-list'>";
foreach ($series as $one) {
	$sid = (string)($one['id'] ?? '');
	$when = $sid;
	if (preg_match('/^(\d{4})(\d{2})$/', $sid, $ym)) {
		$when = $ym[1] . '-' . $ym[2];
	}
	$currentSeries = ((string)($camp['id'] ?? '') === $sid);
	$seriesLabel = nl_campaign_label($one['title'] ?? '');
	echo "<li" . ($currentSeries ? " class='is-current'" : "") . ">";
	echo "<a class='nl-issue-link' href='rkrk_gemeindebrief.php?campaign=" . nl_h($sid) . "'>";
	echo "<span class='nl-issue-when'>" . nl_h($when) . "</span>";
	echo "<span class='nl-issue-name'>" . nl_h($seriesLabel) . "</span>";
	echo "</a>";
	$delAsk = json_encode('Serie «' . $seriesLabel . '» löschen? Die Gemeinde-Adressen bleiben erhalten.', JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
	echo "<form method='post' class='nl-issue-del' onsubmit='return confirm(" . $delAsk . ")'>";
	echo "<input type='hidden' name='action' value='delete_campaign'>";
	echo "<input type='hidden' name='campaign' value='" . nl_h($sid) . "'>";
	echo "<button type='submit' class='nl-issue-del-btn' title='Serie löschen' aria-label='Serie löschen'>" . nl_ico('trash') . "</button>";
	echo "</form>";
	echo "</li>";
}
echo "</ul>";
echo "</div></details>";
echo "<script>(function(){var d=document.getElementById('gb-series');if(!d)return;try{if(localStorage.getItem('gb-series-open')==='0')d.open=false;}catch(e){}d.addEventListener('toggle',function(){try{localStorage.setItem('gb-series-open',d.open?'1':'0');}catch(e){}});var cur=d.querySelector('.nl-issue-list .is-current');var list=d.querySelector('.nl-issue-list');if(cur&&list){var rel=cur.getBoundingClientRect().top-list.getBoundingClientRect().top;if(rel<0||rel+cur.offsetHeight>list.clientHeight)list.scrollTop+=rel;}})();</script>\n";
echo "<details class='nl-fold' id='gb-new-campaign'>";
echo "<summary>Neue Kampagne</summary>";
echo "<div class='nl-fold-body'>";
echo "<form class='nl-form' method='post'><input type='hidden' name='action' value='new_campaign'>";
echo "<label>Neue Kampagne aus</label><select name='base'>";
foreach ($kampagnen as $one) {
	echo "<option value='" . nl_h($one['id']) . "'" . ((string)$one['id'] === (string)$camp['id'] ? " selected" : "") . ">" . nl_h(nl_campaign_label($one['title'] ?? '')) . "</option>";
}
echo "</select>";
echo "<label>Kennung JJJJMM</label><input type='text' name='newId' value='' placeholder='202703'>";
echo "<div class='nl-actions'><button type='submit' class='nl-btn secondary'>Kampagne anlegen</button></div></form>";
echo "</div></details>";
echo "<p><a class='nl-btn secondary' href='rkrk_gemeindebrief.php?pdf=1&campaign=" . nl_h($camp['id']) . "'>" . nl_ico('pdf') . " PDF alle</a> ";
echo "<a class='nl-btn secondary' href='rkrk_gemeindebrief.php?druck=1&campaign=" . nl_h($camp['id']) . "' target='_blank'>Alle drucken</a></p>";
echo "</section>";

echo "<section class='nl-card nl-grid-card nl-noprint'>";
echo "<details class='nl-fold' id='gb-addresses'>";
echo "<summary>Gemeinde-Adressen (" . count($gemeinden) . ")</summary>";
echo "<div class='nl-fold-body'>";
echo "<form class='nl-form' method='post' enctype='multipart/form-data'><input type='hidden' name='action' value='import_gemeinde'><input type='hidden' name='campaign' value='" . nl_h($camp['id']) . "'>";
echo "<label>Excel der Gemeinden importieren</label><input type='file' name='xlsx' accept='.xlsx,.xlsm'> <button type='submit'>Importieren</button></form>";
echo "<p>Wie in Excel: sortieren, filtern, direkt in den Zellen ändern. Speichern nach <code>rkrk/data/gemeinde/</code>.</p>";
$gCols = nl_gemeinde_columns();
$gHidden = nl_ui_hidden_columns('gemeinde_ui.json', $gCols, array('anschrift2', 'strasse2', 'absender', 'bemerkung', 'gdeCode'));
nl_echo_column_panel('gemeinde_ui.json', $gCols, $gHidden, 'save_gemeinde_ui', array('campaign' => $camp['id']));
$gWidths = nl_ui_widths('gemeinde_ui.json', $gCols);
nl_echo_excel_grid('rkrk_gemeindebrief.php', 'save_gemeinde_grid', $gemeinden, $gCols, $gHidden, array(
	'hiddenFields' => array('campaign' => $camp['id']),
	'widths' => $gWidths,
	'widthAction' => 'save_gemeinde_widths',
	'favoritAction' => 'save_gemeinde_favorit',
));
echo "</div></details></section>";
echo "<script>(function(){var d=document.getElementById('gb-addresses');if(!d)return;try{if(localStorage.getItem('gb-addr-open')==='1')d.open=true;}catch(e){}d.addEventListener('toggle',function(){try{localStorage.setItem('gb-addr-open',d.open?'1':'0');}catch(e){}});})();</script>\n";
echo "<script>(function(){var d=document.getElementById('gb-from-list');if(!d)return;try{if(localStorage.getItem('gb-from-list-open')==='1')d.open=true;}catch(e){}d.addEventListener('toggle',function(){try{localStorage.setItem('gb-from-list-open',d.open?'1':'0');}catch(e){}});})();</script>\n";

$base = $current;
$stat = $base ? nl_stats_for_gemeinde($stats, $base) : array('flaeche' => 0, 'einsaetze' => 0, 'felder' => 0, 'fund' => 0, 'label' => '');
$letter = $base ? nl_letter_row($camp, $base['id']) : array();
if ($base) {
	$current = nl_with_letter($camp, $base);
}
$texts = array(
	'text' => (string)($letter['text'] ?? ''),
	'dank' => (string)($letter['dank'] ?? ''),
);
if ($current && trim((string)($texts['text'] ?? '')) === '') {
	$texts['text'] = nl_suggest_gemeinde_text($current, $stat);
}
$suggest = $current ? nl_suggest_gemeinde_text($current, $stat) : '';
$when = nl_campaign_letter_when($camp);

echo "<div class='nl-split'>";
echo "<div class='nl-edit'>";
echo "<form method='post' class='nl-form' id='gb-form' enctype='multipart/form-data'>";
echo "<input type='hidden' name='action' value='save_campaign'>";
echo "<input type='hidden' name='id' value='" . nl_h($camp['id']) . "'>";
echo "<input type='hidden' name='gid' value='" . nl_h($current['id'] ?? '') . "'>";
echo "<section class='nl-card' id='gb-brief'>";
echo "<h2>Brief</h2>";
echo "<label>Gemeinde</label><div class='nl-gde-nav'>";
echo "<button type='button' class='nl-icon-btn' id='gb-gde-prev' title='Vorherige Gemeinde' aria-label='Vorherige Gemeinde'>‹</button>";
echo "<select id='gb-gde'>";
foreach ($gemeinden as $g) {
	$sel = ($base && (string)$g['id'] === (string)$base['id']) ? ' selected' : '';
	$art = ((string)($g['statSicht'] ?? 'gde') === 'jg') ? 'JG' : 'Gde';
	echo "<option value='" . nl_h($g['id']) . "'" . $sel . ">" . nl_h($g['gemeinde'] . ' (' . $art . ')') . "</option>";
}
echo "</select>";
echo "<button type='button' class='nl-icon-btn' id='gb-gde-next' title='Nächste Gemeinde' aria-label='Nächste Gemeinde'>›</button>";
echo "</div>";
echo "<label>Titel der Kampagne</label><input type='text' name='title' value='" . nl_h(nl_campaign_label($camp['title'] ?? '')) . "'>";
echo nl_season_year_select($camp, true);
echo "<p class='nl-note'>Die Saison läuft vom 1. April bis zum 31. Juli des gewählten Jahres.</p>";
echo "<article class='nl-sec is-collapsed' id='gb-kopf'>";
echo "<div class='nl-sec-head'><button type='button' class='nl-sec-toggle' aria-expanded='false'>Briefkopf</button></div>";
echo "<div class='nl-sec-body'>";
echo "<label>Ort vor dem Datum</label><input type='text' name='ortDatum' id='gb-ort' value='" . nl_h($camp['ortDatum'] ?? 'Knonau') . "'>";
$katNow = (string)($camp['rtKat'] ?? 'RK');
if ($katNow === '') {
	$katNow = 'RK';
}
echo "<label>Kategorie</label><select name='rtKat' id='gb-kat'>";
foreach (nl_rt_categories() as $code => $label) {
	echo "<option value='" . nl_h($code) . "'" . ((string)$code === $katNow ? ' selected' : '') . ">" . nl_h($label) . "</option>";
}
echo "</select>";
$slipNow = (string)($camp['slipMode'] ?? 'betrag');
if (!in_array($slipNow, array('betrag', 'beide', 'keine'), true)) {
	$slipNow = 'betrag';
}
echo "<details class='nl-fold nl-fold-sub' id='gb-from-list'>";
echo "<summary>Empfänger dieses Briefs</summary>";
echo "<div class='nl-fold-body'>";
echo "<p class='nl-note'>Wird mit diesem Brief gespeichert. Spätere Änderungen in der Adressliste gelten für neue Briefe, nicht für diesen.</p>";
echo "<label>Anschrift 1</label><input class='gb-own' type='text' name='anschrift1' id='gb-anschrift1' value='" . nl_h($current['anschrift1'] ?? '') . "'>";
echo "<label>Anschrift 2</label><input class='gb-own' type='text' name='anschrift2' id='gb-anschrift2' value='" . nl_h($current['anschrift2'] ?? '') . "'>";
echo "<label>Strasse</label><input class='gb-own' type='text' name='strasse' id='gb-strasse' value='" . nl_h($current['strasse'] ?? '') . "'>";
echo "<label>Strasse 2</label><input class='gb-own' type='text' name='strasse2' id='gb-strasse2' value='" . nl_h($current['strasse2'] ?? '') . "'>";
echo "<label>PLZ</label><input class='gb-own' type='text' name='plz' id='gb-plz' value='" . nl_h($current['plz'] ?? '') . "'>";
echo "<label>Ort</label><input class='gb-own' type='text' name='ort' id='gb-ort-addr' value='" . nl_h($current['ort'] ?? '') . "'>";
echo "<label>Anrede</label><input class='gb-own' type='text' name='anrede' id='gb-anrede' value='" . nl_h($current['anrede'] ?? '') . "'>";
echo "<label>Gemeinde im Titel</label><input class='gb-own' type='text' name='gemeinde' id='gb-name' value='" . nl_h($current['gemeinde'] ?? '') . "'>";
echo "</div></details>";
echo "</div></article>";
$sichtNow = ((string)($current['statSicht'] ?? 'gde') === 'jg') ? 'jg' : 'gde';
echo "<label>Sicht der Flugstatistik</label><select class='gb-own' name='statSicht'>";
echo "<option value='gde'" . ($sichtNow === 'gde' ? ' selected' : '') . ">Gemeinde</option>";
echo "<option value='jg'" . ($sichtNow === 'jg' ? ' selected' : '') . ">Jagdgebiet</option>";
echo "</select>";
echo "<label>Jagdgebiet</label><select class='gb-own' name='jgCode'>";
$jgNow = trim((string)($current['jgCode'] ?? ''));
if ($jgNow === '') {
	$jgNow = nl_jg_for_place($current['gemeinde'] ?? '');
}
foreach (nl_jg_choices() as $code => $label) {
	echo "<option value='" . nl_h($code) . "'" . ((string)$code === $jgNow ? ' selected' : '') . ">" . nl_h($label) . "</option>";
}
echo "</select>";
$showNow = nl_stat_show_list($current ?: array());
echo "<label>Zahlen und Grafik</label><div class='nl-checks'>";
foreach (nl_stat_metrics() as $key => $label) {
	echo "<label><input class='gb-own' type='checkbox' name='statShow[]' value='" . nl_h($key) . "'" . (in_array($key, $showNow, true) ? ' checked' : '') . "> " . nl_h($label) . "</label>";
}
echo "</div>";
$seasonTxt = trim((string)($stat['from'] ?? '') . '–' . (string)($stat['to'] ?? ''));
$sichtTxt = $sichtNow === 'jg' ? 'Jagdgebiet' : 'Gemeinde';
echo "<p>Übernommen aus der Flugstatistik, " . nl_h(trim($seasonTxt)) . ", " . nl_h($sichtTxt) . " " . nl_h($stat['label']) . ": ";
$bits = array();
foreach (nl_stat_show_list($current ?: array()) as $key) {
	$unit = ($key === 'flaeche') ? ' ha' : '';
	$bits[] = (nl_stat_metrics()[$key] ?? $key) . ' ' . (int)($stat[$key] ?? 0) . $unit;
}
echo nl_h(implode(', ', $bits)) . ".</p>";
echo "<div class='nl-sec-list'>";
echo "<article class='nl-sec is-collapsed' id='gb-intro'>";
echo "<div class='nl-sec-head'>";
echo "<button type='button' class='nl-sec-toggle' aria-expanded='false'>Einleitungsektion</button></div>";
echo "<div class='nl-sec-body'>";
echo "<p class='nl-note'>Gilt für alle Gemeinden und steht nach der Anrede, vor der Einsatzentwicklung.</p>";
echo "<label>Text</label><textarea name='sharedHtml' id='gb-shared'>" . nl_h($camp['sharedHtml']) . "</textarea>";
$introPics = (isset($camp['introPics']) && is_array($camp['introPics'])) ? $camp['introPics'] : array();
$introOverAll = (isset($letter['picOver']['intro']) && is_array($letter['picOver']['intro'])) ? $letter['picOver']['intro'] : array();
echo "<div class='nl-pic-list'>";
foreach ($introPics as $picIndex => $picSlot) {
	$over = (isset($introOverAll[$picIndex]) && is_array($introOverAll[$picIndex])) ? $introOverAll[$picIndex] : array();
	gb_echo_pic_slot($picSlot['rel'] ?? '', $picIndex === 0, 'intro', $picSlot['cap'] ?? '', $over, isset($picSlot['crop']) ? $picSlot['crop'] : null);
}
echo "</div>";
echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-more-show'>Weiteres Bild</button></div>";
echo "</div></article></div>";
echo "<label>Resultattext</label><textarea class='nl-specific-field gb-own' name='text' id='gb-text'>" . nl_h($texts['text'] ?? '') . "</textarea>";
echo "<p class='nl-note'>Steht unter der Resultatgrafik, vor den Sektionen. Gilt nur für diese Gemeinde.</p>";
echo "<p><button type='button' class='nl-btn secondary' id='gb-fill-one'>Satz aus Statistik in den Resultattext</button></p>";
echo "<h3>Sektionen</h3>";
echo "<p class='nl-note'>Stehen nach dem Resultattext und gelten für alle Gemeinden. Die Bilder gehören in die Sektion: laden, ändern, entfernen und ziehen.</p>";
echo "<div class='nl-sec-list' id='gb-sections'>";
foreach (gb_seed_sections($camp, $letter) as $si => $section) {
	$secId = trim((string)($section['id'] ?? ''));
	if ($secId === '') {
		$secId = 's' . ($si + 1);
	}
	$secLabel = trim((string)($section['title'] ?? ''));
	if ($secLabel === '') {
		$secLabel = 'Sektion';
	}
	echo "<article class='nl-sec is-collapsed'>";
	echo "<div class='nl-sec-head'><span class='nl-sec-handle' draggable='true' title='Verschieben'>⋮⋮</span>";
	echo "<button type='button' class='nl-sec-toggle' aria-expanded='false'>" . nl_h($secLabel) . "</button>";
	echo "<button type='button' class='nl-sec-del nl-icon-btn' title='Sektion löschen' aria-label='Sektion löschen'>" . nl_ico('trash') . "</button></div>";
	echo "<div class='nl-sec-body'>";
	echo "<input type='hidden' name='sec_id[]' value='" . nl_h($secId) . "'>";
	$showTitle = !array_key_exists('showTitle', $section) || !empty($section['showTitle']);
	echo "<label>Titel</label><input type='text' name='sec_title[]' value='" . nl_h($section['title'] ?? '') . "'>";
	echo "<label class='nl-check'><input type='checkbox' class='nl-show-title-box'" . ($showTitle ? " checked" : "") . "> Titel anzeigen</label>";
	echo "<input type='hidden' class='nl-show-title' name='sec_show[]' value='" . ($showTitle ? "1" : "0") . "'>";
	echo "<p class='nl-note'>Ohne Häkchen bleibt der Titel nur in der Bearbeitung sichtbar.</p>";
	echo "<label>Text</label><textarea name='sec_text[]'>" . nl_h($section['text'] ?? '') . "</textarea>";
	$ordered = nl_section_pic_slots($section);
	$leadRel = count($ordered) ? $ordered[0]['rel'] : '';
	$overAll = (isset($letter['picOver'][$secId]) && is_array($letter['picOver'][$secId])) ? $letter['picOver'][$secId] : array();
	echo "<input type='hidden' name='sec_image[]' value='" . nl_h($leadRel) . "'>";
	echo "<div class='nl-pic-list'>";
	foreach ($ordered as $picIndex => $picSlot) {
		$over = (isset($overAll[$picIndex]) && is_array($overAll[$picIndex])) ? $overAll[$picIndex] : array();
		gb_echo_pic_slot($picSlot['rel'], $picIndex === 0, $secId, $picSlot['cap'], $over, isset($picSlot['crop']) ? $picSlot['crop'] : null);
	}
	echo "</div>";
	if (count($ordered) === 0) {
		echo "<input class='nl-pic-spare-file' type='file' name='sec_file[]' hidden>";
	}
	echo "<div class='nl-file-row'><button type='button' class='nl-btn secondary nl-more-show'>Weiteres Bild</button></div>";
	echo "</div></article>";
}
echo "</div>";
echo "<p><button type='button' class='nl-btn secondary' id='gb-sec-add'>Sektion hinzufügen</button></p>";
echo "<label id='gb-dank-shared-label'>Dank der Serie</label><textarea name='dankShared' id='gb-dank-shared'>" . nl_h($camp['dankShared'] ?? '') . "</textarea>";
echo "<label>Dank nur diese Gemeinde</label><textarea class='gb-own' name='dank' id='gb-dank'>" . nl_h($texts['dank'] ?? '') . "</textarea>";
echo "<p class='nl-note'>Steht nach den Sektionen. Ist der Dank dieser Gemeinde ausgefüllt, gilt nur er. Der Dank der Serie bleibt gespeichert, wird aber nicht gedruckt.</p>";
echo "<label>E-Mail</label><input class='gb-own' type='email' name='email' value='" . nl_h($current['email'] ?? '') . "'>";
echo "<label>Betrag CHF</label><input class='gb-own' type='text' name='betrag' id='gb-betrag' value='" . nl_h($current['betrag'] ?? '') . "'>";
echo "<p class='nl-note'>Nur für den Einzahlungsschein. Im Text mit [Betrag] und [Gemeinde] einsetzen.</p>";
echo "<label>Einzahlungsschein der Serie</label><select name='seriesSlip' id='gb-slip-series'>";
echo "<option value='betrag'" . ($slipNow === 'betrag' ? ' selected' : '') . ">Mit Betrag</option>";
echo "<option value='beide'" . ($slipNow === 'beide' ? ' selected' : '') . ">Mit und ohne Betrag</option>";
echo "<option value='keine'" . ($slipNow === 'keine' ? ' selected' : '') . ">Keinen drucken</option>";
echo "</select>";
echo "<p class='nl-note'>Gilt für jeden Brief dieser Serie, der auf «Wie die Serie» steht. «Mit und ohne Betrag» setzt beide Scheine auf dasselbe A4, den mit Betrag oben, den ohne Betrag darunter.</p>";
$letterSlip = (string)($letter['slipMode'] ?? '');
if (!in_array($letterSlip, array('betrag', 'beide', 'keine'), true)) {
	$letterSlip = 'serie';
}
echo "<label>Einzahlungsschein nur diese Gemeinde</label><select class='gb-own' name='slipMode' id='gb-slip'>";
echo "<option value='serie'" . ($letterSlip === 'serie' ? ' selected' : '') . ">Wie die Serie</option>";
echo "<option value='betrag'" . ($letterSlip === 'betrag' ? ' selected' : '') . ">Mit Betrag</option>";
echo "<option value='beide'" . ($letterSlip === 'beide' ? ' selected' : '') . ">Mit und ohne Betrag</option>";
echo "<option value='keine'" . ($letterSlip === 'keine' ? ' selected' : '') . ">Keinen drucken</option>";
echo "</select>";
echo "<p class='nl-note' id='gb-slip-live'></p>";
echo "<label>Absender</label><input class='gb-own' type='text' name='absender' id='gb-absender' value='" . nl_h($current['absender'] ?? '') . "'>";
echo "<label>Bemerkung (nur intern)</label><textarea class='gb-own' name='bemerkung' id='gb-note'>" . nl_h($current['bemerkung'] ?? '') . "</textarea>";
echo "<label><input class='gb-own' type='checkbox' name='aktiv' value='1'" . (!empty($current['aktiv']) ? ' checked' : '') . "> Im Brief</label>";
echo "<label><input class='gb-own' type='checkbox' name='ausloesen_g' value='1'" . (!empty($current['ausloesen']) ? ' checked' : '') . "> Per Mail auslösen</label>";
$pdfHref = 'rkrk_gemeindebrief.php?pdf=1&campaign=' . rawurlencode((string)$camp['id']) . '&gemeinde=' . rawurlencode((string)($current['id'] ?? '')) . '&rtKat=' . rawurlencode($katNow) . '&slip=' . rawurlencode($slipNow);
$druckHref = 'rkrk_gemeindebrief.php?druck=1&campaign=' . rawurlencode((string)$camp['id']) . '&gemeinde=' . rawurlencode((string)($current['id'] ?? ''));
echo "<div class='nl-actions nl-brief-actions'><button type='submit' class='nl-btn'>" . nl_ico('save') . " Speichern</button>";
echo "<a class='nl-btn' id='gb-pdf' href='" . nl_h($pdfHref) . "'>" . nl_ico('pdf') . " PDF</a>";
echo "<a class='nl-btn secondary' href='" . nl_h($druckHref) . "' target='_blank'>" . nl_ico('print') . " Druckansicht</a></div>";
echo "</section></form>";
echo "<form method='post'><input type='hidden' name='action' value='fill_stats_text'><input type='hidden' name='id' value='" . nl_h($camp['id']) . "'><input type='hidden' name='gemeinde' value='" . nl_h($current['id'] ?? '') . "'>";
echo "<button class='nl-btn secondary' type='submit'>Alle Resultattexte aus der Statistik ersetzen</button></form>";
echo "</div>";

echo "<aside class='nl-view' id='gb-view' data-when='" . nl_h($when) . "'>";
echo "<h2>Ansicht</h2>";
if ($current) {
	$previewCamp = $camp;
	$previewCamp['texts'][$current['id']] = $texts;
	echo nl_render_letter($previewCamp, $current, $stat, true);
}
echo "</aside></div>";

echo "<script type='application/json' id='gb-suggest'>" . json_encode($suggest, JSON_UNESCAPED_UNICODE) . "</script>";
echo "<script src='rkrk_gemeindebrief_pics.js?v=20260925gbown'></script>\n";
echo "<script src='rkrk_newsletter_crop.js?v=20260925open'></script>\n";
echo "<script>
(function(){
	function esc(s){return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\\n/g,'<br>');}
	function asHtml(s){s = String(s == null ? '' : s); if (s.indexOf('<') < 0) return esc(s); s = s.split('<' + '/script>').join('').split('<' + '/style>').join(''); s = s.replace(/<script\\b[^>]*>/gi,'').replace(/<style\\b[^>]*>/gi,'').replace(/\\son\\w+=/gi,' data-x=').replace(/javascript:/gi,''); return s;}
	function fillTokens(html){var betrag=esc(val('gb-betrag'));var name=esc(val('gb-name'));return String(html).replace(/\\[Betrag\\]/gi,betrag).replace(/\\[Gemeinde\\]/gi,name);}
	function val(id){var el=document.getElementById(id); return el ? el.value : '';}
	function paint(){
		var when = document.getElementById('gb-view').getAttribute('data-when') || '';
		var date = document.getElementById('nl-pv-date');
		if (date) date.textContent = (val('gb-ort') || 'Knonau') + ', ' + when;
		var addr = [val('gb-anschrift1'), val('gb-anschrift2'), val('gb-strasse'), val('gb-strasse2'), (val('gb-plz') + ' ' + val('gb-ort-addr')).trim()];
		var address = document.getElementById('nl-pv-address');
		if (address) address.innerHTML = addr.filter(function(p){return String(p).trim() !== '';}).map(esc).join('<br>');
		var name = val('gb-name');
		var title = document.getElementById('nl-pv-title');
		if (title) title.textContent = 'Aktivitäten der Rehkitzrettung mit Drohnen in ' + name;
		var dev = document.getElementById('nl-pv-dev');
		if (dev) dev.textContent = 'Einsatzentwicklung in ' + name;
		var an = document.getElementById('nl-pv-anrede');
		if (an) an.textContent = val('gb-anrede');
		var shared = document.getElementById('nl-pv-shared');
		if (shared) shared.innerHTML = fillTokens(asHtml(val('gb-shared'))) + (window.gbIntroHtml ? window.gbIntroHtml() : '');
		var text = document.getElementById('nl-pv-text');
		if (text) text.innerHTML = fillTokens(asHtml(val('gb-text')));
		var dank = document.getElementById('nl-pv-dank');
		var ownDank = val('gb-dank').trim();
		var seriesDank = val('gb-dank-shared');
		var sharedBox = document.getElementById('gb-dank-shared');
		var sharedLab = document.getElementById('gb-dank-shared-label');
		if (sharedBox) sharedBox.classList.toggle('is-muted', ownDank !== '');
		if (sharedLab) sharedLab.classList.toggle('is-muted', ownDank !== '');
		if (dank) {
			var d = ownDank !== '' ? ownDank : seriesDank;
			dank.innerHTML = d ? fillTokens((d.indexOf('<') >= 0) ? asHtml(d) : ('<strong>' + esc(d) + '</strong>')) : '';
		}
		var betrag = val('gb-betrag');
		document.querySelectorAll('.nl-slip[data-slip=betrag] .nl-slip-amt').forEach(function(el){ el.textContent = betrag; });
		document.querySelectorAll('.nl-slip[data-slip=leer] .nl-slip-amt').forEach(function(el){ el.textContent = ''; });
		var ownSlip = val('gb-slip');
		var mode = (ownSlip === 'serie' || ownSlip === '') ? (val('gb-slip-series') || 'betrag') : ownSlip;
		document.querySelectorAll('.nl-slip').forEach(function(el){
			var kind = el.getAttribute('data-slip');
			var show = mode === 'beide' || (mode === 'betrag' && kind === 'betrag');
			el.style.display = show ? '' : 'none';
		});
		var slipSheet = document.querySelector('.nl-slip-sheet');
		if (slipSheet) {
			slipSheet.style.display = (mode === 'keine') ? 'none' : '';
			slipSheet.classList.toggle('is-duo', mode === 'beide');
		}
		var slipNames = {betrag:'Mit Betrag', beide:'Mit und ohne Betrag', keine:'Keinen drucken'};
		var live = document.getElementById('gb-slip-live');
		if (live) {
			if (ownSlip === 'serie' || ownSlip === '') {
				live.textContent = 'Dieser Brief folgt der Serie: ' + (slipNames[mode] || mode) + '.';
			} else {
				live.textContent = 'Dieser Brief hat eine eigene Einstellung. Die Serie gilt erst bei «Wie die Serie».';
			}
		}
		var pdf = document.getElementById('gb-pdf');
		if (pdf) {
			var url = new URL(pdf.getAttribute('href'), window.location.href);
			url.searchParams.set('slip', mode || 'betrag');
			var kat = document.getElementById('gb-kat');
			if (kat) url.searchParams.set('rtKat', kat.value);
			pdf.setAttribute('href', url.pathname + url.search);
		}
		var signName = document.getElementById('nl-pv-sign-name');
		if (signName) signName.textContent = val('gb-absender');
		var note = document.getElementById('nl-pv-note');
		if (note) note.textContent = val('gb-note') ? ('Hinweis intern: ' + val('gb-note')) : '';
		if (window.gbPaintTheme) window.gbPaintTheme();
	}
	['gb-ort','gb-anschrift1','gb-anschrift2','gb-strasse','gb-strasse2','gb-plz','gb-ort-addr','gb-anrede','gb-name','gb-shared','gb-text','gb-dank','gb-dank-shared','gb-betrag','gb-absender','gb-note','gb-slip','gb-slip-series'].forEach(function(id){
		var el = document.getElementById(id);
		if (el) el.addEventListener('input', paint);
		if (el && el.tagName === 'SELECT') el.addEventListener('change', paint);
	});
	function revealSlip(){
		var sheet = document.querySelector('.nl-slip-sheet');
		if (!sheet || sheet.style.display === 'none') return;
		if (window.nlShowInView) window.nlShowInView(sheet);
	}
	['gb-slip','gb-slip-series'].forEach(function(id){
		var el = document.getElementById(id);
		if (el) el.addEventListener('change', revealSlip);
	});
	window.nlRepaint = paint;
	paint();
	var intro = document.getElementById('gb-intro');
	if (intro) {
		var introTog = intro.querySelector('.nl-sec-toggle');
		if (introTog) introTog.addEventListener('click', function(){
			var closed = intro.classList.toggle('is-collapsed');
			introTog.setAttribute('aria-expanded', closed ? 'false' : 'true');
		});
	}
	var kopf = document.getElementById('gb-kopf');
	if (kopf) {
		var kopfTog = kopf.querySelector('.nl-sec-toggle');
		if (kopfTog) kopfTog.addEventListener('click', function(){
			var closed = kopf.classList.toggle('is-collapsed');
			kopfTog.setAttribute('aria-expanded', closed ? 'false' : 'true');
		});
	}
	var fill = document.getElementById('gb-fill-one');
	if (fill) fill.addEventListener('click', function(){
		var raw = document.getElementById('gb-suggest');
		var text = document.getElementById('gb-text');
		if (!raw || !text) return;
		try { text.value = JSON.parse(raw.textContent || '\"\"'); } catch (e) { text.value = raw.textContent || ''; }
		paint();
	});
})();
</script>";

echo "<section class='nl-card nl-noprint'>";
nl_echo_review_fold(nl_review_publish(nl_gemeinde_review_bundles($camp, $gemeinden)), 'In dieser Serie gibt es keine aktiven Briefe.');
echo "</section>";
echo "<section class='nl-card'><details class='nl-fold' id='gb-versand'><summary class='nl-fold-title'>Versand</summary><div class='nl-fold-body'>";
$gbMarked = 0;
$gbRows = '';
foreach ($gemeinden as $g) {
	if (!is_array($g)) {
		continue;
	}
	$gbEmail = trim((string)($g['email'] ?? ''));
	$gbCan = !empty($g['ausloesen']) && !empty($g['aktiv']) && filter_var($gbEmail, FILTER_VALIDATE_EMAIL);
	if ($gbCan) {
		$gbMarked++;
	}
	$gbRows .= "<tr><td>";
	if ($gbCan) {
		$gbRows .= "<input type='checkbox' class='nl-send-to' name='send_to[]' value='" . nl_h((string)($g['id'] ?? '')) . "' checked>";
	} else {
		$gbRows .= (!empty($g['ausloesen']) ? 'ja' : '');
	}
	$gbRows .= "</td>";
	$gbRows .= "<td><a href='rkrk_gemeindebrief.php?campaign=" . nl_h($camp['id']) . "&gemeinde=" . nl_h($g['id']) . "'>" . nl_h($g['gemeinde']) . "</a></td>";
	$gbRows .= "<td>" . nl_h($g['email'] ?? '') . "</td><td>" . nl_h($g['status'] ?? '') . "</td></tr>";
}
echo "<form method='post' class='nl-form' id='gb-send-form'>";
echo "<input type='hidden' name='action' value='send_gemeinde'><input type='hidden' name='id' value='" . nl_h($camp['id']) . "'>";
echo "<p class='nl-note'>Es gehen Mails an Gemeinden mit gesetztem Häkchen «Mail» in der Adresstabelle (zuerst speichern).</p>";
echo "<table class='nl-table' id='gb-send-table'><tr><th>Mail <input type='checkbox' class='nl-send-all' title='Alle markieren'" . ($gbMarked > 0 ? ' checked' : '') . "></th><th>Gemeinde</th><th>E-Mail</th><th>Status</th></tr>";
echo $gbRows;
echo "</table>";
echo "<label><input type='checkbox' name='confirm' value='1'> Mailversand an die markierten Gemeinden bestätigen</label>";
echo "<div class='nl-actions'><button type='submit' class='nl-send-btn'" . ($gbMarked > 0 ? '' : ' disabled') . ">Gemeindebriefe mailen</button>";
echo "<span class='nl-send-count' id='gb-send-count' aria-live='polite'>" . (int)$gbMarked . " Empfänger markiert</span></div></form>";
echo "<script>(function(){var form=document.getElementById(\"gb-send-form\");if(!form)return;var boxes=form.querySelectorAll(\"input.nl-send-to\");var all=form.querySelector(\"input.nl-send-all\");var countEl=document.getElementById(\"gb-send-count\");var btn=form.querySelector(\"button.nl-send-btn\");function sync(){var n=0,vis=0,i;for(i=0;i<boxes.length;i++){if(boxes[i].disabled)continue;vis++;if(boxes[i].checked)n++;}if(countEl)countEl.textContent=n+\" Empfänger markiert\";if(btn)btn.disabled=(n===0);if(all){all.checked=vis>0&&n===vis;all.indeterminate=n>0&&n<vis;all.disabled=(vis===0);}}if(all)all.addEventListener(\"change\",function(){var on=all.checked,i;for(i=0;i<boxes.length;i++){if(boxes[i].disabled)continue;boxes[i].checked=on;}sync();});form.addEventListener(\"change\",function(ev){if(ev.target===all)return;if(ev.target&&ev.target.classList&&ev.target.classList.contains(\"nl-send-to\"))sync();});sync();})();</script>\n";
$testTo = trim((string)(nl_settings()['fromEmail'] ?? ''));
echo "<form method='post' class='nl-form'>";
echo "<input type='hidden' name='action' value='test_gemeinde'><input type='hidden' name='id' value='" . nl_h($camp['id']) . "'>";
echo "<input type='hidden' name='gemeinde' value='" . nl_h($current['id'] ?? '') . "'>";
echo "<p class='nl-note'>Testmail des gewählten Briefs an den Absender der Mail-Konfiguration: " . nl_h($testTo) . "</p>";
echo "<div class='nl-actions'><button type='submit' class='nl-btn secondary'>Testmail senden</button></div></form>";
echo "</div></details></section>";

echo '<script>(function(){'
	. 'var sel=document.getElementById("gb-gde");if(!sel)return;'
	. 'var prev=document.getElementById("gb-gde-prev");'
	. 'var next=document.getElementById("gb-gde-next");'
	. 'var base="rkrk_gemeindebrief.php?campaign=' . rawurlencode((string)$camp['id']) . '&gemeinde=";'
	. 'function save(){var state={scroll:window.pageYOffset||document.documentElement.scrollTop||0};'
	. 'var pan=document.querySelector("#gb-view .nl-view-pan");'
	. 'if(pan&&pan.style.transform){var m=pan.style.transform.match(/translate\\(([\\-\\d.]+)px,\\s*([\\-\\d.]+)px\\)\\s*scale\\(([\\-\\d.]+)\\)/);'
	. 'if(m){state.x=parseFloat(m[1]);state.y=parseFloat(m[2]);state.scale=parseFloat(m[3]);}}'
	. 'try{sessionStorage.setItem("gb-keep-view",JSON.stringify(state));}catch(e){}}'
	. 'function go(id){if(!id)return;save();if(history.scrollRestoration)history.scrollRestoration="manual";location.href=base+encodeURIComponent(id);}'
	. 'sel.addEventListener("change",function(){go(sel.value);});'
	. 'function step(dir){var i=sel.selectedIndex+dir;if(i<0||i>=sel.options.length)return;go(sel.options[i].value);}'
	. 'if(prev)prev.addEventListener("click",function(){step(-1);});'
	. 'if(next)next.addEventListener("click",function(){step(1);});'
	. 'if(prev)prev.disabled=sel.selectedIndex<=0;'
	. 'if(next)next.disabled=sel.selectedIndex<0||sel.selectedIndex>=sel.options.length-1;'
	. 'var raw="";try{raw=sessionStorage.getItem("gb-keep-view")||"";sessionStorage.removeItem("gb-keep-view");}catch(e){}'
	. 'if(!raw)return;var st=null;try{st=JSON.parse(raw);}catch(e){st=null;}if(!st)return;'
	. 'if(typeof st.scale==="number")window.nlAnsichtKeep=st;'
	. 'if(typeof st.scroll==="number"){if(history.scrollRestoration)history.scrollRestoration="manual";var y=st.scroll;var put=function(){window.scrollTo(0,y);};put();window.addEventListener("load",put);}'
	. '})();</script>' . "\n";
echo "</main>\n";
footer();
