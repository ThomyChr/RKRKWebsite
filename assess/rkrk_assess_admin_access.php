<?php
/**
 * Administration: zentrale Zugriffsrechte je Server und Seite.
 */
if (function_exists("ob_get_level") && ob_get_level() === 0) {
	ob_start();
}
error_reporting(E_ALL);
ini_set("display_errors", "1");
$folderoffset = "../";
include_once $folderoffset . "scripts/rkrk_csvfiles.php";
include_once __DIR__ . "/rkrk_assess_common.php";
if (function_exists("rkrk_assess_rkrs_moodle_bootstrap")) {
	rkrk_assess_rkrs_moodle_bootstrap();
}
if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}
if (function_exists("ProtectFromAccess")) {
	CheckIPandExit($folderoffset);
}
if (function_exists("WriteToLog")) {
	WriteToLog("open", basename($_SERVER["PHP_SELF"]), "", "", "", "", "../");
}
GrantAccess($folderoffset);

$currentAccessLevel = "";
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string) $_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo["Login"]["Level"])) {
	$currentAccessLevel = strtolower(trim((string) $InputUserInfo["Login"]["Level"]));
}
$isGuestRequest = (isset($_GET["GA"]) && strtolower(trim((string) $_GET["GA"])) === "gast");
$isAdminMode = (isset($_GET["ADMIN"]) || isset($_POST["ADMIN"]) || (isset($onAdmin) && (bool) $onAdmin));
$isInstrMode = (isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($onInstr) && (bool) $onInstr));
$canUseExtendedAdmin = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== "gast");

if (empty($LoggedOn)) {
	rkrk_assess_theme_link_tags();
	if (function_exists("headerpage")) {
		headerpage("Zugriffsrechte", false, $folderoffset);
	}
	if (function_exists("RegisterForm")) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

if (!function_exists("rkrk_assess_access_policy_can_manage") || !rkrk_assess_access_policy_can_manage()) {
	if (function_exists("rkrk_assess_portal_personel_emit_access_denied")) {
		rkrk_assess_portal_personel_emit_access_denied("admin_access");
	}
	http_response_code(403);
	echo "Kein Zugriff auf die Zugriffsverwaltung.";
	exit;
}

function rkrk_acc_h($v): string {
	return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}

function rkrk_acc_csrf_token(): string {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		@session_start();
	}
	if (empty($_SESSION["rkrkAssessAccessCsrf"]) || !is_string($_SESSION["rkrkAssessAccessCsrf"])) {
		$_SESSION["rkrkAssessAccessCsrf"] = bin2hex(random_bytes(16));
	}
	return (string) $_SESSION["rkrkAssessAccessCsrf"];
}

function rkrk_acc_csrf_ok(): bool {
	$got = (string) ($_POST["csrf"] ?? "");
	$want = rkrk_acc_csrf_token();
	return $got !== "" && hash_equals($want, $got);
}

/**
 * @param array<string, mixed> $rule
 * @param array<string, array{id:string,label:string}> $cohortCat
 * @return list<string>
 */
function rkrk_acc_rule_bits(array $rule, array $cohortCat): array {
	if (empty($rule["enabled"])) {
		return array();
	}
	$bits = array();
	foreach ($cohortCat as $ck => $c) {
		if (in_array($ck, (array) ($rule["cohorts"] ?? array()), true)) {
			$bits[] = (string) $c["label"];
		}
	}
	if (!empty($rule["allow_siteadmin"])) {
		$bits[] = "Site-Admin";
	}
	if (!empty($rule["allow_cs"])) {
		$bits[] = "cs";
	}
	if (!empty($rule["allow_admin_flag"])) {
		$bits[] = "ADMIN";
	}
	if (!empty($rule["allow_instr_flag"])) {
		$bits[] = "INSTR";
	}
	if (!empty($rule["allow_noh"])) {
		$bits[] = "NOH";
	}
	if (!empty($rule["hash_gate"])) {
		$bits[] = "Hash";
	}
	if (!empty($rule["fail_closed"])) {
		$bits[] = "Fail-closed";
	}
	if (trim((string) ($rule["extra_cohorts"] ?? "")) !== "") {
		$bits[] = "weitere Cohorts";
	}
	return $bits;
}

