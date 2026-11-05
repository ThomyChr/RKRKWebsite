<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
ini_set("default_charset", "UTF-8");
if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("UTF-8");
}
$folderoffset = "../";
include_once($folderoffset . "scripts/rkrk_inputforms.php");
include_once($folderoffset . "scripts/rkrk_csvfiles.php");
include_once("rkrk_assess_common.php");
include_once(__DIR__ . "/rkrk_assess_exam_session.inc.php");
include_once(__DIR__ . "/rkrk_digitalassess_question_stats.inc.php");

if (!$onRKRS) {
	CheckIPandExit($folderoffset);
	WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", $folderoffset);
	GrantAccess($folderoffset);
	if (empty($LoggedOn)) {
		if (function_exists("RegisterForm")) {
			RegisterForm(getIPAddress(), "Login", true);
		}
		exit;
	}
	$currentAccessLevel = "";
	if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
		$currentAccessLevel = strtolower(trim((string) $_SESSION[$SessNme_AccessLevel]));
	} elseif (isset($InputUserInfo["Login"]["Level"])) {
		$currentAccessLevel = strtolower(trim((string) $InputUserInfo["Login"]["Level"]));
	}
	$isGuestRequest = (isset($_GET["GA"]) && strtolower(trim((string) $_GET["GA"])) === "gast");
	$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== "gast" && !$onRKRS)
		&& !(function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode());
} 

function dqs_h($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function dqs_pct_label($pct) {
	if ($pct === null) {
		return "—";
	}
	return number_format((float) $pct, 1, ",", "") . " %";
}

function dqs_tone($pct) {
	if ($pct === null) {
		return "muted";
	}
	if ((float) $pct >= 70) {
		return "good";
	}
	if ((float) $pct >= 50) {
		return "mid";
	}
	return "bad";
}

$dqsQuiz = isset($_GET["quiz"]) ? trim((string) $_GET["quiz"]) : "";
if ($dqsQuiz === "" && isset($_GET["selected_quiz"])) {
	$dqsQuiz = trim((string) $_GET["selected_quiz"]);
}
$dqsScope = isset($_GET["scope"]) ? (string) $_GET["scope"] : "pruefung";
$dqsMin = isset($_GET["min"]) ? (int) $_GET["min"] : 1;
$dqsSort = isset($_GET["sort"]) ? (string) $_GET["sort"] : "schwer";
$dqsFokus = isset($_GET["fokus"]) ? (string) $_GET["fokus"] : "alle";
$dqsAntwortRaw = isset($_GET["antwort"]) ? trim((string) $_GET["antwort"]) : "";
		$dqsProsa = $dqsAntwortRaw !== "" && $dqsAntwortRaw !== "0";
$dqsKritischRaw = isset($_GET["kritisch"]) ? trim((string) $_GET["kritisch"]) : "";
$dqsKritisch = $dqsKritischRaw !== "" && $dqsKritischRaw !== "0";
$dqsView = rkrk_dqs_build(array(
	"quiz" => $dqsQuiz,
	"scope" => $dqsScope,
	"min" => $dqsMin,
	"sort" => $dqsSort,
	"fokus" => $dqsFokus,
	"prosa" => $dqsProsa,
	"pdf_kritisch" => $dqsKritisch,
	"refresh" => isset($_GET["refresh"]),
));
$dqsPdfError = false;
if (isset($_GET["export"]) && strtolower(trim((string) $_GET["export"])) === "pdf") {
	if (!rkrk_dqs_send_pdf($dqsView)) {
		$dqsPdfError = true;
	}
}

if (!headers_sent()) {
	header("Content-Type: text/html; charset=UTF-8");
}
if (!$onRKRS) {
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	if ($showTopMenu) {
		CreateTopMenu($folderoffset);
		echo "<br><br>";
	}
} else {
	rkrk_assess_theme_link_tags();
}

headerpage("Fragenauswertung", false, $folderoffset);
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}

