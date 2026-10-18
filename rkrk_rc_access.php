<?php
/**
 * RC Zugang: Login, Drohnenwahl, dann Einsatz, Feld Editor, Flugfelder und Karte.
 * Nicht im Menü. Erreichbar über die Hilfslinks auf rkrk.php.
 */
session_start();
$rcMission = trim((string)($_GET['mdb'] ?? $_GET['MDB'] ?? '3T'));
if ($rcMission === '') {
	$rcMission = '3T';
}
$_GET['MDB'] = $rcMission;

include_once __DIR__ . '/scripts/rkrk_gen.php';
include_once __DIR__ . '/scripts/rkrk_menu.php';
include_once __DIR__ . '/rkrk_access_control.php';

CheckIPandExit();
$GLOBALS['RKRK_MANIFEST_APP'] = 'rc';
headerpage('RC Zugang', true);
$logoSrc = '/rkropen/favicon/Reh_FA840B_orange/favicon-32x32.png';
if (function_exists('rkrk_favicon_assets')) {
	$rcFav = rkrk_favicon_assets();
	if (!empty($rcFav['png'])) {
		$logoSrc = (string)$rcFav['png'];
	}
}
echo '<style>
  .rc-brand { display:flex; align-items:center; gap:10px; max-width:40rem; width:100%; box-sizing:border-box; margin:6px auto 0; padding:0 12px; font-family:system-ui,Segoe UI,Arial,sans-serif; text-decoration:none; }
  .rc-brand img { height:32px; width:32px; object-fit:contain; display:block; }
  .rc-brand strong { display:block; font-size:18px; color:#48673A; }
  .rc-brand span { color:#5c6b7a; font-size:13px; }
</style>';
echo '<a class="rc-brand" href="rkrk.php"><img src="' . htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') . '" alt="RKRK"><div><strong>RKRK</strong><span>Rehkitzrettung Knonaueramt</span></div></a>';
GrantAccess();

if (empty($LoggedOn)) {
	exit;
}

$showDebug = array_key_exists('DEBUG', $_GET) || array_key_exists('ADMIN', $_GET);
$showExpert = function_exists('rkrk_expert_catalogs_visible') && rkrk_expert_catalogs_visible();
$expertHidden = function_exists('rkrk_catalog_requires_expert') && rkrk_catalog_requires_expert($rcMission) && !$showExpert;
if (!isset($MissionDroneTypes[$rcMission]) || empty($MissionDroneTypes[$rcMission]['active']) || $expertHidden || (($rcMission === 'D' || stripos((string)($MissionDroneTypes[$rcMission]['Name'] ?? ''), 'debug') !== false) && !$showDebug)) {
	$rcMission = '3T';
	$_GET['MDB'] = '3T';
	$RKRKMissionSelected = '3T';
	SetMissionLocation();
}

$drones = array();
foreach ($MissionDroneTypes as $key => $drone) {
	if (empty($drone['active'])) {
		continue;
	}
	$isDebug = ($key === 'D' || stripos((string)($drone['Name'] ?? ''), 'debug') !== false);
	if ($isDebug && !$showDebug) {
		continue;
	}
	if (function_exists('rkrk_catalog_requires_expert') && rkrk_catalog_requires_expert((string)$key) && !$showExpert) {
		continue;
	}
	$release = ' ' . (string)($drone['release'] ?? '');
	$level = (string)($WebFreigabe ?? '');
	if ($level !== '' && function_exists('GetAccess') && GetAccess('InternLinks') && strpos($release, $level) === false) {
		continue;
	}
	$drones[$key] = array(
		'name' => (string)($drone['Name'] ?? $key),
		'button' => (string)($drone['button'] ?? ''),
	);
}
if (!isset($drones[$rcMission])) {
	$rcMission = isset($drones['3T']) ? '3T' : (string)array_key_first($drones);
}
if ($rcMission === '') {
	$rcMission = '3T';
	$drones['3T'] = array('name' => 'Mavic 3T', 'button' => 'mavic3t_button.png');
}

$qMission = rawurlencode($rcMission);
$flagQuery = '';
if (array_key_exists('DEBUG', $_GET)) {
	$flagQuery .= '&DEBUG';
}
if (array_key_exists('ADMIN', $_GET)) {
	$flagQuery .= '&ADMIN';
}
if ($showExpert) {
	$flagQuery .= '&EXPERT';
}
$self = htmlspecialchars($_SERVER['PHP_SELF'] ?? 'rkrk_rc_access.php', ENT_QUOTES, 'UTF-8');
$iconBase = rtrim(str_replace('\\', '/', (string)($IconLoc ?? 'images/23color')), '/');
$mapIntern = (string)($MapIntern ?? '&KA=S&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=off&FZ=on&MAP=1&Zoom=1.0');
$menuIcon = static function ($menuKey) use ($iconBase) {
	if (function_exists('rkrk_menu_icon_src')) {
		$src = rkrk_menu_icon_src($menuKey, true);
		if ($src !== '') {
			return $src;
		}
	}
	global $MainMenu;
	$file = ltrim((string)($MainMenu[$menuKey]['image_small'] ?? $MainMenu[$menuKey]['image'] ?? ''), '/');
	return $file === '' ? '' : $iconBase . '/' . $file;
};
$links = array(
	array('Einsatz', 'Gebuchte Felder, KMZ und Checkliste', 'fe/rkrk_rc_einsatz.php?mdb=' . $qMission . $flagQuery, $menuIcon('RcEinsatz')),
	array('Feld Editor', 'Feld anlegen und Flugpfad rechnen', 'fe/rkrk_feld_editor_rc.php?KAT&MDB=' . ($rcMission === '4CX' ? '3T' : $qMission) . $flagQuery, $menuIcon('map_edit_rc')),
	array('Flugfelder', 'Feldkatalog', 'rkrk_felderliste.php?Reg=leer&MDB=' . $qMission . '&Top=off' . $flagQuery, $menuIcon('FeldListe')),
	array('MAP', 'Felder auf der Karte', 'rkrk_map.php?Reg=leer&MDB=' . $qMission . $mapIntern . '&Top=off&Side=off' . $flagQuery, $menuIcon('FeldKarteIntern')),
);
?>
<style>
  body { margin: 0 !important; padding: 0 !important; background: #f7f8f6; }
  .rc-gate { max-width: 40rem; width: 100%; box-sizing: border-box; margin: 0 auto; padding: 4px 12px 16px; font-family: system-ui, Segoe UI, Arial, sans-serif; color: #172033; }
  .rc-brand { display: flex; align-items: center; gap: 10px; max-width: 40rem; width: 100%; box-sizing: border-box; margin: 4px auto 0; padding: 0 12px; }
  .rc-brand img { height: 32px; width: 32px; object-fit: contain; display: block; }
  .rc-brand strong { display: block; font-size: 18px; color: #48673A; letter-spacing: .02em; }
  .rc-brand span, .rc-brand a { color: #5c6b7a; font-size: 13px; text-decoration: none; }
  .rc-gate h2 { font-size: 12px; margin: 6px 0 4px; letter-spacing: .08em; text-transform: uppercase; color: #48673A; }
  .rc-drones { display: flex; flex-wrap: wrap; gap: 6px; }
  .rc-drones a {
    display: flex; flex-direction: column; align-items: center; gap: 2px;
    min-width: 6.2rem; padding: 4px 8px; border-radius: 10px;
    background: #fff; color: #172033; text-decoration: none; font-weight: 700; font-size: 12px;
    border: 1px solid #d5dde6;
  }
  .rc-drones a img { height: 28px; width: auto; }
  .rc-drones a.is-on { border-color: #48673A; box-shadow: 0 0 0 2px #d7e4cf; }
  .rc-links { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; align-items: start; }
  .rc-links a {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 12px;
    background: #fff; color: #172033; text-decoration: none;
    border: 1px solid #d5dde6; box-shadow: 0 1px 2px rgba(18,24,20,.06);
  }
  .rc-links img { width: 36px; height: 36px; object-fit: contain; flex: 0 0 auto; }
  .rc-links b { display: block; font-size: 15px; }
  .rc-links small { display: block; color: #5c6b7a; font-size: 11px; font-weight: 500; line-height: 1.2; }
  @media (max-width: 1100px) {
    .rkrk-shell-header, #navDemo { display: none !important; }
    .rc-brand { margin-top: 10px; }
    .rc-brand img { height: 32px; width: 32px; }
    .rc-links { grid-template-columns: 1fr 1fr; }
  }
</style>
<div class="rc-gate">
  <h2>Drohne</h2>
  <div class="rc-drones">
    <?php foreach ($drones as $code => $drone): ?>
      <a class="<?php echo $code === $rcMission ? 'is-on' : ''; ?>" href="<?php echo $self . '?mdb=' . rawurlencode($code) . htmlspecialchars($flagQuery, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($drone['button'] !== ''): ?><img src="<?php echo htmlspecialchars($iconBase . '/' . $drone['button'], ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?>
        <?php echo htmlspecialchars($drone['name'], ENT_QUOTES, 'UTF-8'); ?>
      </a>
    <?php endforeach; ?>
  </div>
  <h2>Weiter</h2>
  <div class="rc-links">
    <?php foreach ($links as $link): ?>
      <a href="<?php echo htmlspecialchars($link[2], ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($link[3] !== ''): ?><img src="<?php echo htmlspecialchars($link[3], ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?>
        <span><b><?php echo htmlspecialchars($link[0], ENT_QUOTES, 'UTF-8'); ?></b><small><?php echo htmlspecialchars($link[1], ENT_QUOTES, 'UTF-8'); ?></small></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php
