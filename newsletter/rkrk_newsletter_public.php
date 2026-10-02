<?php
session_start();
$folderoffset = '../';
chdir(dirname(__DIR__));
include_once 'scripts/rkrk_gen.php';
include_once 'scripts/rkrk_menu.php';
include_once 'rkrk_access_control.php';
include_once 'newsletter/rkrk_newsletter_lib.php';

CheckIPandExit();
nl_ensure_store();

if (isset($_GET['grafik']) && (string)$_GET['grafik'] !== '') {
	nl_stream_public_chart((string)$_GET['grafik'], (string)($_GET['n'] ?? '1'));
}

if (isset($_GET['bild']) && (string)$_GET['bild'] !== '') {
	nl_stream_public_image((string)$_GET['bild']);
}

$nlPublicLead = 'IG "Rehkitzrettung mit Drohnen im Knonaueramt und Zug"';
$nlPublicIntro = 'Die Newsletter unten geben Auskunft über die Aktivitäten des Vereins "Rehkitzrettung mit Drohnen im Knonaueramt" und sollen für alle interessierten Kreise die nötige Transparenz schaffen.';

$issues = array();
foreach (nl_items('newsletter_issues.json') as $issue) {
	if (nl_issue_is_public($issue)) {
		$issues[] = $issue;
	}
}
usort($issues, function ($a, $b) {
	return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
});

$want = trim((string)($_GET['id'] ?? ''));
$current = null;
if ($want !== '') {
	foreach ($issues as $issue) {
		if ((string)$issue['id'] === $want) {
			$current = $issue;
			break;
		}
	}
}

$loggedIn = isset($_SESSION[$SessNme_RegisteredUser]) && trim((string)$_SESSION[$SessNme_RegisteredUser]) !== '';
$guestView = rkrk_auth_is_guest_request() || !$loggedIn;
$menuOn = !$guestView && (!isset($_GET['MenuOn']) || (string)$_GET['MenuOn'] !== 'off');

headerpage('Newsletter', false, $folderoffset);
GrantAccess();
if ($menuOn) {
	CreateTopMenu($folderoffset);
}

$byYear = array();
foreach ($issues as $issue) {
	$id = (string)($issue['id'] ?? '');
	$year = preg_match('/^(\d{4})/', $id, $m) ? $m[1] : substr((string)($issue['created'] ?? ''), 0, 4);
	if ($year === '') {
		$year = 'Weitere';
	}
	$byYear[$year][] = $issue;
}

echo "<link rel='stylesheet' href='rkrk_newsletter.css?v=20260925z20'>\n";
echo "<main class='nl-public'>\n";
echo "<div class='nl-pub-head'>\n";
if ($current) {
	$logo = nl_public_media_href((string)($current['headerImage'] ?? ''));
	if ($logo !== '') {
		echo "<p class='nl-logo'><img src='" . nl_h($logo) . "' alt=''></p>\n";
	}
}
echo "<h1>Newsletter</h1>\n";
echo "<p class='nl-lead'>" . nl_h($nlPublicLead) . "</p>\n";
echo "</div>\n";
echo "<p class='nl-intro'>" . nl_h($nlPublicIntro) . "</p>\n";

if (count($issues) === 0) {
	echo "<p>Noch kein Newsletter veröffentlicht.</p>\n";
} elseif ($current) {
	echo "<p class='nl-back nl-noprint'><a href='rkrk_newsletter_public.php?MenuOn=off'>Alle Newsletter</a>";
	echo "<span class='nl-pub-actions'>";
	echo "<button type='button' class='nl-icon-btn' id='nl-share' title='Link kopieren' aria-label='Link kopieren'>" . nl_ico('share') . "</button>";
	echo "<span id='nl-share-note' class='nl-share-note' hidden>Link kopiert</span>";
	echo "</span></p>\n";
	echo "<h2>" . nl_h(nl_issue_public_label($current)) . "</h2>\n";
	$publicPerson = array('anredeArt' => 'Du', 'public' => true, 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
	echo nl_render_newsletter($current, $publicPerson, false);
} else {
	echo "<div class='nl-pub-years'>\n";
	foreach ($byYear as $year => $list) {
		echo "<section class='nl-pub-year'><h2>" . nl_h($year) . "</h2><ul>\n";
		foreach ($list as $issue) {
			echo "<li><a href='rkrk_newsletter_public.php?MenuOn=off&id=" . nl_h($issue['id']) . "'>" . nl_h(nl_issue_public_label($issue)) . "</a></li>\n";
		}
		echo "</ul></section>\n";
	}
	echo "</div>\n";
}
echo "</main>\n";
echo "<div id='nl-zoom' class='nl-zoom' hidden><img alt=''></div>\n";
echo "<script>\n";
echo "function nlOpenImg(ev){\n";
echo "  if (ev.target && ev.target.closest && ev.target.closest('#nl-zoom')) return;\n";
echo "  var img = ev.target && ev.target.closest ? ev.target.closest('.nl-mail-sec img, .nl-results img') : null;\n";
echo "  if (!img) return;\n";
echo "  var box = document.getElementById('nl-zoom');\n";
echo "  var big = box.querySelector('img');\n";
echo "  big.src = img.currentSrc || img.src;\n";
echo "  big.alt = img.alt || '';\n";
echo "  box.hidden = false;\n";
echo "}\n";
echo "document.addEventListener('click', nlOpenImg);\n";
echo "document.addEventListener('dblclick', nlOpenImg);\n";
echo "document.getElementById('nl-zoom').addEventListener('click', function(){ this.hidden = true; var im = this.querySelector('img'); if (im) im.removeAttribute('src'); });\n";
echo "document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape') { var box = document.getElementById('nl-zoom'); if (box) box.hidden = true; } });\n";
echo "var shareBtn = document.getElementById('nl-share');\n";
echo "if (shareBtn) shareBtn.addEventListener('click', function(ev){\n";
echo "  ev.preventDefault();\n";
echo "  ev.stopPropagation();\n";
echo "  var url = window.location.href.split('#')[0];\n";
echo "  var title = document.title;\n";
echo "  var note = document.getElementById('nl-share-note');\n";
echo "  var copied = function(){ if (note) note.hidden = false; shareBtn.title = 'Link kopiert'; setTimeout(function(){ if (note) note.hidden = true; shareBtn.title = 'Link kopieren'; }, 2000); };\n";
echo "  var copyFallback = function(){\n";
echo "    var box = document.createElement('textarea');\n";
echo "    box.value = url; box.setAttribute('readonly', ''); box.style.position = 'fixed'; box.style.left = '0'; box.style.top = '0'; box.style.opacity = '0';\n";
echo "    document.body.appendChild(box); box.focus(); box.select();\n";
echo "    var ok = false; try { ok = document.execCommand('copy'); } catch (e) {}\n";
echo "    box.parentNode.removeChild(box);\n";
echo "    if (ok) copied();\n";
echo "  };\n";
echo "  var copyLink = function(){\n";
echo "    if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(url).then(copied).catch(copyFallback); return; }\n";
echo "    copyFallback();\n";
echo "  };\n";
echo "  var touch = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;\n";
echo "  if (touch && navigator.share) { navigator.share({title: title, url: url}).catch(function(err){ if (!err || err.name !== 'AbortError') copyLink(); }); return; }\n";
echo "  copyLink();\n";
echo "});\n";
echo "</script>\n";
if ($menuOn) {
	footer();
}