function rkrk_acc_self_href(): string {
	$path = "rkrk_assess_admin_access.php";
	if (function_exists("rkrk_assess_append_setting_href")) {
		return rkrk_assess_append_setting_href($path);
	}
	return $path;
}

/**
 * @param mixed $posted
 * @return array<string, mixed>
 */
function rkrk_acc_policy_from_post($posted): array {
	$base = rkrk_assess_access_policy_defaults();
	if (!is_array($posted)) {
		return $base;
	}
	foreach ($base["pages"] as $pid => $page) {
		if (!isset($posted[$pid]) || !is_array($posted[$pid])) {
			$base["pages"][$pid]["hosts"] = rkrk_assess_access_policy_hosts_fill(array());
			continue;
		}
		$hostIn = array();
		foreach (rkrk_assess_access_policy_host_ids() as $hid) {
			$cell = (isset($posted[$pid][$hid]) && is_array($posted[$pid][$hid])) ? $posted[$pid][$hid] : array();
			$hostIn[$hid] = array(
				"enabled" => !empty($cell["enabled"]),
				"allow_cs" => !empty($cell["allow_cs"]),
				"allow_siteadmin" => !empty($cell["allow_siteadmin"]),
				"allow_admin_flag" => !empty($cell["allow_admin_flag"]),
				"allow_instr_flag" => !empty($cell["allow_instr_flag"]),
				"allow_noh" => !empty($cell["allow_noh"]),
				"hash_gate" => !empty($cell["hash_gate"]),
				"fail_closed" => !empty($cell["fail_closed"]),
				"cohorts" => (isset($cell["cohorts"]) && is_array($cell["cohorts"])) ? $cell["cohorts"] : array(),
				"extra_cohorts" => (string) ($cell["extra_cohorts"] ?? ""),
			);
		}
		$base["pages"][$pid]["hosts"] = rkrk_assess_access_policy_hosts_fill($hostIn);
	}
	return $base;
}

$flashOk = "";
$flashErr = "";
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
	$action = trim((string) ($_POST["action"] ?? "save"));
	if (!rkrk_acc_csrf_ok()) {
		$flashErr = "Sitzung abgelaufen. Bitte die Seite neu laden und erneut speichern.";
	} elseif ($action === "reset") {
		$res = rkrk_assess_access_policy_save(rkrk_assess_access_policy_defaults());
		if (!empty($res["ok"])) {
			rkrk_assess_access_policy_load(true);
			$flashOk = "Standardregeln wiederhergestellt.";
		} else {
			$flashErr = (string) ($res["error"] ?? "Zurücksetzen fehlgeschlagen.");
		}
	} else {
		$res = rkrk_assess_access_policy_save(rkrk_acc_policy_from_post($_POST["rule"] ?? array()));
		if (!empty($res["ok"])) {
			rkrk_assess_access_policy_load(true);
			$flashOk = "Zugriffsregeln gespeichert. Sie gelten ab dem nächsten Aufruf der jeweiligen Seite.";
		} else {
			$flashErr = (string) ($res["error"] ?? "Speichern fehlgeschlagen.");
		}
	}
}

$policy = rkrk_assess_access_policy_load();
$status = rkrk_assess_access_policy_runtime_status();
$hostMeta = rkrk_assess_access_policy_host_meta();
$cohortCat = rkrk_assess_access_policy_cohort_catalog();
$currentHost = (string) $status["host_id"];
$csrf = rkrk_acc_csrf_token();

