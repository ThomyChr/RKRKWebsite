<?php
/**
 * Zentrale Zugriffs-Policy für Assess.
 * Eine JSON-Datei steuert, welche Checks (Moodle-Cohort, cs-Hash, ADMIN/INSTR/NOH,
 * Hash-Gate) auf welchem Server für welche Seite gelten.
 */
if (!function_exists("rkrk_assess_access_policy_defaults")) {

	/** @return list<string> */
	function rkrk_assess_access_policy_host_ids(): array {
		return array("portal", "rkrs", "rkrk", "other");
	}

	/** @return array<string, array{id:string,label:string}> */
	function rkrk_assess_access_policy_cohort_catalog(): array {
		return array(
			"kerngruppe" => array("id" => "RKRSplusKerngruppe", "label" => "Kerngruppe"),
			"koordinator" => array("id" => "RKRSplusEinsatzleiter", "label" => "Koordinatoren"),
			"instruktoren" => array("id" => "RKRSplusInstruktorenDE", "label" => "Instruktoren"),
			"teilnehmer" => array("id" => "RKRSplusTeilnehmer", "label" => "Teilnehmer"),
		);
	}

	/** @return array<string, mixed> */
	function rkrk_assess_access_policy_empty_rule(): array {
		return array(
			"enabled" => false,
			"allow_cs" => false,
			"allow_siteadmin" => false,
			"allow_admin_flag" => false,
			"allow_instr_flag" => false,
			"allow_noh" => false,
			"hash_gate" => false,
			"fail_closed" => true,
			"cohorts" => array(),
			"extra_cohorts" => "",
		);
	}

	/**
	 * @param array<string, mixed> $over
	 * @return array<string, mixed>
	 */
	function rkrk_assess_access_policy_rule_merge(array $over): array {
		$base = rkrk_assess_access_policy_empty_rule();
		foreach ($base as $k => $v) {
			if (!array_key_exists($k, $over)) {
				continue;
			}
			if ($k === "cohorts") {
				$c = $over[$k];
				if (!is_array($c)) {
					$c = preg_split("/[\s,;]+/", (string) $c) ?: array();
				}
				$allowed = array_keys(rkrk_assess_access_policy_cohort_catalog());
				$out = array();
				foreach ($c as $item) {
					$item = strtolower(trim((string) $item));
					if ($item !== "" && in_array($item, $allowed, true) && !in_array($item, $out, true)) {
						$out[] = $item;
					}
				}
				$base[$k] = $out;
				continue;
			}
			if ($k === "extra_cohorts") {
				$base[$k] = trim((string) $over[$k]);
				continue;
			}
			$base[$k] = !empty($over[$k]);
		}
		return $base;
	}

	/**
	 * @param array<string, array<string, mixed>> $hostRules
	 * @return array<string, array<string, mixed>>
	 */
	function rkrk_assess_access_policy_hosts_fill(array $hostRules): array {
		$out = array();
		foreach (rkrk_assess_access_policy_host_ids() as $hid) {
			$out[$hid] = rkrk_assess_access_policy_rule_merge(
				(isset($hostRules[$hid]) && is_array($hostRules[$hid])) ? $hostRules[$hid] : array()
			);
		}
		return $out;
	}

	/**
	 * Standard = heutiges Verhalten der parallelen Checks.
	 *
	 * @return array<string, mixed>
	 */
	function rkrk_assess_access_policy_defaults(): array {
		$strictPersonel = rkrk_assess_access_policy_rule_merge(array(
			"enabled" => true,
			"allow_cs" => true,
			"allow_admin_flag" => true,
			"allow_instr_flag" => true,
			"allow_siteadmin" => true,
			"fail_closed" => true,
			"cohorts" => array("kerngruppe", "koordinator"),
		));
		$strictSessions = rkrk_assess_access_policy_rule_merge(array(
			"enabled" => true,
			"allow_cs" => true,
			"allow_siteadmin" => true,
			"fail_closed" => true,
			"cohorts" => array("instruktoren", "kerngruppe"),
		));
		$portalDigital = rkrk_assess_access_policy_rule_merge(array(
			"enabled" => true,
			"allow_cs" => true,
			"allow_admin_flag" => true,
			"allow_instr_flag" => true,
			"allow_noh" => true,
			"hash_gate" => true,
			"fail_closed" => true,
		));
		$portalHash = rkrk_assess_access_policy_rule_merge(array(
			"enabled" => true,
			"allow_admin_flag" => true,
			"allow_instr_flag" => true,
			"allow_noh" => true,
			"hash_gate" => true,
			"fail_closed" => true,
		));
		$adminAccess = rkrk_assess_access_policy_rule_merge(array(
			"enabled" => true,
			"allow_siteadmin" => true,
			"fail_closed" => true,
			"cohorts" => array("kerngruppe"),
		));
		$off = rkrk_assess_access_policy_empty_rule();

		$pages = array(
			"personel" => array(
				"label" => "Personal, Pilotenkarte, Einsatzberechtigung & Teilnehmerliste",
				"scripts" => array("rkrk_assess_live_personel.php", "rkrk_assess_live_personel_map.php", "rkrk_assess_einsatzstatus.php", "rkrk_assess_trainingstatus.php"),
				"cs_kind" => "personel",
				"note" => "Moodle: Kerngruppe oder Koordinatoren, oder gültige cs-Verbindung (Pilot + Name).",
				"hosts" => array(
					"portal" => $strictPersonel,
					"rkrs" => $strictPersonel,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"sessions" => array(
				"label" => "Tagesübersicht / Recovery",
				"scripts" => array("rkrk_assess_sessions.php"),
				"cs_kind" => "sessions",
				"note" => "Moodle: Instruktoren oder Kerngruppe, oder gültige cs-Verknüpfung.",
				"hosts" => array(
					"portal" => $strictSessions,
					"rkrs" => $strictSessions,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"digitalassess" => array(
				"label" => "Schulung & Quiz",
				"scripts" => array("rkrk_digitalassess.php"),
				"cs_kind" => "quiz",
				"note" => "Portal: ADMIN/INSTR/NOH oder gültiger Quiz-cs. Andere Server: kein Extra-Gate.",
				"hosts" => array(
					"portal" => $portalDigital,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"hash_gate" => array(
				"label" => "Quiz-Hash-Gate (cs)",
				"scripts" => array("rkrk_digitalassess.php", "rkrk_assess.php"),
				"cs_kind" => "quiz",
				"note" => "Prüft den cs-Hash für geschützte Quiz-Keys (RKRSp-doku, RKRSp-EP, …). Gilt zusätzlich zur Seitensperre.",
				"hosts" => array(
					"portal" => $portalHash,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"assess" => array(
				"label" => "Assessments",
				"scripts" => array("rkrk_assess.php"),
				"cs_kind" => "",
				"note" => "Standard: nur RKRK-Login (GrantAccess). Extra-Gate aus.",
				"hosts" => array(
					"portal" => $off,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"live" => array(
				"label" => "Beurteilungen",
				"scripts" => array("rkrk_assess_live.php"),
				"cs_kind" => "",
				"note" => "Standard: nur RKRK-Login (GrantAccess). Extra-Gate aus.",
				"hosts" => array(
					"portal" => $off,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"admin" => array(
				"label" => "Planung & Ergebnisse",
				"scripts" => array("rkrk_assess_admin.php"),
				"cs_kind" => "",
				"note" => "Home / Planungs- und Ergebnisübersicht.",
				"hosts" => array(
					"portal" => $off,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"staff_tools" => array(
				"label" => "Fragen-Manager & Editoren",
				"scripts" => array(
					"rkrk_digitalassess_admin.php",
					"rkrk_digitalassess_review.php",
					"rkrk_digitalassess_review_merge.php",
					"rkrk_digitalassess_learning_edit.php",
					"rkrk_digitalassess_rollback.php",
				),
				"cs_kind" => "",
				"note" => "Standard: RKRK-Login. Extra-Gate aus.",
				"hosts" => array(
					"portal" => $off,
					"rkrs" => $off,
					"rkrk" => $off,
					"other" => $off,
				),
			),
			"admin_access" => array(
				"label" => "Zugriffsrechte (diese Seite)",
				"scripts" => array("rkrk_assess_admin_access.php"),
				"cs_kind" => "",
				"note" => "Nur Kerngruppe oder Moodle-Site-Admin. ADMIN/INSTR allein reicht nicht.",
				"hosts" => array(
					"portal" => $adminAccess,
					"rkrs" => $adminAccess,
					"rkrk" => $off,
					"other" => $off,
				),
			),
		);

		return array(
			"version" => 1,
			"updated_at" => "",
			"updated_by" => "",
			"pages" => $pages,
		);
	}

	function rkrk_assess_access_policy_path(): string {
		return str_replace("\\", "/", dirname(__DIR__) . "/data/access_policy.json");
	}

	/**
	 * @param array<string, mixed> $saved
	 * @return array<string, mixed>
	 */
	function rkrk_assess_access_policy_merge_saved(array $saved): array {
		$defaults = rkrk_assess_access_policy_defaults();
		$defaults["updated_at"] = trim((string) ($saved["updated_at"] ?? ""));
		$defaults["updated_by"] = trim((string) ($saved["updated_by"] ?? ""));
		if (isset($saved["version"])) {
			$defaults["version"] = (int) $saved["version"];
		}
		if (!isset($saved["pages"]) || !is_array($saved["pages"])) {
			return $defaults;
		}
		foreach ($defaults["pages"] as $pid => $page) {
			$in = $saved["pages"][$pid] ?? null;
			if (!is_array($in) || !isset($in["hosts"]) || !is_array($in["hosts"])) {
				continue;
			}
			$defaults["pages"][$pid]["hosts"] = rkrk_assess_access_policy_hosts_fill($in["hosts"]);
		}
		return $defaults;
	}

	/** @return array<string, mixed> */
	function rkrk_assess_access_policy_load(bool $reload = false): array {
		static $cached = null;
		if ($reload) {
			$cached = null;
		}
		if (is_array($cached)) {
			return $cached;
		}
		$path = rkrk_assess_access_policy_path();
		$saved = array();
		if (is_file($path)) {
			$raw = @file_get_contents($path);
			if (is_string($raw) && trim($raw) !== "") {
				$decoded = json_decode($raw, true);
				if (is_array($decoded)) {
					$saved = $decoded;
				}
			}
		}
		$cached = rkrk_assess_access_policy_merge_saved($saved);
		return $cached;
	}

	/**
	 * @param array<string, mixed> $policy
	 * @return array{ok:bool, error:string, path:string}
	 */
	function rkrk_assess_access_policy_save(array $policy): array {
		$path = rkrk_assess_access_policy_path();
		$dir = dirname($path);
		$out = array("ok" => false, "error" => "", "path" => $path);
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
				$out["error"] = "Ordner data/ konnte nicht erstellt werden.";
				return $out;
			}
		}
		$merged = rkrk_assess_access_policy_merge_saved($policy);
		$merged["version"] = 1;
		$merged["updated_at"] = date("c");
		$who = "";
		if (function_exists("rkrk_assess_rkrs_moodle_user_id")) {
			$uid = rkrk_assess_rkrs_moodle_user_id();
			if ($uid > 1) {
				$who = "moodle:" . $uid;
			}
		}
		if ($who === "" && !empty($GLOBALS["InputUserInfo"]["Login"]["User"])) {
			$who = "login:" . (string) $GLOBALS["InputUserInfo"]["Login"]["User"];
		}
		if ($who === "" && !empty($_SERVER["REMOTE_USER"])) {
			$who = "user:" . (string) $_SERVER["REMOTE_USER"];
		}
		$merged["updated_by"] = $who;
		$json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($json) || $json === "") {
			$out["error"] = "JSON konnte nicht geschrieben werden.";
			return $out;
		}
		$tmp = $path . ".tmp";
		if (@file_put_contents($tmp, $json) === false) {
			$out["error"] = "Datei nicht beschreibbar: " . $path;
			return $out;
		}
		if (!@rename($tmp, $path)) {
			@unlink($path);
			if (!@rename($tmp, $path)) {
				@unlink($tmp);
				$out["error"] = "Speichern fehlgeschlagen.";
				return $out;
			}
		}
		$out["ok"] = true;
		return $out;
	}

	/** Live portal.rehkitzrettung.ch / rehkitzrettung.ch — nicht NAS und nicht drohnenrehkitzrettung.ch. */
	function rkrk_assess_access_policy_is_live_host(): bool {
		$host = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "")));
		if ($host === "") {
			return false;
		}
		if (strpos($host, "portal.rehkitzrettung.ch") !== false) {
			return true;
		}
		if (strpos($host, "drohnenrehkitzrettung.ch") !== false) {
			return false;
		}
		return preg_match('/(^|\.)rehkitzrettung\.ch$/i', $host) === 1;
	}

	function rkrk_assess_access_policy_host_id(): string {
		$host = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "")));
		if ($host !== "" && strpos($host, "portal.rehkitzrettung.ch") !== false) {
			return "portal";
		}
		if ($host !== "" && strpos($host, "drohnenrehkitzrettung.ch") !== false) {
			return "rkrk";
		}
		if ($host !== "" && preg_match('/(^|\.)rehkitzrettung\.ch$/i', $host) === 1) {
			return "rkrs";
		}
		$self = str_replace("\\", "/", (string) ($_SERVER["PHP_SELF"] ?? ""));
		$script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_FILENAME"] ?? ""));
		$dir = str_replace("\\", "/", (string) __DIR__);
		$blob = $self . "\n" . $script . "\n" . $dir;
		if (stripos($blob, "/rkrsplusassessment/") !== false || stripos($blob, "/portal.rehkitzrettung.ch/") !== false) {
			return "portal";
		}
		// NAS ./rkrs (Pfad /rkrs/) ist nicht Live-RKRS.
		return "other";
	}

	/** @return array<string, mixed> */
	function rkrk_assess_access_policy_host_meta(): array {
		return array(
			"portal" => array(
				"id" => "portal",
				"label" => "Portal",
				"detail" => "portal.rehkitzrettung.ch · Moodle",
			),
			"rkrs" => array(
				"id" => "rkrs",
				"label" => "RKRS",
				"detail" => "rehkitzrettung.ch · Live",
			),
			"rkrk" => array(
				"id" => "rkrk",
				"label" => "RKRK",
				"detail" => "drohnenrehkitzrettung.ch · GrantAccess-Login",
			),
			"other" => array(
				"id" => "other",
				"label" => "Sonst / Test",
				"detail" => "NAS, localhost, unbekannter Host",
			),
		);
	}

	function rkrk_assess_access_policy_script_page(): string {
		$script = basename((string) ($_SERVER["PHP_SELF"] ?? ""));
		if ($script === "") {
			$script = basename((string) ($_SERVER["SCRIPT_FILENAME"] ?? ""));
		}
		$policy = rkrk_assess_access_policy_load();
		foreach ($policy["pages"] as $pid => $page) {
			// hash_gate ist Zusatzprüfung (Bootstrap), keine Seiten-Identität.
			if ((string) $pid === "hash_gate") {
				continue;
			}
			$scripts = (isset($page["scripts"]) && is_array($page["scripts"])) ? $page["scripts"] : array();
			if (in_array($script, $scripts, true)) {
				return (string) $pid;
			}
		}
		return "";
	}

	/** Formular-/Storyboard-Ansicht: ohne cs-Link offen (Portal-Hash-Gate gilt nur für Quiz). */
	function rkrk_assess_access_policy_is_open_form_mode(): bool {
		$mode = trim((string) ($_GET["mode"] ?? $_POST["mode"] ?? ""));
		return in_array($mode, array(
			"form",
			"paperform",
			"storyboards",
			"editstoryboard",
			"storyboardprint",
			"storyboardpdf",
		), true);
	}

	/**
	 * @return array<string, mixed>
	 */
	function rkrk_assess_access_policy_rule(string $pageId, ?string $hostId = null): array {
		$pageId = trim($pageId);
		$hostId = $hostId === null ? rkrk_assess_access_policy_host_id() : $hostId;
		$policy = rkrk_assess_access_policy_load();
		if ($pageId === "" || !isset($policy["pages"][$pageId]["hosts"][$hostId])) {
			return rkrk_assess_access_policy_empty_rule();
		}
		return rkrk_assess_access_policy_hosts_fill($policy["pages"][$pageId]["hosts"])[$hostId];
	}

	/**
	 * @param array<string, mixed> $rule
	 * @return list<string>
	 */
	function rkrk_assess_access_policy_cohort_names(array $rule): array {
		$cat = rkrk_assess_access_policy_cohort_catalog();
		$out = array();
		$keys = (isset($rule["cohorts"]) && is_array($rule["cohorts"])) ? $rule["cohorts"] : array();
		foreach ($keys as $key) {
			$key = strtolower(trim((string) $key));
			if ($key !== "" && isset($cat[$key])) {
				$id = (string) $cat[$key]["id"];
				if ($id !== "" && !in_array($id, $out, true)) {
					$out[] = $id;
				}
			}
		}
		$extra = trim((string) ($rule["extra_cohorts"] ?? ""));
		if ($extra !== "") {
			foreach (preg_split("/[\s,;]+/", $extra) ?: array() as $id) {
				$id = trim((string) $id);
				if ($id !== "" && !in_array($id, $out, true)) {
					$out[] = $id;
				}
			}
		}
		return $out;
	}

	function rkrk_assess_access_policy_hash_gate_active(): bool {
		$rule = rkrk_assess_access_policy_rule("hash_gate");
		return !empty($rule["enabled"]) && !empty($rule["hash_gate"]);
	}

	function rkrk_assess_access_policy_cs_ok(string $pageId): bool {
		$kind = "";
		$policy = rkrk_assess_access_policy_load();
		if (isset($policy["pages"][$pageId]["cs_kind"])) {
			$kind = (string) $policy["pages"][$pageId]["cs_kind"];
		}
		if ($kind === "personel" && function_exists("rkrk_assess_personel_cs_request_ok")) {
			return rkrk_assess_personel_cs_request_ok();
		}
		if ($kind === "sessions" && function_exists("rkrk_assess_sessions_cs_request_ok")) {
			return rkrk_assess_sessions_cs_request_ok();
		}
		if ($kind === "quiz") {
			$quiz = trim((string) ($_GET["quiz"] ?? ""));
			if ($quiz !== "" && function_exists("rkrk_assess_hash_gate_quiz_granted") && rkrk_assess_hash_gate_quiz_granted($quiz)) {
				return true;
			}
			if ($quiz !== "" && trim((string) ($_GET["cs"] ?? "")) !== "" && function_exists("rkrk_assess_hash_gate_evaluate_request")) {
				$strict = !function_exists("rkrk_assess_hash_gate_query_is_truncated") || !rkrk_assess_hash_gate_query_is_truncated();
				$eval = rkrk_assess_hash_gate_evaluate_request($strict);
				return !empty($eval["ok"]);
			}
		}
		return false;
	}

	function rkrk_assess_access_policy_admin_flag(): bool {
		if (array_key_exists("ADMIN", $_GET) || array_key_exists("ADMIN", $_POST)) {
			return true;
		}
		return !empty($GLOBALS["onAdmin"]);
	}

	function rkrk_assess_access_policy_instr_flag(): bool {
		if (isset($_GET["INSTR"]) || isset($_POST["INSTR"])) {
			return true;
		}
		return !empty($GLOBALS["onInstr"]);
	}

	function rkrk_assess_access_policy_noh_flag(): bool {
		return array_key_exists("NOH", $_GET) || array_key_exists("NOH", $_POST);
	}

	/**
	 * @return array{ok:bool, via:string}
	 */
	function rkrk_assess_access_policy_evaluate(string $pageId): array {
		$rule = rkrk_assess_access_policy_rule($pageId);
		if (empty($rule["enabled"])) {
			return array("ok" => true, "via" => "gate_off");
		}
		if (rkrk_assess_access_policy_admin_flag()
			&& function_exists("rkrk_assess_access_policy_is_live_host")
			&& !rkrk_assess_access_policy_is_live_host()
		) {
			return array("ok" => true, "via" => "admin_flag_local");
		}
		if (!empty($rule["allow_cs"]) && rkrk_assess_access_policy_cs_ok($pageId)) {
			return array("ok" => true, "via" => "cs");
		}
		if (!empty($rule["allow_admin_flag"]) && rkrk_assess_access_policy_admin_flag()) {
			return array("ok" => true, "via" => "admin_flag");
		}
		if (!empty($rule["allow_instr_flag"]) && rkrk_assess_access_policy_instr_flag()) {
			return array("ok" => true, "via" => "instr_flag");
		}
		if (!empty($rule["allow_noh"]) && rkrk_assess_access_policy_noh_flag()) {
			return array("ok" => true, "via" => "noh");
		}

		// Vor testpermissions/Cohort: Moodle-Session der Portal-Anmeldung laden.
		if (function_exists("rkrk_assess_rkrs_moodle_bootstrap")) {
			rkrk_assess_rkrs_moodle_bootstrap();
		}

		$cohortKeys = (isset($rule["cohorts"]) && is_array($rule["cohorts"])) ? $rule["cohorts"] : array();
		if (count($cohortKeys) > 0 && function_exists("rkrk_assess_rkrs_require_once_any_permission")
			&& rkrk_assess_rkrs_require_once_any_permission($cohortKeys)
		) {
			return array("ok" => true, "via" => "permission_file");
		}
		$cohorts = rkrk_assess_access_policy_cohort_names($rule);
		$needMoodle = (!empty($rule["allow_siteadmin"]) || count($cohorts) > 0);
		if (!$needMoodle) {
			return array("ok" => false, "via" => "denied");
		}
		$up = function_exists("rkrk_assess_rkrs_moodle_bootstrap") && rkrk_assess_rkrs_moodle_bootstrap();
		if (!$up) {
			if (empty($rule["fail_closed"])) {
				return array("ok" => true, "via" => "moodle_down_open");
			}
			return array("ok" => false, "via" => "moodle_down");
		}
		if (!empty($rule["allow_siteadmin"]) && function_exists("is_siteadmin") && is_siteadmin()) {
			return array("ok" => true, "via" => "siteadmin");
		}
		if (count($cohorts) > 0 && function_exists("rkrk_assess_rkrs_moodle_in_any_cohort")
			&& rkrk_assess_rkrs_moodle_in_any_cohort($cohorts, false, false)
		) {
			return array("ok" => true, "via" => "cohort");
		}
		return array("ok" => false, "via" => "denied");
	}

	function rkrk_assess_access_policy_allowed(string $pageId): bool {
		return rkrk_assess_access_policy_evaluate($pageId)["ok"];
	}

	function rkrk_assess_access_policy_can_manage(): bool {
		$eval = rkrk_assess_access_policy_evaluate("admin_access");
		if (!empty($eval["ok"])) {
			return true;
		}
		$moodleUp = function_exists("rkrk_assess_rkrs_moodle_bootstrap") && rkrk_assess_rkrs_moodle_bootstrap();
		if (!$moodleUp && (rkrk_assess_access_policy_admin_flag() || rkrk_assess_access_policy_instr_flag())) {
			return true;
		}
		$host = rkrk_assess_access_policy_host_id();
		if ($host === "rkrk") {
			$level = "";
			if (isset($GLOBALS["SessNme_AccessLevel"]) && isset($_SESSION[$GLOBALS["SessNme_AccessLevel"]])) {
				$level = strtolower(trim((string) $_SESSION[$GLOBALS["SessNme_AccessLevel"]]));
			} elseif (isset($GLOBALS["InputUserInfo"]["Login"]["Level"])) {
				$level = strtolower(trim((string) $GLOBALS["InputUserInfo"]["Login"]["Level"]));
			}
			if (!empty($GLOBALS["LoggedOn"]) && $level !== "gast") {
				return true;
			}
		}
		return false;
	}

	function rkrk_assess_access_policy_should_auto_enforce(): bool {
		if (PHP_SAPI === "cli") {
			return false;
		}
		if (defined("RKRK_ASSESS_SKIP_ACCESS_POLICY_ENFORCE") && RKRK_ASSESS_SKIP_ACCESS_POLICY_ENFORCE) {
			return false;
		}
		if (defined("RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP") && RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP) {
			return false;
		}
		$mode = trim((string) ($_GET["mode"] ?? $_POST["mode"] ?? ""));
		if (in_array($mode, array(
			"personel_cs",
			"einsatzpruefunglink_cs",
			"einsatzpruefunglink_prefs",
		), true)) {
			return false;
		}
		if (function_exists("rkrk_assess_access_policy_is_open_form_mode") && rkrk_assess_access_policy_is_open_form_mode()) {
			return false;
		}
		return true;
	}

	function rkrk_assess_access_policy_auto_enforce(): void {
		if (!rkrk_assess_access_policy_should_auto_enforce()) {
			return;
		}
		$page = rkrk_assess_access_policy_script_page();
		if ($page === "" || $page === "admin_access") {
			return;
		}
		if (rkrk_assess_access_policy_allowed($page)) {
			return;
		}
		if (function_exists("rkrk_assess_portal_personel_emit_access_denied")) {
			rkrk_assess_portal_personel_emit_access_denied($page);
		}
		http_response_code(403);
		exit;
	}

	/** @return array<string, mixed> */
	function rkrk_assess_access_policy_runtime_status(): array {
		$hostId = rkrk_assess_access_policy_host_id();
		$meta = rkrk_assess_access_policy_host_meta();
		$path = rkrk_assess_access_policy_path();
		$moodleUp = function_exists("rkrk_assess_rkrs_moodle_bootstrap") && rkrk_assess_rkrs_moodle_bootstrap();
		$uid = function_exists("rkrk_assess_rkrs_moodle_user_id") ? rkrk_assess_rkrs_moodle_user_id() : 0;
		$siteadmin = $moodleUp && function_exists("is_siteadmin") && is_siteadmin();
		$logged = $moodleUp && function_exists("isloggedin") && isloggedin() && !(function_exists("isguestuser") && isguestuser());
		$permRoot = function_exists("rkrk_assess_rkrsplusassessment_root") ? rkrk_assess_rkrsplusassessment_root() : "";
		$permFiles = array();
		if (function_exists("rkrk_assess_rkrs_permission_file_names")) {
			foreach (rkrk_assess_rkrs_permission_file_names() as $fn) {
				$p = function_exists("rkrk_assess_rkrs_permission_file_path")
					? rkrk_assess_rkrs_permission_file_path($fn)
					: "";
				$permFiles[$fn] = $p;
			}
		}
		$cohortHits = array();
		if ($moodleUp && $uid > 1 && function_exists("ismemberofcohort")) {
			foreach (rkrk_assess_access_policy_cohort_catalog() as $key => $c) {
				$cohortHits[$key] = ismemberofcohort((string) $c["id"], $uid);
			}
		}
		$cfgPaths = function_exists("rkrk_assess_rkrs_moodle_candidate_paths")
			? rkrk_assess_rkrs_moodle_candidate_paths()
			: array("config" => array(), "moodlefunctions" => array());
		$configFound = "";
		foreach ($cfgPaths["config"] as $p) {
			$p = str_replace("\\", "/", (string) $p);
			if ($p !== "" && is_file($p)) {
				$configFound = $p;
				break;
			}
		}
		$mfFound = "";
		foreach ($cfgPaths["moodlefunctions"] as $p) {
			$p = str_replace("\\", "/", (string) $p);
			if ($p !== "" && is_file($p)) {
				$mfFound = $p;
				break;
			}
		}
		return array(
			"host" => (string) ($_SERVER["HTTP_HOST"] ?? ""),
			"host_id" => $hostId,
			"host_label" => (string) ($meta[$hostId]["label"] ?? $hostId),
			"host_detail" => (string) ($meta[$hostId]["detail"] ?? ""),
			"script" => basename((string) ($_SERVER["PHP_SELF"] ?? "")),
			"on_rkrs" => !empty($GLOBALS["onRKRS"]),
			"policy_file" => $path,
			"policy_exists" => is_file($path),
			"policy_writable" => (is_file($path) && is_writable($path)) || (!is_file($path) && is_writable(dirname($path))),
			"moodle_up" => $moodleUp,
			"moodle_user_id" => $uid,
			"moodle_logged_in" => $logged,
			"moodle_siteadmin" => $siteadmin,
			"moodle_config" => $configFound,
			"moodle_functions" => $mfFound,
			"perm_root" => $permRoot,
			"perm_files" => $permFiles,
			"cohorts" => $cohortHits,
			"admin_flag" => rkrk_assess_access_policy_admin_flag(),
			"instr_flag" => rkrk_assess_access_policy_instr_flag(),
			"grant_access" => function_exists("GrantAccess"),
			"logged_on_stub" => !empty($GLOBALS["LoggedOn"]),
		);
	}

	/**
	 * @param array<string, mixed> $rule
	 */
	function rkrk_assess_access_policy_rule_summary(array $rule): string {
		if (empty($rule["enabled"])) {
			return "kein Extra-Gate";
		}
		$bits = array();
		$cat = rkrk_assess_access_policy_cohort_catalog();
		$cnames = array();
		foreach ((isset($rule["cohorts"]) && is_array($rule["cohorts"])) ? $rule["cohorts"] : array() as $k) {
			$k = (string) $k;
			$cnames[] = isset($cat[$k]) ? (string) $cat[$k]["label"] : $k;
		}
		$extra = trim((string) ($rule["extra_cohorts"] ?? ""));
		if ($extra !== "") {
			$cnames[] = $extra;
		}
		if ($cnames) {
			$bits[] = "Moodle: " . implode(", ", $cnames);
		}
		if (!empty($rule["allow_siteadmin"])) {
			$bits[] = "Site-Admin";
		}
		if (!empty($rule["allow_cs"])) {
			$bits[] = "cs-Link";
		}
		if (!empty($rule["allow_admin_flag"])) {
			$bits[] = "?ADMIN";
		}
		if (!empty($rule["allow_instr_flag"])) {
			$bits[] = "?INSTR";
		}
		if (!empty($rule["allow_noh"])) {
			$bits[] = "?NOH";
		}
		if (!empty($rule["hash_gate"])) {
			$bits[] = "Hash-Gate";
		}
		if (empty($rule["fail_closed"])) {
			$bits[] = "fail-open";
		}
		return $bits ? implode(" · ", $bits) : "Sperre ohne Freigabe";
	}
}