$dqsBack = function_exists("rkrk_assess_append_setting_href")
	? rkrk_assess_append_setting_href("rkrk_digitalassess_admin.php?" . http_build_query(function_exists("rkrk_assess_merge_query_with_setting") ? rkrk_assess_merge_query_with_setting(array("selected_quiz" => $dqsQuiz)) : array("selected_quiz" => $dqsQuiz)))
	: "rkrk_digitalassess_admin.php?selected_quiz=" . rawurlencode($dqsQuiz);

$dqsSelf = function (array $over) use ($dqsView) {
	$q = array(
		"quiz" => (string) $dqsView["quiz"],
		"scope" => (string) $dqsView["scope"],
		"min" => (int) $dqsView["min"],
		"sort" => (string) $dqsView["sort"],
		"fokus" => (string) $dqsView["fokus"],
	);
	if (!empty($dqsView["prosa"])) {
		$q["antwort"] = "1";
	}
	if (!empty($dqsView["pdf_kritisch"])) {
		$q["kritisch"] = "1";
	}
	foreach ($over as $k => $v) {
		$q[$k] = $v;
	}
	$base = "rkrk_digitalassess_question_stats.php?" . http_build_query($q);
	if (function_exists("rkrk_assess_merge_query_with_setting") && function_exists("rkrk_assess_append_setting_href")) {
		return rkrk_assess_append_setting_href($base);
	}
	return $base;
};