rkrk_assess_theme_link_tags();
rkrk_assess_emit_setting_carry_script();
if (function_exists("headerpage")) {
	headerpage("Zugriffsrechte", false, $folderoffset);
}
if (function_exists("rkrk_assess_theme_rkrs_layer_link") && function_exists("rkrk_assess_ci_rkrs_mode") && rkrk_assess_ci_rkrs_mode()) {
	rkrk_assess_theme_rkrs_layer_link();
}
?>
<div class="wrap rkrk-wrap">
<?php
if (function_exists("rkrk_assess_portal_nav_echo")) {
	ob_start();
	rkrk_assess_portal_nav_echo(array(
		"btn_class" => "ql-btn",
		"schulung_mode" => false,
		"is_admin" => $canUseExtendedAdmin && $isAdminMode,
		"is_instr" => $canUseExtendedAdmin && $isInstrMode,
		"selected_quiz" => "",
	));
	$nav = ob_get_clean();
	if ($nav !== "") {
		echo '<div id="rkrk-portal-nav-slot" class="rkrk-portal-nav-slot">' . $nav . "</div>";
	}
}
?>
<style>
.rkrk-acc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;}
.rkrk-acc-kpi{background:var(--kpi-bg);border:1px solid var(--kpi-border);border-radius:10px;padding:10px 12px;}
.rkrk-acc-kpi b{display:block;font-size:12px;opacity:.75;margin-bottom:4px;}
.rkrk-acc-ok{color:var(--ok-strong);}
.rkrk-acc-bad{color:var(--danger);}
.rkrk-acc-flash{padding:10px 12px;border-radius:10px;margin:0 0 12px;}
.rkrk-acc-flash--ok{background:#eff6ff;border:1px solid #93c5fd;color:#1e3a8a;}
.rkrk-acc-flash--err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}
.rkrk-acc-note{font-size:12px;opacity:.8;margin:4px 0 8px;}
.rkrk-acc-fold{border:1px solid var(--border);border-radius:10px;margin:0 0 8px;background:#fff;}
.rkrk-acc-fold > summary{cursor:pointer;padding:8px 12px;font-weight:650;list-style:none;display:flex;gap:8px;align-items:baseline;flex-wrap:wrap;}
.rkrk-acc-fold > summary::-webkit-details-marker{display:none;}
.rkrk-acc-fold > summary::before{content:"";width:.45em;height:.45em;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);display:inline-block;margin-right:2px;opacity:.55;flex:0 0 auto;}
.rkrk-acc-fold[open] > summary::before{transform:rotate(45deg);margin-top:-2px;}
.rkrk-acc-fold > .rkrk-acc-body{padding:0 12px 12px;}
.rkrk-acc-page > summary{font-weight:650;}
.rkrk-acc-sum{display:flex;gap:4px;flex-wrap:wrap;font-weight:500;font-size:12px;}
.rkrk-acc-bit{display:inline-block;padding:1px 6px;border-radius:999px;background:#f4f4f5;border:1px solid var(--border);color:#3f3f46;}
.rkrk-acc-bit--on{background:#e8f0e4;border-color:#9ab08f;color:#1f3d24;}
.rkrk-acc-bit--now{box-shadow:inset 0 0 0 1px #6b8f62;}
.rkrk-acc-tools{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 8px;}
.rkrk-acc-tools button{background:#fff;color:#1f2933;border:1px solid var(--border);border-radius:8px;padding:4px 10px;cursor:pointer;font:inherit;font-size:13px;}
.rkrk-acc-hosts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;}
.rkrk-acc-host{border:1px solid var(--border);border-radius:8px;padding:8px;min-width:0;}
.rkrk-acc-host--now{background:#f7fbf5;border-color:#9ab08f;}
.rkrk-acc-host h4{margin:0 0 6px;font-size:13px;}
.rkrk-acc-checks{display:grid;grid-template-columns:1fr 1fr;gap:1px 8px;}
.rkrk-acc-checks label,.rkrk-acc-host label{display:flex;align-items:flex-start;gap:4px;margin:0;font-size:12px;line-height:1.3;}
.rkrk-acc-en{font-weight:700;grid-column:1 / -1;margin-bottom:4px!important;}
.rkrk-acc-host input[type="text"]{width:100%;margin-top:6px;padding:4px 6px;border:1px solid var(--input-border);border-radius:6px;font:inherit;font-size:12px;}
@media (max-width:980px){.rkrk-acc-hosts{grid-template-columns:1fr 1fr;}}
@media (max-width:640px){.rkrk-acc-hosts,.rkrk-acc-checks{grid-template-columns:1fr;}}
.rkrk-acc-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;}
.rkrk-acc-actions button,.rkrk-acc-actions a.ql-btn{padding:8px 14px;border-radius:8px;border:0;cursor:pointer;font:inherit;}
.rkrk-acc-save{background:var(--brand-dark);color:#fff;}
.rkrk-acc-reset{background:#fff;border:1px solid var(--border)!important;color:var(--text);}
.rkrk-acc-code{font-family:ui-monospace,Consolas,monospace;font-size:12px;word-break:break-all;}
.rkrk-acc-pill{display:inline-block;padding:1px 7px;border-radius:999px;background:var(--green-vlight);border:1px solid var(--border);font-size:11px;margin-right:4px;}
.rkrk-acc-pill--on{background:#e8f0e4;border-color:#9ab08f;}
</style>

<div class="card rkrk-card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;">
	<div>
		<h2 style="margin:0;">Zugriffsrechte</h2>
		<p class="rkrk-acc-note" style="margin:2px 0 0;">
			<code>?ADMIN</code> und <code>?INSTR</code> gelten nur, wenn die Freigabe in der Zeile an ist.
			<a href="<?php echo rkrk_acc_h(function_exists("rkrk_assess_append_setting_href") ? rkrk_assess_append_setting_href("rkrk_assess_zugriff_hilfe.php") : "rkrk_assess_zugriff_hilfe.php"); ?>">Rollen und Zugriffe</a>
		</p>
	</div>
	<?php if (!empty($logopic) && !empty($QuizIconLoc)): ?>
	<img src="<?php echo rkrk_acc_h($QuizIconLoc . $logopic); ?>" alt="" style="height:36px;">
	<?php endif; ?>
</div>

<?php if ($flashOk !== ""): ?>
<div class="rkrk-acc-flash rkrk-acc-flash--ok"><?php echo rkrk_acc_h($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr !== ""): ?>
<div class="rkrk-acc-flash rkrk-acc-flash--err"><?php echo rkrk_acc_h($flashErr); ?></div>
<?php endif; ?>

<details class="rkrk-acc-fold">
	<summary>Dieser Server · <?php echo rkrk_acc_h($status["host_label"]); ?> · Moodle <?php echo !empty($status["moodle_up"]) ? "geladen" : "nicht geladen"; ?></summary>
	<div class="rkrk-acc-body">
	<div class="rkrk-acc-grid">
		<div class="rkrk-acc-kpi"><b>Profil</b><?php echo rkrk_acc_h($status["host_label"]); ?><div class="rkrk-acc-note"><?php echo rkrk_acc_h($status["host_detail"]); ?></div></div>
		<div class="rkrk-acc-kpi"><b>Host</b><span class="rkrk-acc-code"><?php echo rkrk_acc_h($status["host"]); ?></span></div>
		<div class="rkrk-acc-kpi"><b>Moodle</b>
			<?php if (!empty($status["moodle_up"])): ?>
			<span class="rkrk-acc-ok">geladen</span>
			<?php else: ?>
			<span class="rkrk-acc-bad">nicht geladen</span>
			<?php endif; ?>
			<div class="rkrk-acc-note">
				User-ID <?php echo rkrk_acc_h((string) (int) $status["moodle_user_id"]); ?>
				<?php echo !empty($status["moodle_logged_in"]) ? " · angemeldet" : " · nicht angemeldet"; ?>
				<?php echo !empty($status["moodle_siteadmin"]) ? " · Site-Admin" : ""; ?>
			</div>
		</div>
		<div class="rkrk-acc-kpi"><b>Policy-Datei</b>
			<?php echo !empty($status["policy_exists"]) ? "vorhanden" : "Standard (noch nicht gespeichert)"; ?>
			<div class="rkrk-acc-note"><?php echo !empty($status["policy_writable"]) ? "schreibbar" : "nicht schreibbar"; ?></div>
		</div>
	</div>
	<p class="rkrk-acc-note" style="margin-top:12px;">
		Aktuelle Cohorts:
		<?php
		if (empty($status["cohorts"])) {
			echo "keine Prüfung (Moodle nicht geladen oder nicht angemeldet).";
		} else {
			foreach ($status["cohorts"] as $ck => $hit) {
				$lab = isset($cohortCat[$ck]) ? $cohortCat[$ck]["label"] : $ck;
				$cls = $hit ? "rkrk-acc-pill rkrk-acc-pill--on" : "rkrk-acc-pill";
				echo '<span class="' . $cls . '">' . rkrk_acc_h($lab) . ($hit ? " ja" : " nein") . "</span>";
			}
		}
		?>
	</p>
	<p class="rkrk-acc-note">
		testpermissions_*-Originale
		<?php
		$pf = is_array($status["perm_files"] ?? null) ? $status["perm_files"] : array();
		if (!$pf) {
			echo ": nicht gefunden.";
		} else {
			echo " unter <span class=\"rkrk-acc-code\">" . rkrk_acc_h((string) $status["perm_root"]) . "</span>: ";
			foreach ($pf as $fn => $p) {
				echo rkrk_acc_h((string) $fn) . (is_string($p) && $p !== "" ? " ok" : " fehlt") . " · ";
			}
		}
		?>
		Assess wertet dieselben Cohort-IDs per <code>ismemberofcohort</code> aus (OR), ohne die Originaldateien direkt einzubinden — die würden bei einer fehlenden Gruppe sofort <code>die()</code>.
	</p>
	<?php if (!empty($policy["updated_at"])): ?>
	<p class="rkrk-acc-note">Zuletzt gespeichert: <?php echo rkrk_acc_h((string) $policy["updated_at"]); ?>
		<?php echo $policy["updated_by"] !== "" ? " · " . rkrk_acc_h((string) $policy["updated_by"]) : ""; ?></p>
	<?php endif; ?>
	</div>
</details>

<form method="post" action="<?php echo rkrk_acc_h(rkrk_acc_self_href()); ?>">
<input type="hidden" name="csrf" value="<?php echo rkrk_acc_h($csrf); ?>">
<?php if ($isAdminMode): ?><input type="hidden" name="ADMIN" value=""><?php endif; ?>
<?php if ($isInstrMode): ?><input type="hidden" name="INSTR" value=""><?php endif; ?>

<div class="card rkrk-card">
	<h3 style="margin:0 0 6px;">Regeln je Seite und Server</h3>
	<p class="rkrk-acc-note" style="margin-top:0;">
		Gilt jetzt: <strong><?php echo rkrk_acc_h((string) $status["host_label"]); ?></strong>.
		Extra-Gate aus heisst nur der Server-Standard. Extra-Gate an heisst: eine der gewählten Freigaben muss zutreffen.
	</p>
	<div class="rkrk-acc-tools">
		<button type="button" id="rkrk-acc-open-all">Alle aufklappen</button>
		<button type="button" id="rkrk-acc-close-all">Alle zuklappen</button>
	</div>
	<?php foreach ($policy["pages"] as $pid => $page): ?>
	<details class="rkrk-acc-fold rkrk-acc-page" data-page="<?php echo rkrk_acc_h((string) $pid); ?>">
		<summary>
			<span><?php echo rkrk_acc_h((string) $page["label"]); ?></span>
			<span class="rkrk-acc-sum">
			<?php foreach ($hostMeta as $hid => $hm):
				$ruleSum = $page["hosts"][$hid] ?? rkrk_assess_access_policy_empty_rule();
				$bits = rkrk_acc_rule_bits($ruleSum, $cohortCat);
				$bitCls = "rkrk-acc-bit" . ($bits !== array() ? " rkrk-acc-bit--on" : "") . ($hid === $currentHost ? " rkrk-acc-bit--now" : "");
				$bitTxt = (string) $hm["label"] . ": " . ($bits === array() ? "aus" : implode(", ", $bits));
			?>
				<span class="<?php echo rkrk_acc_h($bitCls); ?>"><?php echo rkrk_acc_h($bitTxt); ?></span>
			<?php endforeach; ?>
			</span>
		</summary>
		<div class="rkrk-acc-body">
			<p class="rkrk-acc-note" style="margin-top:0;"><?php echo rkrk_acc_h((string) ($page["note"] ?? "")); ?>
				<span class="rkrk-acc-code"><?php echo rkrk_acc_h(implode(", ", (array) ($page["scripts"] ?? array()))); ?></span>
			</p>
			<div class="rkrk-acc-hosts">
			<?php foreach ($hostMeta as $hid => $hm):
				$rule = $page["hosts"][$hid] ?? rkrk_assess_access_policy_empty_rule();
				$n = "rule[" . $pid . "][" . $hid . "]";
			?>
				<div class="rkrk-acc-host<?php echo $hid === $currentHost ? " rkrk-acc-host--now" : ""; ?>">
					<h4><?php echo rkrk_acc_h((string) $hm["label"]); ?></h4>
					<div class="rkrk-acc-checks">
						<label class="rkrk-acc-en"><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[enabled]" value="1"<?php echo !empty($rule["enabled"]) ? " checked" : ""; ?>> Extra-Gate</label>
						<?php foreach ($cohortCat as $ck => $c): ?>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[cohorts][]" value="<?php echo rkrk_acc_h($ck); ?>"<?php echo in_array($ck, (array) ($rule["cohorts"] ?? array()), true) ? " checked" : ""; ?>> <?php echo rkrk_acc_h((string) $c["label"]); ?></label>
						<?php endforeach; ?>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[allow_siteadmin]" value="1"<?php echo !empty($rule["allow_siteadmin"]) ? " checked" : ""; ?>> Site-Admin</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[allow_cs]" value="1"<?php echo !empty($rule["allow_cs"]) ? " checked" : ""; ?>> cs-Link</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[allow_admin_flag]" value="1"<?php echo !empty($rule["allow_admin_flag"]) ? " checked" : ""; ?>> ?ADMIN</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[allow_instr_flag]" value="1"<?php echo !empty($rule["allow_instr_flag"]) ? " checked" : ""; ?>> ?INSTR</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[allow_noh]" value="1"<?php echo !empty($rule["allow_noh"]) ? " checked" : ""; ?>> ?NOH</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[hash_gate]" value="1"<?php echo !empty($rule["hash_gate"]) ? " checked" : ""; ?>> Hash-Gate</label>
						<label><input type="checkbox" name="<?php echo rkrk_acc_h($n); ?>[fail_closed]" value="1"<?php echo !empty($rule["fail_closed"]) ? " checked" : ""; ?>> Fail-closed</label>
					</div>
					<input type="text" name="<?php echo rkrk_acc_h($n); ?>[extra_cohorts]" value="<?php echo rkrk_acc_h((string) ($rule["extra_cohorts"] ?? "")); ?>" placeholder="weitere Cohort-IDs">
				</div>
			<?php endforeach; ?>
			</div>
		</div>
	</details>
	<?php endforeach; ?>
	<div class="rkrk-acc-actions">
		<button type="submit" name="action" value="save" class="rkrk-acc-save">Regeln speichern</button>
		<button type="submit" name="action" value="reset" class="rkrk-acc-reset" onclick="return confirm('Alle Zugriffsregeln auf den Standard zurücksetzen?');">Standard wiederherstellen</button>
	</div>
</div>
</form>

<details class="rkrk-acc-fold">
	<summary>Wie die Checks zusammenhängen</summary>
	<div class="rkrk-acc-body">
	<ol style="margin:0 0 8px;padding-left:1.2rem;">
		<li><strong>RKRK-Login (GrantAccess)</strong> auf drohnenrehkitzrettung.ch, unabhängig von diesen Regeln.</li>
		<li><strong>Quiz-Hash-Gate</strong> prüft <code>cs</code> für geschützte Quiz-Keys, wenn die Zeile für den Server an ist.</li>
		<li><strong>Seiten-Gate</strong> prüft Cohorts, Site-Admin, cs und URL-Flags. Ohne Extra-Gate keine zusätzliche Sperre.</li>
	</ol>
	<p class="rkrk-acc-note" style="margin-bottom:0;">
		Policy-Datei: <span class="rkrk-acc-code"><?php echo rkrk_acc_h((string) $status["policy_file"]); ?></span>
		<?php if (!empty($status["moodle_config"])): ?>
		<br>Moodle config.php: <span class="rkrk-acc-code"><?php echo rkrk_acc_h((string) $status["moodle_config"]); ?></span>
		<?php endif; ?>
	</p>
	</div>
</details>
<script>
(function () {
	var nodes = document.querySelectorAll("details.rkrk-acc-page");
	var key = "rkrkAccOpen";
	var saved = {};
	try { saved = JSON.parse(localStorage.getItem(key) || "{}") || {}; } catch (e) { saved = {}; }
	function store() {
		var next = {};
		nodes.forEach(function (d) { next[d.getAttribute("data-page")] = d.open; });
		try { localStorage.setItem(key, JSON.stringify(next)); } catch (e) {}
	}
	nodes.forEach(function (d) {
		var id = d.getAttribute("data-page");
		if (Object.prototype.hasOwnProperty.call(saved, id)) { d.open = !!saved[id]; }
		d.addEventListener("toggle", store);
	});
	var openAll = document.getElementById("rkrk-acc-open-all");
	var closeAll = document.getElementById("rkrk-acc-close-all");
	if (openAll) { openAll.addEventListener("click", function () { nodes.forEach(function (d) { d.open = true; }); store(); }); }
	if (closeAll) { closeAll.addEventListener("click", function () { nodes.forEach(function (d) { d.open = false; }); store(); }); }
})();
</script>
</div>
