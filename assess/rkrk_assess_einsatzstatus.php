<?php
/**
 * Filterbare Übersicht: Einsatzberechtigung aus Personalliste + Prüfungen + Lizenz.
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
$esCfg = rkrk_es_config_load();
if ($_SERVER["REQUEST_METHOD"] === "POST" && $canEdit && isset($_POST["es_cfg_save"])) {
	$codesRaw = (string) ($_POST["exam_codes"] ?? "");
	$ok = rkrk_es_config_save(array(
		"theorie_jahre" => (int) ($_POST["theorie_jahre"] ?? 2),
		"lizenz_jahre" => (int) ($_POST["lizenz_jahre"] ?? 5),
		"warn_tage" => (int) ($_POST["warn_tage"] ?? 90),
		"exam_codes" => $codesRaw,
		"braucht_theorie" => isset($_POST["braucht_theorie"]),
		"braucht_lizenz" => isset($_POST["braucht_lizenz"]),
		"braucht_material" => isset($_POST["braucht_material"]),
		"braucht_praxis" => isset($_POST["braucht_praxis"]),
		"material_templates" => isset($_POST["material_templates"]) && is_array($_POST["material_templates"]) ? $_POST["material_templates"] : array(),
		"praxis_templates" => isset($_POST["praxis_templates"]) && is_array($_POST["praxis_templates"]) ? $_POST["praxis_templates"] : array(),
		"pflicht_ab" => (string) ($_POST["pflicht_ab"] ?? "2027-09-01"),
	));
	header("Location: " . rkrk_es_page_href($ok ? array("cfg" => "1", "ok" => "1") : array("cfg" => "1", "err" => "1")), true, 303);
	exit;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && $canEdit && isset($_POST["es_save"])) {
	$id = trim((string) ($_POST["pilot_id"] ?? ""));
	$code = rkrk_es_norm_exam_code((string) ($_POST["theorie_exam_code"] ?? ""));
	$ok = ($id !== "") && rkrk_es_save_pilot_fields($id, array(
		"suspendiert" => (isset($_POST["suspendiert"]) && (string) $_POST["suspendiert"] === "1") ? "1" : "",
		"einsatz_bemerkung" => trim((string) ($_POST["einsatz_bemerkung"] ?? "")),
		"theorie_exam_code" => $code,
		"theorie_gueltig" => rkrk_es_normalize_date_input((string) ($_POST["theorie_gueltig"] ?? "")),
		"fpl_gueltig" => rkrk_es_normalize_date_input((string) ($_POST["fpl_gueltig"] ?? "")),
	));
	$q = $ok ? array("ok" => "1") : array("err" => "1");
	foreach (array("q", "status", "exam", "plus") as $k) {
		if (isset($_POST[$k]) && trim((string) $_POST[$k]) !== "") {
			$q[$k] = trim((string) $_POST[$k]);
		}
	}
	header("Location: " . rkrk_es_page_href($q), true, 303);
	exit;
}

$rows = rkrk_es_build_rows();
$filterQ = trim((string) ($_GET["q"] ?? ""));
$filterStatus = trim((string) ($_GET["status"] ?? ""));
$filterExam = rkrk_es_norm_exam_code((string) ($_GET["exam"] ?? ""));
$filterPlus = isset($_GET["plus"]) && (string) $_GET["plus"] === "1";

$examCodes = array();
foreach ($rows as $r) {
	$code = trim((string) ($r["exam_code"] ?? ""));
	if ($code === "" && is_array($r["theory"] ?? null)) {
		$code = (string) ($r["theory"]["exam_code"] ?? "");
	}
	if ($code !== "") {
		$examCodes[$code] = true;
	}
}
foreach ((array) ($esCfg["exam_codes"] ?? array()) as $preset) {
	$preset = rkrk_es_norm_exam_code((string) $preset);
	if ($preset !== "") {
		$examCodes[$preset] = true;
	}
}
ksort($examCodes);

$visible = array();
foreach ($rows as $r) {
	if ($filterQ !== "") {
		$hay = rkrk_es_norm_name($r["name"] . " " . $r["rkrs_id"] . " " . $r["maturitaet"] . " " . $r["bemerkung"] . " " . (string) ($r["exam_code"] ?? ""));
		if (strpos($hay, rkrk_es_norm_name($filterQ)) === false) {
			continue;
		}
	}
	if ($filterStatus === "berechtigt") {
		if ((string) $r["status"] !== "berechtigt") {
			continue;
		}
	} elseif ($filterStatus !== "" && (string) $r["status"] !== $filterStatus) {
		if (!($filterStatus === "theorie_faellig" && in_array((string) $r["theory_cls"], array("expired", "soon"), true))) {
			if (!($filterStatus === "lizenz_faellig" && in_array((string) $r["license_cls"], array("expired", "soon"), true))) {
				continue;
			}
		}
	}
	if ($filterExam !== "") {
		$tc = trim((string) ($r["exam_code"] ?? ""));
		if ($tc !== $filterExam) {
			continue;
		}
	}
	if ($filterPlus && empty($r["rkrsplus"])) {
		continue;
	}
	$visible[] = $r;
}

$nAll = count($rows);
$nOk = 0;
$nSusp = 0;
$nSoon = 0;
foreach ($rows as $r) {
	if ((string) $r["status"] === "berechtigt") {
		$nOk++;
	}
	if (!empty($r["suspended"])) {
		$nSusp++;
	}
	if (in_array((string) $r["theory_cls"], array("expired", "soon"), true) || in_array((string) $r["license_cls"], array("expired", "soon"), true)) {
		$nSoon++;
	}
}

function rkrk_es_state_icon(?array $hit): string {
	if (!$hit) {
		return "<span class='es-mark es-miss' title='offen'>" . rkrk_es_ico("open") . "</span>";
	}
	$ok = !empty($hit["passed"]);
	$tip = $ok ? "bestanden (mindestens einmal)" : "nicht bestanden";
	if ($hit["date"] instanceof DateTimeImmutable) {
		$tip .= " am " . rkrk_es_fmt_date($hit["date"]);
	}
	if (isset($hit["label"]) && trim((string) $hit["label"]) !== "") {
		$tip .= " · " . trim((string) $hit["label"]);
	}
	$cls = $ok ? "es-ok" : "es-bad";
	$ico = $ok ? "ok" : "no";
	return "<span class='es-mark " . $cls . "' title='" . rkrk_es_h($tip) . "'>" . rkrk_es_ico($ico) . "</span>";
}

$pagetitle = "Einsatzberechtigung";
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
$self = rkrk_es_page_href();
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
.es-ico{width:18px;height:18px;min-width:18px;min-height:18px;display:block;flex-shrink:0;fill:none;stroke:currentColor;}
.es-wrap a.es-ibtn,.es-wrap button.es-ibtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;min-width:32px;padding:0;border:1px solid #9ab08f;border-radius:7px;background:#48673a;color:#fff;cursor:pointer;text-decoration:none;box-sizing:border-box;overflow:visible;}
.es-wrap a.es-ibtn:hover,.es-wrap button.es-ibtn:hover{background:#3a542e;color:#fff;}
.es-wrap a.es-ibtn.ghost,.es-wrap button.es-ibtn.ghost{background:#fff;color:#334155;border-color:#cbd5e1;}
.es-wrap a.es-ibtn.ghost:hover,.es-wrap button.es-ibtn.ghost:hover{background:#f1f5f9;color:#0f172a;}
.es-wrap a.es-ibtn.danger,.es-wrap button.es-ibtn.danger{background:#fff;color:#b91c1c;border-color:#e8c8c8;}
.es-wrap a.es-ibtn.danger.on,.es-wrap button.es-ibtn.danger.on{background:#b91c1c;color:#fff;border-color:#b91c1c;}
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
.es-table td.es-exp-0{background:#fafcfa!important;}
.es-table td.es-exp-1{background:#fceaea!important;}
.es-table td.es-exp-2{background:#fff6dc!important;}
.es-table td.es-exp-3{background:#e8f4ea!important;}
.es-table td.es-exp-4{background:#d8ecde!important;}
.es-table td.es-exp-5{background:#d9f0ec!important;}
.es-table td.es-exp-6{background:#e2ecf8!important;}
.es-table td{padding:5px 7px;border-bottom:1px solid #ecefec;vertical-align:middle;white-space:nowrap;}
.es-table tr.es-row-ok,.es-table tr.es-row-ok td{background:#d9f2c9!important;}
.es-table tr.es-row-soon,.es-table tr.es-row-soon td{background:#ffe4b8!important;}
.es-table tr.es-row-susp,.es-table tr.es-row-susp td{background:#fff1f2!important;}
.es-name{font-weight:700;color:#0f172a;white-space:nowrap;}
.es-sub{display:inline;font-size:13px;color:#64748b;margin-left:5px;white-space:nowrap;}
.es-in{width:100%;max-width:7.4rem;padding:4px 6px;border:1px solid #cbd5e1;border-radius:5px;font:inherit;font-size:14px;height:30px;box-sizing:border-box;}
.es-in.exam{max-width:5.6rem;text-transform:uppercase;}
.es-in.note{max-width:10rem;min-width:7rem;}
.es-in.date{max-width:8.4rem;}
.es-mark{display:inline-flex;align-items:center;justify-content:center;}
.es-ok{color:#166534;}
.es-bad{color:#b91c1c;}
.es-neutral{color:#64748b;}
.es-miss{color:#b45309;}
.es-due-ok .es-in{border-color:#86efac;}
.es-due-soon,.es-due-soon .es-in{border-color:#f59e0b;background:#ffe4b8;}
.es-due-expired .es-in,.es-due-missing .es-in{border-color:#f87171;background:#fef2f2;}
.es-table td.es-due-assumed{
	background-image:repeating-linear-gradient(-45deg,rgba(71,85,105,.16) 0 5px,transparent 5px 10px)!important;
}
.es-due-assumed .es-in{border-color:#64748b;background:rgba(255,255,255,.55);}
.es-acts{display:flex;align-items:center;gap:4px;white-space:nowrap;}
.es-msg{padding:6px 10px;border-radius:6px;margin:0 0 8px;font-size:15px;}
.es-msg-ok{background:#e8f4e5;border:1px solid #9ab08f;}
.es-msg-err{background:#fde9e9;border:1px solid #e3abab;}
.es-legend{margin:8px 0 0;font-size:14px;color:#64748b;}
.es-chk{position:absolute;opacity:0;width:0;height:0;}
.es-cfg{max-width:36rem;background:#fff;border:1px solid #e0e0e0;border-radius:10px;padding:14px 16px;}
.es-cfg label{display:block;font-size:15px;font-weight:700;color:#334155;margin:0 0 8px;}
.es-cfg input[type=number],.es-cfg textarea{width:100%;max-width:14rem;padding:5px 7px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;font-weight:400;box-sizing:border-box;}
.es-cfg textarea,.es-cfg select[multiple]{max-width:100%;min-height:4.5rem;width:100%;padding:5px 7px;border:1px solid #cbd5e1;border-radius:6px;font:inherit;font-weight:400;box-sizing:border-box;}
.es-cfg select[multiple]{min-height:9rem;}
.es-cfg .es-cfg-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.es-cfg .es-cfg-tpls{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:10px 0;}
.es-cfg .hint{font-weight:400;color:#64748b;font-size:11px;margin-top:3px;}
.es-cfg .es-cfg-flags{display:flex;flex-wrap:wrap;gap:10px 16px;margin:10px 0 14px;font-weight:600;}
.es-cfg .es-acts{margin-top:8px;}
</style>
<?php
$cfgHref = rkrk_es_page_href(array("cfg" => "1"));
$showCfg = isset($_GET["cfg"]) && (string) $_GET["cfg"] !== "";
if ($showCfg):
	$esCfg = rkrk_es_config_load();
?>
<div class="es-wrap">
	<div class="es-head">
		<h1>Einsatzberechtigung — Konfiguration</h1>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($self); ?>" title="Zurück" aria-label="Zurück"><?php echo rkrk_es_ico("reset"); ?></a>
	</div>
	<?php if (isset($_GET["ok"])): ?><div class="es-msg es-msg-ok">Konfiguration gespeichert.</div><?php endif; ?>
	<?php if (isset($_GET["err"])): ?><div class="es-msg es-msg-err">Speichern fehlgeschlagen.</div><?php endif; ?>
	<?php if ($canEdit): ?>
	<form class="es-cfg" method="post">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?>
		<input type="hidden" name="es_cfg_save" value="1">
		<div class="es-cfg-row">
			<label>Theorie gültig (Jahre)
				<input type="number" name="theorie_jahre" min="0" max="20" value="<?php echo (int) $esCfg["theorie_jahre"]; ?>">
			</label>
			<label>Lizenz gültig (Jahre)
				<input type="number" name="lizenz_jahre" min="0" max="20" value="<?php echo (int) $esCfg["lizenz_jahre"]; ?>">
			</label>
			<label>Warnung vor Ablauf (Tage)
				<input type="number" name="warn_tage" min="0" max="730" value="<?php echo (int) $esCfg["warn_tage"]; ?>">
			</label>
		</div>
		<label>Prüfungspflicht ab (ohne Berechtigung = gesperrt)
			<input type="date" name="pflicht_ab" value="<?php echo rkrk_es_h((string) ($esCfg["pflicht_ab"] ?? "2027-09-01")); ?>">
			<span class="hint">Bis zu diesem Datum: keine Prüfung = neutral, Pilot bleibt einsatzberechtigt. Ab diesem Datum: ohne Einsatzberechtigung gesperrt.</span>
		</label>
		<label>EXAM-Codes (eine Zeile oder Komma)
			<textarea name="exam_codes"><?php echo rkrk_es_h(implode("\n", (array) $esCfg["exam_codes"])); ?></textarea>
		</label>
		<?php
		$tplFiles = function_exists("rkrk_es_list_templates") ? rkrk_es_list_templates() : array();
		$matSel = rkrk_es_cfg_tpl_list($esCfg, "material");
		$praSel = rkrk_es_cfg_tpl_list($esCfg, "praxis");
		foreach (array_merge($matSel, $praSel) as $extra) {
			if ($extra !== "" && !in_array($extra, $tplFiles, true)) {
				$tplFiles[] = $extra;
			}
		}
		?>
		<div class="es-cfg-tpls">
			<label>Material-Templates
				<select name="material_templates[]" multiple size="8">
					<?php foreach ($tplFiles as $tf): ?>
						<option value="<?php echo rkrk_es_h($tf); ?>"<?php echo in_array($tf, $matSel, true) ? " selected" : ""; ?>><?php echo rkrk_es_h($tf); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="hint">Strg/Cmd für mehrere</span>
			</label>
			<label>Praxis-Templates
				<select name="praxis_templates[]" multiple size="8">
					<?php foreach ($tplFiles as $tf): ?>
						<option value="<?php echo rkrk_es_h($tf); ?>"<?php echo in_array($tf, $praSel, true) ? " selected" : ""; ?>><?php echo rkrk_es_h($tf); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="hint">Strg/Cmd für mehrere</span>
			</label>
		</div>
		<div class="es-cfg-flags">
			<label><input type="checkbox" name="braucht_theorie" value="1"<?php echo !empty($esCfg["braucht_theorie"]) ? " checked" : ""; ?>> Theorie nötig</label>
			<label><input type="checkbox" name="braucht_lizenz" value="1"<?php echo !empty($esCfg["braucht_lizenz"]) ? " checked" : ""; ?>> Lizenz nötig</label>
			<label><input type="checkbox" name="braucht_material" value="1"<?php echo !empty($esCfg["braucht_material"]) ? " checked" : ""; ?>> Material nötig</label>
			<label><input type="checkbox" name="braucht_praxis" value="1"<?php echo !empty($esCfg["braucht_praxis"]) ? " checked" : ""; ?>> Praxis nötig</label>
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
<?php
	if (function_exists("rkrk_assess_footer_echo")) {
		rkrk_assess_footer_echo();
	}
	return;
endif;
?>
<div class="es-wrap">
	<div class="es-head">
		<h1>Einsatzberechtigung</h1>
		<div class="es-stats">
			<span><?php echo (int) $nOk; ?>/<?php echo (int) $nAll; ?> berechtigt</span>
			<span>suspendiert <b><?php echo (int) $nSusp; ?></b></span>
			<span>fällig <b><?php echo (int) $nSoon; ?></b></span>
		</div>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($cfgHref); ?>" title="Konfiguration" aria-label="Konfiguration"><?php echo rkrk_es_ico("cfg"); ?></a>
	</div>
	<?php if (isset($_GET["ok"])): ?><div class="es-msg es-msg-ok">Gespeichert.</div><?php endif; ?>
	<?php if (isset($_GET["err"])): ?><div class="es-msg es-msg-err">Speichern fehlgeschlagen.</div><?php endif; ?>
	<form class="es-filters" method="get">
		<?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } ?>
		<?php if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?>
		<input type="text" name="q" value="<?php echo rkrk_es_h($filterQ); ?>" placeholder="Name, ID, EXAM…">
		<select name="status">
			<option value="">Status</option>
			<option value="berechtigt"<?php echo $filterStatus === "berechtigt" ? " selected" : ""; ?>>Einsatzberechtigt (final)</option>
			<option value="neutral"<?php echo $filterStatus === "neutral" ? " selected" : ""; ?>>Neutral (Prüfung offen)</option>
			<option value="nicht_berechtigt"<?php echo $filterStatus === "nicht_berechtigt" ? " selected" : ""; ?>>Nicht berechtigt</option>
			<option value="gesperrt"<?php echo $filterStatus === "gesperrt" ? " selected" : ""; ?>>Gesperrt</option>
			<option value="suspendiert"<?php echo $filterStatus === "suspendiert" ? " selected" : ""; ?>>Suspendiert</option>
			<option value="theorie_faellig"<?php echo $filterStatus === "theorie_faellig" ? " selected" : ""; ?>>Theorie fällig</option>
			<option value="lizenz_faellig"<?php echo $filterStatus === "lizenz_faellig" ? " selected" : ""; ?>>Lizenz fällig</option>
		</select>
		<select name="exam">
			<option value="">EXAM</option>
			<?php foreach (array_keys($examCodes) as $ec): ?>
				<option value="<?php echo rkrk_es_h($ec); ?>"<?php echo $filterExam === $ec ? " selected" : ""; ?>><?php echo rkrk_es_h($ec); ?></option>
			<?php endforeach; ?>
		</select>
		<label style="display:inline-flex;align-items:center;gap:4px;font-size:12px;">
			<input type="checkbox" name="plus" value="1"<?php echo $filterPlus ? " checked" : ""; ?>> RKRS+
		</label>
		<button type="submit" class="es-ibtn" title="Filtern" aria-label="Filtern"><?php echo rkrk_es_ico("filter"); ?></button>
		<?php
		$okQs = array();
		if ($filterQ !== "") {
			$okQs["q"] = $filterQ;
		}
		if ($filterExam !== "") {
			$okQs["exam"] = $filterExam;
		}
		if ($filterPlus) {
			$okQs["plus"] = "1";
		}
		$okOn = ($filterStatus === "berechtigt");
		if (!$okOn) {
			$okQs["status"] = "berechtigt";
		}
		$okHref = rkrk_es_page_href($okQs);
		?>
		<a class="es-ibtn es-fbtn<?php echo $okOn ? " on" : ""; ?>" href="<?php echo rkrk_es_h($okHref); ?>" title="<?php echo $okOn ? "Alle Piloten zeigen" : "Nur final Einsatzberechtigte (Mat+Prx einmal, Theorie+Lizenz gültig)"; ?>" aria-label="<?php echo $okOn ? "Alle Piloten zeigen" : "Nur final Einsatzberechtigte"; ?>">
			<?php echo rkrk_es_ico("ok"); ?><span>Einsatzberechtigt</span>
		</a>
		<a class="es-ibtn ghost" href="<?php echo rkrk_es_h($self); ?>" title="Zurücksetzen" aria-label="Zurücksetzen"><?php echo rkrk_es_ico("reset"); ?></a>
	</form>
	<datalist id="es-exam-codes">
		<?php foreach (array_keys($examCodes) as $ec): ?>
			<option value="<?php echo rkrk_es_h($ec); ?>">
		<?php endforeach; ?>
	</datalist>
	<?php if ($canEdit): foreach ($visible as $ri => $r):
		$saveKeyPre = $r["id"] !== "" ? $r["id"] : (string) $r["rkrs_id"];
		if ($saveKeyPre === "") { continue; }
	?>
	<form id="esf<?php echo (int) $ri; ?>" method="post" hidden><?php if (function_exists("rkrk_assess_echo_setting_hidden_input")) { rkrk_assess_echo_setting_hidden_input(); } if (function_exists("rkrk_assess_rkrs_echo_cs_hidden_inputs")) { rkrk_assess_rkrs_echo_cs_hidden_inputs(array("Name", "RKRSPilotID", "PilotID", "cs")); } ?></form>
	<?php endforeach; endif; ?>
	<div class="es-table-wrap">
		<table class="es-table" id="es-table">
			<thead>
				<tr>
					<th class="es-th-sort" data-col="0">Pilot</th>
					<th class="es-th-sort" data-col="1">Mat.</th>
					<th class="es-th-sort" data-col="2">EXAM</th>
					<th class="es-th-sort" data-col="3" title="Nächste Theorie-Erneuerung, alle <?php echo (int) $esCfg["theorie_jahre"]; ?> Jahre">Theorie bis</th>
					<th class="es-th-sort" data-col="4" title="Nächste Lizenzerneuerung, alle <?php echo (int) $esCfg["lizenz_jahre"]; ?> Jahre">Lizenz bis</th>
					<th class="es-th-sort" data-col="5" title="Material">Mat</th>
					<th class="es-th-sort" data-col="6" title="Praxis">Prx</th>
					<th class="es-th-sort" data-col="7" title="Status"></th>
					<th class="es-th-sort" data-col="8">Bemerkung</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php if (count($visible) === 0): ?>
				<tr><td colspan="10">Keine Einträge.</td></tr>
			<?php endif; ?>
			<?php foreach ($visible as $ri => $r):
				$soon = in_array((string) $r["theory_cls"], array("soon"), true)
					|| in_array((string) $r["license_cls"], array("soon"), true);
				if (!empty($r["suspended"]) || (string) $r["status"] === "gesperrt") {
					$trCls = "es-row-susp";
				} elseif ($soon) {
					$trCls = "es-row-soon";
				} elseif ((string) $r["status"] === "berechtigt") {
					$trCls = "es-row-ok";
				} else {
					$trCls = "";
				}
				$saveKey = $r["id"] !== "" ? $r["id"] : (string) $r["rkrs_id"];
				$formId = "esf" . (int) $ri;
				$stTip = "Nicht berechtigt";
				$stIco = "no";
				$stCls = "es-bad";
				$stSort = "1";
				if ($r["status"] === "berechtigt") {
					$stTip = "Final einsatzberechtigt: Material und Praxis mindestens einmal, Theorie und Lizenz gültig";
					$stIco = "ok";
					$stCls = "es-ok";
					$stSort = "3";
				} elseif ($r["status"] === "neutral") {
					$stTip = "Neutral — Material/Praxis/Theorie noch nicht vollständig, bis Sept. 2027 noch flugbereit";
					$stIco = "open";
					$stCls = "es-neutral";
					$stSort = "2";
				} elseif ($r["status"] === "suspendiert") {
					$stTip = "Suspendiert";
					$stIco = "ban";
					$stSort = "0";
				} elseif ($r["status"] === "gesperrt") {
					$stTip = "Gesperrt — keine Einsatzberechtigung";
					$stIco = "ban";
					$stSort = "0";
				} elseif ($soon) {
					$stSort = "2";
				}
				$matHit = is_array($r["material"] ?? null) ? $r["material"] : null;
				$praHit = is_array($r["praxis"] ?? null) ? $r["praxis"] : null;
				$chkSort = static function (?array $hit): string {
					if (!$hit) {
						return "0";
					}
					return !empty($hit["passed"]) ? "2" : "1";
				};
			?>
				<tr class="<?php echo $trCls; ?>">
					<td data-sv="<?php echo rkrk_es_h($r["name"]); ?>">
						<?php if ($canEdit && $saveKey !== ""): ?>
							<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="es_save" value="1">
							<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="pilot_id" value="<?php echo rkrk_es_h($saveKey); ?>">
							<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="q" value="<?php echo rkrk_es_h($filterQ); ?>">
							<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="status" value="<?php echo rkrk_es_h($filterStatus); ?>">
							<input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="exam" value="<?php echo rkrk_es_h($filterExam); ?>">
							<?php if ($filterPlus): ?><input type="hidden" form="<?php echo rkrk_es_h($formId); ?>" name="plus" value="1"><?php endif; ?>
						<?php endif; ?>
						<span class="es-name"><?php echo rkrk_es_h($r["name"]); ?></span>
						<span class="es-sub">
							<?php echo rkrk_es_h($r["rkrs_id"] !== "" ? $r["rkrs_id"] : $r["pilot_id"]); ?>
							<?php if (!empty($r["rkrsplus"])): ?> · +<?php endif; ?>
							<?php if (!empty($r["instruktor"])): ?> · I<?php endif; ?>
						</span>
					</td>
					<?php
					$expLvl = rkrk_es_exp_level((string) $r["maturitaet"]);
					$expTips = array(
						0 => "Stufe 0 — neutral / keine Stufe zugewiesen",
						1 => "Stufe 1 — Großes Entwicklungspotenzial — enger Förder- & Begleitpfad RKRS+",
						2 => "Stufe 2 — Anfänger mit Potenzial",
						3 => "Stufe 3 — Pilot mit guter technischer Basiserfahrung",
						4 => "Stufe 4 — Pilot mit Einsatz- und Teamerfahrung",
						5 => "Stufe 5 — Pilot und Einsatzexperte mit Führungspotenzial",
						6 => "Stufe 6 — Experte / Einsatzleiter",
					);
					?>
					<td class="es-exp-<?php echo (int) $expLvl; ?>" data-sv="<?php echo (int) $expLvl; ?>" title="<?php echo rkrk_es_h($expTips[$expLvl] ?? $expTips[0]); ?>">
						<?php echo $r["maturitaet"] !== "" ? rkrk_es_h($r["maturitaet"]) : "—"; ?>
					</td>
					<td data-sv="<?php echo rkrk_es_h((string) ($r["exam_code"] ?? "")); ?>">
						<?php if ($canEdit && $saveKey !== ""): ?>
							<input class="es-in exam" form="<?php echo rkrk_es_h($formId); ?>" type="text" name="theorie_exam_code" list="es-exam-codes" value="<?php echo rkrk_es_h((string) ($r["exam_code"] ?? "")); ?>" placeholder="EP_26" maxlength="12">
						<?php else: ?>
							<?php echo rkrk_es_h((string) ($r["exam_code"] ?? "")) ?: "—"; ?>
						<?php endif; ?>
					</td>
					<?php
					$thDue = $r["theory_due"] instanceof DateTimeImmutable ? $r["theory_due"] : null;
					$licDue = $r["license"] instanceof DateTimeImmutable ? $r["license"] : null;
					$thTip = rkrk_es_renew_tip("Theorie", $thDue, (string) $r["theory_cls"]);
					$licTip = rkrk_es_renew_tip("Lizenz", $licDue, (string) $r["license_cls"]);
					?>
					<td class="es-due-<?php echo rkrk_es_h((string) $r["theory_cls"]); ?>" data-sv="<?php echo rkrk_es_h(rkrk_es_ymd($thDue)); ?>" title="<?php echo rkrk_es_h($thTip); ?>">
						<?php if ($canEdit && $saveKey !== ""): ?>
							<input class="es-in date" form="<?php echo rkrk_es_h($formId); ?>" type="date" name="theorie_gueltig" title="<?php echo rkrk_es_h($thTip); ?>" value="<?php echo rkrk_es_h(rkrk_es_ymd($thDue)); ?>">
						<?php else: ?>
							<?php echo $thDue ? rkrk_es_h(rkrk_es_fmt_date($thDue)) : "—"; ?>
						<?php endif; ?>
					</td>
					<td class="es-due-<?php echo rkrk_es_h((string) $r["license_cls"]); ?>" data-sv="<?php echo rkrk_es_h(rkrk_es_ymd($licDue)); ?>" title="<?php echo rkrk_es_h($licTip); ?>">
						<?php if ($canEdit && $saveKey !== ""): ?>
							<input class="es-in date" form="<?php echo rkrk_es_h($formId); ?>" type="date" name="fpl_gueltig" title="<?php echo rkrk_es_h($licTip); ?>" value="<?php echo rkrk_es_h(rkrk_es_ymd($licDue)); ?>">
						<?php else: ?>
							<?php echo $licDue ? rkrk_es_h(rkrk_es_fmt_date($licDue)) : "—"; ?>
						<?php endif; ?>
					</td>
					<td data-sv="<?php echo rkrk_es_h($chkSort($matHit)); ?>"><?php echo rkrk_es_state_icon($matHit); ?></td>
					<td data-sv="<?php echo rkrk_es_h($chkSort($praHit)); ?>"><?php echo rkrk_es_state_icon($praHit); ?></td>
					<td data-sv="<?php echo rkrk_es_h($stSort); ?>"><span class="es-mark <?php echo $stCls; ?>" title="<?php echo rkrk_es_h($stTip); ?>"><?php echo rkrk_es_ico($stIco); ?></span></td>
					<td data-sv="<?php echo rkrk_es_h($r["bemerkung"]); ?>">
						<?php if ($canEdit && $saveKey !== ""): ?>
							<input class="es-in note" form="<?php echo rkrk_es_h($formId); ?>" type="text" name="einsatz_bemerkung" value="<?php echo rkrk_es_h($r["bemerkung"]); ?>" placeholder="Bemerkung">
						<?php else: ?>
							<?php echo $r["bemerkung"] !== "" ? rkrk_es_h($r["bemerkung"]) : "—"; ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($canEdit && $saveKey !== ""): ?>
							<div class="es-acts">
								<label class="es-ibtn danger<?php echo !empty($r["suspended"]) ? " on" : ""; ?>" title="Suspendieren" aria-label="Suspendieren">
									<input class="es-chk" form="<?php echo rkrk_es_h($formId); ?>" type="checkbox" name="suspendiert" value="1"<?php echo !empty($r["suspended"]) ? " checked" : ""; ?> onchange="this.closest('label').classList.toggle('on', this.checked)">
									<?php echo rkrk_es_ico("ban"); ?>
								</label>
								<button type="submit" form="<?php echo rkrk_es_h($formId); ?>" class="es-ibtn" title="Speichern" aria-label="Speichern"><?php echo rkrk_es_ico("save"); ?></button>
							</div>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p class="es-legend">
		EXAM, Theorie- und Lizenzablauf je Zeile editieren, Diskette speichert. Spaltenkopf sortiert.
		Theorie <?php echo (int) $esCfg["theorie_jahre"]; ?> Jahre · Lizenz <?php echo (int) $esCfg["lizenz_jahre"]; ?> Jahre · Material/Praxis: einmal bestanden gilt dauerhaft.
		Häkchen = final einsatzberechtigt: Material und Praxis mindestens einmal bestanden, Theorie und Lizenz nicht abgelaufen.
		Kreis = Übergang bis <?php echo rkrk_es_h((string) ($esCfg["pflicht_ab"] ?? "2027-09-01")); ?> (Prüfung noch offen).
		Schraffierte Lizenz = noch nicht abgefragt, bis Sept. 2027 als gültig angenommen.
		Ab diesem Datum ohne diese vier Punkte gesperrt. Kreuz = offen/abgelaufen · Verbotsschild = suspendiert/gesperrt.
	</p>
</div>
<script>
(function(){
	var table = document.getElementById("es-table");
	if (!table) return;
	var tbody = table.tBodies[0];
	if (!tbody) return;
	var state = { col: -1, dir: 1 };
	function cellVal(tr, idx) {
		var td = tr.cells[idx];
		if (!td) return "";
		var sv = td.getAttribute("data-sv");
		if (sv !== null) return String(sv).trim().toLowerCase();
		var inp = td.querySelector("input.es-in");
		if (inp) return String(inp.value || "").trim().toLowerCase();
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
