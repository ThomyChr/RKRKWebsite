<?php
/**
 * Filterbare Trainingsübersicht: Theorie/Quiz-Status je Liste (Piloten + EXAM-Codes).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_inputforms.php";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
include_once __DIR__ . "/rkrk_assess_exam_session.inc.php";
include_once __DIR__ . "/rkrk_assess_einsatzstatus.inc.php";
include_once __DIR__ . "/rkrk_assess_trainingstatus.inc.php";
include_once __DIR__ . "/rkrk_assess_trainingstatus_export.inc.php";

if (function_exists("rkrk_assess_portal_personel_enforce_access")) {
	rkrk_assess_portal_personel_enforce_access();
}
if (function_exists("ProtectFromAccess")) {
	CheckIPandExit($folderoffset);
}
GrantAccess($folderoffset);

$__acc = function_exists("rkrk_assess_portal_personel_bootstrap_access")
	? rkrk_assess_portal_personel_bootstrap_access()
	: array("portal" => false, "cs_only" => false);
if (empty($LoggedOn)) {
	if (!empty($__acc["portal"])
		&& function_exists("rkrk_assess_hash_gate_skip_cs_check")
		&& !rkrk_assess_hash_gate_skip_cs_check()
		&& function_exists("rkrk_assess_portal_personel_emit_access_denied")
	) {
		rkrk_assess_portal_personel_emit_access_denied();
	}
	if (function_exists("WriteToLog")) {
		WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", $folderoffset);
	}
	rkrk_assess_theme_link_tags();
	rkrk_assess_emit_setting_carry_script();
	headerpage(function_exists("rkrk_assess_copyright_holder") ? rkrk_assess_copyright_holder() : "Drohnenrehkitzrettung", false, $folderoffset);
	if (function_exists("RegisterForm")) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

$canEdit = empty($__acc["cs_only"]);
$trCfg = rkrk_tr_config_load();

if ($_SERVER["REQUEST_METHOD"] === "POST" && $canEdit && isset($_POST["tr_cfg_save"])) {
	$posted = isset($_POST["lists"]) && is_array($_POST["lists"]) ? $_POST["lists"] : array();
	$newName = trim((string) ($_POST["new_list_name"] ?? ""));
	if ($newName !== "") {
		$maxOrd = 0;
		foreach ($posted as $p) {
			if (is_array($p) && (int) ($p["ord"] ?? 0) > $maxOrd) {
				$maxOrd = (int) $p["ord"];
			}
		}
		$posted[] = array(
			"name" => $newName,
			"exam_codes" => array(),
			"pilot_ids" => array(),
			"ord" => $maxOrd + 1,
		);
	}
	$ok = rkrk_tr_config_save(array("lists" => $posted));
	header("Location: " . rkrk_tr_page_href($ok ? array("cfg" => "1", "ok" => "1") : array("cfg" => "1", "err" => "1")), true, 303);
	exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $canEdit && (isset($_POST["tr_row_save"]) || isset($_POST["tr_remark_save"]))) {
	$listSave = trim((string) ($_POST["list"] ?? ""));
	$pilotSave = trim((string) ($_POST["pilot_id"] ?? ""));
	$disabled = true;
	$rawActive = $_POST["active"] ?? "0";
	if (is_array($rawActive)) {
		$rawActive = end($rawActive);
	}
	if ((string) $rawActive === "1") {
		$disabled = false;
	}
	$ok = rkrk_tr_save_row($listSave, $pilotSave, (string) ($_POST["remark"] ?? ""), $disabled);
	$q = $ok ? array("ok" => "1") : array("err" => "1");
	if ($listSave !== "") {
		$q["list"] = $listSave;
	}
	$sm = strtolower(trim((string) ($_POST["sort"] ?? "nach")));
	$q["sort"] = ($sm === "vor") ? "vor" : "nach";
	if (isset($_POST["q"]) && trim((string) $_POST["q"]) !== "") {
		$q["q"] = trim((string) $_POST["q"]);
	}
	if (isset($_POST["status"]) && trim((string) $_POST["status"]) !== "") {
		$q["status"] = trim((string) $_POST["status"]);
	}
	header("Location: " . rkrk_tr_page_href($q), true, 303);
	exit;
}

$lists = isset($trCfg["lists"]) && is_array($trCfg["lists"]) ? $trCfg["lists"] : array();
$listId = trim((string) ($_GET["list"] ?? ""));
$active = null;
if ($listId !== "") {
	$active = rkrk_tr_list_by_id($listId, $trCfg);
}
if ($active === null && $lists !== array() && is_array($lists[0])) {
	$active = $lists[0];
	$listId = (string) ($active["id"] ?? "");
}

$filterQ = trim((string) ($_GET["q"] ?? ""));
$filterStatus = trim((string) ($_GET["status"] ?? ""));
$sortMode = strtolower(trim((string) ($_GET["sort"] ?? "nach")));
if ($sortMode !== "vor") {
	$sortMode = "nach";
}
$showCfg = isset($_GET["cfg"]) && (string) $_GET["cfg"] !== "";

$rows = array();
$codes = array();
if (!$showCfg && is_array($active)) {
	$codes = rkrk_tr_parse_codes($active["exam_codes"] ?? array());
	$rows = rkrk_tr_sort_rows(rkrk_tr_build_rows($active), $sortMode);
}

$visible = array();
foreach ($rows as $r) {
	if ($filterQ !== "") {
		$hay = rkrk_es_norm_name($r["name"] . " " . $r["rkrs_id"] . " " . $r["pilot_id"] . " " . (string) ($r["remark"] ?? ""));
		if (strpos($hay, rkrk_es_norm_name($filterQ)) === false) {
			continue;
		}
	}
	if ($filterStatus !== "" && (string) $r["status"] !== $filterStatus) {
		continue;
	}
	$visible[] = $r;
}

$nAll = count($rows);
$nOk = 0;
$nFail = 0;
$nOpen = 0;
$nOff = 0;
foreach ($rows as $r) {
	if (!empty($r["disabled"])) {
		$nOff++;
		continue;
	}
	if ((string) $r["status"] === "ok") {
		$nOk++;
	} elseif ((string) $r["status"] === "fail") {
		$nFail++;
	} else {
		$nOpen++;
	}
}

$exportKind = strtolower(trim((string) ($_GET["export"] ?? "")));
if (!$showCfg && $exportKind !== "" && in_array($exportKind, array("pdf", "xlsx", "excel", "personel", "personal"), true)) {
	$okExp = function_exists("rkrk_tr_export") && rkrk_tr_export($exportKind, $active, $visible, $codes);
	if (!$okExp) {
		$q = array("err" => "export");
		if ($listId !== "") {
			$q["list"] = $listId;
		}
		$q["sort"] = $sortMode;
		header("Location: " . rkrk_tr_page_href($q), true, 303);
		exit;
	}
}

$pagetitle = "Teilnehmerliste";
if (is_array($active) && !$showCfg) {
	$pagetitle .= " — " . (string) ($active["name"] ?? "");
}
rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
headerpage($pagetitle, false, $folderoffset);
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
if (function_exists("rkrk_assess_emit_admin_url_managers_menu_strip")) {
	rkrk_assess_emit_admin_url_managers_menu_strip();
}
if (function_exists("rkrk_assess_portal_nav_echo")) {
	rkrk_assess_portal_nav_echo(array(
		"is_admin" => isset($_GET["ADMIN"]) || !empty($onAdmin),
		"is_instr" => !empty($onInstr),
	));
}
$trQuery = array();
if ($listId !== "") {
	$trQuery["list"] = $listId;
}
$trQuery["sort"] = $sortMode;
$self = rkrk_tr_page_href($trQuery);
$cfgHref = rkrk_tr_page_href(array("cfg" => "1", "sort" => $sortMode));
$sortNachHref = rkrk_tr_page_href(array_merge($trQuery, array("q" => $filterQ, "status" => $filterStatus, "sort" => "nach")));
$sortVorHref = rkrk_tr_page_href(array_merge($trQuery, array("q" => $filterQ, "status" => $filterStatus, "sort" => "vor")));
$expQ = array_merge($trQuery, array("q" => $filterQ, "status" => $filterStatus, "sort" => $sortMode));
$pdfHref = rkrk_tr_page_href(array_merge($expQ, array("export" => "pdf")));
$xlsxHref = rkrk_tr_page_href(array_merge($expQ, array("export" => "xlsx")));
$personelHref = rkrk_tr_page_href(array_merge($expQ, array("export" => "personel")));
$mailVisible = array();
foreach ($visible as $vr) {
	if (is_array($vr) && empty($vr["disabled"])) {
		$mailVisible[] = $vr;
	}
}
$mailHref = function_exists("rkrk_tr_mailto_href") ? rkrk_tr_mailto_href($mailVisible, is_array($active) ? (string) ($active["name"] ?? "") : "") : "";
$mailCount = function_exists("rkrk_tr_collect_emails") ? count(rkrk_tr_collect_emails($mailVisible)) : 0;
?>
<style>
.es-wrap{max-width:1180px;margin:0 auto;padding:8px 10px 20px;font-family:Arial,Helvetica,sans-serif;color:#1f2937;font-size:15px;}
.es-head{display:flex;flex-wrap:wrap;align-items:baseline;gap:8px 14px;margin:0 0 8px;}
.es-wrap h1{margin:0;color:#003366;font-size:1.3rem;white-space:nowrap;}
.es-stats{display:flex;flex-wrap:wrap;gap:6px 12px;font-size:14px;color:#475569;white-space:nowrap;}
.es-stats b{color:#003366;}
.es-filters{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:0 0 8px;}
.es-filters input,.es-filters select{padding:5px 7px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;font-size:14px;height:32px;}
.es-filters input[type=text]{width:12rem;}
.es-lists{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 8px;}
.es-ico{width:18px;height:18px;min-width:18px;min-height:18px;display:block;flex-shrink:0;fill:none;stroke:currentColor;}
.es-wrap a.es-ibtn,.es-wrap button.es-ibtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;min-width:32px;padding:0;border:1px solid #9ab08f;border-radius:7px;background:#48673a;color:#fff;cursor:pointer;text-decoration:none;box-sizing:border-box;overflow:visible;}
.es-wrap a.es-ibtn:hover,.es-wrap button.es-ibtn:hover{background:#3a542e;color:#fff;}
.es-wrap a.es-ibtn.ghost,.es-wrap button.es-ibtn.ghost{background:#fff;color:#334155;border-color:#cbd5e1;}
.es-wrap a.es-ibtn.ghost:hover,.es-wrap button.es-ibtn.ghost:hover{background:#f1f5f9;color:#0f172a;}
.es-wrap a.es-ibtn.danger,.es-wrap button.es-ibtn.danger{background:#fff;color:#b91c1c;border-color:#e8c8c8;}
.es-wrap a.es-ibtn.es-fbtn,.es-wrap button.es-ibtn.es-fbtn{width:auto;min-width:0;padding:0 8px;gap:5px;font-size:14px;font-weight:700;height:32px;white-space:nowrap;color:#166534;background:#d9f2c9;border-color:#86efac;}
.es-wrap a.es-ibtn.es-fbtn:hover,.es-wrap button.es-ibtn.es-fbtn:hover{background:#c3e8ae;color:#166534;}
.es-wrap a.es-ibtn.es-fbtn.on,.es-wrap button.es-ibtn.es-fbtn.on{background:#166534;border-color:#166534;color:#fff;}
.es-wrap a.es-ibtn.es-fbtn.on:hover,.es-wrap button.es-ibtn.es-fbtn.on:hover{background:#14532d;color:#fff;}
.es-wrap .es-ibtn .es-ico,.es-wrap .es-ibtn svg{width:18px;height:18px;min-width:18px;min-height:18px;display:block;flex-shrink:0;fill:none;stroke:currentColor;}
.es-table-wrap{overflow-x:auto;max-width:100%;background:#fff;border:1px solid #e0e0e0;border-radius:8px;}
.es-table{width:100%;border-collapse:collapse;font-size:15px;table-layout:auto;}
.es-table th{background:#003366;color:#fff;text-align:left;padding:6px 7px;font-weight:600;white-space:nowrap;}
.es-table th.es-th-sort{cursor:pointer;user-select:none;}
.es-table th.es-th-sort:hover{background:#0a4a80;}
.es-table th.es-th-sort::after{content:" \2195";opacity:.45;font-weight:400;}
.es-table th.es-th-sort.asc::after{content:" \2191";opacity:1;}
.es-table th.es-th-sort.desc::after{content:" \2193";opacity:1;}
.es-table th.es-col-exam,.es-table td.es-col-exam{text-align:center;}
.es-table td{padding:5px 7px;border-bottom:1px solid #ecefec;vertical-align:middle;white-space:nowrap;}
.es-table td.es-cell-ok{background:#d9f2c9!important;}
.es-table td.es-cell-fail{background:#fde8e8!important;}
.es-table td.es-cell-open{background:#fff8e8!important;}
.es-table td.es-cell-prog{background:#ffedd5!important;}
.es-table td.es-cell-sum-ok{background:#d9f2c9!important;}
.es-table td.es-cell-sum-fail{background:#fde8e8!important;}
.es-table td.es-cell-sum-open{background:#fff8e8!important;}
.es-table tr.es-row-off td{opacity:.55;background:#f1f5f9!important;}
.es-table tr.es-row-off .es-name{text-decoration:line-through;}
.es-col-on{text-align:center;width:4.2rem;}
.es-on{display:inline-flex;align-items:center;justify-content:center;margin:0;}
.es-on input{width:1.05rem;height:1.05rem;cursor:pointer;}
.es-score{display:block;font-size:11px;font-weight:600;color:#334155;line-height:1.2;margin-top:2px;}
.es-name{font-weight:700;color:#0f172a;white-space:nowrap;}
.es-sub{display:inline;font-size:13px;color:#64748b;margin-left:5px;white-space:nowrap;}
.es-mark{display:inline-flex;align-items:center;justify-content:center;cursor:help;}
.es-ok{color:#166534;}
.es-bad{color:#b91c1c;}
.es-neutral{color:#64748b;}
.es-miss{color:#b45309;}
.es-prog{color:#c2410c;}
.es-msg{padding:6px 10px;border-radius:6px;margin:0 0 8px;font-size:15px;}
.es-msg-ok{background:#e8f4e5;border:1px solid #9ab08f;}
.es-msg-err{background:#fde9e9;border:1px solid #e3abab;}
.es-legend{margin:8px 0 0;font-size:14px;color:#64748b;}
.es-cfg{max-width:52rem;background:#fff;border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;}
.es-cfg label{display:block;font-size:15px;font-weight:700;color:#334155;margin:0 0 8px;}
.es-cfg input[type=text],.es-cfg textarea,.es-cfg select[multiple]{width:100%;padding:5px 7px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;font-weight:400;box-sizing:border-box;}
.es-cfg textarea{min-height:4.2rem;}
.es-cfg select[multiple]{min-height:12rem;}
.es-cfg .hint{font-weight:400;color:#64748b;font-size:11px;margin-top:3px;}
.es-cfg .es-acts{margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;}
.es-card{border:1px solid #e2e8f0;border-radius:8px;padding:0;margin:0 0 12px;background:#f8fafc;}
.es-card.es-dragging{opacity:.55;}
.es-card.es-drag-over{box-shadow:0 0 0 2px #48673a;}
.es-drag{display:inline-flex;align-items:center;justify-content:center;width:1.2em;color:#94a3b8;cursor:grab;user-select:none;font-size:16px;font-weight:700;letter-spacing:-2px;flex-shrink:0;}
.es-drag:active{cursor:grabbing;}
.es-card h2{margin:0;font-size:1.05rem;color:#003366;}
.es-card summary{cursor:pointer;list-style:none;display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;padding:12px;font-weight:700;color:#003366;}
.es-card summary::-webkit-details-marker{display:none;}
.es-card summary::before{content:"▸";display:inline-block;width:1em;color:#64748b;font-weight:400;}
.es-card details[open] > summary::before{content:"▾";}
.es-card summary:hover{background:#eef4ea;border-radius:8px 8px 0 0;}
.es-card-meta{font-weight:400;font-size:13px;color:#64748b;}
.es-card-body{padding:0 12px 12px;}
.es-card-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.es-card-tools{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:0 0 6px;}
.es-wrap .es-card-tools button{font:inherit;font-size:12px;padding:3px 8px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#334155;cursor:pointer;}
.es-search{width:100%;margin:0 0 6px;padding:5px 7px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;font-weight:400;box-sizing:border-box;}
.es-pick{max-height:14rem;overflow:auto;border:1px solid #cbd5e1;border-radius:6px;background:#fff;padding:4px 6px;}
.es-pick label{display:flex;align-items:flex-start;gap:6px;font-weight:400;margin:0;padding:2px 4px;font-size:14px;line-height:1.35;}
.es-pick label:hover{background:#f1f5f9;}
.es-pick input{margin-top:2px;}
.es-sort{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:0 0 8px;}
.es-sort span{font-size:14px;color:#475569;}
.es-exports{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin:0 0 8px;}
.es-exports span{font-size:14px;color:#475569;}
.es-in{width:100%;max-width:7.4rem;padding:4px 6px;border:1px solid #cbd5e1;border-radius:5px;font:inherit;font-size:14px;height:30px;box-sizing:border-box;}
.es-in.note{max-width:14rem;min-width:8rem;}
.es-note-form{display:flex;gap:4px;align-items:center;margin:0;}
.es-col-note{white-space:normal;min-width:11rem;}
.es-opt{font-weight:600;color:#334155;margin:8px 0 0;}
.es-del{font-weight:600;color:#b91c1c;margin:8px 0 0;}
.es-chips{display:flex;flex-wrap:wrap;gap:6px;min-height:1.8rem;margin:0 0 8px;}
.es-chip{display:inline-flex;align-items:center;gap:4px;background:#fff;border:1px solid #cbd5e1;border-radius:999px;padding:4px 6px 4px 10px;font-weight:600;font-size:13px;color:#0f172a;}
.es-wrap .es-chip button{border:0;background:transparent;color:#64748b;cursor:pointer;font-size:16px;line-height:1;padding:0 4px;font-weight:700;width:auto;min-width:0;height:auto;border-radius:4px;}
.es-wrap .es-chip button:hover{color:#b91c1c;background:transparent;}
.es-add-row{display:flex;gap:6px;align-items:center;}
.es-add-row input[type=text]{flex:1;margin:0;width:auto;}
.es-chips-empty{font-weight:400;color:#94a3b8;font-size:13px;}
.es-new{display:flex;gap:8px;align-items:flex-end;margin:12px 0;}
.es-new label{flex:1;margin:0;}
@media (max-width:800px){.es-card-grid{grid-template-columns:1fr;}}
</style>
<?php if ($showCfg):
	$pilotOpts = rkrk_tr_sort_rows(rkrk_tr_all_pilots_for_select(), $sortMode);
?>
<div class="es-wrap">
	<div class="es-head">
		<h1>Teilnehmerliste — Konfiguration</h1>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($self); ?>" title="Zurück" aria-label="Zurück"><?php echo rkrk_es_ico("reset"); ?></a>
	</div>
	<?php if (isset($_GET["ok"])): ?><div class="es-msg es-msg-ok">Konfiguration gespeichert.</div><?php endif; ?>
	<?php if (isset($_GET["err"])): ?><div class="es-msg es-msg-err">Speichern fehlgeschlagen.</div><?php endif; ?>
	<p class="es-legend" style="margin-top:0;">Pro Teilnehmerliste Codes als Spalten hinzufügen oder entfernen, Teilnehmer auswählen. Listen am Griff ⠿ ziehen, danach Speichern. Beispiele: WK26 Mogelsberg, WK26 SA Engelberg.</p>
	<?php if ($canEdit): ?>
	<form class="es-cfg" method="post">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?>
		<input type="hidden" name="tr_cfg_save" value="1">
		<div class="es-sort">
			<span>Teilnehmer sortieren</span>
			<button type="button" class="es-ibtn es-fbtn<?php echo $sortMode === "nach" ? " on" : ""; ?>" data-tr-psort="nach">Nachname</button>
			<button type="button" class="es-ibtn es-fbtn<?php echo $sortMode === "vor" ? " on" : ""; ?>" data-tr-psort="vor">Vorname</button>
			<span>Teilnehmerlisten</span>
			<button type="button" class="es-ibtn es-fbtn" data-tr-cards="open">Öffnen</button>
			<button type="button" class="es-ibtn es-fbtn" data-tr-cards="close">Schliessen</button>
		</div>
		<div id="tr-list-cards">
		<?php foreach ($lists as $li => $lst):
			if (!is_array($lst)) { continue; }
			$selIds = array();
			foreach (rkrk_tr_parse_ids($lst["pilot_ids"] ?? array()) as $pid) {
				$selIds["id:" . $pid] = true;
			}
			$selCodes = rkrk_tr_parse_codes($lst["exam_codes"] ?? array());
			$pilotBox = "tr-pilots-" . (int) $li;
			$codeBox = "tr-codes-" . (int) $li;
			$nPilots = count($selIds);
			$nCodes = count($selCodes);
			$openFirst = ((int) $li === 0);
		?>
		<div class="es-card">
			<details<?php echo $openFirst ? " open" : ""; ?>>
			<summary>
				<span class="es-drag" title="Ziehen zum Sortieren" draggable="false">⠿</span>
				<span><?php echo rkrk_es_h((string) ($lst["name"] ?? "Liste")); ?></span>
				<span class="es-card-meta"><?php echo (int) $nPilots; ?> <?php echo $nPilots === 1 ? "Person" : "Personen"; ?> · <?php echo (int) $nCodes; ?> Codes</span>
			</summary>
			<div class="es-card-body">
			<input type="hidden" name="lists[<?php echo (int) $li; ?>][id]" value="<?php echo rkrk_es_h((string) ($lst["id"] ?? "")); ?>">
			<input type="hidden" data-tr-ord name="lists[<?php echo (int) $li; ?>][ord]" value="<?php echo (int) $li; ?>">
			<label>Name
				<input type="text" name="lists[<?php echo (int) $li; ?>][name]" value="<?php echo rkrk_es_h((string) ($lst["name"] ?? "")); ?>" maxlength="80" placeholder="z. B. WK26 Mogelsberg">
			</label>
			<div class="es-card-grid">
				<div>
					<label>Codes (Spalten)</label>
					<div class="es-chips" id="<?php echo rkrk_es_h($codeBox); ?>" data-tr-chips="lists[<?php echo (int) $li; ?>][exam_codes][]">
						<?php foreach ($selCodes as $kc): ?>
							<span class="es-chip">
								<?php echo rkrk_es_h($kc); ?>
								<button type="button" data-tr-chip-del title="Entfernen" aria-label="Entfernen">&times;</button>
								<input type="hidden" name="lists[<?php echo (int) $li; ?>][exam_codes][]" value="<?php echo rkrk_es_h($kc); ?>">
							</span>
						<?php endforeach; ?>
						<?php if ($selCodes === array()): ?>
							<span class="es-chips-empty">Keine Codes. Unten hinzufügen.</span>
						<?php endif; ?>
					</div>
					<div class="es-add-row">
						<input type="text" maxlength="48" placeholder="z. B. EP_26 oder 2026-METEO" data-tr-chip-input="<?php echo rkrk_es_h($codeBox); ?>" autocomplete="off">
						<button type="button" class="es-ibtn es-fbtn" data-tr-chip-add="<?php echo rkrk_es_h($codeBox); ?>">Hinzufügen</button>
					</div>
					<span class="hint">Nur diese Codes erscheinen als Spalten. EXAM-Code oder Quiz-Key.</span>
				</div>
				<div>
					<label>Teilnehmer</label>
					<input class="es-search" type="search" data-tr-filter="<?php echo rkrk_es_h($pilotBox); ?>" placeholder="Teilnehmer suchen…">
					<div class="es-card-tools">
						<button type="button" data-tr-box="<?php echo rkrk_es_h($pilotBox); ?>" data-tr-act="all">Sichtbare</button>
						<button type="button" data-tr-box="<?php echo rkrk_es_h($pilotBox); ?>" data-tr-act="none">Keine</button>
					</div>
					<div class="es-pick" id="<?php echo rkrk_es_h($pilotBox); ?>" data-tr-pilots="1">
						<?php foreach ($pilotOpts as $po):
							$k = (string) $po["key"];
							$lab = (string) $po["name"];
							if ((string) $po["rkrs"] !== "") {
								$lab .= " (" . $po["rkrs"] . ")";
							}
							$vor = (string) ($po["vorname"] ?? "");
							$nach = (string) ($po["nachname"] ?? "");
						?>
							<label data-vor="<?php echo rkrk_es_h($vor); ?>" data-nach="<?php echo rkrk_es_h($nach); ?>">
								<input type="checkbox" name="lists[<?php echo (int) $li; ?>][pilot_ids][]" value="<?php echo rkrk_es_h($k); ?>"<?php echo isset($selIds["id:" . $k]) ? " checked" : ""; ?>>
								<?php echo rkrk_es_h($lab); ?>
							</label>
						<?php endforeach; ?>
					</div>
					<span class="hint"><?php echo (int) $nPilots; ?> ausgewählt, <?php echo (int) count($pilotOpts); ?> Personen gesamt. Ohne Auswahl bleibt die Teilnehmerliste leer.</span>
				</div>
			</div>
			<label class="es-del"><input type="checkbox" name="lists[<?php echo (int) $li; ?>][delete]" value="1"> Teilnehmerliste löschen</label>
			</div>
			</details>
		</div>
		<?php endforeach; ?>
		</div>
		<div class="es-new">
			<label>Neue Teilnehmerliste
				<input type="text" name="new_list_name" value="" placeholder="z. B. WK26 Mogelsberg" maxlength="80">
			</label>
		</div>
		<div class="es-acts">
			<button type="submit" class="es-ibtn" title="Speichern" aria-label="Speichern"><?php echo rkrk_es_ico("save"); ?></button>
			<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($self); ?>" title="Zurück" aria-label="Zurück"><?php echo rkrk_es_ico("reset"); ?></a>
		</div>
	</form>
	<?php else: ?>
		<p class="es-legend">Keine Schreibrechte für die Konfiguration.</p>
	<?php endif; ?>
</div>
<script>
(function(){
	function cmpName(a, b, mode) {
		var av = String((mode === "vor" ? a.getAttribute("data-vor") : a.getAttribute("data-nach")) || "").toLowerCase();
		var bv = String((mode === "vor" ? b.getAttribute("data-vor") : b.getAttribute("data-nach")) || "").toLowerCase();
		var a2 = String((mode === "vor" ? a.getAttribute("data-nach") : a.getAttribute("data-vor")) || "").toLowerCase();
		var b2 = String((mode === "vor" ? b.getAttribute("data-nach") : b.getAttribute("data-vor")) || "").toLowerCase();
		if (av < bv) return -1;
		if (av > bv) return 1;
		if (a2 < b2) return -1;
		if (a2 > b2) return 1;
		return 0;
	}
	function sortPilots(mode) {
		document.querySelectorAll(".es-pick[data-tr-pilots]").forEach(function(box){
			var labs = Array.prototype.slice.call(box.querySelectorAll("label"));
			labs.sort(function(a, b){ return cmpName(a, b, mode); });
			labs.forEach(function(lab){ box.appendChild(lab); });
		});
		document.querySelectorAll("[data-tr-psort]").forEach(function(b){
			if (b.getAttribute("data-tr-psort") === mode) b.classList.add("on");
			else b.classList.remove("on");
		});
	}
	function normCode(s) {
		return String(s || "").toUpperCase().replace(/[^A-Z0-9_-]/g, "").slice(0, 48);
	}
	function chipValues(box) {
		return Array.prototype.map.call(box.querySelectorAll("input[type=hidden]"), function(i){ return String(i.value || "").toUpperCase(); });
	}
	function addChip(box, raw) {
		var code = normCode(raw);
		if (code === "") return;
		if (chipValues(box).indexOf(code) >= 0) return;
		var empty = box.querySelector(".es-chips-empty");
		if (empty) empty.remove();
		var name = box.getAttribute("data-tr-chips") || "";
		var span = document.createElement("span");
		span.className = "es-chip";
		span.appendChild(document.createTextNode(code + " "));
		var del = document.createElement("button");
		del.type = "button";
		del.setAttribute("data-tr-chip-del", "");
		del.title = "Entfernen";
		del.setAttribute("aria-label", "Entfernen");
		del.innerHTML = "&times;";
		span.appendChild(del);
		var hid = document.createElement("input");
		hid.type = "hidden";
		hid.name = name;
		hid.value = code;
		span.appendChild(hid);
		box.appendChild(span);
	}
	document.querySelectorAll("[data-tr-chip-add]").forEach(function(btn){
		btn.addEventListener("click", function(){
			var id = btn.getAttribute("data-tr-chip-add") || "";
			var box = document.getElementById(id);
			var inp = document.querySelector('[data-tr-chip-input="' + id + '"]');
			if (!box || !inp) return;
			addChip(box, inp.value);
			inp.value = "";
			inp.focus();
		});
	});
	document.querySelectorAll("[data-tr-chip-input]").forEach(function(inp){
		inp.addEventListener("keydown", function(ev){
			if (ev.key !== "Enter") return;
			ev.preventDefault();
			var id = inp.getAttribute("data-tr-chip-input") || "";
			var box = document.getElementById(id);
			if (!box) return;
			addChip(box, inp.value);
			inp.value = "";
		});
	});
	document.addEventListener("click", function(ev){
		var btn = ev.target.closest("[data-tr-chip-del]");
		if (!btn) return;
		var chip = btn.closest(".es-chip");
		var box = btn.closest(".es-chips");
		if (chip) chip.remove();
		if (box && !box.querySelector(".es-chip")) {
			var hint = document.createElement("span");
			hint.className = "es-chips-empty";
			hint.textContent = "Keine Codes. Unten hinzufügen.";
			box.appendChild(hint);
		}
	});
	document.querySelectorAll("[data-tr-filter]").forEach(function(inp){
		inp.addEventListener("input", function(){
			var box = document.getElementById(inp.getAttribute("data-tr-filter") || "");
			if (!box) return;
			var q = String(inp.value || "").toLowerCase();
			box.querySelectorAll("label").forEach(function(lab){
				var t = String(lab.textContent || "").toLowerCase();
				lab.style.display = (q === "" || t.indexOf(q) >= 0) ? "" : "none";
			});
		});
	});
	document.querySelectorAll("[data-tr-act]").forEach(function(btn){
		btn.addEventListener("click", function(){
			var box = document.getElementById(btn.getAttribute("data-tr-box") || "");
			if (!box) return;
			var on = btn.getAttribute("data-tr-act") === "all";
			box.querySelectorAll("label").forEach(function(lab){
				if (lab.style.display === "none") return;
				var c = lab.querySelector("input[type=checkbox]");
				if (c) c.checked = on;
			});
		});
	});
	document.querySelectorAll("[data-tr-psort]").forEach(function(btn){
		btn.addEventListener("click", function(){
			sortPilots(btn.getAttribute("data-tr-psort") === "vor" ? "vor" : "nach");
		});
	});
	document.querySelectorAll("[data-tr-cards]").forEach(function(btn){
		btn.addEventListener("click", function(){
			var open = btn.getAttribute("data-tr-cards") === "open";
			document.querySelectorAll(".es-card details").forEach(function(d){ d.open = open; });
		});
	});
	(function(){
		var box = document.getElementById("tr-list-cards");
		if (!box) return;
		var dragCard = null;
		function reindex() {
			var i = 0;
			box.querySelectorAll(".es-card").forEach(function(card){
				var inp = card.querySelector("input[data-tr-ord]");
				if (inp) inp.value = String(i);
				i++;
			});
		}
		box.querySelectorAll(".es-card").forEach(function(card){
			var grip = card.querySelector(".es-drag");
			if (grip) {
				grip.addEventListener("mousedown", function(){
					card.setAttribute("draggable", "true");
				});
				grip.addEventListener("click", function(ev){
					ev.preventDefault();
					ev.stopPropagation();
				});
			}
			card.addEventListener("dragstart", function(ev){
				dragCard = card;
				card.classList.add("es-dragging");
				ev.dataTransfer.effectAllowed = "move";
				try { ev.dataTransfer.setData("text/plain", "list"); } catch (err) {}
			});
			card.addEventListener("dragend", function(){
				card.removeAttribute("draggable");
				card.classList.remove("es-dragging");
				box.querySelectorAll(".es-drag-over").forEach(function(c){ c.classList.remove("es-drag-over"); });
				dragCard = null;
				reindex();
			});
			card.addEventListener("dragover", function(ev){
				ev.preventDefault();
				ev.dataTransfer.dropEffect = "move";
				if (!dragCard || dragCard === card) return;
				card.classList.add("es-drag-over");
				var rect = card.getBoundingClientRect();
				if ((ev.clientY - rect.top) < rect.height / 2) {
					box.insertBefore(dragCard, card);
				} else {
					box.insertBefore(dragCard, card.nextSibling);
				}
			});
			card.addEventListener("dragleave", function(){
				card.classList.remove("es-drag-over");
			});
			card.addEventListener("drop", function(ev){
				ev.preventDefault();
				card.classList.remove("es-drag-over");
				reindex();
			});
		});
		reindex();
	})();
})();
</script>
<?php
	if (function_exists("rkrk_assess_footer_echo")) {
		rkrk_assess_footer_echo();
	}
	return;
endif;
$showRemark = true;
$colCount = 4 + count($codes);
?>
<div class="es-wrap">
	<div class="es-head">
		<h1>Teilnehmerliste<?php echo is_array($active) ? " — " . rkrk_es_h((string) ($active["name"] ?? "")) : ""; ?></h1>
		<div class="es-stats">
			<span><b><?php echo (int) $nAll; ?></b> <?php echo $nAll === 1 ? "Teilnehmer" : "Teilnehmer"; ?></span>
			<?php if (count($visible) !== $nAll): ?>
			<span>angezeigt <b><?php echo (int) count($visible); ?></b></span>
			<?php endif; ?>
			<span><?php echo (int) $nOk; ?>/<?php echo (int) ($nAll - $nOff); ?> vollständig</span>
			<span>offen <b><?php echo (int) $nOpen; ?></b></span>
			<span>nicht bestanden <b><?php echo (int) $nFail; ?></b></span>
			<?php if ($nOff > 0): ?>
			<span>inaktiv <b><?php echo (int) $nOff; ?></b></span>
			<?php endif; ?>
		</div>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($cfgHref); ?>" title="Konfiguration" aria-label="Konfiguration"><?php echo rkrk_es_ico("cfg"); ?></a>
	</div>
	<?php if (isset($_GET["ok"])): ?><div class="es-msg es-msg-ok">Gespeichert.</div><?php endif; ?>
	<?php if (isset($_GET["err"])): ?><div class="es-msg es-msg-err"><?php echo (string) $_GET["err"] === "export" ? "Export fehlgeschlagen." : "Speichern fehlgeschlagen."; ?></div><?php endif; ?>
	<div class="es-lists">
		<?php foreach ($lists as $lst):
			if (!is_array($lst)) { continue; }
			$lid = (string) ($lst["id"] ?? "");
			$on = ($lid !== "" && $lid === $listId);
			$nList = count(rkrk_tr_parse_ids($lst["pilot_ids"] ?? array()));
			$href = rkrk_tr_page_href(array("list" => $lid, "sort" => $sortMode));
		?>
			<a class="es-ibtn es-fbtn<?php echo $on ? " on" : ""; ?>" href="<?php echo rkrk_es_h($href); ?>"><?php echo rkrk_es_h((string) ($lst["name"] ?? $lid)); ?> (<?php echo (int) $nList; ?>)</a>
		<?php endforeach; ?>
	</div>
	<div class="es-sort">
		<span>Sortierung</span>
		<a class="es-ibtn es-fbtn<?php echo $sortMode === "nach" ? " on" : ""; ?>" href="<?php echo rkrk_es_h($sortNachHref); ?>">Nachname</a>
		<a class="es-ibtn es-fbtn<?php echo $sortMode === "vor" ? " on" : ""; ?>" href="<?php echo rkrk_es_h($sortVorHref); ?>">Vorname</a>
	</div>
	<div class="es-exports">
		<span>Export</span>
		<a class="es-ibtn es-fbtn" href="<?php echo rkrk_es_h($pdfHref); ?>" title="PDF-Report der Übersicht"><?php echo rkrk_es_ico("pdf"); ?> PDF</a>
		<a class="es-ibtn es-fbtn" href="<?php echo rkrk_es_h($xlsxHref); ?>" title="Excel-Report der Übersicht"><?php echo rkrk_es_ico("xlsx"); ?> Excel</a>
		<a class="es-ibtn es-fbtn" href="<?php echo rkrk_es_h($personelHref); ?>" title="Excel-Teilnehmerliste der ausgewählten Personen"><?php echo rkrk_es_ico("personel"); ?> Teilnehmerliste</a>
		<?php if ($mailHref !== ""): ?>
			<a class="es-ibtn es-fbtn" href="<?php echo rkrk_es_h($mailHref); ?>" title="E-Mail an <?php echo (int) $mailCount; ?> Adressen (BCC) öffnen"><?php echo rkrk_es_ico("mail"); ?> Mail (<?php echo (int) $mailCount; ?>)</a>
		<?php else: ?>
			<span class="es-legend" style="margin:0;" title="Keine E-Mail-Adressen in der Teilnehmerliste">Mail: keine Adressen</span>
		<?php endif; ?>
	</div>
	<form class="es-filters" method="get">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?>
		<?php if ($listId !== ""): ?><input type="hidden" name="list" value="<?php echo rkrk_es_h($listId); ?>"><?php endif; ?>
		<input type="hidden" name="sort" value="<?php echo rkrk_es_h($sortMode); ?>">
		<input type="text" name="q" value="<?php echo rkrk_es_h($filterQ); ?>" placeholder="Name, ID…">
		<select name="status">
			<option value="">Status</option>
			<option value="ok"<?php echo $filterStatus === "ok" ? " selected" : ""; ?>>Vollständig bestanden</option>
			<option value="open"<?php echo $filterStatus === "open" ? " selected" : ""; ?>>Offen / in Arbeit</option>
			<option value="fail"<?php echo $filterStatus === "fail" ? " selected" : ""; ?>>Nicht bestanden</option>
		</select>
		<button type="submit" class="es-ibtn" title="Filtern" aria-label="Filtern"><?php echo rkrk_es_ico("filter"); ?></button>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($self); ?>" title="Zurücksetzen" aria-label="Zurücksetzen"><?php echo rkrk_es_ico("reset"); ?></a>
	</form>
	<?php if ($canEdit): foreach ($visible as $ri => $r):
		if ((string) ($r["id"] ?? "") === "" || $listId === "") { continue; }
	?>
	<form id="trf<?php echo (int) $ri; ?>" method="post" hidden>
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?>
		<input type="hidden" name="tr_row_save" value="1">
		<input type="hidden" name="list" value="<?php echo rkrk_es_h($listId); ?>">
		<input type="hidden" name="pilot_id" value="<?php echo rkrk_es_h((string) $r["id"]); ?>">
		<input type="hidden" name="sort" value="<?php echo rkrk_es_h($sortMode); ?>">
		<?php if ($filterQ !== ""): ?><input type="hidden" name="q" value="<?php echo rkrk_es_h($filterQ); ?>"><?php endif; ?>
		<?php if ($filterStatus !== ""): ?><input type="hidden" name="status" value="<?php echo rkrk_es_h($filterStatus); ?>"><?php endif; ?>
	</form>
	<?php endforeach; endif; ?>
	<div class="es-table-wrap">
		<table class="es-table" id="tr-table">
			<thead>
				<tr>
					<th class="es-th-sort" data-col="0"><?php echo $sortMode === "vor" ? "Teilnehmer (Vorname)" : "Teilnehmer (Nachname)"; ?></th>
					<?php foreach ($codes as $ci => $code): ?>
						<th class="es-th-sort es-col-exam" data-col="<?php echo (int) ($ci + 1); ?>" title="<?php echo rkrk_es_h($code); ?>"><?php echo rkrk_es_h($code); ?></th>
					<?php endforeach; ?>
					<th class="es-th-sort es-col-exam" data-col="<?php echo (int) (count($codes) + 1); ?>" title="Gesamtstatus">Σ</th>
					<th class="es-th-sort es-col-on" data-col="<?php echo (int) (count($codes) + 2); ?>" title="Inaktive Teilnehmer bleiben in der Liste, zählen aber nicht mit">Aktiv</th>
					<th class="es-th-sort es-col-note" data-col="<?php echo (int) (count($codes) + 3); ?>">Bemerkung</th>
				</tr>
			</thead>
			<tbody>
			<?php if (!is_array($active)): ?>
				<tr><td colspan="<?php echo (int) $colCount; ?>">Keine Teilnehmerliste. In der Konfiguration eine Liste anlegen.</td></tr>
			<?php elseif (count($visible) === 0): ?>
				<tr><td colspan="<?php echo (int) $colCount; ?>">Keine Teilnehmer in dieser Liste. In der Konfiguration Teilnehmer und Codes wählen.</td></tr>
			<?php endif; ?>
			<?php foreach ($visible as $ri => $r):
				$rowSt = (string) ($r["status"] ?? "");
				$sumSort = $rowSt === "ok" ? "3" : ($rowSt === "fail" ? "1" : "2");
				$sumCls = $rowSt === "ok" ? "es-cell-sum-ok" : ($rowSt === "fail" ? "es-cell-sum-fail" : "es-cell-sum-open");
				$isOff = !empty($r["disabled"]);
				$pilotSv = $sortMode === "vor"
					? trim((string) ($r["vorname"] ?? "") . " " . (string) ($r["nachname"] ?? ""))
					: trim((string) ($r["nachname"] ?? "") . " " . (string) ($r["vorname"] ?? ""));
				if ($pilotSv === "") {
					$pilotSv = (string) ($r["name"] ?? "");
				}
				$formId = "trf" . (int) $ri;
				$rem = (string) ($r["remark"] ?? "");
			?>
				<tr<?php echo $isOff ? ' class="es-row-off"' : ""; ?>>
					<td data-sv="<?php echo rkrk_es_h($pilotSv); ?>">
						<span class="es-name"><?php echo rkrk_es_h($r["name"]); ?></span>
						<span class="es-sub">
							<?php echo rkrk_es_h($r["rkrs_id"] !== "" ? $r["rkrs_id"] : $r["pilot_id"]); ?>
							<?php if (!empty($r["rkrsplus"])): ?> · +<?php endif; ?>
						</span>
					</td>
					<?php foreach ($codes as $code):
						$cell = isset($r["cells"][$code]) && is_array($r["cells"][$code]) ? $r["cells"][$code] : array("hit" => null, "sort" => "0", "status" => "open");
						$hit = isset($cell["hit"]) && is_array($cell["hit"]) ? $cell["hit"] : null;
						$cst = (string) ($cell["status"] ?? "open");
						$cellCls = "es-col-exam";
						if ($cst === "ok") {
							$cellCls .= " es-cell-ok";
						} elseif ($cst === "fail") {
							$cellCls .= " es-cell-fail";
						} elseif ($cst === "progress") {
							$cellCls .= " es-cell-prog";
						} else {
							$cellCls .= " es-cell-open";
						}
						$score = function_exists("rkrk_tr_result_score") ? rkrk_tr_result_score($hit) : "";
					?>
						<td class="<?php echo $cellCls; ?>" data-sv="<?php echo rkrk_es_h((string) ($cell["sort"] ?? "0")); ?>">
							<?php echo rkrk_tr_result_icon($hit, $code); ?>
							<?php if ($score !== ""): ?><span class="es-score"><?php echo rkrk_es_h($score); ?></span><?php endif; ?>
						</td>
					<?php endforeach; ?>
					<td class="es-col-exam <?php echo $sumCls; ?>" data-sv="<?php echo rkrk_es_h($sumSort); ?>">
						<?php echo rkrk_tr_overall_icon(is_array($r["sum"] ?? null) ? $r["sum"] : array()); ?>
					</td>
					<td class="es-col-on" data-sv="<?php echo $isOff ? "0" : "1"; ?>">
						<?php if ($canEdit && $listId !== "" && (string) ($r["id"] ?? "") !== ""): ?>
							<label class="es-on" title="<?php echo $isOff ? "Inaktiv — Klick aktiviert" : "Aktiv — Häkchen entfernen deaktiviert"; ?>">
								<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="active" value="0">
								<input type="checkbox" form="<?php echo rkrk_es_h($formId); ?>" name="active" value="1" data-tr-active<?php echo $isOff ? "" : " checked"; ?>>
							</label>
						<?php else: ?>
							<?php echo $isOff ? "Nein" : "Ja"; ?>
						<?php endif; ?>
					</td>
					<td class="es-col-note" data-sv="<?php echo rkrk_es_h($rem); ?>">
						<?php if ($canEdit && $listId !== "" && (string) ($r["id"] ?? "") !== ""): ?>
							<div class="es-note-form">
								<input class="es-in note" form="<?php echo rkrk_es_h($formId); ?>" type="text" name="remark" value="<?php echo rkrk_es_h($rem); ?>" placeholder="Bemerkung" maxlength="200">
								<button form="<?php echo rkrk_es_h($formId); ?>" type="submit" class="es-ibtn" title="Speichern" aria-label="Speichern"><?php echo rkrk_es_ico("save"); ?></button>
							</div>
						<?php else: ?>
							<?php echo $rem !== "" ? rkrk_es_h($rem) : "—"; ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="es-legend">
		<?php echo rkrk_tr_result_icon(array("passed" => true, "status" => "done", "pct" => 100, "anz" => 0, "richtig" => 0), ""); ?> Bestanden
		· <?php echo rkrk_tr_result_icon(array("passed" => false, "status" => "done", "pct" => 0, "anz" => 0, "richtig" => 0), ""); ?> Nicht bestanden
		· <?php echo rkrk_tr_result_icon(array("status" => "progress"), ""); ?> In Arbeit
		· <?php echo rkrk_tr_result_icon(null, ""); ?> Offen.
		Hover auf dem Icon: Status, Datum, EXAM, Quiz und Resultat in Prozent.
		Zelle grün = bestanden, rot = nicht bestanden, gelb = offen, orange = in Arbeit.
		PDF und Excel zeigen die Resultate (Punkte / Prozent) direkt.
		Aktiv-Häkchen entfernen deaktiviert den Teilnehmer (bleibt in der Liste).
		Teilnehmerlisten, Personen und Codes im Zahnrad.
	</p>
</div>
<script>
(function(){
	document.querySelectorAll("input[data-tr-active]").forEach(function(cb){
		cb.addEventListener("change", function(){
			var id = cb.getAttribute("form") || "";
			var f = id ? document.getElementById(id) : cb.form;
			if (f) f.submit();
		});
	});
	var table = document.getElementById("tr-table");
	if (!table) return;
	var tbody = table.tBodies[0];
	if (!tbody) return;
	var state = { col: -1, dir: 1 };
	function cellVal(tr, idx) {
		var td = tr.cells[idx];
		if (!td) return "";
		var sv = td.getAttribute("data-sv");
		if (sv !== null) return String(sv).trim().toLowerCase();
		return String(td.textContent || "").trim().toLowerCase();
	}
	function cmp(a, b) {
		if (a === "" && b !== "") return 1;
		if (b === "" && a !== "") return -1;
		var na = parseFloat(a.replace(",", "."));
		var nb = parseFloat(b.replace(",", "."));
		if (a !== "" && b !== "" && !isNaN(na) && !isNaN(nb) && /^-?[\d.]+$/.test(a) && /^-?[\d.]+$/.test(b)) {
			if (na < nb) return -1;
			if (na > nb) return 1;
			return 0;
		}
		if (a < b) return -1;
		if (a > b) return 1;
		return 0;
	}
	table.querySelectorAll("th.es-th-sort").forEach(function(th) {
		th.addEventListener("click", function() {
			var col = parseInt(th.getAttribute("data-col") || "-1", 10);
			if (col < 0) return;
			if (state.col === col) state.dir = -state.dir;
			else { state.col = col; state.dir = 1; }
			table.querySelectorAll("th.es-th-sort").forEach(function(h) {
				h.classList.remove("asc", "desc");
			});
			th.classList.add(state.dir > 0 ? "asc" : "desc");
			var rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));
			rows.sort(function(ra, rb) {
				return cmp(cellVal(ra, col), cellVal(rb, col)) * state.dir;
			});
			rows.forEach(function(tr) { tbody.appendChild(tr); });
		});
	});
})();
</script>
<?php
if (function_exists("rkrk_assess_footer_echo")) {
	rkrk_assess_footer_echo();
}
