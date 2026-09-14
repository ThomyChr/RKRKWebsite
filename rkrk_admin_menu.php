<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_menu.php');
include_once('rkrk_access_control.php');

CheckIPandExit();
if (!rkrk_admin_boot('Menü')) {
	exit;
}
rkrk_admin_nav('rkrk_admin_menu.php');

global $MainMenu, $IconLoc;
$msg = '';
$iconChoices = rkrk_config_menu_icon_choices();
$iconBase = rkrk_config_menu_icon_web_base();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu']) && is_array($_POST['menu'])) {
	$overlay = array();
	foreach ($MainMenu as $key => $row) {
		$posted = $_POST['menu'][$key] ?? array();
		if (!is_array($posted)) {
			continue;
		}
		list($imageSmall, $image) = rkrk_config_menu_icon_pair(rkrk_str($posted['image_small'] ?? ($row['image_small'] ?? '')));
		$overlay[$key] = array(
			'title' => rkrk_str($posted['title'] ?? ($row['title'] ?? '')),
			'main_menu' => rkrk_str($posted['main_menu'] ?? ($row['main_menu'] ?? '')),
			'release' => rkrk_str($posted['release'] ?? ($row['release'] ?? '')),
			'users' => rkrk_config_sanitize_menu_users($posted['users'] ?? ($row['users'] ?? '')),
			'sort' => intval($posted['sort'] ?? ($row['sort'] ?? 0)),
			'activ' => !empty($posted['activ']),
			'image_small' => $imageSmall,
			'image' => $image,
		);
	}
	$msg = rkrk_config_save('menu', $overlay);
	if ($msg === '') {
		rkrk_config_apply_menu($MainMenu);
		$iconChoices = rkrk_config_menu_icon_choices();
		$msg = 'Gespeichert.';
	}
}

echo "<p>Dateinamen der Zielseiten bleiben. Sichtbarkeit über <code>activ</code>, Stufen in <code>release</code> und optional kommagetrennte <code>users</code> (wie bisher im Menü-Array). Icons aus <code>" . htmlspecialchars($iconBase, ENT_QUOTES, 'UTF-8') . "</code> (<code>*_small.png</code>).</p>\n";
if ($msg !== '') {
	echo "<p><b>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</b></p>\n";
}
echo "<form method='post'>\n";
echo "<table class='big'><tr><th>Key</th><th>Icon</th><th>Titel</th><th>Gruppe</th><th>An</th><th>Sort</th><th>Freigabe</th><th>Personen</th><th>Seite (fest)</th></tr>\n";
foreach ($MainMenu as $key => $row) {
	$keyH = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
	$title = htmlspecialchars(rkrk_str($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
	$grp = htmlspecialchars(rkrk_str($row['main_menu'] ?? ''), ENT_QUOTES, 'UTF-8');
	$rel = htmlspecialchars(rkrk_str($row['release'] ?? ''), ENT_QUOTES, 'UTF-8');
	$users = htmlspecialchars(rkrk_config_sanitize_menu_users($row['users'] ?? ($row['Benutzername'] ?? '')), ENT_QUOTES, 'UTF-8');
	$sort = htmlspecialchars(rkrk_str($row['sort'] ?? ''), ENT_QUOTES, 'UTF-8');
	$site = htmlspecialchars(rkrk_str($row['site'] ?? ''), ENT_QUOTES, 'UTF-8');
	$curIcon = rkrk_config_sanitize_menu_icon($row['image_small'] ?? '');
	$previewSrc = ($curIcon !== '') ? htmlspecialchars($iconBase . $curIcon, ENT_QUOTES, 'UTF-8') : '';
	$previewVis = ($curIcon !== '') ? '' : ' style="visibility:hidden"';
	echo "<tr><td><code>{$keyH}</code></td>";
	echo "<td style='white-space:nowrap'>";
	echo "<img class='rkrk-menu-icon-preview' src='{$previewSrc}' height='20' width='20' alt=''{$previewVis}> ";
	echo "<select name='menu[{$keyH}][image_small]' data-icon-base='" . htmlspecialchars($iconBase, ENT_QUOTES, 'UTF-8') . "' onchange='rkrkMenuIconPreview(this)'>";
	echo "<option value=''>(keins)</option>";
	$seen = array();
	foreach ($iconChoices as $file) {
		$seen[$file] = true;
		$sel = ($file === $curIcon) ? " selected" : "";
		$lab = htmlspecialchars(ltrim($file, '/'), ENT_QUOTES, 'UTF-8');
		$val = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
		echo "<option value='{$val}'{$sel}>{$lab}</option>";
	}
	if ($curIcon !== '' && empty($seen[$curIcon])) {
		$lab = htmlspecialchars(ltrim($curIcon, '/'), ENT_QUOTES, 'UTF-8');
		$val = htmlspecialchars($curIcon, ENT_QUOTES, 'UTF-8');
		echo "<option value='{$val}' selected>{$lab}</option>";
	}
	echo "</select></td>";
	echo "<td><input name='menu[{$keyH}][title]' value='{$title}' size='22'></td>";
	echo "<td><input name='menu[{$keyH}][main_menu]' value='{$grp}' size='14'></td>";
	echo "<td>";
	rkrk_admin_echo_pill('checkbox', "menu[{$keyH}][activ]", '1', !empty($row['activ']), 'an');
	echo "</td>";
	echo "<td><input name='menu[{$keyH}][sort]' value='{$sort}' size='5'></td>";
	echo "<td><input name='menu[{$keyH}][release]' value='{$rel}' size='32'></td>";
	echo "<td><input name='menu[{$keyH}][users]' value='{$users}' size='28' placeholder='Name, Name'></td>";
	echo "<td><code>{$site}</code></td></tr>\n";
}
echo "</table>\n";
echo "<p><button type='submit'>Speichern</button></p>\n";
echo "</form>\n";
echo "<script>\n";
echo "function rkrkMenuIconPreview(sel){\n";
echo "  var img=sel.parentNode.querySelector('img.rkrk-menu-icon-preview');\n";
echo "  if(!img){return;}\n";
echo "  var base=sel.getAttribute('data-icon-base')||'';\n";
echo "  if(!sel.value){img.removeAttribute('src');img.style.visibility='hidden';return;}\n";
echo "  img.style.visibility='visible';\n";
echo "  img.src=base+sel.value;\n";
echo "}\n";
echo "</script>\n";
echo "</main>\n";
footer();
