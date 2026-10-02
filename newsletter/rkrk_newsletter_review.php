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

$token = isset($_GET['t']) ? (string)$_GET['t'] : '';
$pack = nl_review_find($token);

headerpage('Vorabreview', false, $folderoffset);
GrantAccess();

echo "<link rel='stylesheet' href='rkrk_newsletter.css?v=20260925z20'>\n";
echo "<script>document.body.classList.add('nl-print-body');</script>\n";

if (!$pack) {
	echo "<main class='rkrk-shell-main nl-wrap'><p>Dieser Vorabreview-Link ist ungültig.</p></main>\n";
	exit;
}

$kind = (string)($pack['kind'] ?? '');
if ($kind === 'gemeinde') {
	$kampagnen = nl_items('newsletter_kampagnen.json');
	$camp = nl_find_item($kampagnen, (string)($pack['campaign'] ?? ''));
	echo "<main class='rkrk-shell-main nl-wrap nl-sheets'>\n";
	if (!$camp) {
		echo "<p>Dieser Vorabreview-Link ist ungültig.</p></main>\n";
		exit;
	}
	echo "<p class='nl-noprint'><button class='nl-btn' type='button' onclick='window.print()'>Drucken</button></p>\n";
	$want = nl_review_akey($pack['absender'] ?? '');
	$gemeinden = nl_items('newsletter_gemeinden.json');
	$stats = nl_flight_bundle($camp);
	$shown = 0;
	foreach ($gemeinden as $g) {
		if (!is_array($g) || empty($g['aktiv'])) {
			continue;
		}
		$row = nl_with_letter($camp, $g);
		if (nl_review_akey($row['absender'] ?? '') !== $want) {
			continue;
		}
		echo nl_render_letter($camp, $g, nl_stats_for_gemeinde($stats, $g));
		$shown++;
	}
	if ($shown === 0) {
		echo "<p>Für diesen Absender gibt es keine Briefe.</p>\n";
	}
	echo "</main>\n";
	if ($shown > 0) {
		nl_echo_sheet_pages_script();
	}
	exit;
}

if ($kind === 'newsletter') {
	$all = nl_items('newsletter_issues.json');
	$issues = array();
	if ((string)($pack['mode'] ?? '') === 'absender') {
		$want = nl_review_akey($pack['absender'] ?? '');
		foreach ($all as $issue) {
			if (!is_array($issue)) {
				continue;
			}
			if (nl_review_akey(nl_issue_explicit_absender($issue)) === $want) {
				$issues[] = $issue;
			}
		}
	} else {
		$ids = (isset($pack['ids']) && is_array($pack['ids'])) ? $pack['ids'] : array();
		foreach ($ids as $id) {
			$issue = nl_find_item($all, (string)$id);
			if ($issue) {
				$issues[] = $issue;
			}
		}
	}
	echo "<main class='nl-public'>\n";
	echo "<p class='nl-back nl-noprint'><button class='nl-btn' type='button' onclick='window.print()'>Drucken</button></p>\n";
	if (count($issues) === 0) {
		echo "<p>Für diesen Absender gibt es keine Ausgabe.</p></main>\n";
		exit;
	}
	$publicPerson = array('anredeArt' => 'Du', 'public' => true, 'geschlecht' => '', 'vorname' => '', 'name' => '', 'anrede' => '');
	foreach ($issues as $issue) {
		echo "<section class='nl-review-issue'>\n";
		echo "<h2>" . nl_h(nl_issue_public_label($issue)) . "</h2>\n";
		echo nl_render_newsletter($issue, $publicPerson);
		echo "</section>\n";
	}
	echo "</main>\n";
	echo "<script>window.addEventListener('load', function(){ window.print(); });</script>\n";
	exit;
}

echo "<main class='rkrk-shell-main nl-wrap'><p>Dieser Vorabreview-Link ist ungültig.</p></main>\n";
