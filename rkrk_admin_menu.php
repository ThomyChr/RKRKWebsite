<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();

global $MainMenu, $IconLoc;
$msg = '';
$iconChoices = rkrk_config_menu_icon_choices();
$iconBase = rkrk_config_menu_icon_web_base();
$newForm = array(
	'key' => '',
	'title' => '',
	'main_menu' => '',
	'parameter' => '',
	'release' => '',
	'users' => '',
	'sort' => '',
	'site' => '',
	'activ' => false,
	'image_small' => '',
	'image' => '',
);

if (!function_exists('rkrk_admin_menu_icon_select')) {
	function rkrk_admin_menu_icon_select($name, $curIcon, array $iconChoices, $iconBase): void {
		$h = static function ($s) {
			return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
		};
		echo "<select name='" . $h($name) . "' data-icon-base='" . $h($iconBase) . "' onchange='rkrkMenuIconPreview(this)'>";
		echo "<option value=''>(keins)</option>";
		$seen = array();
		foreach ($iconChoices as $file) {
			$seen[$file] = true;
			$sel = ((string)$file === (string)$curIcon) ? " selected" : "";
			echo "<option value='" . $h($file) . "'" . $sel . ">" . $h(ltrim((string)$file, '/')) . "</option>";
		}
		if ($curIcon !== '' && empty($seen[$curIcon])) {
			echo "<option value='" . $h($curIcon) . "' selected>" . $h(ltrim((string)$curIcon, '/')) . "</option>";
		}
		echo "</select>";
	}
}