$dqsScopeLabel = array(
	"pruefung" => "Prüfungen",
	"schulung" => "Schulungen",
	"alle" => "Prüfungen und Schulungen",
);
?>
<style>
.dqs-wrap { margin: 0 10px 28px; font-size: 15px; line-height: 1.45; color: var(--text, #1c2416); }
.dqs-wrap * { box-sizing: border-box; }
.dqs-head { display: flex; flex-wrap: wrap; gap: 12px 18px; align-items: flex-end; justify-content: space-between; margin-bottom: 12px; }
.dqs-title { margin: 0; font-size: 1.45rem; line-height: 1.2; }
.dqs-lead { margin: 4px 0 0; max-width: 46rem; color: var(--text, #1c2416); opacity: .8; font-size: 14px; }
.dqs-card { background: var(--card, #fff); border: 1px solid var(--border, #d5e0cf); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
.dqs-filters { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: flex-end; }
.dqs-filters label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; font-weight: 700; }
.dqs-filters select, .dqs-filters input[type="number"], .dqs-filters input[type="search"] {
	font: inherit; font-weight: 500; font-size: 14px; padding: 7px 8px; border-radius: 8px;
	border: 1px solid #7aad76; background: #fff; min-width: 0;
}
.dqs-filters input[type="search"] { min-width: min(100%, 220px); }
.dqs-btn, .dqs-chip {
	display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 7px 12px;
	font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid var(--border, #d5e0cf);
	background: var(--kpi-bg, #f4f7f3); color: inherit; cursor: pointer;
}
.dqs-btn-solid { background: var(--brand, #4a8c34); color: #fff; border-color: transparent; }
.dqs-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
@media (max-width: 800px) { .dqs-kpis { grid-template-columns: 1fr 1fr; } }
.dqs-kpi { display: block; padding: 10px 12px; border-radius: 10px; text-decoration: none; color: inherit; border: 1px solid var(--border, #d5e0cf); background: var(--kpi-bg, #f7faf6); }
.dqs-kpi strong { display: block; font-size: 1.45rem; line-height: 1.1; }
.dqs-kpi span { font-size: 12px; opacity: .8; }
.dqs-kpi.is-on { outline: 2px solid var(--brand, #4a8c34); }
.dqs-kpi.bad strong { color: #9f1239; }
.dqs-kpi.good strong { color: #166534; }
.dqs-sec-title { margin: 0 0 8px; font-size: 1.05rem; }
.dqs-grid { display: grid; gap: 10px; }
.dqs-q {
	border: 1px solid var(--border, #d5e0cf); border-left: 4px solid #9f1239; border-radius: 10px;
	padding: 10px 12px; background: #fff;
}
.dqs-q-top { display: flex; flex-wrap: wrap; gap: 8px; align-items: baseline; }
.dqs-badge { font-weight: 800; font-variant-numeric: tabular-nums; }
.dqs-badge.bad { color: #9f1239; }
.dqs-badge.mid { color: #a16207; }
.dqs-badge.good { color: #166534; }
.dqs-badge.muted { color: #64748b; }
.dqs-q h3 { margin: 6px 0; font-size: 1rem; font-weight: 650; }
.dqs-meta { font-size: 12px; opacity: .75; }
.dqs-note { margin: 6px 0 0; font-size: 13px; color: #9a3412; }
.dqs-ans { margin: 8px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 4px; }
.dqs-ans li { display: flex; gap: 8px; align-items: baseline; font-size: 14px; border-radius: 8px; padding: 4px 8px; }
.dqs-ans li.ok { background: #e8f6df; color: #14532d; }
.dqs-ans li.bad { background: #fde8e8; color: #9f1239; }
.dqs-ans li.mid { background: #fef3c7; color: #854d0e; }
.dqs-ans li.muted { background: #f1f5f9; color: #475569; }
.dqs-sol { margin: 6px 0 0; padding: 6px 8px; border-radius: 8px; background: #e8f6df; color: #14532d; }
.dqs-mark { display: inline; padding: 1px 6px; border-radius: 6px; }
.dqs-mark.bad { background: #fde8e8; color: #9f1239; }
.dqs-num-ok { color: #166534; font-weight: 800; }
.dqs-num-bad { color: #9f1239; font-weight: 800; }
.dqs-num-mid { color: #a16207; font-weight: 800; }
.dqs-ans b { flex: 0 0 auto; font-variant-numeric: tabular-nums; min-width: 2.2rem; }
.dqs-tag { font-size: 11px; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; color: #9f1239; }
.dqs-tag.miss { color: #9a3412; }
.dqs-tag.ok { color: #166534; }
.dqs-bar { height: 8px; border-radius: 99px; background: #e8eee6; overflow: hidden; min-width: 72px; flex: 1; }
.dqs-bar > i { display: block; height: 100%; background: var(--brand, #4a8c34); }
.dqs-bar.bad > i { background: #be123c; }
.dqs-bar.mid > i { background: #ca8a04; }
.dqs-table-wrap { overflow: auto; max-height: 70vh; border: 1px solid var(--border, #d5e0cf); border-radius: 10px; }
.dqs-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.dqs-table th, .dqs-table td { padding: 8px 10px; border-bottom: 1px solid #e5ece3; vertical-align: top; text-align: left; }
.dqs-table th { position: sticky; top: 0; background: #f4f7f3; font-size: 12px; }
.dqs-table td.num, .dqs-table th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.dqs-find-hide { display: none !important; }
.dqs-prose { margin: 4px 0 0; font-size: 13px; line-height: 1.4; }
.dqs-check { flex-direction: row; align-items: center; gap: 6px; font-size: 13px; padding-bottom: 7px; }
.dqs-check input { width: 16px; height: 16px; margin: 0; }
.dqs-easy { margin: 0; padding-left: 1.1rem; }
.dqs-easy li { margin: 3px 0; }
details.dqs-card > summary { cursor: pointer; font-weight: 750; }
</style>
	<div class="dqs-wrap">
	<?php if (!empty($dqsPdfError)) { ?>
		<div class="dqs-card" style="border-color:#e3abab;background:#fde9e9;">PDF konnte nicht erzeugt werden.<?php if (!empty($GLOBALS["rkrk_dqs_pdf_error"])) { echo " " . dqs_h((string) $GLOBALS["rkrk_dqs_pdf_error"]); } ?></div>
	<?php } ?>
	<div class="dqs-head">
		<div>
			<h1 class="dqs-title">Fragenauswertung</h1>
			<p class="dqs-lead">Wie oft eine Frage in abgeschlossenen <?php echo dqs_h($dqsScopeLabel[$dqsView["scope"]] ?? "Prüfungen"); ?> vorkam, wie oft sie voll richtig war, und welche Antworten bei den schwierigen Fragen gewählt wurden. Teilpunkte zählen nicht als richtig.</p>
		</div>
		<a class="dqs-btn" href="<?php echo dqs_h($dqsBack); ?>">Zurück zum Fragen-Manager</a>
	</div>

	<form class="dqs-card dqs-filters" method="get">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<label>Assessment
			<select name="quiz">
				<option value="">Alle</option>
				<?php foreach ($dqsView["quiz_options"] as $qk => $qt) { ?>
					<option value="<?php echo dqs_h($qk); ?>"<?php echo (strcasecmp((string) $dqsView["quiz"], (string) $qk) === 0 ? " selected" : ""); ?>><?php echo dqs_h($qk . " — " . $qt); ?></option>
				<?php } ?>
			</select>
		</label>
		<label>Quelle
			<select name="scope">
				<option value="pruefung"<?php echo $dqsView["scope"] === "pruefung" ? " selected" : ""; ?>>Nur Prüfungen</option>
				<option value="schulung"<?php echo $dqsView["scope"] === "schulung" ? " selected" : ""; ?>>Nur Schulungen</option>
				<option value="alle"<?php echo $dqsView["scope"] === "alle" ? " selected" : ""; ?>>Beides</option>
			</select>
		</label>
		<label>Mindestens
			<input type="number" name="min" min="1" max="100" value="<?php echo dqs_h((string) $dqsView["min"]); ?>">
		</label>
		<label>Sortierung
			<select name="sort">
				<option value="schwer"<?php echo $dqsView["sort"] === "schwer" ? " selected" : ""; ?>>Schwierigste zuerst</option>
				<option value="haeufig"<?php echo $dqsView["sort"] === "haeufig" ? " selected" : ""; ?>>Häufigste zuerst</option>
				<option value="leicht"<?php echo $dqsView["sort"] === "leicht" ? " selected" : ""; ?>>Beste Quote zuerst</option>
				<option value="name"<?php echo $dqsView["sort"] === "name" ? " selected" : ""; ?>>Fragetext</option>
			</select>
		</label>
		<input type="hidden" name="fokus" value="<?php echo dqs_h($dqsView["fokus"]); ?>">
		<label class="dqs-check" title="Lösungstext, sonst der kurze Antworttext aus dem Katalog">
			<input type="checkbox" name="antwort" value="1"<?php echo !empty($dqsView["prosa"]) ? " checked" : ""; ?> onchange="this.form.submit()">
			Antworttext
		</label>
		<label class="dqs-check" title="PDF ohne die volle Fragenliste, nur Übersicht und mehrheitlich falsche Fragen">
			<input type="checkbox" name="kritisch" value="1"<?php echo !empty($dqsView["pdf_kritisch"]) ? " checked" : ""; ?> onchange="this.form.submit()">
			Nur kritische Fragen
		</label>
		<label>Suche
			<input type="search" id="dqs-find" placeholder="Frage, Bereich, Antwort" autocomplete="off">
		</label>
		<button class="dqs-btn dqs-btn-solid" type="submit">Anzeigen</button>
		<a class="dqs-btn" href="<?php echo dqs_h($dqsSelf(array("export" => "pdf"))); ?>">PDF</a>
		<a class="dqs-btn" href="<?php echo dqs_h($dqsSelf(array("refresh" => "1"))); ?>">Neu berechnen</a>
	</form>

	<div class="dqs-kpis">
		<a class="dqs-kpi<?php echo $dqsView["fokus"] === "alle" ? " is-on" : ""; ?>" href="<?php echo dqs_h($dqsSelf(array("fokus" => "alle"))); ?>">
			<strong><?php echo dqs_h((string) $dqsView["attempts_detail"]); ?></strong>
			<span>Versuche mit Einzelfragen<?php if ($dqsView["attempts"] > $dqsView["attempts_detail"]) { echo " von " . dqs_h((string) $dqsView["attempts"]); } ?></span>
		</a>
		<a class="dqs-kpi bad<?php echo $dqsView["fokus"] === "schwer" ? " is-on" : ""; ?>" href="<?php echo dqs_h($dqsSelf(array("fokus" => "schwer"))); ?>">
			<strong><?php echo dqs_h((string) $dqsView["counts"]["schwer"]); ?></strong>
			<span>Mehrheitlich falsch</span>
		</a>
		<a class="dqs-kpi good<?php echo $dqsView["fokus"] === "leicht" ? " is-on" : ""; ?>" href="<?php echo dqs_h($dqsSelf(array("fokus" => "leicht"))); ?>">
			<strong><?php echo dqs_h((string) $dqsView["counts"]["leicht"]); ?></strong>
			<span>Mehrheitlich richtig</span>
		</a>
		<a class="dqs-kpi<?php echo $dqsView["fokus"] === "wenig" ? " is-on" : ""; ?>" href="<?php echo dqs_h($dqsSelf(array("fokus" => "wenig"))); ?>">
			<strong><?php echo dqs_h((string) ((int) $dqsView["counts"]["wenig"] + (int) $dqsView["counts"]["nie"])); ?></strong>
			<span>Unter <?php echo dqs_h((string) $dqsView["min"]); ?> Abfragen oder noch nie</span>
		</a>
	</div>

	<?php if ($dqsView["fokus"] === "alle" || $dqsView["fokus"] === "schwer") { ?>
	<section class="dqs-card">
		<h2 class="dqs-sec-title">Schwierige Fragen</h2>
		<p class="dqs-meta" style="margin-top:0;">Voll richtig in weniger als der Hälfte der Abfragen, mindestens <?php echo dqs_h((string) $dqsView["min"]); ?> mal gestellt. <span class="dqs-num-ok">Grün</span> ist die richtige Lösung, <span class="dqs-num-bad">Rot</span> die falsche Wahl.</p>
		<?php if (count($dqsView["hard"]) === 0) { ?>
			<p>Keine Frage fällt in diese Gruppe.</p>
		<?php } else { ?>
			<div class="dqs-grid">
			<?php foreach ($dqsView["hard"] as $row) {
				$tone = dqs_tone($row["pct"]);
				$find = rkrk_dqs_fold($row["frage"] . " " . $row["bereich"] . " " . $row["quiz_key"] . " " . $row["top_wrong"] . " " . ($row["antworttext"] ?? ""));
				?>
				<article class="dqs-q" data-dqs-find="<?php echo dqs_h($find); ?>">
					<div class="dqs-q-top">
						<span class="dqs-badge <?php echo dqs_h($tone); ?>"><?php echo dqs_h(dqs_pct_label($row["pct"])); ?> richtig</span>
						<span class="dqs-meta"><?php echo dqs_h((string) $row["full"]); ?>/<?php echo dqs_h((string) $row["asked"]); ?> · <?php echo dqs_h($row["quiz_key"]); ?> · ID <?php echo dqs_h($row["nr"]); ?><?php if ($row["bereich"] !== "") { echo " · " . dqs_h($row["bereich"]); } ?></span>
					</div>
					<h3><?php echo dqs_h($row["frage"]); ?></h3>
					<?php if (!empty($row["drift"])) { ?>
						<p class="dqs-note">Der Katalogtext an dieser ID weicht ab: <?php echo dqs_h($row["catalog_frage"]); ?></p>
					<?php } elseif (!$row["in_catalog"] && $dqsView["quiz"] !== "") { ?>
						<p class="dqs-note">Diese ID steht im aktuellen Fragenkatalog nicht mehr.</p>
					<?php } ?>
					<?php if ($row["loesung"] !== "") { ?>
						<p class="dqs-sol"><b>Richtig:</b> <?php echo dqs_h($row["loesung"]); ?></p>
					<?php } ?>
					<?php if (!empty($dqsView["prosa"]) && trim((string) ($row["antworttext"] ?? "")) !== "") { ?>
						<p class="dqs-prose"><b>Antworttext:</b> <?php echo dqs_h($row["antworttext"]); ?></p>
					<?php } ?>
					<?php if (count($row["patterns"]) > 0) { ?>
						<ul class="dqs-ans">
						<?php foreach ($row["patterns"] as $pat) {
							$patTone = rkrk_dqs_pick_tone((string) $pat["text"]);
							?>
							<li class="<?php echo dqs_h($patTone); ?>"><b><?php echo dqs_h((string) $pat["n"]); ?>×</b><span><?php echo dqs_h((string) $pat["text"]); ?></span></li>
						<?php } ?>
						</ul>
					<?php } ?>
					<?php
					$trouble = array();
					foreach ($row["options"] as $opt) {
						$off = (int) $opt["offered"];
						if ($off < 1) {
							continue;
						}
						$wrongRate = ((int) $opt["picked_wrong"]) / $off;
						$missRate = ((int) $opt["as_correct"]) > 0 ? ((int) $opt["missed"]) / (int) $opt["as_correct"] : 0;
						if ($wrongRate >= 0.2 || $missRate >= 0.35) {
							$opt["_missRate"] = $missRate;
							$trouble[] = $opt;
						}
					}
					$trouble = array_slice($trouble, 0, 4);
					if (count($trouble) > 0) { ?>
						<ul class="dqs-ans">
						<?php foreach ($trouble as $opt) {
							$asCorrect = (int) $opt["as_correct"] >= (int) $opt["offered"] / 2;
							?>
							<li class="<?php echo $asCorrect ? "ok" : "bad"; ?>">
								<?php if ($asCorrect) { ?>
									<span class="dqs-tag ok">richtig, oft verpasst</span>
								<?php } else { ?>
									<span class="dqs-tag">falsch</span>
								<?php } ?>
								<span><?php echo dqs_h($opt["text"]); ?> — <?php echo dqs_h((string) $opt["picked"]); ?> von <?php echo dqs_h((string) $opt["offered"]); ?> gewählt<?php if ($asCorrect) { echo ", " . dqs_h((string) $opt["missed"]) . "× nicht gewählt"; } ?></span>
							</li>
						<?php } ?>
						</ul>
					<?php } ?>
				</article>
			<?php } ?>
			</div>
		<?php } ?>
	</section>
	<?php } ?>

	<?php if (($dqsView["fokus"] === "alle" || $dqsView["fokus"] === "leicht") && count($dqsView["easy"]) > 0) { ?>
	<details class="dqs-card"<?php echo $dqsView["fokus"] === "leicht" ? " open" : ""; ?>>
		<summary>Mehrheitlich richtig (<?php echo dqs_h((string) count($dqsView["easy"])); ?>)</summary>
		<ul class="dqs-easy">
			<?php foreach ($dqsView["easy"] as $row) { ?>
				<li data-dqs-find="<?php echo dqs_h(rkrk_dqs_fold($row["frage"] . " " . $row["bereich"] . " " . ($row["antworttext"] ?? ""))); ?>">
					<strong class="dqs-badge good"><?php echo dqs_h(dqs_pct_label($row["pct"])); ?></strong>
					<?php echo dqs_h($row["frage"]); ?>
					<span class="dqs-meta"><?php echo dqs_h((string) $row["full"]); ?>/<?php echo dqs_h((string) $row["asked"]); ?> · <?php echo dqs_h($row["quiz_key"]); ?></span>
					<?php if (!empty($dqsView["prosa"]) && trim((string) ($row["antworttext"] ?? "")) !== "") { ?>
						<div class="dqs-prose"><b>Antworttext:</b> <?php echo dqs_h($row["antworttext"]); ?></div>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</details>
	<?php } ?>

	<section class="dqs-card">
		<h2 class="dqs-sec-title">Alle Fragen in dieser Sicht</h2>
		<div class="dqs-table-wrap">
			<table class="dqs-table">
				<thead>
					<tr>
						<th>Frage</th>
						<th>Quiz</th>
						<th class="num">Abgefragt</th>
						<th class="num">Richtig</th>
						<th class="num">Teilweise</th>
						<th class="num">Falsch</th>
						<th>Quote</th>
						<th>Häufigste Fehlantwort</th>
					</tr>
				</thead>
				<tbody>
				<?php if (count($dqsView["rows"]) === 0) { ?>
					<tr><td colspan="8">Keine Fragen für diese Auswahl.</td></tr>
				<?php } ?>
				<?php foreach ($dqsView["rows"] as $row) {
					$tone = dqs_tone($row["pct"]);
					$bar = $row["pct"] === null ? 0 : min(100, max(0, (float) $row["pct"]));
					$falsch = (int) $row["wrong"] + (int) $row["blank"];
					?>
					<tr data-dqs-find="<?php echo dqs_h(rkrk_dqs_fold($row["frage"] . " " . $row["bereich"] . " " . $row["quiz_key"] . " " . $row["top_wrong"] . " " . $row["nr"] . " " . ($row["antworttext"] ?? ""))); ?>">
						<td>
							<?php echo dqs_h($row["frage"]); ?>
							<div class="dqs-meta">ID <?php echo dqs_h($row["nr"]); ?><?php if ($row["bereich"] !== "") { echo " · " . dqs_h($row["bereich"]); } ?></div>
							<?php if (!empty($dqsView["prosa"]) && trim((string) ($row["antworttext"] ?? "")) !== "") { ?>
								<div class="dqs-prose"><b>Antworttext:</b> <?php echo dqs_h($row["antworttext"]); ?></div>
							<?php } ?>
						</td>
						<td><?php echo dqs_h($row["quiz_key"]); ?></td>
						<td class="num"><?php echo dqs_h((string) $row["asked"]); ?></td>
						<td class="num<?php echo (int) $row["full"] > 0 ? " dqs-num-ok" : ""; ?>"><?php echo dqs_h((string) $row["full"]); ?></td>
						<td class="num<?php echo (int) $row["partial"] > 0 ? " dqs-num-mid" : ""; ?>"><?php echo dqs_h((string) $row["partial"]); ?></td>
						<td class="num<?php echo $falsch > 0 ? " dqs-num-bad" : ""; ?>"><?php echo dqs_h((string) $falsch); ?></td>
						<td>
							<span class="dqs-badge <?php echo dqs_h($tone); ?>"><?php echo dqs_h(dqs_pct_label($row["pct"])); ?></span>
							<div class="dqs-bar <?php echo dqs_h($tone); ?>"><i style="width:<?php echo dqs_h((string) $bar); ?>%"></i></div>
						</td>
						<td><?php
							if ($row["top_wrong"] !== "") {
								echo "<span class='dqs-mark bad'>" . dqs_h($row["top_wrong"]) . "</span> <span class='dqs-meta'>(" . dqs_h((string) $row["top_wrong_n"]) . "×)</span>";
							} else {
								echo "—";
							}
						?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<p class="dqs-meta"><?php echo dqs_h((string) $dqsView["files"]); ?> Exam-Dateien<?php if ($dqsView["from_cache"]) { echo ", aus Zwischenspeicher"; } ?>. Stand <?php echo dqs_h($dqsView["built_at"]); ?>.</p>
	</section>
</div>
<script>
(function () {
	var input = document.getElementById("dqs-find");
	if (!input) return;
	input.addEventListener("input", function () {
		var q = String(input.value || "").toLowerCase().trim();
		document.querySelectorAll("[data-dqs-find]").forEach(function (el) {
			var hay = String(el.getAttribute("data-dqs-find") || "");
			el.classList.toggle("dqs-find-hide", q !== "" && hay.indexOf(q) === -1);
		});
	});
})();
</script>
<?php