if (!function_exists('rkrk_admin_menu_save')) {
	function rkrk_admin_menu_save(): void {
		global $MainMenu, $msg, $iconChoices, $newForm;
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['menu']) || !is_array($_POST['menu'])) {
			return;
		}
	$overlay = array();
	$errors = array();
	$dropped = array();
	foreach ($MainMenu as $key => $row) {
		$posted = $_POST['menu'][$key] ?? array();
		if (!is_array($posted)) {
			continue;
		}
		$isPhp = rkrk_config_menu_is_php_key($key);
		if (!$isPhp && !empty($posted['drop'])) {
			$dropped[] = (string)$key;
			continue;
		}
		list($imageSmall, $image) = rkrk_config_menu_icons($posted['image_small'] ?? ($row['image_small'] ?? ''), $posted['image'] ?? ($row['image'] ?? ''));
		$paramRaw = rkrk_str($posted['parameter'] ?? ($row['parameter'] ?? ''));
		$paramTrim = ltrim(trim($paramRaw), '?');
		$param = rkrk_config_sanitize_menu_parameter($paramRaw);
		if ($paramTrim !== '' && $param === '') {
			$errors[] = 'Parameter ungültig für ' . $key . '.';
			$param = rkrk_config_sanitize_menu_parameter($row['parameter'] ?? '');
		}
		$entry = array(
			'title' => rkrk_str($posted['title'] ?? ($row['title'] ?? '')),
			'main_menu' => rkrk_str($posted['main_menu'] ?? ($row['main_menu'] ?? '')),
			'parameter' => $param,
			'release' => rkrk_str($posted['release'] ?? ($row['release'] ?? '')),
			'users' => rkrk_config_sanitize_menu_users($posted['users'] ?? ($row['users'] ?? '')),
			'sort' => intval($posted['sort'] ?? ($row['sort'] ?? 0)),
			'activ' => !empty($posted['activ']),
			'image_small' => $imageSmall,
			'image' => $image,
		);
		$postedSiteRaw = trim(rkrk_str($posted['site'] ?? ''));
		if ($isPhp) {
			$baseSite = trim(rkrk_config_menu_base_site($key));
			if ($postedSiteRaw !== $baseSite) {
				$postedSite = rkrk_config_sanitize_menu_site($postedSiteRaw);
				if ($postedSite === '' || $postedSite !== $postedSiteRaw) {
					$errors[] = 'Seite ungültig für ' . $key . '.';
				} else {
					$entry['site'] = $postedSite;
				}
			}
			$overlay[$key] = $entry;
		} else {
			$postedSite = rkrk_config_sanitize_menu_site($postedSiteRaw);
			if ($postedSite === '' || $postedSite !== $postedSiteRaw) {
				$errors[] = 'Seite ungültig für ' . $key . '.';
			} else {
				$entry['site'] = $postedSite;
				$entry['target'] = rkrk_config_sanitize_menu_target($row['target'] ?? '_self');
				$entry['desc_short'] = $entry['title'];
				$entry['desc_long'] = $entry['title'];
				$entry['securl'] = !empty($row['securl']);
				$overlay[$key] = $entry;
			}
		}
		$MainMenu[$key]['title'] = $entry['title'];
		$MainMenu[$key]['main_menu'] = $entry['main_menu'];
		$MainMenu[$key]['parameter'] = $paramTrim !== '' && $param === '' ? $paramRaw : $param;
		$MainMenu[$key]['release'] = $entry['release'];
		$MainMenu[$key]['users'] = rkrk_str($posted['users'] ?? ($row['users'] ?? ''));
		$MainMenu[$key]['sort'] = $entry['sort'];
		$MainMenu[$key]['activ'] = $entry['activ'];
		$MainMenu[$key]['image_small'] = $imageSmall;
		$MainMenu[$key]['image'] = $image;
		$MainMenu[$key]['site'] = $postedSiteRaw;
	}
	$newPosted = $_POST['new'] ?? null;
	if (is_array($newPosted)) {
		$newForm['key'] = trim(rkrk_str($newPosted['key'] ?? ''));
		$newForm['title'] = trim(rkrk_str($newPosted['title'] ?? ''));
		$newForm['main_menu'] = rkrk_str($newPosted['main_menu'] ?? '');
		$newForm['parameter'] = rkrk_str($newPosted['parameter'] ?? '');
		$newForm['release'] = rkrk_str($newPosted['release'] ?? '');
		$newForm['users'] = rkrk_str($newPosted['users'] ?? '');
		$newForm['sort'] = trim(rkrk_str($newPosted['sort'] ?? ''));
		$newForm['site'] = trim(rkrk_str($newPosted['site'] ?? ''));
		$newForm['activ'] = !empty($newPosted['activ']);
		$newForm['image_small'] = rkrk_config_sanitize_menu_icon($newPosted['image_small'] ?? '');
		$newForm['image'] = rkrk_config_sanitize_menu_icon($newPosted['image'] ?? '');
		$wantsNew = ($newForm['key'] !== '' || $newForm['title'] !== '' || $newForm['site'] !== '' || trim($newForm['parameter']) !== '' || trim($newForm['main_menu']) !== '' || trim($newForm['release']) !== '' || trim($newForm['users']) !== '');
		if ($wantsNew) {
			$newKey = rkrk_config_sanitize_menu_key($newForm['key']);
			if ($newKey === '') {
				$errors[] = 'Neue Zeile: Key fehlt oder ist ungültig (Buchstabe, danach Buchstaben, Ziffern oder _).';
			} elseif (isset($MainMenu[$newKey]) || isset($overlay[$newKey]) || rkrk_config_menu_is_php_key($newKey)) {
				$errors[] = 'Neue Zeile: Key ' . $newKey . ' gibt es schon.';
			}
			$newSite = rkrk_config_sanitize_menu_site($newForm['site']);
			if ($newSite === '' || $newSite !== $newForm['site']) {
				$errors[] = 'Neue Zeile: Seite fehlt oder ist ungültig.';
			}
			if ($newForm['title'] === '') {
				$errors[] = 'Neue Zeile: Titel fehlt.';
			}
			$newParamTrim = ltrim(trim($newForm['parameter']), '?');
			$newParam = rkrk_config_sanitize_menu_parameter($newForm['parameter']);
			if ($newParamTrim !== '' && $newParam === '') {
				$errors[] = 'Neue Zeile: Parameter ungültig.';
			}
			if ($errors === array() && $newKey !== '') {
				list($imageSmall, $image) = rkrk_config_menu_icons($newForm['image_small'], $newForm['image']);
				$sort = $newForm['sort'];
				if ($sort === '') {
					$maxSort = 0;
					foreach ($MainMenu as $row) {
						$maxSort = max($maxSort, intval($row['sort'] ?? 0));
					}
					$sort = $maxSort + 10;
				} else {
					$sort = intval($sort);
				}
				$overlay[$newKey] = array(
					'title' => $newForm['title'],
					'main_menu' => $newForm['main_menu'],
					'parameter' => $newParam,
					'release' => $newForm['release'],
					'users' => rkrk_config_sanitize_menu_users($newForm['users']),
					'sort' => $sort,
					'activ' => $newForm['activ'],
					'image_small' => $imageSmall,
					'image' => $image,
					'site' => $newSite,
					'target' => '_self',
					'desc_short' => $newForm['title'],
					'desc_long' => $newForm['title'],
					'securl' => false,
				);
			}
		}
	}
	if ($errors !== array()) {
		$msg = implode(' ', $errors);
	} else {
		$msg = rkrk_config_save('menu', $overlay);
		if ($msg === '') {
			foreach ($dropped as $dk) {
				unset($MainMenu[$dk]);
			}
			rkrk_config_apply_menu($MainMenu);
			$iconChoices = rkrk_config_menu_icon_choices();
			$newForm = array(
				'key' => '',
				'title' => '',
				'main_menu' => '',
				'parameter' => '',
				'release' => '',
				'users' => '',
				'sort' => '',
				'site' => '',
				'activ' => false,
				'image_small' => '',
				'image' => '',
			);
			$msg = ($dropped !== array()) ? 'Gespeichert. Zeile entfernt.' : 'Gespeichert.';
		}
	}
	}
}

if (!rkrk_admin_boot('Menü', 'rkrk_admin_menu_save')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_menu.php');

echo "<p>Seite und Parameter bestimmen den Aufruf <code>Seite?Parameter</code> (zum Beispiel Seite <code>rkrk_map.php</code> und Parameter <code>Reg=ALL</code>, oder <code>KAT</code> für die Feldeditoren). Beides lässt sich ändern. Eine neue Zeile steht am Tabellenende: Key, Titel, Gruppe, Sort, Seite und Parameter. Gruppe ist der Menüabschnitt. Die Reihenfolge der Einträge zieht man an der Zeile, die Reihenfolge der Gruppen an der Gruppenzeile. <code>sort</code> wird dabei neu gesetzt und gilt nach dem Speichern auch in der Navigationsleiste. Sichtbarkeit über <code>activ</code>, Stufen in <code>release</code> und optional kommagetrennte <code>users</code>. Eingefügte Zeilen lassen sich mit Entfernen löschen. Kleines und normales Symbol kommen aus <code>" . htmlspecialchars($iconBase, ENT_QUOTES, 'UTF-8') . "</code>. Bleibt das normale leer, wird es aus dem kleinen abgeleitet (<code>name_small.png</code> zu <code>name.png</code>).</p>\n";
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<style>\n";
echo "tr.rkrk-menu-group td{background:#e7eee4;font-weight:700;}\n";
echo "td.rkrk-menu-grip{cursor:grab;user-select:none;text-align:center;}\n";
echo "td.rkrk-menu-icons{white-space:normal;vertical-align:middle;}\n";
echo ".rkrk-icon-pair{display:grid;grid-template-columns:4.2em 22px 11em;align-items:center;column-gap:6px;row-gap:4px;}\n";
echo ".rkrk-icon-pair span{font-size:12px;line-height:1;}\n";
echo ".rkrk-icon-pair img{display:block;}\n";
echo ".rkrk-icon-pair select{width:11em;max-width:11em;box-sizing:border-box;}\n";
echo "</style>\n";
echo "<form method='post'>\n";
echo "<table class='big' id='rkrkMenuTable'><thead><tr><th></th><th>Key</th><th>Icon</th><th>Titel</th><th>Gruppe</th><th>An</th><th>Sort</th><th>Freigabe</th><th>Personen</th><th>Seite</th><th>Parameter</th><th>Aufruf</th></tr></thead>\n<tbody id='rkrkMenuBody'>\n";
$menuKeys = array_keys($MainMenu);
usort($menuKeys, function ($a, $b) use ($MainMenu) {
	$sa = intval($MainMenu[$a]['sort'] ?? 0);
	$sb = intval($MainMenu[$b]['sort'] ?? 0);
	if ($sa === $sb) {
		return strcmp((string)$a, (string)$b);
	}
	return $sa <=> $sb;
});
$menuGroups = array();
foreach ($menuKeys as $key) {
	$gname = trim(rkrk_str($MainMenu[$key]['main_menu'] ?? ''));
	$menuGroups[$gname][] = $key;
}
foreach ($menuGroups as $gname => $groupKeys) {
	$gLabel = ($gname === '') ? 'ohne Gruppe' : $gname;
	$gAttr = htmlspecialchars($gname, ENT_QUOTES, 'UTF-8');
	$gLabelH = htmlspecialchars($gLabel, ENT_QUOTES, 'UTF-8');
	echo "<tr class='rkrk-menu-group' data-menu-group='1' data-group='{$gAttr}'>";
	echo "<td class='rkrk-menu-grip' draggable='true' title='Gruppe verschieben'>↕</td>";
	echo "<td colspan='11'>{$gLabelH} · " . count($groupKeys) . "</td></tr>\n";
	foreach ($groupKeys as $key) {
	$row = $MainMenu[$key];
	$keyH = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
	$title = htmlspecialchars(rkrk_str($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
	$grp = htmlspecialchars(rkrk_str($row['main_menu'] ?? ''), ENT_QUOTES, 'UTF-8');
	$rel = htmlspecialchars(rkrk_str($row['release'] ?? ''), ENT_QUOTES, 'UTF-8');
	$users = htmlspecialchars(rkrk_config_sanitize_menu_users($row['users'] ?? ($row['Benutzername'] ?? '')), ENT_QUOTES, 'UTF-8');
	$sort = htmlspecialchars(rkrk_str($row['sort'] ?? ''), ENT_QUOTES, 'UTF-8');
	$site = htmlspecialchars(rkrk_str($row['site'] ?? ''), ENT_QUOTES, 'UTF-8');
	$param = htmlspecialchars(rkrk_config_sanitize_menu_parameter($row['parameter'] ?? ''), ENT_QUOTES, 'UTF-8');
	$call = $site . ($param !== '' ? ('?' . $param) : '');
	$curSmall = rkrk_config_sanitize_menu_icon($row['image_small'] ?? '');
	$curImage = rkrk_config_sanitize_menu_icon($row['image'] ?? '');
	$previewSmall = ($curSmall !== '') ? htmlspecialchars($iconBase . $curSmall, ENT_QUOTES, 'UTF-8') : '';
	$previewImage = ($curImage !== '') ? htmlspecialchars($iconBase . $curImage, ENT_QUOTES, 'UTF-8') : '';
	$previewSmallVis = ($curSmall !== '') ? '' : ' style="visibility:hidden"';
	$previewImageVis = ($curImage !== '') ? '' : ' style="visibility:hidden"';
	echo "<tr>";
	echo "<td class='rkrk-menu-grip' draggable='true' title='Ziehen zum Sortieren'>↕</td>";
	echo "<td><code>{$keyH}</code>";
	if (!rkrk_config_menu_is_php_key($key)) {
		echo "<br><label><input type='checkbox' name='menu[{$keyH}][drop]' value='1'> Entfernen</label>";
	}
	echo "</td>";
	echo "<td class='rkrk-menu-icons'>";
	echo "<div class='rkrk-icon-pair'><span>klein</span><img class='rkrk-menu-icon-preview' src='{$previewSmall}' height='20' width='20' alt=''{$previewSmallVis}>";
	rkrk_admin_menu_icon_select("menu[{$key}][image_small]", $curSmall, $iconChoices, $iconBase);
	echo "<span>normal</span><img class='rkrk-menu-icon-preview' src='{$previewImage}' height='20' width='20' alt=''{$previewImageVis}>";
	rkrk_admin_menu_icon_select("menu[{$key}][image]", $curImage, $iconChoices, $iconBase);
	echo "</div></td>";
	echo "<td><input name='menu[{$keyH}][title]' value='{$title}' size='22'></td>";
	echo "<td><input name='menu[{$keyH}][main_menu]' value='{$grp}' size='14'></td>";
	echo "<td>";
	rkrk_admin_echo_pill('checkbox', "menu[{$keyH}][activ]", '1', !empty($row['activ']), 'an');
	echo "</td>";
	echo "<td><input class='rkrk-menu-sort' name='menu[{$keyH}][sort]' value='{$sort}' size='5'></td>";
	echo "<td><input name='menu[{$keyH}][release]' value='{$rel}' size='32'></td>";
	echo "<td><input name='menu[{$keyH}][users]' value='{$users}' size='28' placeholder='Name, Name'></td>";
	echo "<td><input class='rkrk-menu-site' name='menu[{$keyH}][site]' value='{$site}' size='28' placeholder='rkrk_map.php'></td>";
	echo "<td><input class='rkrk-menu-param' name='menu[{$keyH}][parameter]' value='{$param}' size='28' placeholder='KAT oder Reg=ALL'></td>";
	echo "<td><code class='rkrk-menu-call'>{$call}</code></td></tr>\n";
	}
}
$newKeyH = htmlspecialchars($newForm['key'], ENT_QUOTES, 'UTF-8');
$newTitleH = htmlspecialchars($newForm['title'], ENT_QUOTES, 'UTF-8');
$newGrpH = htmlspecialchars($newForm['main_menu'], ENT_QUOTES, 'UTF-8');
$newRelH = htmlspecialchars($newForm['release'], ENT_QUOTES, 'UTF-8');
$newUsersH = htmlspecialchars($newForm['users'], ENT_QUOTES, 'UTF-8');
$newSortH = htmlspecialchars($newForm['sort'], ENT_QUOTES, 'UTF-8');
$newSiteH = htmlspecialchars($newForm['site'], ENT_QUOTES, 'UTF-8');
$newParamShow = htmlspecialchars(ltrim($newForm['parameter'], '?'), ENT_QUOTES, 'UTF-8');
$newCall = htmlspecialchars($newForm['site'] . ($newForm['parameter'] !== '' ? ('?' . ltrim($newForm['parameter'], '?')) : ''), ENT_QUOTES, 'UTF-8');
$newSmall = rkrk_config_sanitize_menu_icon($newForm['image_small']);
$newImage = rkrk_config_sanitize_menu_icon($newForm['image'] ?? '');
$newSmallSrc = ($newSmall !== '') ? htmlspecialchars($iconBase . $newSmall, ENT_QUOTES, 'UTF-8') : '';
$newImageSrc = ($newImage !== '') ? htmlspecialchars($iconBase . $newImage, ENT_QUOTES, 'UTF-8') : '';
$newSmallVis = ($newSmall !== '') ? '' : ' style="visibility:hidden"';
$newImageVis = ($newImage !== '') ? '' : ' style="visibility:hidden"';
echo "<tr data-menu-new='1'>";
echo "<td class='rkrk-menu-grip' title='Neue Zeile'>＋</td>";
echo "<td><input name='new[key]' value='{$newKeyH}' size='14' placeholder='NeuerKey'></td>";
echo "<td class='rkrk-menu-icons'>";
echo "<div class='rkrk-icon-pair'><span>klein</span><img class='rkrk-menu-icon-preview' src='{$newSmallSrc}' height='20' width='20' alt=''{$newSmallVis}>";
rkrk_admin_menu_icon_select('new[image_small]', $newSmall, $iconChoices, $iconBase);
echo "<span>normal</span><img class='rkrk-menu-icon-preview' src='{$newImageSrc}' height='20' width='20' alt=''{$newImageVis}>";
rkrk_admin_menu_icon_select('new[image]', $newImage, $iconChoices, $iconBase);
echo "</div></td>";
echo "<td><input name='new[title]' value='{$newTitleH}' size='22' placeholder='Titel'></td>";
echo "<td><input name='new[main_menu]' value='{$newGrpH}' size='14' placeholder='Gruppe'></td>";
echo "<td>";
rkrk_admin_echo_pill('checkbox', 'new[activ]', '1', !empty($newForm['activ']), 'an');
echo "</td>";
echo "<td><input class='rkrk-menu-sort' name='new[sort]' value='{$newSortH}' size='5' placeholder='auto'></td>";
echo "<td><input name='new[release]' value='{$newRelH}' size='32' placeholder='test, intern'></td>";
echo "<td><input name='new[users]' value='{$newUsersH}' size='28' placeholder='Name, Name'></td>";
echo "<td><input class='rkrk-menu-site' name='new[site]' value='{$newSiteH}' size='28' placeholder='rkrk_map.php'></td>";
echo "<td><input class='rkrk-menu-param' name='new[parameter]' value='{$newParamShow}' size='28' placeholder='KAT oder Reg=ALL'></td>";
echo "<td><code class='rkrk-menu-call'>{$newCall}</code></td></tr>\n";
echo "</tbody></table>\n";
echo "<p><button type='submit'>Speichern</button></p>\n";
echo "</form>\n";
echo "<script>\n";
echo "function rkrkMenuIconPreview(sel){\n";
echo "  var img=sel.previousElementSibling;\n";
echo "  if(!img || !img.classList || !img.classList.contains('rkrk-menu-icon-preview')){return;}\n";
echo "  var base=sel.getAttribute('data-icon-base')||'';\n";
echo "  if(!sel.value){img.removeAttribute('src');img.style.visibility='hidden';return;}\n";
echo "  img.style.visibility='visible';\n";
echo "  img.src=base+sel.value;\n";
echo "}\n";
echo "(function(){\n";
echo "  var body=document.getElementById('rkrkMenuBody');\n";
echo "  if(!body){return;}\n";
echo "  var drag=null;\n";
echo "  function fieldText(el){\n";
echo "    if(!el){return '';}\n";
echo "    if(el.tagName==='INPUT'||el.tagName==='TEXTAREA'||el.tagName==='SELECT'){return el.value||'';}\n";
echo "    return el.textContent||'';\n";
echo "  }\n";
echo "  function paintCall(row){\n";
echo "    var site=row.querySelector('.rkrk-menu-site');\n";
echo "    var param=row.querySelector('.rkrk-menu-param');\n";
echo "    var call=row.querySelector('.rkrk-menu-call');\n";
echo "    if(!site||!param||!call){return;}\n";
echo "    var q=fieldText(param).replace(/^\\?+/, '').trim();\n";
echo "    var s=fieldText(site).trim();\n";
echo "    call.textContent=s+(q?('?'+q):'');\n";
echo "  }\n";
echo "  function isGroup(row){return !!(row && row.getAttribute('data-menu-group')==='1');}\n";
echo "  function isNew(row){return !!(row && row.getAttribute('data-menu-new')==='1');}\n";
echo "  function groupHead(row){\n";
echo "    var cur=row;\n";
echo "    while(cur && !isGroup(cur)){cur=cur.previousElementSibling;}\n";
echo "    return cur;\n";
echo "  }\n";
echo "  function groupBlock(head){\n";
echo "    var nodes=[head];\n";
echo "    var n=head.nextElementSibling;\n";
echo "    while(n && !isGroup(n) && !isNew(n)){nodes.push(n);n=n.nextElementSibling;}\n";
echo "    return nodes;\n";
echo "  }\n";
echo "  function syncItemGroup(row){\n";
echo "    if(!row || isGroup(row) || isNew(row)){return;}\n";
echo "    var head=groupHead(row);\n";
echo "    var input=row.querySelector('input[name$=\"[main_menu]\"]');\n";
echo "    if(head && input){input.value=head.getAttribute('data-group')||'';}\n";
echo "  }\n";
echo "  function renumber(){\n";
echo "    var rows=body.querySelectorAll('tr');\n";
echo "    var n=0;\n";
echo "    for(var i=0;i<rows.length;i++){\n";
echo "      if(isGroup(rows[i]) || isNew(rows[i])){continue;}\n";
echo "      n++;\n";
echo "      var sort=rows[i].querySelector('.rkrk-menu-sort');\n";
echo "      if(sort){sort.value=String(n*10);}\n";
echo "    }\n";
echo "    var head=null, count=0;\n";
echo "    function paintHead(){\n";
echo "      if(!head){return;}\n";
echo "      var cell=head.cells && head.cells[1];\n";
echo "      if(!cell){return;}\n";
echo "      var name=head.getAttribute('data-group')||'';\n";
echo "      cell.textContent=(name===''?'ohne Gruppe':name)+' · '+count;\n";
echo "    }\n";
echo "    for(var g=0;g<rows.length;g++){\n";
echo "      if(isGroup(rows[g])){paintHead();head=rows[g];count=0;continue;}\n";
echo "      if(isNew(rows[g])){paintHead();head=null;continue;}\n";
echo "      count++;\n";
echo "    }\n";
echo "    paintHead();\n";
echo "  }\n";
echo "  body.addEventListener('input', function(ev){\n";
echo "    var row=ev.target && ev.target.closest ? ev.target.closest('tr') : null;\n";
echo "    if(row && (ev.target.classList.contains('rkrk-menu-param')||ev.target.classList.contains('rkrk-menu-site'))){paintCall(row);}\n";
echo "  });\n";
echo "  body.addEventListener('dragstart', function(ev){\n";
echo "    var grip=ev.target && ev.target.closest ? ev.target.closest('.rkrk-menu-grip') : null;\n";
echo "    if(!grip){return;}\n";
echo "    var row=grip.closest('tr');\n";
echo "    if(!row || isNew(row)){return;}\n";
echo "    drag=row;\n";
echo "    var block=isGroup(row) ? groupBlock(row) : [row];\n";
echo "    for(var i=0;i<block.length;i++){block[i].style.opacity='0.45';}\n";
echo "    if(ev.dataTransfer){ev.dataTransfer.effectAllowed='move'; try{ev.dataTransfer.setData('text/plain', 'row');}catch(e){}}\n";
echo "  });\n";
echo "  body.addEventListener('dragend', function(){\n";
echo "    var rows=body.querySelectorAll('tr');\n";
echo "    for(var i=0;i<rows.length;i++){rows[i].style.opacity='';}\n";
echo "    drag=null;\n";
echo "  });\n";
echo "  body.addEventListener('dragover', function(ev){\n";
echo "    if(!drag){return;}\n";
echo "    ev.preventDefault();\n";
echo "    var row=ev.target && ev.target.closest ? ev.target.closest('tr') : null;\n";
echo "    if(!row || row.parentNode!==body || isNew(row)){return;}\n";
echo "    if(isGroup(drag)){\n";
echo "      var targetHead=groupHead(row);\n";
echo "      if(!targetHead || targetHead===drag){return;}\n";
echo "      var targetNodes=groupBlock(targetHead);\n";
echo "      var top=targetNodes[0].getBoundingClientRect().top;\n";
echo "      var bottom=targetNodes[targetNodes.length-1].getBoundingClientRect().bottom;\n";
echo "      var after=ev.clientY > (top+bottom)/2;\n";
echo "      var ref=after ? targetNodes[targetNodes.length-1].nextSibling : targetHead;\n";
echo "      var moving=groupBlock(drag);\n";
echo "      if(ref===moving[0]){return;}\n";
echo "      for(var i=0;i<moving.length;i++){body.insertBefore(moving[i], ref);}\n";
echo "      return;\n";
echo "    }\n";
echo "    if(row===drag){return;}\n";
echo "    var rect=row.getBoundingClientRect();\n";
echo "    var afterItem=ev.clientY > rect.top + rect.height/2;\n";
echo "    var dest=afterItem ? row.nextSibling : row;\n";
echo "    if(dest===drag){return;}\n";
echo "    body.insertBefore(drag, dest);\n";
echo "    syncItemGroup(drag);\n";
echo "  });\n";
echo "  body.addEventListener('drop', function(ev){\n";
echo "    ev.preventDefault();\n";
echo "    renumber();\n";
echo "  });\n";
echo "})();\n";
echo "</script>\n";
echo "</main>\n";
footer();
