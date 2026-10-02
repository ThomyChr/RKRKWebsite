<?php
	$onRKRS = false;
	$folderoffset = "../";
	// Portal-Login hängt an MoodleSession. Eine schon aktive PHP-Session
	// (auto_start / anderer Name) blockiert sonst das Lesen dieser Anmeldung.
	$__rkrkHostEarly = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "")));
	$__rkrkMoodleHostEarly = ($__rkrkHostEarly !== ""
		&& strpos($__rkrkHostEarly, "drohnenrehkitzrettung.ch") === false
		&& (strpos($__rkrkHostEarly, "portal.rehkitzrettung.ch") !== false
			|| preg_match('/(^|\.)rehkitzrettung\.ch$/i', $__rkrkHostEarly) === 1));
	if ($__rkrkMoodleHostEarly && session_status() === PHP_SESSION_ACTIVE) {
		$__rkrkSessNameEarly = (string) session_name();
		if (stripos($__rkrkSessNameEarly, "MoodleSession") !== 0) {
			session_write_close();
			unset($_SESSION);
		}
	}
	unset($__rkrkHostEarly, $__rkrkMoodleHostEarly, $__rkrkSessNameEarly);
	include_once($folderoffset . 'scripts/rkrk_gen_ext.php');
	$hashAssessmentPath = $folderoffset . 'scripts/hash_assessment.php';
	if (is_file($hashAssessmentPath)) {
		include_once($hashAssessmentPath);
	}
	$__rkrkAccessPolicyInc = __DIR__ . "/rkrk_assess_access_policy.inc.php";
	if (is_file($__rkrkAccessPolicyInc)) {
		include_once $__rkrkAccessPolicyInc;
	}
	unset($__rkrkAccessPolicyInc);
	/**
	 * Portal-Host-Erkennung für Hash-Gate (früh im Bootstrap, unabhängig von späteren Helpern).
	 */
	function rkrk_assess_hash_gate_is_portal_host(): bool {
		$host = strtolower(trim((string)($_SERVER["HTTP_HOST"] ?? "")));
		return $host !== "" && strpos($host, "portal.rehkitzrettung.ch") !== false;
	}

	/**
	 * Debug-Freigabe für Hash-Gate Umschalter.
	 * Default ist aus; aktiv nur über Constant/Env in kontrollierten Umgebungen.
	 */
	function rkrk_assess_hash_gate_debug_allowed(): bool {
		if (defined("RKRK_ASSESS_HASH_DEBUG")) {
			return (bool) constant("RKRK_ASSESS_HASH_DEBUG");
		}
		$env = getenv("RKRK_ASSESS_HASH_DEBUG");
		if ($env === false) {
			return false;
		}
		$env = strtolower(trim((string) $env));
		return in_array($env, array("1", "true", "yes", "on"), true);
	}

	/**
	 * Zentraler Schalter: Hash-Prüfung auf portal.rehkitzrettung.ch standardmässig EIN.
	 * Abschaltbar per RKRK_ASSESS_HASH_ENABLED (Constant/Env) oder im Debug per hash_debug=off.
	 */
	function rkrk_assess_hash_gate_enabled(): bool {
		$hostOk = rkrk_assess_hash_gate_is_portal_host();
		if (function_exists("rkrk_assess_access_policy_hash_gate_active")) {
			$hostOk = rkrk_assess_access_policy_hash_gate_active();
		}
		if (!$hostOk) {
			return false;
		}
		if (defined("RKRK_ASSESS_HASH_ENABLED")) {
			$enabled = (bool) constant("RKRK_ASSESS_HASH_ENABLED");
		} else {
			$env = getenv("RKRK_ASSESS_HASH_ENABLED");
			if ($env === false) {
				$enabled = true;
			} else {
				$env = strtolower(trim((string) $env));
				$enabled = in_array($env, array("1", "true", "yes", "on"), true);
			}
		}
		if (!$enabled) {
			return false;
		}
		if (!rkrk_assess_hash_gate_debug_allowed()) {
			return true;
		}
		$debug = strtolower(trim((string) ($_GET["hash_debug"] ?? "")));
		if ($debug === "off" || $debug === "0" || $debug === "false") {
			return false;
		}
		return true;
	}

	/**
	 * Prüft, ob für den aktuellen Request eine Hash-Regel gilt (nur dann wird cs geprüft).
	 */
	function rkrk_assess_hash_gate_request_has_rule(): bool {
		$scope = basename((string) ($_SERVER["PHP_SELF"] ?? ""));
		$quiz = trim((string) ($_GET["quiz"] ?? ""));
		if ($quiz === "") {
			return false;
		}
		return rkrk_assess_hash_gate_resolve_rule($scope, $quiz) !== null;
	}

	/**
	 * Zentrale Regel-Registry: pro Seite (Scope) und Quiz-Key definierbar.
	 * Neue Felder werden nur hier ergänzt.
	 *
	 * @return array<string, array<string, array{required_fields: list<string>, optional_fields: list<string>, allow_extra: bool}>>
	 */
	function rkrk_assess_hash_gate_rule_registry(): array {
		$defaultRules = array(
			"RKRSp-doku" => array(
				"required_fields" => array("quiz", "PilotID", "Name", "Drone"),
				"optional_fields" => array(),
				"allow_extra" => true,
			),
			"RKRSp-EP" => array(
				"required_fields" => array("quiz", "Schulung", "EXAM", "PilotID", "Name", "Drone"),
				"optional_fields" => array(),
				"allow_extra" => true,
			),
			"RKRSp-EP-preuf" => array(
				"required_fields" => array("quiz", "Schulung", "QuizModus", "EXAM", "PilotID", "Name", "Drone"),
				"optional_fields" => array(),
				"allow_extra" => true,
			),
		);

		return array(
			"default" => $defaultRules,
			// Scope-Override Beispiele:
			// "rkrk_digitalassess.php" => array(
			//   "RKRSp-EP" => array("required_fields" => array(...), "optional_fields" => array(...), "allow_extra" => true),
			// ),
		);
	}

	/**
	 * @return array{required_fields: list<string>, optional_fields: list<string>, allow_extra: bool}|null
	 */
	function rkrk_assess_hash_gate_resolve_rule(string $scope, string $quiz): ?array {
		$scope = trim($scope);
		$quiz = trim($quiz);
		if ($quiz === "") {
			return null;
		}
		$registry = rkrk_assess_hash_gate_rule_registry();
		if ($scope !== "" && isset($registry[$scope][$quiz]) && is_array($registry[$scope][$quiz])) {
			return $registry[$scope][$quiz];
		}
		if (isset($registry["default"][$quiz]) && is_array($registry["default"][$quiz])) {
			return $registry["default"][$quiz];
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $request
	 */
	function rkrk_assess_hash_gate_payload_from_rule(array $rule, array $request): ?string {
		$build = rkrk_assess_hash_gate_build_payload_from_rule($rule, $request);
		return $build["payload"];
	}

	/**
	 * Kanonische Feld-Reihenfolge für den cs-Hash (muss dem Link-Generator entsprechen):
	 * quiz . QuizModus . EXAM . ohne_bilder . Status . RKRSPilotID . Name . Drone
	 * (+ Salt in assessment::entry).
	 * Felder werden nur einbezogen, wenn der Parameter in der URL-QUERY vorhanden ist.
	 *
	 * @return list<string>
	 */
	function rkrk_assess_hash_gate_hash_fields(): array {
		return array("quiz", "QuizModus", "EXAM", "ohne_bilder", "Status", "RKRSPilotID", "Name", "Drone");
	}

	/**
	 * Parameter-Keys aus dem originalen QUERY_STRING (vor Session-Grant / Aliasen).
	 * «Vorhanden» für den Hash = wirklich in der URL, nicht nachträglich injiziert.
	 *
	 * @return array<string, true> lowercase key => true
	 */
	function rkrk_assess_hash_gate_url_param_keys(): array {
		$keys = array();
		$qs = (string) ($_SERVER["QUERY_STRING"] ?? "");
		if ($qs === "") {
			$uriQ = parse_url((string) ($_SERVER["REQUEST_URI"] ?? ""), PHP_URL_QUERY);
			if (is_string($uriQ)) {
				$qs = $uriQ;
			}
		}
		if ($qs === "") {
			return $keys;
		}
		$parsed = array();
		parse_str($qs, $parsed);
		if (is_array($parsed)) {
			foreach ($parsed as $k => $_v) {
				$keys[strtolower((string) $k)] = true;
			}
		}
		if (preg_match_all('/(?:^|&)([^=&]+)=/', $qs, $m) > 0) {
			foreach ($m[1] as $rawKey) {
				$keys[strtolower(rawurldecode(str_replace("+", " ", (string) $rawKey)))] = true;
			}
		}
		return $keys;
	}

	/**
	 * @param array<string, true> $urlKeys
	 */
	function rkrk_assess_hash_gate_url_has_param(array $urlKeys, string $name): bool {
		return isset($urlKeys[strtolower($name)]);
	}

	/**
	 * @param array<string, mixed> $request
	 * @param bool $strictUrlKeys true = nur Parameter aus QUERY_STRING (Quiz-Link-Prüfung);
	 *                             false = Keys aus $request (cs-API / Link-Generator)
	 * @return array{payload: ?string, missing_field: ?string, required_fields: list<string>}
	 */
	function rkrk_assess_hash_gate_build_payload_from_rule(array $rule, array $request, bool $strictUrlKeys = false): array {
		$required = isset($rule["required_fields"]) && is_array($rule["required_fields"])
			? $rule["required_fields"]
			: array();
		// 1) Pflichtfeld-Prüfung (unabhängig vom Hash-Payload).
		foreach ($required as $field) {
			$key = (string) $field;
			if ($key === "") {
				return array(
					"payload" => null,
					"missing_field" => null,
					"required_fields" => $required,
				);
			}
			// Flag-/optionale Parameter dürfen fehlen
			// (Schulung = Flag; PilotID optional für Theorie-Links mit nur Name+Drohne;
			// QuizModus wird bei RKRSp-EP-preuf per Default gesetzt).
			if (!array_key_exists($key, $request)) {
				if (strcasecmp($key, "Schulung") === 0 || strcasecmp($key, "PilotID") === 0) {
					continue;
				}
				return array(
					"payload" => null,
					"missing_field" => $key,
					"required_fields" => $required,
				);
			}
		}
		// 2) Payload strikt in Generator-Reihenfolge.
		$urlKeys = $strictUrlKeys ? rkrk_assess_hash_gate_url_param_keys() : null;
		$payload = "";
		foreach (rkrk_assess_hash_gate_hash_fields() as $field) {
			if ($field === "RKRSPilotID") {
				$useRkrs = ($urlKeys === null || rkrk_assess_hash_gate_url_has_param($urlKeys, "RKRSPilotID"))
					&& array_key_exists("RKRSPilotID", $request)
					&& trim((string) $request["RKRSPilotID"]) !== "";
				$usePilot = ($urlKeys === null || rkrk_assess_hash_gate_url_has_param($urlKeys, "PilotID"))
					&& array_key_exists("PilotID", $request)
					&& trim((string) $request["PilotID"]) !== "";
				if ($useRkrs) {
					$payload .= (string) $request["RKRSPilotID"];
				} elseif ($usePilot) {
					$payload .= (string) $request["PilotID"];
				}
				continue;
			}
			if ($urlKeys !== null && !rkrk_assess_hash_gate_url_has_param($urlKeys, $field)) {
				continue;
			}
			if (array_key_exists($field, $request)) {
				$payload .= (string) $request[$field];
			}
		}
		return array(
			"payload" => $payload,
			"missing_field" => null,
			"required_fields" => $required,
		);
	}

	function rkrk_assess_hash_gate_debug_fail_output_requested(): bool {
		if (!rkrk_assess_hash_gate_debug_allowed()) {
			return false;
		}
		$debug = strtolower(trim((string) ($_GET["hash_debug"] ?? "")));
		return in_array($debug, array("1", "true", "on", "info"), true);
	}

	function rkrk_assess_hash_gate_debug_info_requested(): bool {
		if (!rkrk_assess_hash_gate_debug_allowed()) {
			return false;
		}
		return strtolower(trim((string) ($_GET["hash_debug"] ?? ""))) === "info";
	}

	/**
	 * Technische Hash-Details (Payload, erwarteter Hash) nur im expliziten Debug-Modus.
	 */
	function rkrk_assess_hash_gate_show_technical_details(): bool {
		return rkrk_assess_hash_gate_debug_fail_output_requested();
	}

	/**
	 * Diagnose-Ausgabe nur mit RKRK_ASSESS_HASH_DEBUG und hash_debug in der URL.
	 */
	function rkrk_assess_hash_gate_portal_trace_requested(): bool {
		if (!rkrk_assess_hash_gate_debug_allowed()) {
			return false;
		}
		$debug = strtolower(trim((string) ($_GET["hash_debug"] ?? "")));
		return in_array($debug, array("1", "true", "on", "info"), true);
	}

	/**
	 * @param array<string, string> $rows
	 */
	function rkrk_assess_hash_gate_emit_trace_html_and_exit(array $rows, string $title, int $status = 200): void {
		if (!headers_sent()) {
			http_response_code($status);
			header("Content-Type: text/html; charset=utf-8");
		}
		$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><title>"
			. rkrk_assess_hash_gate_h($title)
			. "</title><style>body{font-family:system-ui,sans-serif;margin:2rem;line-height:1.5}"
			. "table{border-collapse:collapse}th,td{border:1px solid #ccc;padding:.4rem .6rem;text-align:left;vertical-align:top}"
			. "th{background:#f5f5f5;width:14rem}code{word-break:break-all}</style></head><body><h1>"
			. rkrk_assess_hash_gate_h($title)
			. "</h1><p>Hash-Gate Diagnose (portal.rehkitzrettung.ch, <code>hash_debug</code> in der URL).</p><table>";
		foreach ($rows as $label => $value) {
			if ($value === "") {
				continue;
			}
			$html .= "<tr><th>" . rkrk_assess_hash_gate_h($label) . "</th><td><code>"
				. rkrk_assess_hash_gate_h($value) . "</code></td></tr>";
		}
		$html .= "</table></body></html>";
		echo $html;
		exit;
	}

	/**
	 * @param array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * } $result
	 * @return array<string, string>
	 */
	function rkrk_assess_hash_gate_trace_rows(array $result): array {
		$requiredFields = isset($result["required_fields"]) && is_array($result["required_fields"])
			? implode(", ", $result["required_fields"])
			: "";
		return array(
			"Ergebnis" => !empty($result["ok"]) ? "OK — Zugriff erlaubt" : "BLOCKIERT",
			"Grund" => (string) ($result["reason"] ?? ""),
			"Host" => (string) ($_SERVER["HTTP_HOST"] ?? ""),
			"Scope" => (string) ($result["scope"] ?? ""),
			"Quiz" => (string) ($result["quiz"] ?? ""),
			"Regel aktiv" => !empty($result["rule_found"]) ? "ja" : "nein",
			"Pflichtfelder" => $requiredFields,
			"Fehlendes Feld" => (string) ($result["missing_field"] ?? ""),
			"Payload" => (string) ($result["payload"] ?? ""),
			"Client cs" => (string) ($result["client_cs"] ?? ""),
			"Erwarteter Hash" => (string) ($result["expected_cs"] ?? ""),
		);
	}

	/**
	 * @return array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * }
	 */
	/**
	 * QUERY_STRING enthält quiz, aber cs/EXAM/Name … nur in $_GET (Session-Grant oder POST).
	 * Strikte URL-Keys würden dann nur «quiz» hashen → hash_mismatch trotz gültigem Grant.
	 */
	function rkrk_assess_hash_gate_query_is_truncated(): bool {
		$urlKeys = rkrk_assess_hash_gate_url_param_keys();
		$csInGet = trim((string) ($_GET["cs"] ?? "")) !== "";
		$csInQs = rkrk_assess_hash_gate_url_has_param($urlKeys, "cs");
		if ($csInGet && !$csInQs) {
			return true;
		}
		foreach (array("EXAM", "QuizModus", "Name", "Drone", "RKRSPilotID") as $field) {
			$inGet = array_key_exists($field, $_GET) && trim((string) $_GET[$field]) !== "";
			if ($inGet && !rkrk_assess_hash_gate_url_has_param($urlKeys, $field)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Session-Grant für dieses Quiz (unabgelaufen, cs passt oder URL hat keinen anderen cs).
	 */
	function rkrk_assess_hash_gate_grant_matches(string $quiz): bool {
		$quiz = trim($quiz);
		if ($quiz === "") {
			return false;
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$g = $_SESSION["rkrkAssessHashGrant"][$quiz] ?? null;
		if (!is_array($g)) {
			return false;
		}
		if ((time() - (int) ($g["ts"] ?? 0)) > 28800) {
			return false;
		}
		$grantCs = strtolower(trim((string) ($g["cs"] ?? "")));
		if ($grantCs === "") {
			return false;
		}
		$clientCs = strtolower(trim((string) ($_GET["cs"] ?? "")));
		if ($clientCs !== "" && !hash_equals($grantCs, $clientCs)) {
			return false;
		}
		return true;
	}

	/**
	 * Hash-Felder aus dem aktuellen Request (für Menü-Links / urladder), ohne Status:
	 * Status wird intern gesetzt und darf den cs nicht nachträglich ändern.
	 *
	 * @return array<string, string>
	 */
	function rkrk_assess_hash_gate_carry_fields_from_request(): array {
		$out = array();
		foreach (array("quiz", "QuizModus", "EXAM", "RKRSPilotID", "Name", "Drone", "cs") as $k) {
			if (isset($_GET[$k]) && trim((string) $_GET[$k]) !== "") {
				$out[$k] = (string) $_GET[$k];
			}
		}
		return $out;
	}

	function rkrk_assess_hash_gate_append_urladder(string $urladder): string {
		foreach (rkrk_assess_hash_gate_carry_fields_from_request() as $k => $v) {
			if ($k === "quiz") {
				continue;
			}
			if (preg_match("/(?:^|&)" . preg_quote($k, "/") . "(?:=|&|$)/", $urladder) === 1) {
				continue;
			}
			$bit = $k . "=" . rawurlencode($v);
			$urladder = ($urladder === "") ? $bit : ($urladder . "&" . $bit);
		}
		return $urladder;
	}

	function rkrk_assess_hash_gate_evaluate_request(bool $strictUrlKeys = true): array {
		$scope = basename((string) ($_SERVER["PHP_SELF"] ?? ""));
		$quiz = trim((string) ($_GET["quiz"] ?? ""));
		$result = array(
			"ok" => false,
			"reason" => "unknown",
			"scope" => $scope,
			"quiz" => $quiz,
			"rule_found" => false,
			"required_fields" => array(),
			"missing_field" => null,
			"payload" => null,
			"client_cs" => strtolower(trim((string) ($_GET["cs"] ?? ""))),
			"expected_cs" => "",
		);

		if (!class_exists("assessment") || !is_callable(array("assessment", "entry"))) {
			$result["reason"] = "assessment_class_missing";
			return $result;
		}
		$rule = rkrk_assess_hash_gate_resolve_rule($scope, $quiz);
		if ($rule === null) {
			$result["ok"] = true;
			$result["reason"] = "skipped_no_rule";
			return $result;
		}
		$result["rule_found"] = true;
		$build = rkrk_assess_hash_gate_build_payload_from_rule($rule, $_GET, $strictUrlKeys);
		$result["required_fields"] = $build["required_fields"];
		$result["missing_field"] = $build["missing_field"];
		$result["payload"] = $build["payload"];
		if ($build["payload"] === null) {
			$result["reason"] = "missing_field";
			return $result;
		}
		if ($result["client_cs"] === "") {
			$result["reason"] = "missing_cs";
			return $result;
		}
		$result["expected_cs"] = strtolower((string) assessment::entry($build["payload"]));
		if ($result["expected_cs"] === "") {
			$result["reason"] = "expected_empty";
			return $result;
		}
		if (!hash_equals($result["expected_cs"], $result["client_cs"])) {
			$result["reason"] = "hash_mismatch";
			return $result;
		}
		$result["ok"] = true;
		$result["reason"] = "ok";
		return $result;
	}

	function rkrk_assess_hash_gate_verify_request(): bool {
		return rkrk_assess_hash_gate_evaluate_request()["ok"];
	}

	function rkrk_assess_hash_gate_h(string $value): string {
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
	}

	function rkrk_assess_hash_gate_deny_message(array $result): string {
		$reason = (string) ($result["reason"] ?? "");
		$missingField = (string) ($result["missing_field"] ?? "");
		switch ($reason) {
			case "missing_cs":
				return "Der Zugriffscode (Parameter cs) fehlt in der URL.";
			case "missing_field":
				return $missingField !== ""
					? "Pflichtparameter fehlt in der URL: " . $missingField
					: "Ein Pflichtparameter fehlt in der URL.";
			case "hash_mismatch":
				return "Der Zugriffscode cs stimmt nicht mit den übrigen Parametern überein. "
					. "Bitte Link vollständig aus dem Portal übernehmen oder Schulungsleitung kontaktieren.";
			case "assessment_class_missing":
				return "Zugriffsprüfung auf dem Server nicht verfügbar. Bitte Schulungsleitung kontaktieren.";
			case "expected_empty":
				return "Zugriffscode konnte nicht geprüft werden. Bitte Schulungsleitung kontaktieren.";
			case "direct_access":
				return "Sie haben die Fehlerseite direkt geöffnet (z. B. nach einem alten Redirect). "
					. "Bitte den vollständigen Quiz-Link aus dem Link-Generator erneut öffnen "
					. "(URL mit quiz=…, PilotID oder RKRSPilotID, Name, Drone, cs=…).";
			case "staff_or_cs_required":
				return "Zugriff auf dem Portal nur mit &ADMIN, &INSTR, &NOH oder einem gültigen cs-Link "
					. "(Link-Generator / Schulungsleitung).";
			case "cohort_or_link_required":
				return "Auf rehkitzrettung.ch ist der Zugriff nur für die berechtigte Moodle-Gruppe "
					. "(zuerst im Portal anmelden) oder mit einem gültigen Verbindungs-/Verknüpfungs-Link "
					. "(Parameter cs) erlaubt.";
			default:
				return "Zugriff auf dieses Quiz ist mit den angegebenen Parametern nicht möglich.";
		}
	}

	/**
	 * @param array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * } $result
	 */
	function rkrk_assess_hash_gate_deny_html(array $result): string {
		return rkrk_assess_hash_gate_deny_page_html($result);
	}

	function rkrk_assess_hash_gate_deny_page_html(array $result): string {
		$title = "Zugriff verweigert";
		$message = rkrk_assess_hash_gate_deny_message($result);
		$requiredFields = isset($result["required_fields"]) && is_array($result["required_fields"])
			? implode(", ", $result["required_fields"])
			: "";
		$rows = array(
			"Quiz" => (string) ($result["quiz"] ?? ""),
			"Technischer Grund" => (string) ($result["reason"] ?? ""),
			"Pflichtfelder" => $requiredFields,
			"Fehlendes Feld" => (string) ($result["missing_field"] ?? ""),
			"Payload" => (string) ($result["payload"] ?? ""),
			"Ihr cs" => (string) ($result["client_cs"] ?? ""),
			"Erwarteter Hash" => (string) ($result["expected_cs"] ?? ""),
		);
		$portalHome = "https://portal.rehkitzrettung.ch/";
		$mailto = "thomas.christen@rehkitzrettung.ch?subject="
			. rawurlencode("RKRS Quiz — Zugriff verweigert");
		$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
			. "<title>" . rkrk_assess_hash_gate_h($title) . "</title>"
			. "<style>"
			. "body{font-family:system-ui,-apple-system,sans-serif;margin:0;line-height:1.5;background:#f7f7f7;color:#1a1a1a}"
			. ".wrap{max-width:44rem;margin:0 auto;padding:2rem 1.25rem}"
			. ".card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}"
			. "h1{color:#b00020;margin:0 0 .75rem;font-size:1.5rem}"
			. ".intro{margin:0 0 1.25rem;font-size:1.05rem}"
			. "table{border-collapse:collapse;width:100%;font-size:.92rem}"
			. "th,td{border:1px solid #e0e0e0;padding:.45rem .65rem;text-align:left;vertical-align:top}"
			. "th{background:#f3f3f3;width:10.5rem;font-weight:600}"
			. "code{word-break:break-all;font-size:.88em}"
			. ".actions{margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem}"
			. ".btn{display:inline-block;padding:.55rem 1rem;border-radius:6px;text-decoration:none;font-size:.95rem}"
			. ".btn--primary{background:#2e7d32;color:#fff}.btn--secondary{background:#eee;color:#222;border:1px solid #ccc}"
			. ".foot{margin-top:1.25rem;font-size:.85rem;color:#666}"
			. "</style></head><body><div class=\"wrap\"><div class=\"card\"><h1>"
			. rkrk_assess_hash_gate_h($title)
			. "</h1><p class=\"intro\">"
			. rkrk_assess_hash_gate_h($message)
			. "</p>";
		// Immer kurze Diagnose (ohne Hashes), damit fehlendes PilotID etc. sichtbar ist.
		$denyHint = "Wenn die QUERY_STRING nur quiz/PersPick zeigt, kam der Aufruf aus dem Menü oder einem internen Link ohne cs. Bitte den vollständigen Generator-Link (mit EXAM, Name, Drone, cs) in die Adresszeile einfügen.";
		$portalLoginRow = "";
		if ((string) ($result["reason"] ?? "") === "cohort_or_link_required") {
			$scope = (string) ($result["scope"] ?? "");
			$groups = ($scope === "sessions")
				? "Kerngruppe oder Instruktoren"
				: "die berechtigte Moodle-Gruppe";
			$moodleUp = function_exists("rkrk_assess_rkrs_moodle_bootstrap") && rkrk_assess_rkrs_moodle_bootstrap();
			$uid = function_exists("rkrk_assess_rkrs_moodle_user_id") ? rkrk_assess_rkrs_moodle_user_id() : 0;
			$guest = function_exists("isguestuser") && isguestuser();
			$logged = $moodleUp && $uid > 1 && !$guest;
			$sessName = (session_status() === PHP_SESSION_ACTIVE) ? (string) session_name() : "keine";
			$portalLoginRow = $logged
				? ("erkannt, aber nicht in " . $groups)
				: "nicht erkannt";
			$portalLoginRow .= " (Moodle " . ($moodleUp ? "geladen" : "nicht geladen")
				. ", Benutzer " . (string) $uid
				. ", Session " . $sessName . ")";
			$denyHint = "Zuerst im Portal anmelden (gleiche Browser-Sitzung), danach diese Seite neu laden. "
				. "ADMIN allein genügt nicht. Zugriff haben " . $groups . " oder ein gültiger cs-Link.";
		}
		$publicRows = array(
			"Quiz" => (string) ($result["quiz"] ?? ""),
			"Grund" => (string) ($result["reason"] ?? ""),
			"Fehlendes Feld" => (string) ($result["missing_field"] ?? ""),
			"Portal-Login" => $portalLoginRow,
			"Empfangene Parameter" => implode(", ", array_keys($_GET)),
			"QUERY_STRING (gekürzt)" => substr((string)($_SERVER["QUERY_STRING"] ?? ""), 0, 300),
			"Hinweis" => $denyHint,
		);
		$html .= "<table>";
		foreach ($publicRows as $label => $value) {
			if ($value === "") {
				continue;
			}
			$html .= "<tr><th>" . rkrk_assess_hash_gate_h($label) . "</th><td><code>"
				. rkrk_assess_hash_gate_h($value) . "</code></td></tr>";
		}
		$html .= "</table>";
		if (rkrk_assess_hash_gate_show_technical_details()) {
			$html .= "<table>";
			foreach ($rows as $label => $value) {
				if ($value === "") {
					continue;
				}
				$html .= "<tr><th>" . rkrk_assess_hash_gate_h($label) . "</th><td><code>"
					. rkrk_assess_hash_gate_h($value) . "</code></td></tr>";
			}
			$html .= "</table>";
		}
		$html .= "<div class=\"actions\">"
			. "<a class=\"btn btn--primary\" href=\"" . rkrk_assess_hash_gate_h($portalHome) . "\">Zurück zum Portal</a>"
			. "<a class=\"btn btn--secondary\" href=\"mailto:" . rkrk_assess_hash_gate_h($mailto) . "\">Schulungsleitung kontaktieren</a>"
			. "</div><p class=\"foot\">Rehkitzrettung Schweiz — geschützter Quiz-Zugriff</p>"
			. "</div></div></body></html>";
		return $html;
	}

	function rkrk_assess_hash_gate_no_access_url(): string {
		$self = str_replace("\\", "/", (string) ($_SERVER["PHP_SELF"] ?? ""));
		if (strpos($self, "/data/rkrsplus_ep/") !== false) {
			if (preg_match('#^(.*)/data/rkrsplus_ep/#', $self, $matches) === 1) {
				return $matches[1] . "/data/rkrsplus_ep/rkrk_assess_no_access.php";
			}
			return "rkrk_assess_no_access.php";
		}
		if (strpos($self, "/data/assess/") !== false) {
			if (preg_match('#^(.*)/data/assess/#', $self, $matches) === 1) {
				return $matches[1] . "/assess/public/rkrk_assess_no_access.php";
			}
			return "../../assess/public/rkrk_assess_no_access.php";
		}
		$dir = dirname($self);
		if ($dir === "/" || $dir === ".") {
			return "/rkrk_assess_no_access.php";
		}
		return rtrim($dir, "/") . "/rkrk_assess_no_access.php";
	}

	/**
	 * @param array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * } $result
	 */
	/**
	 * URL-Aliase für Hash-Gate und nachfolgende Logik normalisieren (direkt in $_GET).
	 */
	function rkrk_assess_hash_gate_normalize_request_aliases(): void {
		$mergeIntoGet = static function (array $parsed): void {
			foreach ($parsed as $k => $v) {
				$key = (string)$k;
				if ($key === "") {
					continue;
				}
				$cur = array_key_exists($key, $_GET) ? trim((string)$_GET[$key]) : "";
				if ($cur === "") {
					$_GET[$key] = $v;
				}
			}
		};

		// 1) QUERY_STRING / REQUEST_URI nachziehen (manche Proxys füllen $_GET unvollständig).
		$qs = (string)($_SERVER["QUERY_STRING"] ?? "");
		if ($qs !== "") {
			$parsed = array();
			parse_str($qs, $parsed);
			if (is_array($parsed)) {
				$mergeIntoGet($parsed);
			}
			// Regex-Fallback für EXAM/QuizModus falls parse_str/$_GET sie verschluckt.
			foreach (array("EXAM", "QuizModus", "Schulung", "PilotID", "RKRSPilotID", "Name", "Drone", "ohne_bilder", "Status", "cs", "quiz") as $need) {
				if (array_key_exists($need, $_GET) && trim((string)$_GET[$need]) !== "") {
					continue;
				}
				if (preg_match('/(?:^|&)' . preg_quote($need, "/") . '=([^&]*)/i', $qs, $m) === 1) {
					$_GET[$need] = rawurldecode(str_replace("+", " ", (string)$m[1]));
				}
			}
		}
		$uriQ = parse_url((string)($_SERVER["REQUEST_URI"] ?? ""), PHP_URL_QUERY);
		if (is_string($uriQ) && $uriQ !== "") {
			$parsedUri = array();
			parse_str($uriQ, $parsedUri);
			if (is_array($parsedUri)) {
				$mergeIntoGet($parsedUri);
			}
		}

		// 2) POST-Felder (Form-Hidden) nachziehen, falls GET unvollständig.
		if (!empty($_POST) && is_array($_POST)) {
			foreach (array("EXAM", "QuizModus", "Schulung", "PilotID", "RKRSPilotID", "Name", "Drone", "quiz", "cs") as $k) {
				$cur = array_key_exists($k, $_GET) ? trim((string)$_GET[$k]) : "";
				if ($cur === "" && isset($_POST[$k]) && trim((string)$_POST[$k]) !== "") {
					$_GET[$k] = (string)$_POST[$k];
				}
			}
		}

		// 3) Case-insensitive + bekannte Aliase.
		$aliasMap = array(
			"QuizModus" => array("QuizModus", "quizmodus", "QModus", "qmodus", "Modus"),
			"EXAM" => array("EXAM", "Exam", "exam", "ExamCode", "exam_code", "EXAMCode", "PruefCode"),
			"PilotID" => array("PilotID", "pilotId", "pilotid"),
			"RKRSPilotID" => array("RKRSPilotID", "rkrsPilotId"),
			"Schulung" => array("Schulung", "schulung"),
			"Name" => array("Name", "name"),
			"Drone" => array("Drone", "drone", "Drohne"),
			"cs" => array("cs", "CS"),
			"quiz" => array("quiz", "Quiz"),
		);
		foreach ($aliasMap as $canonical => $aliases) {
			$cur = array_key_exists($canonical, $_GET) ? trim((string)$_GET[$canonical]) : "";
			if ($cur !== "") {
				continue;
			}
			foreach ($aliases as $a) {
				foreach ($_GET as $k => $v) {
					if (strcasecmp((string)$k, (string)$a) === 0 && trim((string)$v) !== "") {
						$_GET[$canonical] = $v;
						break 2;
					}
				}
			}
		}

		// Historisches Hash-Feld «PilotID»; Generatoren liefern oft nur RKRSPilotID.
		$pilot = isset($_GET["PilotID"]) ? trim((string)$_GET["PilotID"]) : "";
		if ($pilot === "" && isset($_GET["RKRSPilotID"]) && trim((string)$_GET["RKRSPilotID"]) !== "") {
			$_GET["PilotID"] = (string)$_GET["RKRSPilotID"];
		}

		$quiz = isset($_GET["quiz"]) ? trim((string)$_GET["quiz"]) : "";

		// Kein leeres $_GET['Schulung'] setzen: isset(Schulung) aktiviert die Schulungs-UI.
		// Hash-Payload behandelt fehlendes Schulung bereits als ''.

		// RKRSp-EP-preuf: QuizModus=pruefung Default, wenn Proxy den Parameter verschluckt.
		if ($quiz === "RKRSp-EP-preuf") {
			$qm = array_key_exists("QuizModus", $_GET) ? trim((string)$_GET["QuizModus"]) : "";
			if ($qm === "") {
				$_GET["QuizModus"] = "pruefung";
			}
		}
	}

	/**
	 * Geschützter Quiz-Key (cs-Pflicht)?
	 */
	function rkrk_assess_hash_gate_quiz_is_gated(string $quiz): bool {
		$quiz = trim($quiz);
		if ($quiz === "") {
			return false;
		}
		return rkrk_assess_hash_gate_resolve_rule("rkrk_digitalassess.php", $quiz) !== null
			|| rkrk_assess_hash_gate_resolve_rule("", $quiz) !== null;
	}

	/**
	 * Gültiger cs-Zugriff auf ein geschütztes Quiz (aktueller Request oder Session-Grant).
	 * Erlaubt Teilnehmern den Start ohne ADMIN/INSTR, wenn der Link cs enthält.
	 */
	function rkrk_assess_hash_gate_quiz_granted(string $quiz): bool {
		$quiz = trim($quiz);
		if ($quiz === "" || !rkrk_assess_hash_gate_quiz_is_gated($quiz)) {
			return false;
		}
		$reqQuiz = trim((string)($_GET["quiz"] ?? ""));
		$quizMatch = ($reqQuiz !== "" && ($reqQuiz === $quiz || strcasecmp($reqQuiz, $quiz) === 0));

		$dbg = $GLOBALS["rkrkAssessHashGateDebugResult"] ?? null;
		if ($quizMatch && is_array($dbg) && !empty($dbg["ok"]) && !empty($dbg["rule_found"])) {
			return true;
		}
		if ($quizMatch && trim((string)($_GET["cs"] ?? "")) !== "") {
			$strict = !rkrk_assess_hash_gate_query_is_truncated();
			$eval = rkrk_assess_hash_gate_evaluate_request($strict);
			if (!empty($eval["ok"]) && !empty($eval["rule_found"])) {
				return true;
			}
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		foreach ((array)($_SESSION["rkrkAssessHashGrant"] ?? array()) as $gk => $g) {
			if (!is_array($g)) {
				continue;
			}
			$gQuiz = trim((string)$gk);
			if ($gQuiz === "" || ($gQuiz !== $quiz && strcasecmp($gQuiz, $quiz) !== 0)) {
				continue;
			}
			if ((time() - (int)($g["ts"] ?? 0)) > 28800) {
				continue;
			}
			if (trim((string)($g["cs"] ?? "")) !== "" || trim((string)($g["payload"] ?? "")) !== "") {
				return true;
			}
		}
		return false;
	}

	/** NOH in der URL: Hash-Gate auf portal.rehkitzrettung.ch umgehen (ohne ADMIN/INSTR). */
	function rkrk_assess_hash_gate_noh_requested(): bool {
		if (!rkrk_assess_hash_gate_is_portal_host()) {
			return false;
		}
		return array_key_exists("NOH", $_GET);
	}

	/**
	 * Früher: Schulungs-Training ohne cs. Nicht mehr als Freigabe nutzen —
	 * Portal braucht ADMIN/INSTR/NOH oder gültigen cs.
	 * @deprecated
	 */
	function rkrk_assess_hash_gate_portal_training_without_exam(): bool {
		return false;
	}

	/** Hash-Gate für diesen Request überspringen (ADMIN, INSTR oder NOH). */
	function rkrk_assess_hash_gate_skip_cs_check(): bool {
		if (array_key_exists("ADMIN", $_GET)
			&& function_exists("rkrk_assess_access_policy_is_live_host")
			&& !rkrk_assess_access_policy_is_live_host()
		) {
			return true;
		}
		$allowAdmin = true;
		$allowInstr = true;
		$allowNoh = true;
		if (function_exists("rkrk_assess_access_policy_rule")) {
			$rule = rkrk_assess_access_policy_rule("hash_gate");
			if (is_array($rule) && !empty($rule["enabled"])) {
				$allowAdmin = !empty($rule["allow_admin_flag"]);
				$allowInstr = !empty($rule["allow_instr_flag"]);
				$allowNoh = !empty($rule["allow_noh"]);
			}
		}
		if ($allowAdmin && array_key_exists("ADMIN", $_GET)) {
			return true;
		}
		if ($allowInstr && (isset($_GET["INSTR"]) || isset($_POST["INSTR"]))) {
			return true;
		}
		if ($allowNoh && rkrk_assess_hash_gate_noh_requested()) {
			return true;
		}
		return false;
	}

	/**
	 * Portal: gesperrtes Quiz ohne ADMIN/INSTR (NOH oder gültiger cs-Grant).
	 */
	function rkrk_assess_hash_gate_portal_quiz_hub_access(string $quiz): bool {
		$quiz = trim($quiz);
		if ($quiz === "") {
			return false;
		}
		if (rkrk_assess_hash_gate_noh_requested()) {
			return true;
		}
		return rkrk_assess_hash_gate_quiz_granted($quiz);
	}

	/**
	 * Portal: rkrk_digitalassess.php nur mit ADMIN/INSTR/NOH oder gültigem cs/Grant.
	 * Auf Nicht-Portal-Hosts immer true (kein zusätzlicher Gate).
	 */
	function rkrk_assess_portal_digitalassess_access_allowed(): bool {
		$scope = basename((string)($_SERVER["PHP_SELF"] ?? ""));
		if ($scope !== "rkrk_digitalassess.php") {
			return true;
		}
		$mode = trim((string)($_GET["mode"] ?? ""));
		if (in_array($mode, array("einsatzpruefunglink_cs", "einsatzpruefunglink_prefs"), true)) {
			return true;
		}
		if (function_exists("rkrk_assess_access_policy_allowed")) {
			return rkrk_assess_access_policy_allowed("digitalassess");
		}
		if (!function_exists("rkrk_assess_hash_gate_is_portal_host") || !rkrk_assess_hash_gate_is_portal_host()) {
			return true;
		}
		if (array_key_exists("ADMIN", $_GET) || (!empty($GLOBALS["onAdmin"]))) {
			return true;
		}
		if (isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (!empty($GLOBALS["onInstr"]))) {
			return true;
		}
		if (function_exists("rkrk_assess_hash_gate_noh_requested") && rkrk_assess_hash_gate_noh_requested()) {
			return true;
		}
		$quiz = trim((string)($_GET["quiz"] ?? ""));
		if ($quiz !== "" && function_exists("rkrk_assess_hash_gate_quiz_granted") && rkrk_assess_hash_gate_quiz_granted($quiz)) {
			return true;
		}
		if ($quiz !== "" && trim((string)($_GET["cs"] ?? "")) !== "" && function_exists("rkrk_assess_hash_gate_evaluate_request")) {
			$strict = !function_exists("rkrk_assess_hash_gate_query_is_truncated") || !rkrk_assess_hash_gate_query_is_truncated();
			$eval = rkrk_assess_hash_gate_evaluate_request($strict);
			if (!empty($eval["ok"])) {
				return true;
			}
		}
		return false;
	}

	/** Portal-digitalassess: bei fehlendem Staff/cs sofort Deny. */
	function rkrk_assess_portal_digitalassess_enforce_access(): void {
		if (rkrk_assess_portal_digitalassess_access_allowed()) {
			return;
		}
		rkrk_assess_hash_gate_emit_deny_and_exit(array(
			"ok" => false,
			"reason" => "staff_or_cs_required",
			"scope" => "rkrk_digitalassess.php",
			"quiz" => trim((string)($_GET["quiz"] ?? "")),
			"rule_found" => false,
			"required_fields" => array(),
			"missing_field" => null,
			"payload" => null,
			"client_cs" => trim((string)($_GET["cs"] ?? "")),
			"expected_cs" => "",
		));
	}

	/**
	 * Strikte Moodle-/cs-Kontrolle nur auf portal.rehkitzrettung.ch und Live rehkitzrettung.ch.
	 * NAS ./rkrs (Pfad /rkrs/, Host tchr-frog) ist nicht strict — ?ADMIN gilt.
	 */
	function rkrk_assess_rkrs_strict_host(): bool {
		if (function_exists("rkrk_assess_access_policy_is_live_host")) {
			return rkrk_assess_access_policy_is_live_host();
		}
		if (function_exists("rkrk_assess_hash_gate_is_portal_host") && rkrk_assess_hash_gate_is_portal_host()) {
			return true;
		}
		$host = strtolower(trim((string) ($_SERVER["HTTP_HOST"] ?? "")));
		if ($host === "" || strpos($host, "drohnenrehkitzrettung.ch") !== false) {
			return false;
		}
		return (bool) preg_match('/(^|\.)rehkitzrettung\.ch$/i', $host);
	}

	/**
	 * Original-Zugriffsprüfungen auf dem Portal:
	 * /portal.rehkitzrettung.ch/rkrsplusassessment/testpermissions_*.php
	 * Assess-Basis: /portal.rehkitzrettung.ch/rkrsplusassessment/assess/public
	 *
	 * @return list<string>
	 */
	function rkrk_assess_rkrs_permission_file_names(): array {
		return array(
			"testpermissions_kerngruppe.php",
			"testpermissions_koordinator.php",
			"testpermissions_instruktoren.php",
			"testpermissions_teilnehmer.php",
		);
	}

	/** Wurzel …/rkrsplusassessment (dort liegen die Original-testpermissions_*.php). */
	function rkrk_assess_rkrsplusassessment_root(): string {
		static $cached = null;
		if ($cached !== null) {
			return $cached;
		}
		$cached = "";
		$norm = static function ($p): string {
			return rtrim(str_replace("\\", "/", (string) $p), "/");
		};
		$hasPerm = static function (string $dir): bool {
			if ($dir === "" || !is_dir($dir)) {
				return false;
			}
			foreach (rkrk_assess_rkrs_permission_file_names() as $fn) {
				if (is_file($dir . "/" . $fn)) {
					return true;
				}
			}
			return false;
		};

		$candidates = array();
		$doc = $norm($_SERVER["DOCUMENT_ROOT"] ?? "");
		// 1) Moodle-VHost: /portal.rehkitzrettung.ch/rkrsplusassessment/testpermissions_*.php
		if ($doc !== "") {
			$candidates[] = $doc . "/rkrsplusassessment";
			if (strcasecmp(basename($doc), "rkrsplusassessment") === 0) {
				$candidates[] = $doc;
			}
		}
		// 2) Von assess/public zwei Ebenen hoch (…/rkrsplusassessment/assess/public)
		$candidates[] = dirname(__DIR__, 2);
		$sf = $norm($_SERVER["SCRIPT_FILENAME"] ?? "");
		if ($sf !== "" && preg_match('#^(.*?/rkrsplusassessment)(?:/|$)#i', $sf, $m) === 1) {
			$candidates[] = $m[1];
		}
		$sn = $norm($_SERVER["SCRIPT_NAME"] ?? "");
		if ($doc !== "" && $sn !== "" && preg_match('#^(.*?/rkrsplusassessment)(?:/|$)#i', $doc . $sn, $m) === 1) {
			$candidates[] = $m[1];
		}
		$dir = $norm(__DIR__);
		for ($i = 0; $i < 8; $i++) {
			$candidates[] = $dir;
			$parent = dirname($dir);
			if ($parent === $dir) {
				break;
			}
			$dir = $parent;
		}

		$seen = array();
		foreach ($candidates as $root) {
			$root = $norm($root);
			if ($root === "" || isset($seen[$root])) {
				continue;
			}
			$seen[$root] = true;
			if ($hasPerm($root)) {
				$cached = $root;
				return $cached;
			}
			if (strcasecmp(basename($root), "rkrsplusassessment") === 0) {
				$cached = $root;
				return $cached;
			}
		}
		return $cached;
	}

	function rkrk_assess_rkrs_permission_file_path(string $fileName): string {
		foreach (rkrk_assess_rkrs_permission_file_candidates($fileName) as $path) {
			if ($path !== "" && is_file($path)) {
				return $path;
			}
		}
		return "";
	}

	/**
	 * Originale zuerst (Portal …/rkrsplusassessment/), danach lokale Wrapper unter assess/scripts.
	 *
	 * @return list<string>
	 */
	function rkrk_assess_rkrs_permission_file_candidates(string $fileName): array {
		$fileName = basename(str_replace("\\", "/", $fileName));
		if ($fileName === "" || !preg_match('/^testpermissions_[a-z0-9_]+\.php$/i', $fileName)) {
			return array();
		}
		$out = array();
		$add = static function ($p) use (&$out): void {
			$p = str_replace("\\", "/", (string) $p);
			if ($p !== "" && !in_array($p, $out, true)) {
				$out[] = $p;
			}
		};
		$root = rkrk_assess_rkrsplusassessment_root();
		if ($root !== "") {
			$add($root . "/" . $fileName);
		}
		$doc = rtrim(str_replace("\\", "/", (string) ($_SERVER["DOCUMENT_ROOT"] ?? "")), "/");
		if ($doc !== "") {
			$add($doc . "/rkrsplusassessment/" . $fileName);
			$add($doc . "/" . $fileName);
			$add(dirname($doc) . "/portal.rehkitzrettung.ch/rkrsplusassessment/" . $fileName);
			$add(dirname($doc) . "/rkrsplusassessment/" . $fileName);
		}
		foreach (array(
			"/volume1/web/portal.rehkitzrettung.ch/rkrsplusassessment",
			"/volume1/web/rkrsplusassessment",
			"/var/www/portal.rehkitzrettung.ch/rkrsplusassessment",
			"/var/www/html/rkrsplusassessment",
		) as $dir) {
			$add($dir . "/" . $fileName);
		}
		$sf = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_FILENAME"] ?? ""));
		if ($sf !== "" && preg_match('#^(.*?/rkrsplusassessment)(?:/|$)#i', $sf, $m) === 1) {
			$add($m[1] . "/" . $fileName);
		}
		$add(dirname(__DIR__) . "/scripts/" . $fileName);
		return $out;
	}

	/**
	 * Soft-Kopie nach sys_get_temp_dir(): __DIR__/__FILE__ und relative require("../…")
	 * auf den Originalordner umbiegen (sonst /tmp/../config.php).
	 */
	function rkrk_assess_rkrs_rewrite_permission_source(string $src, string $origPath): ?string {
		$origDir = str_replace("\\", "/", dirname($origPath));
		$origFile = str_replace("\\", "/", $origPath);
		$rewritten = preg_replace('/\brequire_login\s*\([^;]*\)\s*;/i', "/* require_login skipped */;", $src);
		if (!is_string($rewritten)) {
			$rewritten = $src;
		}
		$rewritten = preg_replace('/\b(?:die|exit)\s*(?:\([^;]*\))?\s*;/i', "return false;", $rewritten);
		if (!is_string($rewritten) || $rewritten === "") {
			return null;
		}
		$rewritten = preg_replace('/\b__DIR__\b/', var_export($origDir, true), $rewritten);
		if (!is_string($rewritten)) {
			return null;
		}
		$rewritten = preg_replace('/\b__FILE__\b/', var_export($origFile, true), $rewritten);
		if (!is_string($rewritten)) {
			return null;
		}
		$norm = static function (string $base, string $rel): string {
			$abs = str_replace("\\", "/", $base . "/" . $rel);
			$parts = array();
			foreach (explode("/", $abs) as $i => $seg) {
				if ($seg === "." || ($seg === "" && $i > 0)) {
					continue;
				}
				if ($seg === "..") {
					if (!empty($parts) && end($parts) !== "") {
						array_pop($parts);
					}
					continue;
				}
				$parts[] = $seg;
			}
			return implode("/", $parts);
		};
		$rewritten = preg_replace_callback(
			'/\b((?:require|include)(?:_once)?)\s*\(\s*([\'"])(\.\.?\/[^\'"]+)\2\s*\)/i',
			static function ($m) use ($origDir, $norm) {
				return $m[1] . "(" . var_export($norm($origDir, $m[3]), true) . ")";
			},
			$rewritten
		);
		return is_string($rewritten) ? $rewritten : null;
	}

	/**
	 * Original-testpermissions per require_once. Soft: die()/require_login() nicht abbrechen
	 * (für OR mehrerer Gruppen).
	 */
	function rkrk_assess_rkrs_require_once_permission(string $fileName, bool $soft = true): bool {
		if ($soft && function_exists("rkrk_assess_rkrs_moodle_bootstrap")) {
			rkrk_assess_rkrs_moodle_bootstrap();
		}
		$path = rkrk_assess_rkrs_permission_file_path($fileName);
		if ($path === "" || !is_file($path)) {
			return false;
		}
		static $cache = array();
		$key = $path . "|" . ($soft ? "soft" : "hard");
		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}
		if (!$soft) {
			require_once $path;
			$cache[$key] = true;
			return true;
		}
		$GLOBALS["RKRK_ASSESS_PERM_SOFT"] = true;
		$src = @file_get_contents($path);
		if (!is_string($src) || trim($src) === "") {
			$cache[$key] = false;
			return false;
		}
		$rewritten = rkrk_assess_rkrs_rewrite_permission_source($src, $path);
		if ($rewritten === null || $rewritten === "") {
			$cache[$key] = false;
			return false;
		}
		$tmp = rtrim(str_replace("\\", "/", (string) sys_get_temp_dir()), "/") . "/rkrk_perm_" . md5($path . $rewritten) . ".php";
		if (!is_file($tmp)) {
			@file_put_contents($tmp, $rewritten);
		}
		if (is_file($tmp)) {
			$origDir = str_replace("\\", "/", dirname($path));
			$prevCwd = @getcwd();
			if ($origDir !== "" && is_dir($origDir)) {
				@chdir($origDir);
			}
			$GLOBALS["RKRK_ASSESS_PERM_IN_REQUIRE"] = true;
			try {
				$prev = include $tmp;
				$ok = ($prev !== false);
			} catch (Throwable $e) {
				$ok = false;
			}
			$GLOBALS["RKRK_ASSESS_PERM_IN_REQUIRE"] = false;
			if (is_string($prevCwd) && $prevCwd !== "") {
				@chdir($prevCwd);
			}
			$cache[$key] = $ok;
			return $ok;
		}
		$GLOBALS["RKRK_ASSESS_PERM_SOFT"] = true;
		$GLOBALS["RKRK_ASSESS_PERM_OK"] = false;
		require_once $path;
		$ok = !empty($GLOBALS["RKRK_ASSESS_PERM_OK"]);
		$cache[$key] = $ok;
		return $ok;
	}

	/**
	 * @param list<string> $cohortKeys Catalog-Keys (kerngruppe, …)
	 */
	function rkrk_assess_rkrs_require_once_any_permission(array $cohortKeys): bool {
		$map = array(
			"kerngruppe" => "testpermissions_kerngruppe.php",
			"koordinator" => "testpermissions_koordinator.php",
			"instruktoren" => "testpermissions_instruktoren.php",
			"teilnehmer" => "testpermissions_teilnehmer.php",
		);
		foreach ($cohortKeys as $key) {
			$key = strtolower(trim((string) $key));
			if ($key === "" || !isset($map[$key])) {
				continue;
			}
			if (rkrk_assess_rkrs_require_once_permission($map[$key], true)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Moodle-Pfade wie in den Originaldateien unter …/rkrsplusassessment/:
	 * ../config.php und ../../portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php
	 *
	 * @return array{config: list<string>, moodlefunctions: list<string>}
	 */
	function rkrk_assess_rkrs_moodle_candidate_paths(): array {
		$root = rkrk_assess_rkrsplusassessment_root();
		$config = array();
		$mf = array();
		$pub = str_replace("\\", "/", __DIR__);
		if ($root !== "") {
			$config[] = $root . "/../config.php";
			$mf[] = $root . "/../../portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php";
		}
		// public → assess → rkrsplusassessment → Moodle-Root
		$config[] = $pub . "/../../../config.php";
		$mf[] = $pub . "/../../../../portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php";
		$doc = rtrim(str_replace("\\", "/", (string) ($_SERVER["DOCUMENT_ROOT"] ?? "")), "/");
		if ($doc !== "") {
			$config[] = $doc . "/config.php";
			$config[] = dirname($doc) . "/portal.rehkitzrettung.ch/config.php";
			$config[] = $doc . "/rkrsplusassessment/../config.php";
			$mf[] = dirname($doc) . "/portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php";
			$mf[] = $doc . "/../portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php";
			$mf[] = dirname($doc) . "/portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php";
		}
		foreach (array(
			"/volume1/web/portal.rehkitzrettung.ch/config.php",
			"/var/www/portal.rehkitzrettung.ch/config.php",
		) as $absCfg) {
			$config[] = $absCfg;
		}
		foreach (array(
			"/volume1/web/portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php",
			"/volume1/portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php",
			"/var/www/portal.rehkitzrettung.ch-data/memberarea/moodlefunctions.inc.php",
		) as $absMf) {
			$mf[] = $absMf;
		}
		$dir = $pub;
		for ($i = 0; $i < 6; $i++) {
			$config[] = $dir . "/config.php";
			$parent = dirname($dir);
			if ($parent === $dir) {
				break;
			}
			$dir = $parent;
		}
		return array(
			"config" => $config,
			"moodlefunctions" => $mf,
		);
	}

	/**
	 * Fremde PHP-Session schliessen. $_SESSION nur entfernen (nicht als
	 * leeres Array setzen): ein gesetztes Array blockiert das Laden der
	 * Portal-Anmeldung aus dem Cookie MoodleSession.
	 */
	function rkrk_assess_rkrs_prepare_moodle_session(): void {
		$live = function_exists("rkrk_assess_access_policy_is_live_host") && rkrk_assess_access_policy_is_live_host();
		if (!$live) {
			return;
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			$name = (string) session_name();
			if (stripos($name, "MoodleSession") !== 0) {
				session_write_close();
			}
		}
		if (session_status() !== PHP_SESSION_ACTIVE && isset($_SESSION)) {
			unset($_SESSION);
		}
	}

	/** Moodle wie /rkrsplusassessment/testpermissions_*.php laden. */
	function rkrk_assess_rkrs_moodle_bootstrap(): bool {
		static $done = null;
		if ($done === true) {
			return true;
		}
		rkrk_assess_rkrs_prepare_moodle_session();
		if ($done === false && (defined("MOODLE_INTERNAL") || (isset($GLOBALS["CFG"]) && is_object($GLOBALS["CFG"])))) {
			return false;
		}
		$paths = rkrk_assess_rkrs_moodle_candidate_paths();
		$config = "";
		$fallback = "";
		foreach ($paths["config"] as $p) {
			$p = str_replace("\\", "/", (string) $p);
			if ($p === "" || !is_file($p)) {
				continue;
			}
			if ($fallback === "") {
				$fallback = $p;
			}
			$head = @file_get_contents($p, false, null, 0, 8192);
			if (is_string($head) && (stripos($head, "wwwroot") !== false || stripos($head, "dirroot") !== false || stripos($head, "MOODLE") !== false)) {
				$config = $p;
				break;
			}
		}
		if ($config === "") {
			$config = $fallback;
		}
		$mf = "";
		foreach ($paths["moodlefunctions"] as $p) {
			$p = str_replace("\\", "/", (string) $p);
			if ($p !== "" && is_file($p)) {
				$mf = $p;
				break;
			}
		}
		if ($config === "") {
			$done = false;
			return false;
		}
		require_once $config;
		if ($mf === "" && isset($GLOBALS["CFG"]) && is_object($GLOBALS["CFG"]) && !empty($GLOBALS["CFG"]->dataroot)) {
			$fromData = rtrim(str_replace("\\", "/", (string) $GLOBALS["CFG"]->dataroot), "/") . "/memberarea/moodlefunctions.inc.php";
			if (is_file($fromData)) {
				$mf = $fromData;
			}
		}
		if ($mf !== "" && is_file($mf)) {
			require_once $mf;
		}
		if (function_exists("rkrk_flush_deferred_session")) {
			rkrk_flush_deferred_session();
		}
		$done = defined("MOODLE_INTERNAL") || (isset($GLOBALS["CFG"]) && is_object($GLOBALS["CFG"]));
		return $done;
	}

	function rkrk_assess_rkrs_moodle_user_id(): int {
		if (isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"]) && isset($GLOBALS["USER"]->id)) {
			return (int) $GLOBALS["USER"]->id;
		}
		if (isset($GLOBALS["USER"]) && is_array($GLOBALS["USER"]) && isset($GLOBALS["USER"]["id"])) {
			return (int) $GLOBALS["USER"]["id"];
		}
		return 0;
	}

	/**
	 * @param list<string> $cohorts
	 */
	function rkrk_assess_rkrs_moodle_db_in_cohort(string $cohort, int $uid): bool {
		global $DB;
		if ($uid <= 1 || $cohort === "" || !isset($DB) || !is_object($DB) || !method_exists($DB, "record_exists_sql")) {
			return false;
		}
		$params = array(
			"uid" => $uid,
			"idn" => $cohort,
			"idname" => $cohort,
		);
		$sql = "SELECT cm.id
		          FROM {cohort_members} cm
		          JOIN {cohort} c ON c.id = cm.cohortid
		         WHERE cm.userid = :uid
		           AND (LOWER(c.idnumber) = LOWER(:idn) OR LOWER(c.name) = LOWER(:idname)";
		if (ctype_digit($cohort)) {
			$sql .= " OR c.id = :cid";
			$params["cid"] = (int) $cohort;
		}
		$sql .= ")";
		try {
			return (bool) $DB->record_exists_sql($sql, $params);
		} catch (Throwable $e) {
			return false;
		}
	}

	function rkrk_assess_rkrs_moodle_in_any_cohort(array $cohorts, bool $requireLogin = false, bool $includeSiteAdmin = true): bool {
		if (!rkrk_assess_rkrs_moodle_bootstrap()) {
			return false;
		}
		unset($requireLogin);
		if ($includeSiteAdmin && function_exists("is_siteadmin") && is_siteadmin()) {
			return true;
		}
		if (function_exists("isguestuser") && isguestuser()) {
			return false;
		}
		$uid = rkrk_assess_rkrs_moodle_user_id();
		if ($uid <= 1) {
			return false;
		}
		foreach ($cohorts as $cohort) {
			$cohort = trim((string) $cohort);
			if ($cohort === "") {
				continue;
			}
			$hit = false;
			if (function_exists("ismemberofcohort")) {
				try {
					$hit = (bool) ismemberofcohort($cohort, $uid);
				} catch (Throwable $e) {
					$hit = false;
				}
			}
			if (!$hit && rkrk_assess_rkrs_moodle_db_in_cohort($cohort, $uid)) {
				$hit = true;
			}
			if ($hit) {
				return true;
			}
		}
		return false;
	}

	function rkrk_assess_rkrs_access_session_bucket(): array {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$key = "rkrkAssessRkrsGate";
		if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
			$_SESSION[$key] = array();
		}
		return $_SESSION[$key];
	}

	function rkrk_assess_rkrs_access_session_get(string $scope): array {
		$scope = trim($scope);
		if ($scope === "") {
			return array();
		}
		$all = rkrk_assess_rkrs_access_session_bucket();
		return (isset($all[$scope]) && is_array($all[$scope])) ? $all[$scope] : array();
	}

	function rkrk_assess_rkrs_access_session_store(string $scope, string $via, array $fields = array()): void {
		$scope = trim($scope);
		if ($scope === "") {
			return;
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$key = "rkrkAssessRkrsGate";
		if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
			$_SESSION[$key] = array();
		}
		$_SESSION[$key][$scope] = array(
			"via" => $via,
			"ts" => time(),
			"fields" => $fields,
		);
		$GLOBALS["rkrkAssessRkrsAccessVia"][$scope] = $via;
	}

	function rkrk_assess_rkrs_access_via(string $scope): string {
		if (isset($GLOBALS["rkrkAssessRkrsAccessVia"][$scope])) {
			return (string) $GLOBALS["rkrkAssessRkrsAccessVia"][$scope];
		}
		$grant = rkrk_assess_rkrs_access_session_get($scope);
		return trim((string) ($grant["via"] ?? ""));
	}

	/** Portal-Menü-Verknüpfung: ?ADMIN / ?INSTR (auch als leere Flags). */
	function rkrk_assess_rkrs_staff_link_requested(): bool {
		$qs = (string) ($_SERVER["QUERY_STRING"] ?? "");
		if ($qs !== "" && preg_match('/(?:^|&)(ADMIN|INSTR)(?:=|&|$)/i', $qs) === 1) {
			return true;
		}
		foreach (array("ADMIN", "INSTR") as $k) {
			if (array_key_exists($k, $_GET) || array_key_exists($k, $_POST)) {
				return true;
			}
		}
		if (!empty($GLOBALS["onAdmin"]) || !empty($GLOBALS["onInstr"])) {
			return true;
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $params
	 * @param list<string> $required
	 * @param list<string> $payloadFields
	 * @return array{payload:string, cs:string, required_fields:list<string>, missing_field:?string}
	 */
	function rkrk_assess_hash_build_cs_for_fields(array $params, array $required, array $payloadFields): array {
		$out = array(
			"payload" => "",
			"cs" => "",
			"required_fields" => $required,
			"missing_field" => null,
		);
		foreach ($required as $field) {
			if (trim((string) ($params[$field] ?? "")) === "") {
				$out["missing_field"] = $field;
				return $out;
			}
		}
		$payload = "";
		foreach ($payloadFields as $field) {
			$payload .= (string) ($params[$field] ?? "");
		}
		$out["payload"] = $payload;
		$out["cs"] = function_exists("rkrk_assess_hash_compute_cs")
			? rkrk_assess_hash_compute_cs($payload)
			: "";
		return $out;
	}

	/**
	 * cs für Personal-Verbindung (Link-Generator): RKRSPilotID + Name.
	 *
	 * @param array<string, mixed> $params
	 * @return array{payload:string, cs:string, required_fields:list<string>, missing_field:?string}
	 */
	function rkrk_assess_hash_build_cs_for_personel(array $params): array {
		$name = trim((string) ($params["Name"] ?? ""));
		$pid = trim((string) ($params["RKRSPilotID"] ?? $params["PilotID"] ?? ""));
		return rkrk_assess_hash_build_cs_for_fields(
			array("RKRSPilotID" => $pid, "Name" => $name),
			array("RKRSPilotID", "Name"),
			array("RKRSPilotID", "Name")
		);
	}

	/**
	 * cs für Sessions-Verknüpfung (fester Scope-Hash, unguessbar durch assessment-Salt).
	 *
	 * @param array<string, mixed> $params
	 * @return array{payload:string, cs:string, required_fields:list<string>, missing_field:?string}
	 */
	function rkrk_assess_hash_build_cs_for_sessions(array $params = array()): array {
		unset($params);
		return rkrk_assess_hash_build_cs_for_fields(
			array("scope" => "rkrk_assess_sessions.php"),
			array(),
			array("scope")
		);
	}

	/**
	 * @param callable $builder
	 * @param list<string> $carryKeys
	 */
	function rkrk_assess_rkrs_cs_request_matches(callable $builder, array $carryKeys): bool {
		$src = array();
		foreach ($carryKeys as $k) {
			if (isset($_GET[$k]) && trim((string) $_GET[$k]) !== "") {
				$src[$k] = (string) $_GET[$k];
			} elseif (isset($_POST[$k]) && trim((string) $_POST[$k]) !== "") {
				$src[$k] = (string) $_POST[$k];
			}
		}
		$client = strtolower(trim((string) ($src["cs"] ?? $_GET["cs"] ?? $_POST["cs"] ?? "")));
		if ($client === "") {
			return false;
		}
		$build = $builder($src);
		$expected = strtolower(trim((string) ($build["cs"] ?? "")));
		if ($expected === "" || !empty($build["missing_field"])) {
			return false;
		}
		return hash_equals($expected, $client);
	}

	function rkrk_assess_personel_cs_request_ok(): bool {
		return rkrk_assess_rkrs_cs_request_matches("rkrk_assess_hash_build_cs_for_personel", array("Name", "RKRSPilotID", "PilotID", "cs"));
	}

	function rkrk_assess_sessions_cs_request_ok(): bool {
		return rkrk_assess_rkrs_cs_request_matches("rkrk_assess_hash_build_cs_for_sessions", array("cs"));
	}

	/**
	 * @param array<string, mixed> $queryParams
	 * @return array<string, mixed>
	 */
	function rkrk_assess_personel_merge_cs_query(array $queryParams): array {
		$grant = rkrk_assess_rkrs_access_session_get("personel");
		$fields = (isset($grant["fields"]) && is_array($grant["fields"])) ? $grant["fields"] : array();
		foreach (array("Name", "RKRSPilotID", "PilotID", "cs") as $k) {
			if (array_key_exists($k, $queryParams) && trim((string) $queryParams[$k]) !== "") {
				continue;
			}
			if (isset($_GET[$k]) && trim((string) $_GET[$k]) !== "") {
				$queryParams[$k] = (string) $_GET[$k];
				continue;
			}
			if (isset($fields[$k]) && trim((string) $fields[$k]) !== "") {
				$queryParams[$k] = (string) $fields[$k];
			}
		}
		return $queryParams;
	}

	/**
	 * @param array<string, mixed> $queryParams
	 * @return array<string, mixed>
	 */
	function rkrk_assess_sessions_merge_cs_query(array $queryParams): array {
		$grant = rkrk_assess_rkrs_access_session_get("sessions");
		$fields = (isset($grant["fields"]) && is_array($grant["fields"])) ? $grant["fields"] : array();
		if (!array_key_exists("cs", $queryParams) || trim((string) $queryParams["cs"]) === "") {
			if (isset($_GET["cs"]) && trim((string) $_GET["cs"]) !== "") {
				$queryParams["cs"] = (string) $_GET["cs"];
			} elseif (isset($fields["cs"]) && trim((string) $fields["cs"]) !== "") {
				$queryParams["cs"] = (string) $fields["cs"];
			}
		}
		return $queryParams;
	}

	function rkrk_assess_rkrs_echo_cs_hidden_inputs(array $keys): void {
		$merged = array();
		foreach ($keys as $k) {
			$merged[$k] = "";
		}
		if (in_array("Name", $keys, true) || in_array("RKRSPilotID", $keys, true)) {
			$merged = rkrk_assess_personel_merge_cs_query($merged);
		} else {
			$merged = rkrk_assess_sessions_merge_cs_query($merged);
		}
		foreach ($keys as $k) {
			$v = trim((string) ($merged[$k] ?? ""));
			if ($v === "") {
				continue;
			}
			echo '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES, "UTF-8")
				. '" value="' . htmlspecialchars($v, ENT_QUOTES, "UTF-8") . '">';
		}
	}

	/**
	 * @param list<string> $cohorts
	 */
	function rkrk_assess_rkrs_strict_access_allowed(string $scope, array $cohorts, bool $allowCs, bool $requireLogin): bool {
		unset($requireLogin);
		if (function_exists("rkrk_assess_hash_gate_skip_cs_check") && rkrk_assess_hash_gate_skip_cs_check()) {
			return true;
		}
		if (function_exists("rkrk_assess_access_policy_allowed")) {
			$page = ($scope === "sessions") ? "sessions" : "personel";
			return rkrk_assess_access_policy_allowed($page);
		}
		if (!rkrk_assess_rkrs_strict_host()) {
			return true;
		}
		if ($allowCs) {
			$csOk = ($scope === "sessions")
				? rkrk_assess_sessions_cs_request_ok()
				: rkrk_assess_personel_cs_request_ok();
			if ($csOk) {
				return true;
			}
		}
		if (rkrk_assess_rkrs_moodle_in_any_cohort($cohorts, false)) {
			return true;
		}
		return false;
	}

	/**
	 * @param list<string> $cohorts
	 */
	function rkrk_assess_rkrs_strict_access_enforce(string $scope, array $cohorts, bool $allowCs = true): void {
		if (rkrk_assess_rkrs_strict_access_allowed($scope, $cohorts, $allowCs, false)) {
			return;
		}
		rkrk_assess_portal_personel_emit_access_denied($scope);
	}

	function rkrk_assess_portal_personel_emit_access_denied(string $scope = "personel"): void {
		rkrk_assess_hash_gate_emit_deny_and_exit(array(
			"ok" => false,
			"reason" => "cohort_or_link_required",
			"scope" => $scope,
			"quiz" => "",
			"rule_found" => false,
			"required_fields" => ($scope === "sessions") ? array("cs") : array("RKRSPilotID", "Name", "cs"),
			"missing_field" => null,
			"payload" => null,
			"client_cs" => trim((string) ($_GET["cs"] ?? "")),
			"expected_cs" => "",
		));
	}

	function rkrk_assess_portal_personel_access_allowed(bool $requireLogin = true): bool {
		return rkrk_assess_rkrs_strict_access_allowed(
			"personel",
			array("RKRSplusKerngruppe", "RKRSplusEinsatzleiter"),
			true,
			$requireLogin
		);
	}

	function rkrk_assess_portal_personel_enforce_access(): void {
		rkrk_assess_rkrs_strict_access_enforce(
			"personel",
			array("RKRSplusKerngruppe", "RKRSplusEinsatzleiter"),
			true
		);
	}

	/** @return array{portal:bool, cs_only:bool} */
	function rkrk_assess_portal_personel_bootstrap_access(): array {
		$portal = rkrk_assess_rkrs_strict_host();
		return array(
			"portal" => $portal,
			"cs_only" => $portal && rkrk_assess_rkrs_access_via("personel") === "cs",
		);
	}

	function rkrk_assess_portal_sessions_access_allowed(bool $requireLogin = true): bool {
		return rkrk_assess_rkrs_strict_access_allowed(
			"sessions",
			array("RKRSplusInstruktorenDE", "RKRSplusKerngruppe"),
			true,
			$requireLogin
		);
	}

	function rkrk_assess_portal_sessions_enforce_access(): void {
		rkrk_assess_rkrs_strict_access_enforce(
			"sessions",
			array("RKRSplusInstruktorenDE", "RKRSplusKerngruppe"),
			true
		);
	}

	/**
	 * @return array<string, string>
	 */
	function rkrk_assess_hash_gate_grant_fields_from_get(): array {
		$out = array();
		// ohne_bilder / Status gehören NICHT in den Session-Grant:
		// sonst landen sie beim nächsten Aufruf ohne diese URL-Parameter in $_GET
		// und verfälschen den cs-Hash («hash_mismatch»).
		foreach (array("EXAM", "ExamCode", "QuizModus", "PilotID", "RKRSPilotID", "Name", "Drone", "Schulung", "cs") as $k) {
			if (isset($_GET[$k]) && trim((string)$_GET[$k]) !== "") {
				$out[$k] = (string)$_GET[$k];
			}
		}
		return $out;
	}

	function rkrk_assess_hash_gate_store_grant(array $result): void {
		if (empty($result["ok"])) {
			return;
		}
		$quiz = trim((string)($result["quiz"] ?? ""));
		if ($quiz === "") {
			return;
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$key = "rkrkAssessHashGrant";
		if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
			$_SESSION[$key] = array();
		}
		$_SESSION[$key][$quiz] = array(
			"ts" => time(),
			"payload" => (string)($result["payload"] ?? ""),
			"cs" => (string)($result["client_cs"] ?? ""),
			"fields" => rkrk_assess_hash_gate_grant_fields_from_get(),
		);
	}

	function rkrk_assess_hash_gate_apply_session_grant_fields(string $quiz): bool {
		$quiz = trim($quiz);
		if ($quiz === "") {
			return false;
		}
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$g = $_SESSION["rkrkAssessHashGrant"][$quiz] ?? null;
		if (!is_array($g) || empty($g["fields"]) || !is_array($g["fields"])) {
			return false;
		}
		if ((time() - (int)($g["ts"] ?? 0)) > 28800) {
			return false;
		}
		$applied = false;
		foreach ($g["fields"] as $k => $v) {
			$key = (string)$k;
			$cur = array_key_exists($key, $_GET) ? trim((string)$_GET[$key]) : "";
			if ($cur === "" && trim((string)$v) !== "") {
				$_GET[$key] = (string)$v;
				$applied = true;
			}
		}
		return $applied;
	}

	function rkrk_assess_hash_gate_emit_deny_and_exit(array $result): void {
		// Direkt die Deny-Seite ausgeben (kein 303 auf no_access):
		// Redirect + Session verliert den echten Grund, wenn Cookies blockiert sind
		// («direct_access» statt «PilotID fehlt») und hält den Nutzer auf no_access fest.
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$_SESSION["rkrkAssessHashGateDeny"] = $result;
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		if (!headers_sent()) {
			http_response_code(403);
			header("Content-Type: text/html; charset=utf-8");
		}
		echo rkrk_assess_hash_gate_deny_page_html($result);
		exit;
	}

	/**
	 * @param array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * } $result
	 */
	function rkrk_assess_hash_gate_debug_html(array $result, bool $denied): string {
		$host = (string) ($_SERVER["HTTP_HOST"] ?? "");
		$title = $denied ? "Hash-Zugriff verweigert" : "Hash-Zugriff OK";
		$statusLabel = $denied ? "DENIED" : "OK";
		$requiredFields = isset($result["required_fields"]) && is_array($result["required_fields"])
			? implode(", ", $result["required_fields"])
			: "";
		$rows = array(
			"Status" => $statusLabel,
			"Grund" => (string) ($result["reason"] ?? ""),
			"Host" => $host,
			"Scope" => (string) ($result["scope"] ?? ""),
			"Quiz" => (string) ($result["quiz"] ?? ""),
			"Regel gefunden" => !empty($result["rule_found"]) ? "ja" : "nein",
			"Pflichtfelder" => $requiredFields,
			"Fehlendes Feld" => (string) ($result["missing_field"] ?? ""),
			"Payload" => (string) ($result["payload"] ?? ""),
			"Client cs" => (string) ($result["client_cs"] ?? ""),
			"Erwarteter Hash" => (string) ($result["expected_cs"] ?? ""),
		);
		$html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"utf-8\"><title>"
			. rkrk_assess_hash_gate_h($title)
			. "</title><style>body{font-family:system-ui,sans-serif;margin:2rem;line-height:1.5}"
			. "table{border-collapse:collapse}th,td{border:1px solid #ccc;padding:.4rem .6rem;text-align:left;vertical-align:top}"
			. "th{background:#f5f5f5;width:12rem}code{word-break:break-all}"
			. ".denied h1{color:#b00020}.ok h1{color:#1b5e20}</style></head><body class=\""
			. ($denied ? "denied" : "ok")
			. "\"><h1>"
			. rkrk_assess_hash_gate_h($title)
			. "</h1><p>Hash-Gate Debug (nur portal.rehkitzrettung.ch, nur mit RKRK_ASSESS_HASH_DEBUG).</p><table>";
		foreach ($rows as $label => $value) {
			if ($value === "") {
				continue;
			}
			$html .= "<tr><th>" . rkrk_assess_hash_gate_h($label) . "</th><td><code>"
				. rkrk_assess_hash_gate_h($value) . "</code></td></tr>";
		}
		$html .= "</table></body></html>";
		return $html;
	}

	/**
	 * @param array{
	 *   ok: bool,
	 *   reason: string,
	 *   scope: string,
	 *   quiz: string,
	 *   rule_found: bool,
	 *   required_fields: list<string>,
	 *   missing_field: ?string,
	 *   payload: ?string,
	 *   client_cs: string,
	 *   expected_cs: string
	 * } $result
	 */
	function rkrk_assess_hash_gate_emit_debug_and_exit(array $result): void {
		rkrk_assess_hash_gate_emit_deny_and_exit($result);
	}

	function rkrk_assess_hash_gate_success_banner_html(array $result): string {
		return "<div style=\"margin:1rem;padding:.75rem 1rem;border:1px solid #1b5e20;background:#e8f5e9;"
			. "font-family:system-ui,sans-serif;font-size:.95rem\">"
			. "<strong>Hash-Gate OK</strong> — Quiz: <code>"
			. rkrk_assess_hash_gate_h((string) ($result["quiz"] ?? ""))
			. "</code>, Payload: <code>"
			. rkrk_assess_hash_gate_h((string) ($result["payload"] ?? ""))
			. "</code></div>";
	}

	function rkrk_assess_hash_gate_begin_success_banner_buffer(array $result): void {
		if (!rkrk_assess_hash_gate_debug_info_requested() || empty($result["ok"])) {
			return;
		}
		$banner = rkrk_assess_hash_gate_success_banner_html($result);
		ob_start(static function (string $buffer) use ($banner): string {
			return $banner . $buffer;
		});
	}

	function rkrk_assess_hash_gate_deny_and_exit(): void {
		if (!headers_sent()) {
			http_response_code(204);
		}
		exit;
	}

	function rkrk_assess_hash_gate_bootstrap(): void {
		$trace = rkrk_assess_hash_gate_portal_trace_requested();
		$host = (string) ($_SERVER["HTTP_HOST"] ?? "");
		rkrk_assess_hash_gate_normalize_request_aliases();
		$quiz = trim((string) ($_GET["quiz"] ?? ""));
		$mode = trim((string) ($_GET["mode"] ?? ""));

		// CS-/Prefs-APIs des Link-Generators müssen quiz=* setzen, dürfen aber nicht selbst
		// vom Hash-Gate blockiert werden (sonst schlägt die cs-Berechnung fehl).
		if (function_exists("rkrk_assess_access_policy_is_open_form_mode") && rkrk_assess_access_policy_is_open_form_mode()) {
			return;
		}
		if (in_array($mode, array("einsatzpruefunglink_cs", "einsatzpruefunglink_prefs"), true)) {
			if ($trace) {
				rkrk_assess_hash_gate_emit_trace_html_and_exit(array(
					"Ergebnis" => "Hash-Prüfung übersprungen",
					"Grund" => "linkbuilder_api_mode",
					"Host" => $host,
					"Mode" => $mode,
					"Quiz" => $quiz,
					"Hinweis" => "Link-Generator APIs erzeugen/speichern cs und sind nicht geschützt",
				), "Hash-Gate Diagnose — Linkbuilder-API");
			}
			return;
		}

		if (!rkrk_assess_hash_gate_enabled()) {
			if ($trace) {
				rkrk_assess_hash_gate_emit_trace_html_and_exit(array(
					"Ergebnis" => "Hash-Prüfung übersprungen",
					"Grund" => "gate_disabled_or_host",
					"Host" => $host,
					"Quiz" => $quiz,
					"Hinweis" => "Hash-Gate laut Zugriffsregeln für diesen Server aus, oder per Env/hash_debug deaktiviert",
				), "Hash-Gate Diagnose — deaktiviert");
			}
			return;
		}
		if (!rkrk_assess_hash_gate_request_has_rule()) {
			if ($trace) {
				rkrk_assess_hash_gate_emit_trace_html_and_exit(array(
					"Ergebnis" => "Hash-Prüfung übersprungen",
					"Grund" => "no_matching_rule",
					"Host" => $host,
					"Quiz" => $quiz,
					"Geschützte Quiz-Keys" => "RKRSp-doku, RKRSp-EP, RKRSp-EP-preuf",
					"Hinweis" => "quiz fehlt oder ist nicht in der Regel-Registry",
				), "Hash-Gate Diagnose — keine Regel");
			}
			return;
		}

		// Nach Navigation ohne EXAM/cs: Session-Grant aus früherem OK-Aufruf nachziehen.
		rkrk_assess_hash_gate_apply_session_grant_fields($quiz);

		// ADMIN / INSTR / NOH: Hash nicht blockierend.
		if (rkrk_assess_hash_gate_skip_cs_check()) {
			if ($trace) {
				$grund = "admin_bypass";
				$hinweis = "ADMIN in der URL — Zugriff ohne cs (nur Staff)";
				if (isset($_GET["INSTR"]) || isset($_POST["INSTR"])) {
					$grund = "instr_bypass";
					$hinweis = "INSTR in der URL — Zugriff ohne cs (Instruktor)";
				}
				if (rkrk_assess_hash_gate_noh_requested()) {
					$grund = "noh_bypass";
					$hinweis = "NOH in der URL — Hash-Gate umgangen (portal.rehkitzrettung.ch)";
				}
				rkrk_assess_hash_gate_emit_trace_html_and_exit(array(
					"Ergebnis" => "Hash-Prüfung übersprungen",
					"Grund" => $grund,
					"Host" => $host,
					"Quiz" => $quiz,
					"Hinweis" => $hinweis,
				), "Hash-Gate Diagnose — Bypass");
			}
			return;
		}

		$result = rkrk_assess_hash_gate_evaluate_request(true);
		if (!$result["ok"] && in_array((string)($result["reason"] ?? ""), array("missing_field", "missing_cs", "hash_mismatch"), true)) {
			rkrk_assess_hash_gate_apply_session_grant_fields($quiz);
			if (rkrk_assess_hash_gate_query_is_truncated() || rkrk_assess_hash_gate_grant_matches($quiz)) {
				$result = rkrk_assess_hash_gate_evaluate_request(false);
			}
		}
		if (!$result["ok"]) {
			rkrk_assess_hash_gate_emit_deny_and_exit($result);
		}
		rkrk_assess_hash_gate_store_grant($result);
		if ($trace && strtolower(trim((string) ($_GET["hash_debug"] ?? ""))) !== "info") {
			rkrk_assess_hash_gate_emit_trace_html_and_exit(
				rkrk_assess_hash_gate_trace_rows($result),
				"Hash-Gate Diagnose — OK"
			);
		}
		$GLOBALS["rkrkAssessHashGateDebugResult"] = $result;
		rkrk_assess_hash_gate_begin_success_banner_buffer($result);
	}

	if (!(defined('RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP') && RKRK_ASSESS_SKIP_HASH_GATE_BOOTSTRAP)) {
		rkrk_assess_hash_gate_bootstrap();
	}


	/**
	 * rkrk_gen.php calls SetMissionLocation() on include; it uses relative "../../rkropen/", which
	 * resolves from PHP's CWD. Scripts under assess/ often have CWD …/assess, producing
	 * …/rkropen/rkropen/Missionen/… for logs and CSVs. Main site entry points are unchanged.
	 * This optional pass rebases mission/doc paths using $folderoffset (typically "../") so that
	 * $folderoffset . "../" matches the real web root …/rkropen/ — without modifying SetMissionLocation().
	 */
	function rkrk_assess_apply_folderoffset_to_mission_doc_paths(): void {
		global $folderoffset;
		global $MissionLocation, $DocLocation, $csvFileDirectory, $xmlFileDirectory;
		global $RKRKRelWebLocation, $RKRKLocalLoactionMissionFiles, $RKRKRelRKRKDataLocation, $RKRKLocalLoactionDocFiles;
		global $csvMissionLocation, $csvDocLocation;
		global $xmlMissionLocation, $xmlDocLocation;
		global $jsonFileDirectory, $jsonMissionLocation, $jsonDocLocation;
		global $historyfilelocation, $logfilelocation, $deletefilelocation, $tempfilelocation;

		if (!isset($folderoffset) || (string) $folderoffset !== '../') {
			return;
		}
		if (!isset($RKRKRelWebLocation) || (string) $RKRKRelWebLocation !== '../../rkropen/') {
			return;
		}
		if (!isset($MissionLocation, $DocLocation, $csvFileDirectory, $xmlFileDirectory)) {
			return;
		}

		$RKRKRelWebLocation = (string) $folderoffset . '../';
		$RKRKLocalLoactionMissionFiles = $RKRKRelWebLocation . $MissionLocation;
		$RKRKRelRKRKDataLocation = $RKRKRelWebLocation . 'rkrk/';
		$RKRKLocalLoactionDocFiles = $RKRKRelWebLocation . $DocLocation;
		$csvDocLocation = $RKRKRelRKRKDataLocation . $csvFileDirectory;
		$csvMissionLocation = $csvDocLocation;
		if (!empty($jsonFileDirectory)) {
			$jsonDocLocation = $RKRKRelRKRKDataLocation . $jsonFileDirectory;
			$jsonMissionLocation = $jsonDocLocation;
		}
		$xmlDocLocation = $RKRKLocalLoactionDocFiles . $xmlFileDirectory;
		$xmlMissionLocation = $RKRKLocalLoactionMissionFiles . $xmlFileDirectory;
		$historyfilelocation = $RKRKLocalLoactionDocFiles . '_history/';
		$logfilelocation = $RKRKLocalLoactionDocFiles . '_log/';
		$deletefilelocation = $RKRKLocalLoactionDocFiles . '_deleted/';
		$tempfilelocation = $RKRKLocalLoactionDocFiles . '_temp/';
	}

	$httpHost = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
	$phpSelf = isset($_SERVER['PHP_SELF']) ? (string)$_SERVER['PHP_SELF'] : '';
	$scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : '';
	$currentDir = (string)__DIR__;
	// Copyright / CI: portal.rehkitzrettung.ch oder Pfad …/rkrs/… → rehkitzrettung.ch
	// (nicht strpos Host "rehkitzrettung.ch" — das matcht auch drohnenrehkitzrettung.ch)
	$isPortalHost = (strpos(strtolower($httpHost), "portal.rehkitzrettung.ch") !== false);
	$isRkrsPath = (
		(strpos($phpSelf, "/rkrs/") !== false) ||
		(strpos(str_replace('\\', '/', $scriptFilename), "/rkrs/") !== false) ||
		(strpos(str_replace('\\', '/', $currentDir), "/rkrs/") !== false)
	);
	if ($isPortalHost || $isRkrsPath ||
		(strpos($phpSelf, "/test/assess_rkrs") !== false)) {
		$onRKRS = true;
		$Layout= "2023";
		//echo "<div>ON RKRS</div>";
		$logo = "rkrs";
		$logopic ="rkrs_logo.png";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRS";
		$AddURL="";
	} else {
		$onRKRS = false;
		//echo "<br><br><div>ON RKRK</div>";
		include_once($folderoffset . 'scripts/rkrk_gen.php');
		rkrk_assess_apply_folderoffset_to_mission_doc_paths();
		$menuPath = $folderoffset . 'scripts/rkrk_menu.php';
		if (file_exists($menuPath)) {
			include_once($menuPath);
		}
		$__rkrkAssessPublicPaper = (
			(isset($_GET['mode']) && (string) $_GET['mode'] === 'paperform')
			|| (isset($_GET['paper']) && (string) $_GET['paper'] === '1' && trim((string) ($_GET['quiz'] ?? '')) !== '')
		);
		$accessPath = $folderoffset . 'rkrk_access_control.php';
		if (file_exists($accessPath) && !$__rkrkAssessPublicPaper) {
			include_once($accessPath);
		}
		unset($__rkrkAssessPublicPaper);
		$logo = "rkrk";
		$logopic ="rkrk_logo.png";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "RKRK";
		$AddURL="";
	} 


	if (isset($_GET["TEST"])) {
		$setting = "TEST";
		$AddURL = "";
		// Auf …/rkrs/assess/ bleibt $onRKRS true (Pfad-Erkennung); TEST soll nur die Zielgruppe lockern, nicht CI überschreiben.
		if (!$onRKRS) {
			$logo = "rkrk";
			$logopic = "rkrk_logo.png";
			$copyright = "drohnenrehkitzrettung.ch";
		}
	}

	if (isset($_GET["RKRK"])) {
		$logo = "rkrk";
		$logopic ="rkrk_logo.png";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "RKRK";
		$onRKRS=false;
		$AddURL="";
	}

	if (array_key_exists("RKRS", $_GET)) {
		$logo = "rkrs";
		$logopic ="rkrs_logo.png";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRS";
		$onRKRS=true;
		$AddURL="RKRS";
	}

	if (array_key_exists("RKRSplus", $_GET)) {
		$logo = "rkrs";
		$logopic ="rkrs_logo.png";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRSplus";
		$onRKRS=true;
		$AddURL="RKRSplus";
	}
	if (array_key_exists("RKRSprev", $_GET)) {
		$logo = "rkrs";
		$logopic ="rkrs_logo.png";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRSprev";
		$onRKRS=true;
		$AddURL="RKRSprev";
	}
	if (isset($_GET["ADMIN"])) {
		$setting = "ADMIN";
		$onAdmin=true;
	} else {
		$onAdmin=false;
	}
	if (isset($_GET["INSTR"]) || isset($_POST["INSTR"])) {
		$onInstr = true;
	} else {
		$onInstr = false;
	}
	if (
		(isset($_GET) && is_array($_GET) && (isset($_GET["NOM"]) || array_key_exists("NOM", $_GET)))
		|| (isset($_POST) && is_array($_POST) && (isset($_POST["NOM"]) || array_key_exists("NOM", $_POST)))
	) {
		$onNom = true;
	} else {
		$onNom = false;
	}

	// RKRS mode should work without depending on access/menu bootstrap from RKRK modules.
	if ($onRKRS) {
		if (!isset($LoggedOn)) {
			$LoggedOn = true;
		}
		if (!function_exists('CheckIPandExit')) {
			function CheckIPandExit(): void {}
		}
		if (!function_exists('GrantAccess')) {
			function GrantAccess($folderoffset = ""): void {
				$GLOBALS['rkrk_grant_folderoffset'] = (string)$folderoffset;
				if (!isset($GLOBALS["LoggedOn"])) {
					$GLOBALS["LoggedOn"] = true;
				}
			}
		}
		if (!function_exists('CreateTopMenu')) {
			function CreateTopMenu($folderoffset = ""): void {}
		}
		if (!function_exists('GetAccessByFunction')) {
			function GetAccessByFunction($fn): bool { return true; }
		}
		if (!function_exists('RegisterForm')) {
			function RegisterForm($ip = "", $mode = "", $auto = false): void {}
		}
	}

	// AddURL mit GET abgleichen (letzter Kanal-Schlüssel gewinnt wie bei den Blöcken oben).
	if (!isset($AddURL) || (string)$AddURL === "") {
		$__sync = "";
		foreach (["RKRS", "RKRSplus", "RKRSprev"] as $__k) {
			if (isset($_GET) && is_array($_GET) && array_key_exists($__k, $_GET)) {
				$__sync = $__k;
			}
		}
		if ($__sync !== "") {
			$AddURL = $__sync;
		}
		unset($__sync, $__k);
	}

/* ---------------- FOLDER SETUP ---------------- */
if (!$onRKRS) {
	//$rkrkDataRoot = isset($rkrkAssessFsDataRoot) ? $rkrkAssessFsDataRoot : $RKRKRelRKRKDataLocation;
	$rkrkDataRoot = $folderoffset.$RKRKRelRKRKDataLocation;
} else {
	if ((strpos($phpSelf, "/rkrs/assess") !== false)) {
		$rkrkDataRoot = "../../"; 
	} else {
		$rkrkDataRoot = "../../"; 
	}
}

//$files = array_diff(scandir($rkrkDataRoot), array('.', '..'));

//foreach ($files as $file) {
//    echo $file . "<br>";
//}


//rkrk_assess_live.php
//rkrk_assess_live_personel.php
$rkrkLiveassessData = $rkrkDataRoot . "data/liveassess";
$rkrkLivePersonelRoot = $rkrkLiveassessData . "/personel";
$rkrkLiveassessTemplates = $rkrkLiveassessData . "/templates";
$rkrkLiveassessResults  = $rkrkLiveassessData . "/results/checklists/";
//echo "<div>LiveAssess Results: ".$rkrkLiveassessResults."</div>";

//rkrk_assess_admin.php
//rkrk_digitalassess.php
$QuizLoc = $rkrkDataRoot . 'data/assess/';
$QuizLocResults = $QuizLoc . 'results/';
$QuizIconLoc = $QuizLoc . 'images/';
$QuizLocTemplates = $QuizLoc . 'templates/';
$QuizLocStoryboards = $QuizLoc . 'storyboards/';
//echo "<div>QuizLog: ".$QuizLoc."</div>";
//echo "<div>QuizIconLoc: ".$QuizIconLoc."</div>";

// rkrk_assess.php — abgeschlossene Checklisten unter results/checklists/
$rkrkAssessTemplates  = $rkrkDataRoot . "data/assess/templates";
$rkrkAssessStoryboards = $rkrkDataRoot . "data/assess/storyboards";
$rkrkAssessResults    = $QuizLocResults . "checklists/";

$__rkrkStorageMigrateInc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_storage_migrate.inc.php";
if (is_readable($__rkrkStorageMigrateInc)) {
	include_once $__rkrkStorageMigrateInc;
	if (function_exists("rkrk_assess_storage_migrate_legacy_layout")) {
		rkrk_assess_storage_migrate_legacy_layout();
	}
}

$__rkrkStoryboardInc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_storyboard.inc.php";
if (is_readable($__rkrkStoryboardInc)) {
	include_once $__rkrkStoryboardInc;
}
$__rkrkStoryboardEditorInc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_storyboard_editor.inc.php";
if (is_readable($__rkrkStoryboardEditorInc)) {
	include_once $__rkrkStoryboardEditorInc;
}

$__rkrkReportPdfInc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_report_pdf.inc.php";
if (is_readable($__rkrkReportPdfInc)) {
	include_once $__rkrkReportPdfInc;
	if (function_exists("rkrk_assess_report_migrate_portable_html_files")) {
		rkrk_assess_report_migrate_portable_html_files();
	}
	if (function_exists("rkrk_assess_report_migrate_html_to_pdf")) {
		rkrk_assess_report_migrate_html_to_pdf();
	}
}

/* ------------- END FOLDER SETUP -------------- */

/**
 * Führender Punktcode eines Labels (z. B. A.5, A.10*, M.2#), sonst "".
 */
function rkrk_field_label_point_prefix($label) {
	$label = trim((string)$label);
	if ($label === "") {
		return "";
	}
	if (preg_match('/^([A-Za-z]\.\d+[*#]?)\b/u', $label, $m)) {
		return (string)$m[1];
	}
	return "";
}

/**
 * Punktcode ohne Trailing-* / Trailing-# (A.10* → A.10).
 */
function rkrk_field_label_point_base($prefix) {
	return rtrim((string)$prefix, "*#");
}

/**
 * Label-Text ohne führenden Punktcode, normalisiert wie rkrk_normalize_field_label_key
 * (Trailing-(g)/(o)/(r)/(n) entfallen). Für Zuordnung nach Template-Umnummerierung.
 */
function rkrk_field_label_body_key($label) {
	$s = trim((string)$label);
	if ($s === "") {
		return "";
	}
	$s = preg_replace('/^[A-Za-z]\.\d+[*#]?\s+/u', "", $s);
	$s = preg_replace('/^[A-Za-z]\.\d+[*#]?$/u', "", $s);
	if (!function_exists("rkrk_normalize_field_label_key")) {
		return mb_strtolower(trim((string)$s), "UTF-8");
	}
	return rkrk_normalize_field_label_key($s);
}

/**
 * Zeigt die dynamische Bestehensschwelle (z. B. 44 Punkte bei 80 % von 54).
 */
function rkrk_assess_echo_pass_need($threshold) {
	$th = trim((string)$threshold);
	if ($th === "") {
		return;
	}
	$thEsc = htmlspecialchars($th, ENT_QUOTES, "UTF-8");
	echo "<div class='pass-need-line field-placeholder'>Zum Bestehen nötig: <strong class='sum-need-value' data-pass-need='1'>–</strong> <span class='pass-need-threshold'>(Schwelle " . $thEsc . ")</span></div>";
}

/**
 * Mappt gespeicherte Feldlabels einer Section auf aktuelle Template-Labels.
 * 1) exakter Treffer, 2) normalisierter Key, 3) eindeutiger Body-Key (ohne Punktcode),
 * 4) mehrdeutige Bodies (z. B. Bemerkung) über denselben alten→neuen Punktcode.
 *
 * @param array<string, mixed> $templateSection
 * @param array<string, mixed> $savedSection
 * @return array<string, string> savedLabel => templateLabel
 */
function rkrk_map_saved_labels_to_template(array $templateSection, array $savedSection) {
	$map = array();
	if ($savedSection === array()) {
		return $map;
	}

	$tplLabels = array_keys($templateSection);
	$tplByExact = array();
	$tplByNorm = array();
	$tplByBody = array();
	foreach ($tplLabels as $tl) {
		$tl = (string)$tl;
		$tplByExact[$tl] = $tl;
		if (function_exists("rkrk_normalize_field_label_key")) {
			$tplByNorm[rkrk_normalize_field_label_key($tl)] = $tl;
		}
		$bk = rkrk_field_label_body_key($tl);
		if ($bk !== "") {
			if (!isset($tplByBody[$bk])) {
				$tplByBody[$bk] = array();
			}
			$tplByBody[$bk][] = $tl;
		}
	}

	$ambiguousBody = array();
	foreach ($tplByBody as $bk => $labels) {
		if (count($labels) > 1) {
			$ambiguousBody[$bk] = true;
		}
	}

	$pointRemap = array();

	// 1) Eindeutige Body-Keys zuerst → Punktcode-Remap (A.5 Auswahl… → A.6 …)
	foreach ($savedSection as $savedLabel => $_value) {
		$savedLabel = (string)$savedLabel;
		$bk = rkrk_field_label_body_key($savedLabel);
		if ($bk === "" || isset($ambiguousBody[$bk])) {
			continue;
		}
		if (!isset($tplByBody[$bk]) || count($tplByBody[$bk]) !== 1) {
			continue;
		}
		$tplLabel = $tplByBody[$bk][0];
		$map[$savedLabel] = $tplLabel;
		$oldP = rkrk_field_label_point_base(rkrk_field_label_point_prefix($savedLabel));
		$newP = rkrk_field_label_point_base(rkrk_field_label_point_prefix($tplLabel));
		if ($oldP !== "" && $newP !== "") {
			$pointRemap[$oldP] = $newP;
		}
	}

	// 2) Mehrdeutige Bodies (Bemerkung, Nicht erfüllte Punkte, …) dem Score-Remap folgen
	foreach ($savedSection as $savedLabel => $_value) {
		$savedLabel = (string)$savedLabel;
		if (isset($map[$savedLabel])) {
			continue;
		}
		$bk = rkrk_field_label_body_key($savedLabel);
		if ($bk === "" || !isset($ambiguousBody[$bk])) {
			continue;
		}
		$oldP = rkrk_field_label_point_base(rkrk_field_label_point_prefix($savedLabel));
		if ($oldP === "" || !isset($pointRemap[$oldP])) {
			continue;
		}
		$newP = $pointRemap[$oldP];
		foreach ($tplLabels as $tl) {
			$tl = (string)$tl;
			if (rkrk_field_label_point_base(rkrk_field_label_point_prefix($tl)) !== $newP) {
				continue;
			}
			if (rkrk_field_label_body_key($tl) !== $bk) {
				continue;
			}
			$map[$savedLabel] = $tl;
			break;
		}
	}

	// 3) Rest: exakt / normalisiert (unveränderte Punktcodes, Felder ohne Remap)
	foreach ($savedSection as $savedLabel => $_value) {
		$savedLabel = (string)$savedLabel;
		if (isset($map[$savedLabel])) {
			continue;
		}
		if (isset($tplByExact[$savedLabel])) {
			$map[$savedLabel] = $savedLabel;
			continue;
		}
		if (function_exists("rkrk_normalize_field_label_key")) {
			$nk = rkrk_normalize_field_label_key($savedLabel);
			if (isset($tplByNorm[$nk])) {
				$map[$savedLabel] = $tplByNorm[$nk];
			}
		}
	}

	return $map;
}

/**
 * Zeilen aus pilotenliste.json (Schlüssel pilots oder rows; Legacy: numerisches Array).
 * Gleiche Datenbasis wie rkrk_assess_live.php (Vorschläge Pilot / Instruktor).
 *
 * @return list<array<string, mixed>>
 */
function rkrk_assess_personel_rows_from_pilotenliste(?string $personelRoot = null): array {
	$root = $personelRoot !== null ? trim((string)$personelRoot) : "";
	if ($root === "") {
		$candidates = array();
		if (isset($GLOBALS["rkrkLivePersonelRoot"])) {
			$candidates[] = trim((string)$GLOBALS["rkrkLivePersonelRoot"]);
		}
		if (isset($GLOBALS["rkrkDataRoot"])) {
			$candidates[] = rtrim(str_replace("/", DIRECTORY_SEPARATOR, (string)$GLOBALS["rkrkDataRoot"]), DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "assess" . DIRECTORY_SEPARATOR . "personel";
		}
		$root = "";
		foreach ($candidates as $cand) {
			$cand = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $cand), DIRECTORY_SEPARATOR);
			if ($cand === "") {
				continue;
			}
			if (is_file($cand . DIRECTORY_SEPARATOR . "pilotenliste.json")) {
				$root = $cand;
				break;
			}
		}
		if ($root === "") {
			foreach ($candidates as $cand) {
				$cand = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $cand), DIRECTORY_SEPARATOR);
				if ($cand !== "") {
					$root = $cand;
					break;
				}
			}
		}
	}
	$root = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);
	if ($root === "") {
		return array();
	}
	$path = $root . DIRECTORY_SEPARATOR . "pilotenliste.json";
	if (!is_file($path)) {
		return array();
	}
	$raw = @file_get_contents($path);
	if ($raw === false || trim($raw) === "") {
		return array();
	}
	$data = @json_decode($raw, true);
	if (!is_array($data)) {
		return array();
	}
	if (isset($data["pilots"]) && is_array($data["pilots"])) {
		return $data["pilots"];
	}
	if (isset($data["rows"]) && is_array($data["rows"])) {
		return $data["rows"];
	}
	if (isset($data[0]) && is_array($data[0])) {
		return $data;
	}
	return array();
}

/** Anzeigename einer Personalzeile (Vorname + Nachname, sonst name/display). */
function rkrk_assess_personel_row_display_name(array $row): string {
	$vn = trim((string)($row["vorname"] ?? ""));
	$nn = trim((string)($row["nachname"] ?? ""));
	if ($vn !== "" || $nn !== "") {
		return trim($vn . " " . $nn);
	}
	$n = trim((string)($row["name"] ?? ""));
	if ($n !== "") {
		return $n;
	}
	return trim((string)($row["display"] ?? ""));
}

/**
 * Zellenwert als Ja/Nein (Personal-JSON): true bei ja, 1, yes, true, …
 *
 * @param mixed $val
 */
function rkrk_assess_personel_cell_truthy($val): bool {
	if (is_bool($val)) {
		return $val;
	}
	$s = strtolower(trim((string)$val));
	return $s === "1" || $s === "ja" || $s === "yes" || $s === "true" || $s === "wahr" || $s === "y" || $s === "j";
}

/**
 * Zeile gilt als Instruktor/Prüfer für Vorschläge, wenn eine passende Spalte «wahr» ist
 * (z. B. instruktor / Instruktor / is_instruktor, pruefer als Rolle — nicht der Anzeigename «Prüfer» im Formular).
 */
function rkrk_assess_personel_row_instruktor_flag(array $row): bool {
	foreach (array("instruktor", "Instruktor", "is_instruktor", "IsInstruktor", "pruefer", "Pruefer", "ist_pruefer", "IstPruefer", "ist_prüfer") as $k) {
		if (!array_key_exists($k, $row)) {
			continue;
		}
		if (rkrk_assess_personel_cell_truthy($row[$k])) {
			return true;
		}
	}
	if (array_key_exists("Prüfer", $row) && rkrk_assess_personel_cell_truthy($row["Prüfer"])) {
		return true;
	}
	return false;
}

/**
 * Wie rkrk_assess_personel_flat_for_select, nur Zeilen mit Instruktor-/Prüfer-Flag (PersPick: Vorschläge für Instruktor/Prüfer).
 *
 * @return list<array{pilot_id:string, rkrs_pilot_id:string, name:string, row:array<string, mixed>}>
 */
function rkrk_assess_personel_flat_for_instruktor_pruefer_select(?string $personelRoot = null): array {
	$all = rkrk_assess_personel_flat_for_select($personelRoot);
	$out = array();
	foreach ($all as $e) {
		$row = isset($e["row"]) && is_array($e["row"]) ? $e["row"] : array();
		if (!rkrk_assess_personel_row_instruktor_flag($row)) {
			continue;
		}
		$out[] = $e;
	}
	return $out;
}

/**
 * Sortierte eindeutige Namen für Quiz-/Schulungs-Teilnehmer (datalist), aus pilotenliste.json.
 *
 * @return list<string>
 */
function rkrk_assess_quiz_participant_name_suggestions(): array {
	static $cache = null;
	if (is_array($cache)) {
		return $cache;
	}
	$cache = array();
	foreach (rkrk_assess_personel_rows_from_pilotenliste() as $row) {
		if (!is_array($row)) {
			continue;
		}
		$n = rkrk_assess_personel_row_display_name($row);
		if ($n !== "") {
			$cache[] = $n;
		}
	}
	$cache = array_values(array_unique($cache));
	sort($cache, SORT_NATURAL | SORT_FLAG_CASE);
	return $cache;
}

/**
 * HTML &lt;datalist id="rkrk-dga-quiz-names"&gt; für rkrk_digitalassess.php (einmal pro Seite cachen).
 */
function rkrk_assess_emit_digitalassess_name_datalist_html(): string {
	static $memo = null;
	if ($memo !== null) {
		return $memo;
	}
	$names = rkrk_assess_quiz_participant_name_suggestions();
	if (count($names) === 0) {
		$memo = "";
		return "";
	}
	$buf = "<datalist id=\"rkrk-dga-quiz-names\">";
	foreach ($names as $nm) {
		$buf .= "<option value=\"" . htmlspecialchars($nm, ENT_QUOTES, "UTF-8") . "\">";
	}
	$buf .= "</datalist>";
	$memo = $buf;
	return $memo;
}

/** JSON-Feldname für die RKRS-PilotID (Checklisten). */
function rkrk_assess_pers_pick_id_label(): string {
	return "RKRSPilotID";
}

/** Legacy-Feldnamen für Migration und URL-Aliase. */
function rkrk_assess_pers_pick_legacy_id_labels(): array {
	return array("PilotenID", "PilotID", "pilot_id", "pilotid");
}

/**
 * GET-Wert aus mehreren möglichen Parameternamen.
 *
 * @param list<string> $keys
 */
function rkrk_assess_request_param_from_keys(array $keys, ?array $source = null): string {
	$src = $source !== null ? $source : $_GET;
	foreach ($keys as $gk) {
		if (isset($src[$gk]) && trim((string)$src[$gk]) !== "") {
			return trim((string)$src[$gk]);
		}
	}
	return "";
}

/**
 * @param array<string, array<string, mixed>> $sections
 */
function rkrk_assess_template_has_field(array $sections, string $label): bool {
	foreach ($sections as $fields) {
		if (!is_array($fields)) {
			continue;
		}
		if (array_key_exists($label, $fields)) {
			return true;
		}
	}
	return false;
}

/**
 * RKRS-PilotID einer Personalzeile (nur `rkrs_pilot_id`).
 * Nicht mit der fortlaufenden Listen-Spalte `pilot_id` (Anzeige: PilotID) vermischen.
 */
function rkrk_assess_personel_row_effective_rkrs_id(array $row): string {
	return trim((string)($row["rkrs_pilot_id"] ?? ""));
}

/** Fortlaufende Listen-PilotID (1…n) einer Personalzeile. */
function rkrk_assess_personel_row_seq_pilot_id(array $row): string {
	return trim((string)($row["pilot_id"] ?? ""));
}

/** Remote-Piloten-ID aus Personalzeile. */
function rkrk_assess_personel_row_remote_pilot_id(array $row): string {
	$v = trim((string)($row["remote_pilot_id"] ?? ""));
	if ($v !== "") {
		return $v;
	}
	return trim((string)($row["remote_piloten_id"] ?? ""));
}

/** Betreiber-ID aus Personalzeile. */
function rkrk_assess_personel_row_betreiber_id(array $row): string {
	return trim((string)($row["betreiber_id"] ?? ""));
}

/**
 * Alte Resultat-/Formularfelder auf RKRSPilotID migrieren.
 *
 * @param array<string, mixed> $fields
 */
function rkrk_assess_migrate_fields_legacy_pilot_ids(array &$fields): void {
	if (!isset($fields["Allgemein"]) || !is_array($fields["Allgemein"])) {
		return;
	}
	$ag = &$fields["Allgemein"];
	$idLab = rkrk_assess_pers_pick_id_label();
	if (trim((string)($ag[$idLab] ?? "")) === "") {
		foreach (rkrk_assess_pers_pick_legacy_id_labels() as $legacy) {
			if (!array_key_exists($legacy, $ag)) {
				continue;
			}
			$v = trim((string)$ag[$legacy]);
			if ($v !== "") {
				$ag[$idLab] = $v;
				if ($legacy !== $idLab) {
					unset($ag[$legacy]);
				}
				break;
			}
		}
	}
	// Legacy EASA-Feldnamen in Material-Resultaten
	if (trim((string)($ag["RemotePilotID"] ?? "")) === "") {
		foreach (array("RemotePilotenID", "Fernpiloten-ID", "FernpilotenID") as $legacy) {
			if (!array_key_exists($legacy, $ag)) {
				continue;
			}
			$v = trim((string)$ag[$legacy]);
			if ($v !== "") {
				$ag["RemotePilotID"] = $v;
				if ($legacy !== "RemotePilotID") {
					unset($ag[$legacy]);
				}
				break;
			}
		}
	} elseif (array_key_exists("RemotePilotenID", $ag)) {
		unset($ag["RemotePilotenID"]);
	}
	if (trim((string)($ag["BetreiberID"] ?? "")) === "") {
		if (array_key_exists("Betreiber-ID", $ag)) {
			$v = trim((string)$ag["Betreiber-ID"]);
			if ($v !== "") {
				$ag["BetreiberID"] = $v;
				unset($ag["Betreiber-ID"]);
			}
		}
	} elseif (array_key_exists("Betreiber-ID", $ag)) {
		unset($ag["Betreiber-ID"]);
	}
}

/**
 * PersPick/Personalzeile in gespeicherte Allgemein-Felder übernehmen (template-abhängig).
 *
 * @param array<string, mixed> $saved
 * @param array<string, array<string, mixed>> $sections
 * @param array{pilot_id:string, name:string, row:array<string, mixed>} $hit
 */
function rkrk_assess_pers_pick_apply_person_to_saved(array &$saved, array $sections, array $hit): void {
	if (!isset($saved["Allgemein"]) || !is_array($saved["Allgemein"])) {
		$saved["Allgemein"] = array();
	}
	$ag = &$saved["Allgemein"];
	$plab = rkrk_assess_pers_pick_resolve_pilot_label($sections);
	if ($plab !== null && trim((string)($ag[$plab] ?? "")) === "") {
		$ag[$plab] = (string)($hit["name"] ?? "");
	}
	$idLab = rkrk_assess_pers_pick_id_label();
	if (trim((string)($ag[$idLab] ?? "")) === "") {
		$ag[$idLab] = rkrk_assess_personel_row_effective_rkrs_id($hit["row"]);
	}
	$row = isset($hit["row"]) && is_array($hit["row"]) ? $hit["row"] : array();
	if (rkrk_assess_template_has_field($sections, "RemotePilotID") && trim((string)($ag["RemotePilotID"] ?? "")) === "") {
		$ag["RemotePilotID"] = rkrk_assess_personel_row_remote_pilot_id($row);
	}
	if (rkrk_assess_template_has_field($sections, "BetreiberID") && trim((string)($ag["BetreiberID"] ?? "")) === "") {
		$ag["BetreiberID"] = rkrk_assess_personel_row_betreiber_id($row);
	}
	if (rkrk_assess_template_has_field($sections, "Kontaktnummer") && trim((string)($ag["Kontaktnummer"] ?? "")) === "") {
		$tel = trim((string)($row["telefon"] ?? ""));
		if ($tel !== "") {
			$ag["Kontaktnummer"] = $tel;
		}
	}
}

/** Pfad zu pilotenliste.json oder null. */
function rkrk_assess_personel_get_file_path(): ?string {
	$candidates = array();
	if (isset($GLOBALS["rkrkLivePersonelRoot"])) {
		$candidates[] = trim((string)$GLOBALS["rkrkLivePersonelRoot"]);
	}
	if (isset($GLOBALS["rkrkDataRoot"])) {
		$candidates[] = rtrim(str_replace("/", DIRECTORY_SEPARATOR, (string)$GLOBALS["rkrkDataRoot"]), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "liveassess" . DIRECTORY_SEPARATOR . "personel";
		$candidates[] = rtrim(str_replace("/", DIRECTORY_SEPARATOR, (string)$GLOBALS["rkrkDataRoot"]), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "assess" . DIRECTORY_SEPARATOR . "personel";
	}
	foreach ($candidates as $cand) {
		$cand = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $cand), DIRECTORY_SEPARATOR);
		if ($cand === "") {
			continue;
		}
		$path = $cand . DIRECTORY_SEPARATOR . "pilotenliste.json";
		if (is_file($path)) {
			return $path;
		}
	}
	foreach ($candidates as $cand) {
		$cand = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $cand), DIRECTORY_SEPARATOR);
		if ($cand !== "") {
			return $cand . DIRECTORY_SEPARATOR . "pilotenliste.json";
		}
	}
	return null;
}

/**
 * @return array{columns:list<string>, pilots:list<array<string, mixed>>}
 */
function rkrk_assess_personel_load_file(?string $path = null): array {
	$path = $path !== null ? trim($path) : "";
	if ($path === "") {
		$resolved = rkrk_assess_personel_get_file_path();
		$path = $resolved !== null ? $resolved : "";
	}
	if ($path === "") {
		return array("columns" => array(), "pilots" => array());
	}
	$rows = rkrk_assess_personel_rows_from_pilotenliste(dirname($path));
	$columns = array(
		"pilot_id", "vorname", "nachname", "strasse", "plz", "ort", "email", "telefon",
		"erste_erfahrung", "instruktor", "alternative_rollen", "drohnentyp",
		"rkrs_pilot_id", "remote_pilot_id", "betreiber_id",
	);
	if (is_file($path)) {
		$raw = @file_get_contents($path);
		if ($raw !== false && trim($raw) !== "") {
			$data = @json_decode($raw, true);
			if (is_array($data) && isset($data["columns"]) && is_array($data["columns"])) {
				foreach ($data["columns"] as $c) {
					$c = trim((string)$c);
					if ($c !== "" && !in_array($c, $columns, true)) {
						$columns[] = $c;
					}
				}
			}
		}
	}
	foreach (array("rkrs_pilot_id", "remote_pilot_id", "betreiber_id") as $req) {
		if (!in_array($req, $columns, true)) {
			$columns[] = $req;
		}
	}
	return array("columns" => $columns, "pilots" => $rows);
}

/**
 * @param list<string> $columns
 * @param list<array<string, mixed>> $pilots
 */
function rkrk_assess_personel_save_file(string $path, array $columns, array $pilots): bool {
	$dir = dirname($path);
	if ($dir !== "" && !is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}
	$columnsSave = array();
	foreach ($columns as $c) {
		$c = trim((string)$c);
		if ($c !== "" && $c !== "id") {
			$columnsSave[] = $c;
		}
	}
	$payload = array(
		"version" => 2,
		"updated_at" => date("c"),
		"columns" => array_values(array_unique($columnsSave)),
		"pilots" => array_values($pilots),
	);
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if (!is_string($json) || $json === "") {
		return false;
	}
	$tmp = $path . ".tmp." . substr(hash("sha1", microtime(true) . "|" . $path), 0, 10);
	if (@file_put_contents($tmp, $json) === false) {
		@unlink($tmp);
		return false;
	}
	if (!@rename($tmp, $path)) {
		$ok = @copy($tmp, $path);
		@unlink($tmp);
		return $ok;
	}
	return true;
}

/**
 * Name in Vorname/Nachname splitten.
 *
 * @return array{vorname:string, nachname:string}
 */
function rkrk_assess_split_display_name(string $name): array {
	$name = preg_replace('/\s+/u', " ", trim($name));
	if ($name === "") {
		return array("vorname" => "", "nachname" => "");
	}
	$parts = preg_split('/\s+/u', $name, 2);
	return array(
		"vorname" => (string)($parts[0] ?? ""),
		"nachname" => (string)($parts[1] ?? ""),
	);
}

/**
 * URL-/Request-Parameter in pilotenliste.json übernehmen.
 * `pilot_id` (Listen-Nr.) und `rkrs_pilot_id` (RKRSPilotID) bleiben getrennt.
 * Fehlende RKRSPilotID zum Pilotenamen wird bei Prüfung/Test/Assessment nachgetragen.
 *
 * @param array<string, mixed> $params
 * @param array<string, array<string, mixed>>|null $sections
 */
function rkrk_assess_personel_upsert_from_url_params(array $params, ?array $sections = null): void {
	// Quiz-URLs nutzen historisch noch «PilotID» als Wertträger für die RKRS-ID (Hash-Feld).
	$rkrsId = rkrk_assess_request_param_from_keys(
		array_merge(array("RKRSPilotID"), rkrk_assess_pers_pick_legacy_id_labels()),
		$params
	);
	$remoteId = rkrk_assess_request_param_from_keys(
		array("RemotePilotID", "RemotePilotenID", "Fernpiloten-ID", "FernpilotenID"),
		$params
	);
	$betreiberId = rkrk_assess_request_param_from_keys(
		array("BetreiberID", "Betreiber-ID", "BetreiberId"),
		$params
	);
	$name = rkrk_assess_request_param_from_keys(array("Pilot", "Piloten", "Name"), $params);
	$tel = rkrk_assess_request_param_from_keys(array("Kontaktnummer", "telefon", "Telefon"), $params);

	if ($sections !== null) {
		if (!rkrk_assess_template_has_field($sections, "RemotePilotID")) {
			$remoteId = "";
		}
		if (!rkrk_assess_template_has_field($sections, "BetreiberID")) {
			$betreiberId = "";
		}
	}

	if ($rkrsId === "" && $remoteId === "" && $betreiberId === "" && $name === "" && $tel === "") {
		return;
	}

	$path = rkrk_assess_personel_get_file_path();
	if ($path === null) {
		return;
	}
	$loaded = rkrk_assess_personel_load_file($path);
	$columns = $loaded["columns"];
	$pilots = $loaded["pilots"];
	$idx = null;
	$changed = false;

	// Zuerst per RKRSPilotID, nie über die fortlaufende Listen-PilotID.
	if ($rkrsId !== "") {
		for ($i = 0; $i < count($pilots); $i++) {
			if (!is_array($pilots[$i])) {
				continue;
			}
			if (rkrk_assess_personel_row_effective_rkrs_id($pilots[$i]) === $rkrsId) {
				$idx = $i;
				break;
			}
		}
	}
	if ($idx === null && $name !== "") {
		for ($i = 0; $i < count($pilots); $i++) {
			if (!is_array($pilots[$i])) {
				continue;
			}
			if (rkrk_assess_personel_names_equal(rkrk_assess_personel_row_display_name($pilots[$i]), $name)) {
				$idx = $i;
				break;
			}
		}
	}

	if ($idx === null && ($name !== "" || $rkrsId !== "")) {
		$nn = rkrk_assess_split_display_name($name);
		$newSeq = (string)(count($pilots) + 1);
		$pilots[] = array(
			"id" => substr(hash("sha1", microtime(true) . "|" . $name . "|" . $rkrsId), 0, 16),
			"pilot_id" => $newSeq,
			"rkrs_pilot_id" => $rkrsId,
			"remote_pilot_id" => "",
			"betreiber_id" => "",
			"vorname" => $nn["vorname"],
			"nachname" => $nn["nachname"],
			"telefon" => "",
		);
		$idx = count($pilots) - 1;
		$changed = true;
	}
	if ($idx === null) {
		return;
	}

	$row = &$pilots[$idx];
	if ($rkrsId !== "") {
		$curRkrs = rkrk_assess_personel_row_effective_rkrs_id($row);
		if ($curRkrs === "") {
			// Fehlende RKRSPilotID zum Pilotenamen nachtragen.
			$row["rkrs_pilot_id"] = $rkrsId;
			$changed = true;
		} elseif ($curRkrs !== $rkrsId) {
			$row["rkrs_pilot_id"] = $rkrsId;
			$changed = true;
		}
	}
	if ($remoteId !== "") {
		if (trim((string)($row["remote_pilot_id"] ?? "")) !== $remoteId) {
			$row["remote_pilot_id"] = $remoteId;
			$changed = true;
		}
		unset($row["remote_piloten_id"]);
	}
	if ($betreiberId !== "") {
		if (trim((string)($row["betreiber_id"] ?? "")) !== $betreiberId) {
			$row["betreiber_id"] = $betreiberId;
			$changed = true;
		}
	}
	if ($tel !== "") {
		if (trim((string)($row["telefon"] ?? "")) !== $tel) {
			$row["telefon"] = $tel;
			$changed = true;
		}
	}
	if ($name !== "") {
		$nn = rkrk_assess_split_display_name($name);
		if ($nn["vorname"] !== "" && trim((string)($row["vorname"] ?? "")) !== $nn["vorname"]) {
			$row["vorname"] = $nn["vorname"];
			$changed = true;
		}
		if ($nn["nachname"] !== "" && trim((string)($row["nachname"] ?? "")) !== $nn["nachname"]) {
			$row["nachname"] = $nn["nachname"];
			$changed = true;
		}
	}

	foreach (array("rkrs_pilot_id", "remote_pilot_id", "betreiber_id") as $reqCol) {
		if (!in_array($reqCol, $columns, true)) {
			$columns[] = $reqCol;
			$changed = true;
		}
	}

	if (!$changed) {
		return;
	}
	rkrk_assess_personel_save_file($path, $columns, $pilots);
}

/** JSON-Map Anzeigename → RKRS-PilotID für Link-Generatoren. */
function rkrk_assess_pers_pick_name_to_pilot_id_map_json(): string {
	$map = array();
	foreach (rkrk_assess_personel_flat_for_select() as $e) {
		$nm = trim((string)($e["name"] ?? ""));
		$pid = rkrk_assess_personel_row_effective_rkrs_id(
			isset($e["row"]) && is_array($e["row"]) ? $e["row"] : array()
		);
		if ($nm === "" || $pid === "") {
			continue;
		}
		$map[$nm] = $pid;
	}
	return json_encode($map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * JSON-Map Anzeigename → IDs/Stammdaten aus der Pilotenliste (nur gesetzte Werte).
 * Schlüssel je Eintrag: rkrs, remote, betreiber, telefon, drohnentyp.
 */
function rkrk_assess_pers_pick_name_to_ids_map_json(): string {
	$map = array();
	foreach (rkrk_assess_personel_flat_for_select() as $e) {
		$nm = trim((string)($e["name"] ?? ""));
		if ($nm === "") {
			continue;
		}
		$row = isset($e["row"]) && is_array($e["row"]) ? $e["row"] : array();
		$entry = array();
		$rkrs = rkrk_assess_personel_row_effective_rkrs_id($row);
		$remote = rkrk_assess_personel_row_remote_pilot_id($row);
		$betreiber = rkrk_assess_personel_row_betreiber_id($row);
		$telefon = trim((string)($row["telefon"] ?? ""));
		$drohne = trim((string)($row["drohnentyp"] ?? ""));
		if ($rkrs !== "") {
			$entry["rkrs"] = $rkrs;
		}
		if ($remote !== "") {
			$entry["remote"] = $remote;
		}
		if ($betreiber !== "") {
			$entry["betreiber"] = $betreiber;
		}
		if ($telefon !== "") {
			$entry["telefon"] = $telefon;
		}
		if ($drohne !== "") {
			$entry["drohnentyp"] = $drohne;
		}
		if ($entry === []) {
			continue;
		}
		$map[$nm] = $entry;
	}
	return json_encode($map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/** cs-Hash für Portal-Quiz-URLs (assessment::entry). */
function rkrk_assess_hash_compute_cs(string $payload): string {
	if (!class_exists("assessment") || !is_callable(array("assessment", "entry"))) {
		return "";
	}
	return strtolower((string)assessment::entry($payload));
}

/**
 * Payload und cs für Hash-Gate-Regel eines Quiz-Keys.
 *
 * @param array<string, mixed> $params
 * @return array{payload:string, cs:string, required_fields:list<string>}
 */
function rkrk_assess_hash_build_cs_for_quiz(string $quiz, array $params): array {
	$quiz = trim($quiz);
	$out = array("payload" => "", "cs" => "", "required_fields" => array());
	if ($quiz === "" || !function_exists("rkrk_assess_hash_gate_resolve_rule")) {
		return $out;
	}
	$scope = "rkrk_digitalassess.php";
	$rule = rkrk_assess_hash_gate_resolve_rule($scope, $quiz);
	if ($rule === null) {
		return $out;
	}
	$build = rkrk_assess_hash_gate_build_payload_from_rule($rule, $params);
	$out["required_fields"] = $build["required_fields"];
	if ($build["payload"] === null) {
		return $out;
	}
	$out["payload"] = (string)$build["payload"];
	$out["cs"] = rkrk_assess_hash_compute_cs($out["payload"]);
	return $out;
}

/**
 * PersPick aus GET/POST wie rkrk_digitalassess.php (nur explizite Werte gelten als «bekannt»).
 *
 * @return array{raw:string, known:bool, enabled:bool}
 */
function rkrk_assess_pers_pick_parse_request(): array {
	$raw = "";
	if (isset($_GET["PersPick"])) {
		$raw = trim((string)$_GET["PersPick"]);
	} elseif (isset($_POST["PersPick"])) {
		$raw = trim((string)$_POST["PersPick"]);
	}
	$norm = strtolower($raw);
	$enabled = true;
	$known = false;
	if ($raw !== "") {
		if (in_array($norm, array("off", "0", "false", "no", "aus"), true)) {
			$enabled = false;
			$known = true;
		} elseif (in_array($norm, array("on", "1", "true", "yes", "ein", "an"), true)) {
			$enabled = true;
			$known = true;
		}
	}
	return array("raw" => $raw, "known" => $known, "enabled" => $enabled);
}

/** PersPick=on explizit (für Checklisten: Personal-Auswahl nur bei URL-Parameter «on»). */
function rkrk_assess_pers_pick_explicit_on(): bool {
	$st = rkrk_assess_pers_pick_parse_request();
	return ($st["known"] && $st["enabled"]);
}

/** PersPick für Links/Hidden nur, wenn GET/POST explizit «on» oder «off» setzt (sonst null). */
function rkrk_assess_carry_perspick_value(): ?string {
	$st = rkrk_assess_pers_pick_parse_request();
	if (!$st["known"]) {
		return null;
	}
	return $st["enabled"] ? "on" : "off";
}

/**
 * Wert für HTML &lt;input type="date"&gt; (nur Y-m-d). Leer im Bearbeitungsmodus → leer;
 * neuer Datensatz ohne Wert → heutiges Datum. Akzeptiert gespeicherte Notation d.m.Y.
 *
 * @param mixed $raw
 */
function rkrk_html_date_input_value($raw, bool $isEditMode): string {
	$s = trim((string)$raw);
	if ($s === "") {
		return $isEditMode ? "" : date("Y-m-d");
	}
	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1) {
		return $s;
	}
	foreach (array("d.m.Y", "d.m.Y H:i", "j.n.Y", "j.n.y") as $fmt) {
		$dt = DateTime::createFromFormat($fmt, $s);
		if ($dt instanceof DateTime) {
			return $dt->format("Y-m-d");
		}
	}
	$ts = strtotime($s);
	if ($ts !== false) {
		return date("Y-m-d", $ts);
	}
	return $isEditMode ? "" : date("Y-m-d");
}

/**
 * Personalzeilen mit Anzeigename, Listen-PilotID und RKRSPilotID.
 *
 * @return list<array{pilot_id:string, rkrs_pilot_id:string, name:string, row:array<string, mixed>}>
 */
function rkrk_assess_personel_flat_for_select(?string $personelRoot = null): array {
	$rows = rkrk_assess_personel_rows_from_pilotenliste($personelRoot);
	$out = array();
	$seq = 0;
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$name = rkrk_assess_personel_row_display_name($row);
		if ($name === "") {
			continue;
		}
		$seq++;
		$seqId = rkrk_assess_personel_row_seq_pilot_id($row);
		if ($seqId === "") {
			$seqId = (string)$seq;
		}
		$rkrsId = rkrk_assess_personel_row_effective_rkrs_id($row);
		$out[] = array(
			"pilot_id" => $seqId,
			"rkrs_pilot_id" => $rkrsId,
			"name" => $name,
			"row" => $row,
		);
	}
	return $out;
}

/**
 * Suche nach RKRSPilotID (nicht nach fortlaufender Listen-PilotID).
 *
 * @param mixed $wantId
 */
function rkrk_assess_personel_find_by_pilot_id($wantId, ?string $personelRoot = null): ?array {
	$wantId = trim((string)$wantId);
	if ($wantId === "") {
		return null;
	}
	foreach (rkrk_assess_personel_flat_for_select($personelRoot) as $e) {
		$rkrs = trim((string)($e["rkrs_pilot_id"] ?? ""));
		if ($rkrs === "" && isset($e["row"]) && is_array($e["row"])) {
			$rkrs = rkrk_assess_personel_row_effective_rkrs_id($e["row"]);
		}
		if ($rkrs !== "" && $rkrs === $wantId) {
			return $e;
		}
	}
	return null;
}

function rkrk_assess_personel_names_equal(string $a, string $b): bool {
	$na = preg_replace('/\s+/u', " ", trim($a));
	$nb = preg_replace('/\s+/u', " ", trim($b));
	if ($na === "" || $nb === "") {
		return false;
	}
	return (strcasecmp($na, $nb) === 0);
}

/**
 * GET-Parameter für die RKRS-PilotID.
 * URL-Aliase: RKRSPilotID sowie historisch PilotID/PilotenID (Quiz-Hash-Feld).
 * Entspricht nicht der Listen-Spalte «PilotID» (1…n) in pilotenliste.json.
 */
function rkrk_assess_request_pilot_id_param(): string {
	return rkrk_assess_request_param_from_keys(
		array_merge(array("RKRSPilotID"), rkrk_assess_pers_pick_legacy_id_labels())
	);
}

/** GET-Parameter Remote Pilot ID. */
function rkrk_assess_request_remote_pilot_id_param(): string {
	return rkrk_assess_request_param_from_keys(array("RemotePilotID", "RemotePilotenID", "Fernpiloten-ID", "FernpilotenID"));
}

/** GET-Parameter Betreiber ID. */
function rkrk_assess_request_betreiber_id_param(): string {
	return rkrk_assess_request_param_from_keys(array("BetreiberID", "Betreiber-ID", "BetreiberId"));
}

/**
 * Welches Template-Feld in «Allgemein» die Person trägt: «Pilot» oder «Piloten».
 *
 * @param array<string, array<string, mixed>> $sections
 */
function rkrk_assess_pers_pick_resolve_pilot_label(array $sections): ?string {
	if (!isset($sections["Allgemein"]) || !is_array($sections["Allgemein"])) {
		return null;
	}
	$ag = $sections["Allgemein"];
	if (array_key_exists("Pilot", $ag)) {
		return "Pilot";
	}
	if (array_key_exists("Piloten", $ag)) {
		return "Piloten";
	}
	return null;
}

/**
 * Nach POST: Namen aus PilotenID ergänzen bzw. PilotenID aus exakt passendem Listen-Namen.
 *
 * @param array<string, array<string, mixed>> $fields
 */
function rkrk_assess_save_normalize_perspick_fields(array &$fields): void {
	$pp = isset($_POST["PersPick"]) && strtolower(trim((string)$_POST["PersPick"])) === "on";
	if (!$pp) {
		return;
	}
	if (!isset($fields["Allgemein"]) || !is_array($fields["Allgemein"])) {
		return;
	}
	$ag = &$fields["Allgemein"];
	$idLab = rkrk_assess_pers_pick_id_label();
	$pid = isset($ag[$idLab]) ? trim((string)$ag[$idLab]) : "";
	$pname = "";
	$pilotKey = null;
	foreach (array("Pilot", "Piloten") as $pl) {
		if (array_key_exists($pl, $ag)) {
			$pilotKey = $pl;
			$pname = trim((string)$ag[$pl]);
			break;
		}
	}
	if ($pid !== "" && $pname === "" && $pilotKey !== null) {
		$hit = rkrk_assess_personel_find_by_pilot_id($pid);
		if ($hit !== null) {
			$ag[$pilotKey] = $hit["name"];
		}
	}
	if ($pid === "" && $pname !== "") {
		foreach (rkrk_assess_personel_flat_for_select() as $e) {
			if (rkrk_assess_personel_names_equal($e["name"], $pname)) {
				$ag[$idLab] = rkrk_assess_personel_row_effective_rkrs_id($e["row"]);
				break;
			}
		}
	}
	$hit = null;
	if ($pid !== "") {
		$hit = rkrk_assess_personel_find_by_pilot_id($pid);
	} elseif ($pname !== "") {
		foreach (rkrk_assess_personel_flat_for_select() as $e) {
			if (rkrk_assess_personel_names_equal($e["name"], $pname)) {
				$hit = $e;
				break;
			}
		}
	}
	if ($hit !== null) {
		$row = isset($hit["row"]) && is_array($hit["row"]) ? $hit["row"] : array();
		if (array_key_exists("RemotePilotID", $ag) && trim((string)$ag["RemotePilotID"]) === "") {
			$ag["RemotePilotID"] = rkrk_assess_personel_row_remote_pilot_id($row);
		}
		if (array_key_exists("BetreiberID", $ag) && trim((string)$ag["BetreiberID"]) === "") {
			$ag["BetreiberID"] = rkrk_assess_personel_row_betreiber_id($row);
		}
	}
	$instrPairs = array(
		array("Instruktor", "InstruktorID"),
		array("Prüfer", "PrüferID"),
		array("Pruefer", "PrueferID"),
	);
	foreach ($instrPairs as $pair) {
		list($nk, $ik) = $pair;
		$hasN = array_key_exists($nk, $ag);
		$hasI = array_key_exists($ik, $ag);
		if (!$hasN && !$hasI) {
			continue;
		}
		$iname = $hasN ? trim((string)$ag[$nk]) : "";
		$iid = $hasI ? trim((string)$ag[$ik]) : "";
		if ($iid !== "" && $iname === "" && $hasN) {
			$hit = rkrk_assess_personel_find_by_pilot_id($iid);
			if ($hit !== null) {
				$ag[$nk] = $hit["name"];
			}
		}
		if ($iname !== "" && $iid === "" && $hasI) {
			foreach (rkrk_assess_personel_flat_for_select() as $e) {
				if (rkrk_assess_personel_names_equal($e["name"], $iname)) {
					$rk = rkrk_assess_personel_row_effective_rkrs_id(
						isset($e["row"]) && is_array($e["row"]) ? $e["row"] : array()
					);
					if ($rk !== "") {
						$ag[$ik] = $rk;
					}
					break;
				}
			}
		}
	}
}

/**
 * Ob ein Feldwert als «ausgefüllt» gilt (Trim, HTML-Nutztext ohne Tags/Leerzeichen).
 *
 * @param mixed $val
 */
function rkrk_assess_checklist_value_nonempty($val): bool {
	if ($val === null) {
		return false;
	}
	if (is_array($val)) {
		return false;
	}
	$s = trim((string)$val);
	if ($s === "") {
		return false;
	}
	$plain = trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, "UTF-8"));
	return $plain !== "";
}

/**
 * True, wenn in den Checklisten-Feldern keinerlei Inhalt erfasst wurde (kein sinnvolles Speichern).
 *
 * @param mixed $fields Sektion → (Label → Wert|Liste)
 */
function rkrk_assess_checklist_fields_are_empty($fields): bool {
	if (!is_array($fields)) {
		return true;
	}
	foreach ($fields as $items) {
		if (!is_array($items)) {
			if (rkrk_assess_checklist_value_nonempty($items)) {
				return false;
			}
			continue;
		}
		foreach ($items as $val) {
			if (is_array($val)) {
				foreach ($val as $vv) {
					if (rkrk_assess_checklist_value_nonempty($vv)) {
						return false;
					}
				}
			} elseif (rkrk_assess_checklist_value_nonempty($val)) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Früher: Link-Streifen «URL-Verwaltung» unter dem Hauptmenü. Bewusst deaktiviert.
 */
function rkrk_assess_emit_admin_url_managers_menu_strip(): void {
	if (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode()) {
		return;
	}
}

/**
 * Einmal pro Seite: HTML-Datalist für PersPick (gleiche Namens-Vorschläge wie früher im Select).
 *
 * @param list<array{pilot_id:string, name:string, row:array<string, mixed>}> $entries
 */
function rkrk_assess_emit_perspick_datalist_once(array $entries): void {
	static $done = false;
	if ($done || empty($entries)) {
		return;
	}
	$done = true;
	echo "<datalist id=\"rkrk-perspick-datalist\">\n";
	foreach ($entries as $e) {
		$nm = trim((string)($e["name"] ?? ""));
		if ($nm === "") {
			continue;
		}
		echo "<option value=\"" . htmlspecialchars($nm, ENT_QUOTES, "UTF-8") . "\"></option>\n";
	}
	echo "</datalist>\n";
}

/**
 * Einmal pro Seite: datalist für Instruktor/Prüfer (nur Personal mit Instruktor-Flag).
 *
 * @param list<array{pilot_id:string, name:string, row:array<string, mixed>}> $entries
 */
function rkrk_assess_emit_perspick_instr_pruefer_datalist_once(array $entries): void {
	static $doneInstr = false;
	if ($doneInstr || empty($entries)) {
		return;
	}
	$doneInstr = true;
	echo "<datalist id=\"rkrk-perspick-instr-datalist\">\n";
	foreach ($entries as $e) {
		$nm = trim((string)($e["name"] ?? ""));
		if ($nm === "") {
			continue;
		}
		echo "<option value=\"" . htmlspecialchars($nm, ENT_QUOTES, "UTF-8") . "\"></option>\n";
	}
	echo "</datalist>\n";
}

function rkrk_assess_emit_perspick_form_script_once(): void {
	static $memo = null;
	if ($memo !== null) {
		return;
	}
	$memo = true;
	echo "<script>
(function(){
function qa(sel){ return Array.prototype.slice.call(document.querySelectorAll(sel)); }
function norm(s){
  return String(s||\"\").trim().replace(/\\s+/g,\" \").toLowerCase();
}
window.rkrkPersPickApply = function(sel){
  if (!sel || sel.tagName !== \"SELECT\" || !sel.getAttribute(\"data-rk-perspick\")) return;
  var wrap = sel.closest(\".field\");
  if (!wrap) return;
  var nameInp = wrap.querySelector(\"input[data-rk-perspick-name]\");
  var pidInp = wrap.querySelector(\"input[data-rk-perspick-pid]\");
  var opt = sel.options[sel.selectedIndex];
  if (!nameInp || !pidInp) return;
  var v = String(sel.value||\"\").trim();
  if (!v){ nameInp.value=\"\"; pidInp.value=\"\"; return; }
  pidInp.value = v;
  nameInp.value = opt ? String(opt.textContent||\"\").trim() : \"\";
};
window.rkrkPersPickSyncText = function(inp){
  if (!inp || !inp.getAttribute(\"data-rk-perspick-text\")) return;
  var wrap = inp.closest(\"[data-rk-perspick-wrap]\");
  if (!wrap) return;
  var pidInp = wrap.querySelector(\"input[data-rk-perspick-pid]\");
  if (!pidInp) return;
  var raw = wrap.getAttribute(\"data-rk-perspick-map\") || \"[]\";
  var map = [];
  try { map = JSON.parse(raw); } catch(e1) { map = []; }
  var typed = norm(inp.value);
  if (!typed){ pidInp.value=\"\"; return; }
  var hit = \"\";
  for (var i = 0; i < map.length; i++){
    var row = map[i];
    if (!row || typeof row.n !== \"string\") continue;
    if (norm(row.n) === typed){ hit = String(row.id||\"\"); break; }
  }
  pidInp.value = hit;
};
window.rkrkPersPickInitAll = function(){
  qa(\"select[data-rk-perspick]\").forEach(function(s){ window.rkrkPersPickApply(s); });
  qa(\"input[data-rk-perspick-text]\").forEach(function(inp){
    window.rkrkPersPickSyncText(inp);
    [\"input\",\"change\",\"blur\"].forEach(function(ev){
      inp.addEventListener(ev, function(){ window.rkrkPersPickSyncText(inp); });
    });
  });
};
if (document.readyState === \"loading\"){
  document.addEventListener(\"DOMContentLoaded\", window.rkrkPersPickInitAll);
} else { window.rkrkPersPickInitAll(); }
})();
</script>";
}

/** Dateiname der Quiz-Ergebnisse unter `…/data/assess/results/` (JSON, kein CSV mehr). */
function rkrk_assess_quiz_results_json_basename(): string {
	return "QuizResultate.json";
}

/**
 * @param mixed $rows
 * @return list<array<string, string>>
 */
function rkrk_assess_quiz_results_normalize_row_list($rows): array {
	$out = array();
	if (!is_array($rows)) {
		return $out;
	}
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$line = array();
		foreach ($row as $k => $v) {
			$line[(string)$k] = is_scalar($v) ? (string)$v : "";
		}
		$out[] = $line;
	}
	return $out;
}

/**
 * Zeilen ohne «Zeit» entfernen (entspricht früherem CSV-Verhalten).
 *
 * @param list<array<string, string>> $rows
 * @return list<array<string, string>>
 */
function rkrk_assess_quiz_results_drop_empty_time_rows(array $rows): array {
	$out = array();
	foreach ($rows as $row) {
		$z = isset($row["Zeit"]) ? trim((string)$row["Zeit"]) : "";
		if ($z === "") {
			continue;
		}
		$out[] = $row;
	}
	return $out;
}

/**
 * Alle Zeilen auf dieselbe Schlüsselmenge bringen (stabiles JSON).
 *
 * @param list<array<string, string>> $rows
 * @return list<array<string, string>>
 */
function rkrk_assess_quiz_results_union_normalize(array $rows): array {
	$all = array();
	foreach ($rows as $r) {
		if (!is_array($r)) {
			continue;
		}
		foreach (array_keys($r) as $k) {
			$all[(string)$k] = true;
		}
	}
	$keys = array_keys($all);
	sort($keys, SORT_STRING);
	$out = array();
	foreach ($rows as $r) {
		if (!is_array($r)) {
			continue;
		}
		$line = array();
		foreach ($keys as $k) {
			$line[$k] = isset($r[$k]) ? (string)$r[$k] : "";
		}
		$out[] = $line;
	}
	return $out;
}

/**
 * Punkte wie im Abschlussbericht: eine Nachkommastelle, ganze Zahlen ohne .0.
 *
 * @param mixed $n
 */
function rkrk_assess_format_punkte($n): string {
	if ($n === null || $n === "" || !is_numeric((string) $n)) {
		return "";
	}
	$r = round((float) $n, 1);
	return (abs($r - (int) $r) < 0.00001) ? (string) (int) $r : number_format($r, 1, ".", "");
}

/**
 * PunkteModus aus AssessmentListe: standard | teilpunkt_gewichtet | teilpunkt_pro_option.
 */
function rkrk_assess_punkte_modus_normalize($raw): string {
	$m = strtolower(trim((string) $raw));
	if ($m === "teilpunkt_gewichtet" || $m === "teilpunkt_pro_option") {
		return $m;
	}
	return "standard";
}

function rkrk_assess_punkte_modus_label($raw): string {
	switch (rkrk_assess_punkte_modus_normalize($raw)) {
		case "teilpunkt_gewichtet":
			return "Teilpunkte gewichtet";
		case "teilpunkt_pro_option":
			return "Teilpunkte pro Option";
		default:
			return "Standard (1 Punkt/Frage)";
	}
}

/**
 * PunkteModus zum Quiz-Key (AssessmentListe.csv), sonst standard.
 */
function rkrk_assess_quiz_punkte_modus(string $quizKey): string {
	$quizKey = trim($quizKey);
	if ($quizKey === "") {
		return "standard";
	}
	static $map = null;
	static $mapPath = "";
	$path = "";
	if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
		$path = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/AssessmentListe.csv";
	}
	if (!is_array($map) || $mapPath !== $path) {
		$map = array();
		$mapPath = $path;
		$rows = array();
		if ($path !== "" && function_exists("csv_to_array_ascII") && is_readable($path)) {
			$loaded = csv_to_array_ascII($path, ";");
			if (is_array($loaded)) {
				$rows = $loaded;
			}
		}
		foreach ($rows as $r) {
			if (!is_array($r)) {
				continue;
			}
			$k = trim((string) ($r["Key"] ?? ""));
			if ($k === "") {
				continue;
			}
			$map[strtolower($k)] = rkrk_assess_punkte_modus_normalize($r["PunkteModus"] ?? "");
		}
	}
	$hit = $map[strtolower($quizKey)] ?? "";
	return $hit !== "" ? $hit : "standard";
}

/**
 * @return list<string>
 */
function rkrk_assess_parse_antwort_teile($raw): array {
	$s = trim((string) $raw);
	if ($s === "") {
		return array();
	}
	$parts = preg_split('/\s*\|\s*/', $s);
	$out = array();
	if (!is_array($parts)) {
		return $out;
	}
	foreach ($parts as $p) {
		$t = strtoupper(trim((string) $p));
		if ($t !== "") {
			$out[] = $t;
		}
	}
	return $out;
}

function rkrk_assess_answers_equal($loes, $ant): bool {
	$L = trim((string) $loes);
	$A = trim((string) $ant);
	if ($L === "Ja" || $L === "Nein") {
		return $A === $L;
	}
	$sa = rkrk_assess_parse_antwort_teile($L);
	$sb = rkrk_assess_parse_antwort_teile($A);
	sort($sa);
	sort($sb);
	return $sa === $sb;
}

/**
 * Punkte einer Frage analog JS rkrkDigiAssessQuestionScore (drei PunkteModus-Einstellungen).
 *
 * @param array<string,mixed> $qu
 * @return array{points:float,max:float,status:string}
 */
function rkrk_assess_question_score(array $qu, string $mode): array {
	$mode = rkrk_assess_punkte_modus_normalize($mode);
	$loes = (string) ($qu["loesung"] ?? ($qu["Loesung"] ?? ""));
	$ant = (string) ($qu["antwort"] ?? ($qu["Antwort"] ?? ""));
	$answered = trim($ant) !== "";
	$isMulti = strpos($loes, "|") !== false;
	if ($mode === "standard" || !$isMulti) {
		$p = ($answered && rkrk_assess_answers_equal($loes, $ant)) ? 1.0 : 0.0;
		return array("points" => $p, "max" => 1.0, "status" => ($p > 0.0) ? "full" : "zero");
	}
	$corr = rkrk_assess_parse_antwort_teile($loes);
	$pick = rkrk_assess_parse_antwort_teile($ant);
	$corrSet = array_fill_keys($corr, true);
	$corrCount = max(1, count($corr));
	$correctPicked = 0;
	$wrongPicked = 0;
	foreach ($pick as $tok) {
		if (isset($corrSet[$tok])) {
			$correctPicked++;
		} else {
			$wrongPicked++;
		}
	}
	if ($mode === "teilpunkt_gewichtet") {
		$points = ($correctPicked - $wrongPicked) / $corrCount;
		$max = 1.0;
	} else {
		$points = (float) ($correctPicked - $wrongPicked);
		$max = (float) max(1, $corrCount);
	}
	if (!$answered) {
		$points = 0.0;
	}
	$points = max(0.0, min($max, $points));
	$eps = 1e-9;
	$status = "zero";
	if ($points >= ($max - $eps) && $max > $eps) {
		$status = "full";
	} elseif ($points > $eps) {
		$status = "partial";
	}
	return array("points" => $points, "max" => $max, "status" => $status);
}

/**
 * Summe punkte/punkteMax aus Ergebniszeilen; fehlt der Wert, nach PunkteModus neu bewerten.
 *
 * @param list<mixed> $fragen
 * @return array{punkte_erreicht:float,punkte_max:float,richtig:int,anz:int}|null
 */
function rkrk_assess_score_result_frage_rows(array $fragen, string $mode = "standard"): ?array {
	$pe = 0.0;
	$pm = 0.0;
	$richtig = 0;
	$anz = 0;
	$has = false;
	$mode = rkrk_assess_punkte_modus_normalize($mode);
	foreach ($fragen as $row) {
		if (!is_array($row)) {
			continue;
		}
		$anz++;
		$hasStored = array_key_exists("punkte", $row)
			|| array_key_exists("punkteMax", $row)
			|| array_key_exists("punkte_max", $row);
		if ($hasStored) {
			$p = $row["punkte"] ?? 0;
			$m = $row["punkteMax"] ?? ($row["punkte_max"] ?? 1);
			$points = is_numeric((string) $p) ? (float) $p : 0.0;
			$max = is_numeric((string) $m) ? (float) $m : 0.0;
			$st = strtolower(trim((string) ($row["status"] ?? "")));
			if ($st === "full" || (!empty($row["richtig"]) && $st !== "partial" && $st !== "zero")) {
				$richtig++;
			}
		} else {
			$sc = rkrk_assess_question_score($row, $mode);
			$points = (float) $sc["points"];
			$max = (float) $sc["max"];
			if ($sc["status"] === "full") {
				$richtig++;
			}
		}
		$has = true;
		$pe += $points;
		$pm += $max;
	}
	if (!$has) {
		return null;
	}
	return array(
		"punkte_erreicht" => $pe,
		"punkte_max" => $pm,
		"richtig" => $richtig,
		"anz" => $anz,
	);
}

/**
 * Theorie-Score analog PDF: PunkteModus (Fragezeilen bzw. gespeicherte Punkte), sonst Fragen.
 * Bestanden nur über Punkte/Schwelle, nicht über Fragensumme.
 *
 * @param array<string,mixed> $row QuizResultate-Zeile, Exam-Result oder exam_list_completed-Eintrag
 * @return array{pct:float,passed:bool,pe:float,pm:float,anz:int,richtig:int,factor:float,use_points:bool,punkte_modus:string}
 */
function rkrk_assess_theory_result_metrics(array $row): array {
	$first = static function (array $src, array $keys) {
		foreach ($keys as $k) {
			if (!array_key_exists($k, $src)) {
				continue;
			}
			$v = $src[$k];
			if ($v === null || $v === "") {
				continue;
			}
			return $v;
		}
		return null;
	};

	$quizKey = trim((string) $first($row, array("Quiz", "quiz_key", "quiz", "delete_quiz")));
	$modusRaw = $first($row, array("PunkteModus", "punkte_modus", "punkteModus"));
	if ($modusRaw === null || trim((string) $modusRaw) === "") {
		$modusRaw = ($quizKey !== "") ? rkrk_assess_quiz_punkte_modus($quizKey) : "standard";
	}
	$punkteModus = rkrk_assess_punkte_modus_normalize($modusRaw);

	$fragen = $row["quiz_ergebnis_fragen"] ?? ($row["fragen"] ?? null);
	if (!is_array($fragen) && isset($row["result"]) && is_array($row["result"])) {
		$fragen = $row["result"]["quiz_ergebnis_fragen"] ?? null;
	}

	$peRaw = $first($row, array("PunkteErreicht", "punkte_erreicht", "pe"));
	$pmRaw = $first($row, array("PunkteMax", "punkte_max", "pm"));
	$anzRaw = $first($row, array("AnzFragen", "anz_fragen", "anz"));
	$richtigRaw = $first($row, array("Richtig", "fragen_richtig", "richtig"));
	$factorRaw = $first($row, array("Erfuellungsfaktor", "erfuellt", "factor"));
	$statusRaw = $first($row, array("Erfuellung", "bestanden"));

	$pe = is_numeric((string) $peRaw) ? (float) $peRaw : 0.0;
	$pm = is_numeric((string) $pmRaw) ? (float) $pmRaw : 0.0;
	$anz = is_numeric((string) $anzRaw) ? (int) $anzRaw : 0;
	$richtig = is_numeric((string) $richtigRaw) ? (int) $richtigRaw : 0;
	$factor = is_numeric((string) $factorRaw) ? (float) $factorRaw : 0.0;

	if (is_array($fragen) && $fragen !== array()) {
		$scored = rkrk_assess_score_result_frage_rows($fragen, $punkteModus);
		if (is_array($scored) && (float) $scored["punkte_max"] > 0) {
			$pe = (float) $scored["punkte_erreicht"];
			$pm = (float) $scored["punkte_max"];
			if ((int) $scored["anz"] > 0) {
				$anz = (int) $scored["anz"];
			}
			$richtig = (int) $scored["richtig"];
		}
	}

	$usePoints = ($pm > 0.0);
	if ($usePoints) {
		$pct = round(($pe / $pm) * 100, 1);
	} elseif ($anz > 0) {
		$pct = round(($richtig / $anz) * 100, 1);
	} else {
		$pct = 0.0;
	}

	if (is_bool($statusRaw)) {
		$status = $statusRaw ? "ja" : "nein";
	} else {
		$status = mb_strtolower(trim((string) $statusRaw), "UTF-8");
	}

	// Wie PDF: bei Punkten gilt nur pe/pm gegen die Schwelle (nicht Fragensumme).
	if ($usePoints && $factor > 0) {
		$passed = ($pe / $pm) >= $factor;
	} elseif ($usePoints) {
		$passed = ($status === "ja" || $status === "yes");
	} elseif ($anz > 0 && $factor > 0) {
		$passed = ($richtig / $anz) >= $factor;
	} else {
		$passed = ($status === "ja" || $status === "yes");
	}

	return array(
		"pct" => $pct,
		"passed" => $passed,
		"pe" => $pe,
		"pm" => $pm,
		"anz" => $anz,
		"richtig" => $richtig,
		"factor" => $factor,
		"use_points" => $usePoints,
		"punkte_modus" => $punkteModus,
	);
}

/**
 * Quiz-Ergebnisse laden: bevorzugt QuizResultate.json; sonst einmalige Migration aus QuizLog.csv / QuizResultate.csv.
 *
 * @return list<array<string, string>>
 */
function rkrk_assess_quiz_results_load_rows(string $resultsDir): array {
	$dir = rtrim(str_replace("\\", DIRECTORY_SEPARATOR, (string)$resultsDir), DIRECTORY_SEPARATOR);
	$dirs = array();
	if ($dir !== "") {
		$dirs[] = $dir;
	}
	if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
		$quizRoot = rtrim(str_replace("\\", DIRECTORY_SEPARATOR, (string) $GLOBALS["QuizLoc"]), DIRECTORY_SEPARATOR);
		foreach (array($quizRoot, $quizRoot . DIRECTORY_SEPARATOR . "results") as $extra) {
			if ($extra !== $dir && is_dir($extra) && !in_array($extra, $dirs, true)) {
				$dirs[] = $extra;
			}
		}
	}
	foreach ($dirs as $tryDir) {
		if ($tryDir === "" || !is_dir($tryDir)) {
			continue;
		}
		$jsonPath = $tryDir . DIRECTORY_SEPARATOR . rkrk_assess_quiz_results_json_basename();
		if (is_file($jsonPath) && is_readable($jsonPath)) {
			$raw = @file_get_contents($jsonPath);
			if ($raw !== false && trim($raw) !== "") {
				$decoded = @json_decode($raw, true);
				if (is_array($decoded)) {
					if (isset($decoded["rows"]) && is_array($decoded["rows"])) {
						return rkrk_assess_quiz_results_drop_empty_time_rows(rkrk_assess_quiz_results_normalize_row_list($decoded["rows"]));
					}
					if (isset($decoded[0]) && is_array($decoded[0])) {
						return rkrk_assess_quiz_results_drop_empty_time_rows(rkrk_assess_quiz_results_normalize_row_list($decoded));
					}
				}
			}
			return array();
		}
	}
	$tryDir = ($dir !== "" && is_dir($dir)) ? $dir : (isset($dirs[0]) ? $dirs[0] : "");
	if ($tryDir === "" || !is_dir($tryDir)) {
		return array();
	}
	$legacyCsv = array(
		$tryDir . DIRECTORY_SEPARATOR . "QuizLog.csv",
		$tryDir . DIRECTORY_SEPARATOR . "QuizResultate.csv",
	);
	foreach ($legacyCsv as $csvPath) {
		if (!is_file($csvPath) || !is_readable($csvPath) || !function_exists("csv_to_array_ascII")) {
			continue;
		}
		$data = csv_to_array_ascII($csvPath, ";");
		if (!is_array($data) || count($data) === 0) {
			continue;
		}
		$rows = rkrk_assess_quiz_results_drop_empty_time_rows(rkrk_assess_quiz_results_normalize_row_list($data));
		if (count($rows) > 0) {
			rkrk_assess_quiz_results_save_rows($tryDir, $rows);
		}
		return $rows;
	}
	return array();
}

/**
 * @param list<array<string, string>> $rows
 */
function rkrk_assess_quiz_results_save_rows(string $resultsDir, array $rows): bool {
	$dir = rtrim(str_replace("\\", DIRECTORY_SEPARATOR, (string)$resultsDir), DIRECTORY_SEPARATOR);
	if ($dir === "") {
		return false;
	}
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$jsonPath = $dir . DIRECTORY_SEPARATOR . rkrk_assess_quiz_results_json_basename();
	$rows = rkrk_assess_quiz_results_union_normalize(rkrk_assess_quiz_results_drop_empty_time_rows($rows));
	$payload = array(
		"format" => "rkropen_quiz_results_v1",
		"updated_at" => date("c"),
		"rows" => array_values($rows),
	);
	$tmp = $jsonPath . ".tmp";
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json === false) {
		return false;
	}
	if (@file_put_contents($tmp, $json) === false) {
		return false;
	}
	if (!@rename($tmp, $jsonPath)) {
		@unlink($jsonPath);
		if (!@rename($tmp, $jsonPath)) {
			@unlink($tmp);
			return false;
		}
	}
	return true;
}

/* ---------- Checklisten-/Live-Resultat-JSON Dateinamen (Template_Pilot_PilotID_Zeitstempel) ---------- */

/** Datei-Stamm aus Titel (wie früher slugifyTitle in rkrk_assess*.php). */
function rkrk_assess_slugify_file_stem(string $title): string {
	$title = trim($title);
	$title = str_replace(
		array("ä", "ö", "ü", "Ä", "Ö", "Ü", "ß"),
		array("ae", "oe", "ue", "Ae", "Oe", "Ue", "ss"),
		$title
	);
	$title = preg_replace('/[^a-zA-Z0-9]+/', "_", $title);
	$title = preg_replace('/_+/', "_", $title);
	$title = strtolower(trim((string)$title, "_"));
	return $title !== "" ? $title : "template";
}

/**
 * @param array<string, mixed> $fields
 * @return array{name:string,id:string}
 */
function rkrk_assess_fields_pilot_name_and_id(array $fields): array {
	$name = "";
	$id = "";
	$ag = (isset($fields["Allgemein"]) && is_array($fields["Allgemein"])) ? $fields["Allgemein"] : array();
	foreach (array("Pilot", "Piloten", "Teambezeichnung") as $k) {
		if (!isset($ag[$k])) {
			continue;
		}
		$v = trim((string)$ag[$k]);
		if ($v !== "") {
			$name = $v;
			break;
		}
	}
	$idKey = function_exists("rkrk_assess_pers_pick_id_label") ? rkrk_assess_pers_pick_id_label() : "RKRSPilotID";
	if (isset($ag[$idKey])) {
		$id = trim((string)$ag[$idKey]);
	}
	foreach (rkrk_assess_pers_pick_legacy_id_labels() as $legacy) {
		if ($id !== "") {
			break;
		}
		if (isset($ag[$legacy])) {
			$id = trim((string)$ag[$legacy]);
		}
	}
	$id = preg_replace('/[^0-9A-Za-z_-]/', "", $id);
	if (strlen($id) > 48) {
		$id = substr($id, 0, 48);
	}
	return array("name" => $name, "id" => $id);
}

/** Zeitstempel für Dateinamen aus created_at (Ymd_Hi), sonst jetzt. */
function rkrk_assess_result_filename_stamp_from_created(string $createdAt): string {
	$createdAt = trim($createdAt);
	if ($createdAt === "") {
		return date("Ymd_Hi");
	}
	$t = strtotime(str_replace("T", " ", $createdAt));
	if ($t === false) {
		return date("Ymd_Hi");
	}
	return date("Ymd_Hi", $t);
}

/**
 * RKRSPilotID aus der Pilotenliste zum Anzeigenamen.
 */
function rkrk_assess_personel_rkrs_id_by_name(string $name): string {
	$name = trim($name);
	if ($name === "") {
		return "";
	}
	foreach (rkrk_assess_personel_flat_for_select() as $e) {
		$nm = trim((string) ($e["name"] ?? ""));
		if ($nm === "") {
			continue;
		}
		if (function_exists("rkrk_assess_personel_names_equal")) {
			if (!rkrk_assess_personel_names_equal($nm, $name)) {
				continue;
			}
		} elseif (strcasecmp($nm, $name) !== 0) {
			continue;
		}
		$rkrs = trim((string) ($e["rkrs_pilot_id"] ?? ""));
		if ($rkrs === "" && isset($e["row"]) && is_array($e["row"])) {
			$rkrs = rkrk_assess_personel_row_effective_rkrs_id($e["row"]);
		}
		if ($rkrs !== "") {
			return $rkrs;
		}
	}
	return "";
}

/**
 * Schulungstitel aus AssessmentListe.csv (Spalte Titel zum Key), sonst der Key.
 */
function rkrk_assess_quiz_title_from_key(string $quizKey): string {
	$quizKey = trim($quizKey);
	if ($quizKey === "") {
		return "";
	}
	static $map = null;
	static $mapPath = "";
	$path = "";
	if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
		$path = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/AssessmentListe.csv";
	}
	if (!is_array($map) || $map === array() || $mapPath !== $path) {
		$map = array();
		$mapPath = $path;
		if ($path !== "" && is_readable($path)) {
			$fh = @fopen($path, "r");
			if ($fh !== false) {
				$headers = fgetcsv($fh, 0, ";");
				if (is_array($headers)) {
					$keyIdx = null;
					$titelIdx = null;
					foreach ($headers as $i => $h) {
						$hn = strtolower(trim((string) $h));
						if ($hn === "key") {
							$keyIdx = (int) $i;
						} elseif ($hn === "titel" || $hn === "title") {
							$titelIdx = (int) $i;
						}
					}
					if ($keyIdx !== null) {
						rewind($fh);
						while (($line = fgets($fh)) !== false) {
							$line = (string) $line;
							$parts = explode(";", $line, 4);
							if (count($parts) < 3) {
								continue;
							}
							$k = trim((string) $parts[1], " \t\"'\r\n");
							$t = trim((string) $parts[2], " \t\"'\r\n");
							if ($k === "" || strtolower($k) === "key") {
								continue;
							}
							$map[strtolower($k)] = ($t !== "") ? $t : $k;
						}
					}
				}
				fclose($fh);
			}
		}
	}
	$hit = $map[strtolower($quizKey)] ?? "";
	return $hit !== "" ? $hit : $quizKey;
}

/**
 * Checklisten-Titel-Slug: Template-JSON title, sonst Dateistamm.
 */
function rkrk_assess_checklist_title_slug(string $templateFile, string $templatesDir = ""): string {
	$file = basename(trim($templateFile));
	$fallback = pathinfo($file, PATHINFO_FILENAME);
	if ($templatesDir === "") {
		if (isset($GLOBALS["rkrkAssessTemplates"]) && trim((string) $GLOBALS["rkrkAssessTemplates"]) !== "") {
			$templatesDir = (string) $GLOBALS["rkrkAssessTemplates"];
		} elseif (isset($GLOBALS["rkrkLiveassessTemplates"]) && trim((string) $GLOBALS["rkrkLiveassessTemplates"]) !== "") {
			$templatesDir = (string) $GLOBALS["rkrkLiveassessTemplates"];
		}
	}
	$title = "";
	if ($file !== "" && $templatesDir !== "") {
		$path = rtrim(str_replace("\\", "/", $templatesDir), "/") . "/" . $file;
		if (is_readable($path)) {
			$raw = @file_get_contents($path);
			if (is_string($raw) && $raw !== "") {
				$data = json_decode($raw, true);
				if (is_array($data)) {
					$title = trim((string) ($data["title"] ?? ""));
				}
			}
		}
	}
	$src = $title !== "" ? $title : $fallback;
	if (function_exists("rkrk_assess_result_title_slug")) {
		return rkrk_assess_result_title_slug($src);
	}
	if (function_exists("rkrk_assess_slugify_file_stem")) {
		return rkrk_assess_slugify_file_stem($src);
	}
	return strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', "_", $src));
}

/**
 * Abschluss-Stamm: {titel}_{nachname}_{vorname}_{rkrsId}_{YYYYMMDD}
 *
 * @param array<string, mixed> $fields
 */
function rkrk_assess_build_checklist_result_json_stem(string $templateSlug, array $fields, string $stamp = ""): string {
	if (!function_exists("rkrk_assess_build_result_stem")) {
		$__stemInc = __DIR__ . "/rkrk_assess_result_stem.inc.php";
		if (is_readable($__stemInc)) {
			include_once $__stemInc;
		}
		unset($__stemInc);
	}
	$pi = rkrk_assess_fields_pilot_name_and_id($fields);
	$name = (string) ($pi["name"] ?? "");
	$id = (string) ($pi["id"] ?? "");
	$stampUse = trim($stamp) !== "" ? $stamp : date("Ymd");
	if (!function_exists("rkrk_assess_build_result_stem")) {
		$slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($templateSlug));
		$slug = strtolower(trim((string) $slug, '_'));
		if ($slug === '') {
			$slug = 'result';
		}
		$pilot = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($name));
		$pilot = strtolower(trim((string) $pilot, '_'));
		$idTok = preg_replace('/[^0-9A-Za-z_-]/', '', $id);
		if ($idTok === "") {
			$idTok = "ohneid";
		}
		$ymd = preg_match('/(\d{8})/', $stampUse, $m) ? $m[1] : date('Ymd');
		$parts = array($slug);
		if ($pilot !== "") {
			$parts[] = $pilot;
			$parts[] = $idTok;
		}
		$parts[] = $ymd;
		return implode("_", $parts);
	}
	return rkrk_assess_build_result_stem($templateSlug, $name, $stampUse, true, $id);
}

/**
 * Eindeutiger Stamm im Zielordner (.json). $exceptFullPath = aktuelle Datei beim Umbenennen.
 */
function rkrk_assess_unique_checklist_result_stem(string $resultsDir, string $stem, ?string $exceptFullPath = null): string {
	$dir = rtrim(str_replace("\\", DIRECTORY_SEPARATOR, (string)$resultsDir), DIRECTORY_SEPARATOR);
	$rpExcept = "";
	if ($exceptFullPath !== null && is_file($exceptFullPath)) {
		$rp = realpath($exceptFullPath);
		$rpExcept = ($rp !== false) ? (string)$rp : "";
	}
	$candidate = $stem;
	$n = 2;
	while (true) {
		$p = $dir . DIRECTORY_SEPARATOR . $candidate . ".json";
		if (!is_file($p)) {
			return $candidate;
		}
		$rpCur = realpath($p);
		if ($rpExcept !== "" && $rpCur !== false && $rpCur === $rpExcept) {
			return $candidate;
		}
		$candidate = $stem . "_" . $n;
		$n++;
	}
}

/**
 * Moodle-Portal RKRS (portal.rehkitzrettung.ch): Assess-Daten unter /rkrsplusassessment/data/assess/.
 */
function rkrk_assess_is_portal_rkrs_host(): bool {
	$host = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';
	return strpos($host, 'portal.rehkitzrettung.ch') !== false;
}

/**
 * Pfad enthält …/rkrs/… (z. B. /rkropen/rkrs/assess/public/).
 */
function rkrk_assess_is_rkrs_copyright_path(): bool {
	$candidates = array(
		(string) ($_SERVER['PHP_SELF'] ?? ''),
		str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? '')),
		str_replace('\\', '/', (string) __DIR__),
	);
	foreach ($candidates as $p) {
		if ($p !== '' && strpos($p, '/rkrs/') !== false) {
			return true;
		}
	}
	return false;
}

/**
 * Default-Copyright ohne GET-Overrides:
 * portal.rehkitzrettung.ch oder …/rkrs/… → rehkitzrettung.ch, sonst drohnenrehkitzrettung.ch.
 */
function rkrk_assess_default_copyright_holder(): string {
	if (rkrk_assess_is_portal_rkrs_host() || rkrk_assess_is_rkrs_copyright_path()) {
		return 'rehkitzrettung.ch';
	}
	return 'drohnenrehkitzrettung.ch';
}

/** Aktuelle Copyright-Marke ($copyright aus Bootstrap, sonst Default-Regel). */
function rkrk_assess_copyright_holder(): string {
	if (isset($GLOBALS['copyright']) && trim((string) $GLOBALS['copyright']) !== '') {
		return trim((string) $GLOBALS['copyright']);
	}
	return rkrk_assess_default_copyright_holder();
}

/**
 * Bekannte Präfixe in gespeicherten Bildpfaden (CSV/HTML), die vor Anhängen an imageBase entfernt werden.
 *
 * @return list<string>
 */
function rkrk_assess_quiz_image_url_markers(): array {
	return array(
		'/rkrk/data/assess/quiz/images/',
		'/rkrs/data/assess/quiz/images/',
		'/rkrsplusassessment/data/assess/quiz/images/',
		'/rkrk/data/assess/images/',
		'/rkrs/data/assess/images/',
		'/rkrsplusassessment/data/assess/images/',
	);
}

/**
 * Öffentlicher URL-Pfad zu …/data/assess/ (mit Slash am Ende).
 * Primär über SCRIPT_NAME bis zum Segment "rkropen" (historisches Verhalten),
 * danach über bekannte Globals ($WebLocation / $RKRKRel*).
 */
function rkrk_assess_quiz_data_assess_web_prefix(): string {
	if (rkrk_assess_is_portal_rkrs_host()) {
		return '/rkrsplusassessment/data/assess/';
	}
	if (isset($GLOBALS["onRKRS"]) && (bool)$GLOBALS["onRKRS"] === true) {
		$_sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
		$_dn = trim(dirname($_sn), '/');
		$_seg = ($_dn === '') ? array() : explode('/', $_dn);
		$_ri = false;
		foreach ($_seg as $_i => $_p) {
			if (strtolower((string)$_p) === 'rkropen') {
				$_ri = (int)$_i;
				break;
			}
		}
		if ($_ri !== false) {
			return '/' . implode('/', array_slice($_seg, 0, $_ri + 1)) . '/rkrs/data/assess/';
		}
		return '/rkropen/rkrs/data/assess/';
	}

	$_rkrkSn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$_rkrkDn = trim(dirname($_rkrkSn), '/');
	$_rkrkSeg = ($_rkrkDn === '') ? array() : explode('/', $_rkrkDn);
	$_rkrkRi = false;
	foreach ($_rkrkSeg as $_i => $_p) {
		if (strtolower((string)$_p) === 'rkropen') {
			$_rkrkRi = (int)$_i;
			break;
		}
	}
	if ($_rkrkRi !== false) {
		return '/' . implode('/', array_slice($_rkrkSeg, 0, $_rkrkRi + 1)) . '/rkrk/data/assess/';
	}
	if (isset($GLOBALS["onRKRS"]) && (bool)$GLOBALS["onRKRS"] === true) {
		return '/rkropen/rkrs/data/assess/';
	}
	if (isset($GLOBALS["WebLocation"]) && is_string($GLOBALS["WebLocation"]) && trim((string)$GLOBALS["WebLocation"]) !== "") {
		$w = trim((string)$GLOBALS["WebLocation"]);
		$w = preg_replace('#^https?://[^/]+#i', '', $w);
		$w = '/' . trim((string)$w, '/');
		if ($w === '/') {
			return '/rkropen/rkrk/data/assess/';
		}
		return rtrim($w, '/') . '/rkrk/data/assess/';
	}
	if (isset($GLOBALS["RKRKRelWebLocation"]) && is_string($GLOBALS["RKRKRelWebLocation"])) {
		$rel = trim((string)$GLOBALS["RKRKRelWebLocation"]);
		if ($rel !== '') {
			$rel = trim(str_replace('\\', '/', $rel), '/');
			if ($rel !== '') {
				return '/' . $rel . '/rkrk/data/assess/';
			}
		}
	}
	if (isset($GLOBALS["RKRKRelRKRKDataLocation"]) && is_string($GLOBALS["RKRKRelRKRKDataLocation"])) {
		$rel = trim((string)$GLOBALS["RKRKRelRKRKDataLocation"]);
		$rel = str_replace('\\', '/', $rel);
		$rel = preg_replace('#(^|/)\.\.(?=/|$)#', '', $rel);
		$rel = '/' . trim((string)$rel, '/');
		if ($rel !== '/') {
			return rtrim($rel, '/') . '/data/assess/';
		}
	}
	if (isset($GLOBALS["onRKRS"]) && (bool)$GLOBALS["onRKRS"] === true) {
		return '/rkropen/rkrs/data/assess/';
	}
		return "/rkropen/rkrk/data/assess/";
}

/**
 * Kodiert einen relativen URL-Pfad segmentweise (img/src, fetch): Umlaute und Sonderzeichen
 * zuverlässig als UTF-8-Percent-Encoding; optional NFC-Normalisierung (Dateinamen von macOS/APFS).
 * Bereits kodierte Segmente werden zuerst per rawurldecode entfaltet, um Doppel-Encoding zu vermeiden.
 */
function rkrk_assess_encode_url_path_segments(string $relPath): string {
	$relPath = str_replace('\\', '/', trim($relPath));
	$relPath = ltrim($relPath, '/');
	if ($relPath === '') {
		return '';
	}
	$parts = explode('/', $relPath);
	$out = array();
	foreach ($parts as $seg) {
		if ($seg === '') {
			continue;
		}
		$s = $seg;
		for ($i = 0; $i < 6; $i++) {
			$decoded = rawurldecode($s);
			if ($decoded === $s) {
				break;
			}
			$s = $decoded;
		}
		if ($s === '') {
			continue;
		}
		if (class_exists('Normalizer')) {
			$n = @Normalizer::normalize($s, Normalizer::FORM_C);
			if (is_string($n) && $n !== '') {
				$s = $n;
			}
		}
		$out[] = rawurlencode($s);
	}
	return implode('/', $out);
}

/**
 * HTML-Entities in Attribut-URLs vollständig auflösen (&amp;amp;d= → &d=).
 */
function rkrk_assess_decode_html_attr_url(string $url): string {
	$u = $url;
	for ($i = 0; $i < 12; $i++) {
		$d = html_entity_decode($u, ENT_QUOTES | ENT_HTML5, "UTF-8");
		if ($d === $u) {
			break;
		}
		$u = $d;
	}
	return $u;
}

/**
 * Percent-Encoding für komplette URLs/Pfade (Umlaute in Ordner- und Dateinamen).
 */
function rkrk_assess_encode_url_for_request(string $url): string {
	$url = str_replace('\\', '/', trim($url));
	if ($url === '') {
		return '';
	}
	if (preg_match('#^(https?://[^/]+)(/.*)?$#i', $url, $m) === 1) {
		$host = $m[1];
		$rest = isset($m[2]) ? (string) $m[2] : '';
		$frag = '';
		$query = '';
		if (preg_match('#^([^?#]*)(\?[^#]*)?(#.*)?$#', $rest, $rm) === 1) {
			$path = (string) ($rm[1] ?? '');
			$query = (string) ($rm[2] ?? '');
			$frag = (string) ($rm[3] ?? '');
		} else {
			$path = $rest;
		}
		$path = ltrim($path, '/');
		$encPath = $path !== '' ? rkrk_assess_encode_url_path_segments($path) : '';
		return $host . ($encPath !== '' ? '/' . $encPath : '') . $query . $frag;
	}
	if (preg_match('#^(mailto:|tel:|data:)#i', $url) === 1) {
		return $url;
	}
	if ($url[0] === '#') {
		return $url;
	}
	$frag = '';
	$query = '';
	if (($hp = strpos($url, '#')) !== false) {
		$frag = substr($url, $hp);
		$url = substr($url, 0, $hp);
	}
	if (($qp = strpos($url, '?')) !== false) {
		$query = substr($url, $qp);
		$url = substr($url, 0, $qp);
	}
	$leading = '';
	if ($url !== '' && $url[0] === '/') {
		$leading = '/';
		$url = ltrim($url, '/');
	}
	return $leading . rkrk_assess_encode_url_path_segments($url) . $query . $frag;
}

/**
 * src/href in HTML-Fragmenten für Umlaute in Pfaden kodieren.
 */
function rkrk_assess_encode_urls_in_html_fragment(string $html): string {
	if ($html === '') {
		return $html;
	}
	$enc = static function (string $u): string {
		return rkrk_assess_encode_url_for_request(rkrk_assess_decode_html_attr_url($u));
	};
	$html = preg_replace_callback(
		'/\bsrc\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($enc): string {
			return 'src=' . $m[1] . htmlspecialchars($enc($m[2]), ENT_QUOTES, 'UTF-8') . $m[1];
		},
		$html
	);
	$html = preg_replace_callback(
		'/\bhref\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($enc): string {
			$t = $m[2];
			if (trim((string) $t) !== '' && $t[0] === '#') {
				return $m[0];
			}
			return 'href=' . $m[1] . htmlspecialchars($enc($t), ENT_QUOTES, 'UTF-8') . $m[1];
		},
		$html
	);
	return $html;
}

/**
 * Bild-URL unter …/images/ auf kanonischen DriveLocation-Ordner umschreiben
 * (z. B. Einsatzprüfung → Einsatzpruefung laut AssessmentListe.csv).
 */
function rkrk_assess_rewrite_image_url_to_drive_location(string $url, string $driveLocation): string {
	$driveLocation = rkrk_assess_clean_drive_location_rel($driveLocation);
	if ($driveLocation === "") {
		return $url;
	}
	$url = rkrk_assess_decode_html_attr_url($url);
	$raw = trim(str_replace("\\", "/", $url));
	if ($raw === "") {
		return $url;
	}
	if ($raw[0] === "#" || preg_match("#^(mailto:|tel:|data:)#i", $raw) === 1) {
		return $url;
	}
	$u = $raw;
	for ($i = 0; $i < 6; $i++) {
		$decoded = rawurldecode($u);
		if ($decoded === $u) {
			break;
		}
		$u = $decoded;
	}
	$host = "";
	$path = $u;
	if (preg_match("#^(https?://[^/]+)(/.*)$#i", $u, $hm) === 1) {
		$host = (string) $hm[1];
		$path = (string) $hm[2];
	} elseif ($u[0] !== "/") {
		$path = "/" . $u;
	}
	if (preg_match("#^(.*?)/(?:quiz/)?images/(.+)$#i", $path, $pm) !== 1) {
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($raw)
			: $raw;
	}
	$pathLead = (string) $pm[1];
	$after = (string) $pm[2];
	$slash = strrpos($after, "/");
	if ($slash === false) {
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($raw)
			: $raw;
	}
	$folderPart = substr($after, 0, $slash);
	$filePart = substr($after, $slash + 1);
	if ($filePart === "") {
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($raw)
			: $raw;
	}
	$driveNorm = rtrim($driveLocation, "/");
	$folderMatches = rkrk_assess_folder_name_equivalent($folderPart, $driveNorm);
	if (!$folderMatches) {
		$folderFirst = explode("/", $folderPart, 2)[0];
		$driveFirst = explode("/", $driveNorm, 2)[0];
		$folderMatches = rkrk_assess_folder_name_equivalent($folderFirst, $driveFirst);
	}
	if (!$folderMatches) {
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($raw)
			: $raw;
	}
	$prefix = $pathLead . "/images/";
	$newPath = $prefix . $driveNorm . "/" . $filePart;
	if ($host !== "") {
		$newPath = $host . $newPath;
	}
	return function_exists("rkrk_assess_encode_url_for_request")
		? rkrk_assess_encode_url_for_request($newPath)
		: $newPath;
}

/**
 * src/href/style-URLs in HTML-Fragmenten auf DriveLocation-Ordner normalisieren.
 */
function rkrk_assess_rewrite_image_urls_drive_location_in_fragment(string $html, string $driveLocation): string {
	if ($html === "" || rkrk_assess_clean_drive_location_rel($driveLocation) === "") {
		return $html;
	}
	$rewrite = static function (string $u) use ($driveLocation): string {
		return rkrk_assess_rewrite_image_url_to_drive_location($u, $driveLocation);
	};
	$attrUrl = static function (string $attr, array $m) use ($rewrite): string {
		return $attr . "=" . $m[1] . htmlspecialchars($rewrite($m[2]), ENT_QUOTES, "UTF-8") . $m[1];
	};
	$html = preg_replace_callback(
		'/\bsrc\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($attrUrl): string {
			return $attrUrl("src", $m);
		},
		$html
	);
	$html = preg_replace_callback(
		'/\bdata-src\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($attrUrl): string {
			return $attrUrl("data-src", $m);
		},
		$html
	);
	$html = preg_replace_callback(
		'/\bposter\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($attrUrl): string {
			return $attrUrl("poster", $m);
		},
		$html
	);
	$html = preg_replace_callback(
		'/\bsrcset\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($rewrite): string {
			$parts = preg_split("/\s*,\s*/", (string) $m[2]);
			$resolved = array();
			foreach ($parts as $part) {
				$part = trim((string) $part);
				if ($part === "") {
					continue;
				}
				if (preg_match("/^(\S+)(\s+.+)?$/", $part, $pm) === 1) {
					$resolved[] = htmlspecialchars($rewrite($pm[1]), ENT_QUOTES, "UTF-8") . (isset($pm[2]) ? $pm[2] : "");
				}
			}
			return "srcset=" . $m[1] . implode(", ", $resolved) . $m[1];
		},
		$html
	);
	$html = preg_replace_callback(
		'/\bstyle\s*=\s*(["\'])([^"\']*)\1/i',
		static function (array $m) use ($rewrite): string {
			$style = preg_replace_callback(
				'/url\(\s*(["\']?)([^"\')\s]+)\1\s*\)/i',
				static function (array $um) use ($rewrite): string {
					$q = $um[1];
					return "url(" . $q . $rewrite($um[2]) . $q . ")";
				},
				$m[2]
			);
			return "style=" . $m[1] . htmlspecialchars((string) $style, ENT_QUOTES, "UTF-8") . $m[1];
		},
		$html
	);
	return $html;
}

/**
 * Relativen Bildpfad aus Template-HTML normalisieren.
 * Diagramme: ../image/…, ./images/… → data/assess/image/ oder templates/images/
 * Quiz-Bilder: …/images/Einsatzpruefung/…
 */
function rkrk_assess_normalize_template_image_rel(string $rawPath): string {
	$p = str_replace("\\", "/", trim($rawPath));
	if ($p === "") {
		return "";
	}
	for ($i = 0; $i < 4; $i++) {
		$decoded = rawurldecode($p);
		if ($decoded === $p) {
			break;
		}
		$p = $decoded;
	}
	if (preg_match("#^https?://#i", $p) === 1 || strncmp($p, "data:", 5) === 0) {
		return $p;
	}
	if (preg_match("#/(?:rkrs|rkrk|rkrsplusassessment)/data/assess/image/(.+)$#i", $p, $dm) === 1) {
		return "@diagram@/" . ltrim((string) $dm[1], "/");
	}
	if (preg_match("#/(?:rkrs|rkrk|rkrsplusassessment)/data/assess/templates/images/(.+)$#i", $p, $dm) === 1) {
		return "@diagram@/" . ltrim((string) $dm[1], "/");
	}
	if (preg_match("#(?:^|(?:\\./|\\../)+)images/(.+)$#i", $p, $dm) === 1) {
		return "@diagram@/" . ltrim((string) $dm[1], "/");
	}
	if (preg_match("#(?:^|(?:\\./|\\../)+)image/(.+)$#i", $p, $dm) === 1) {
		return "@diagram@/" . ltrim((string) $dm[1], "/");
	}
	$markers = function_exists("rkrk_assess_quiz_image_url_markers")
		? rkrk_assess_quiz_image_url_markers()
		: array(
			"/rkrk/data/assess/quiz/images/",
			"/rkrs/data/assess/quiz/images/",
			"/rkrsplusassessment/data/assess/quiz/images/",
			"/rkrk/data/assess/images/",
			"/rkrs/data/assess/images/",
			"/rkrsplusassessment/data/assess/images/",
		);
	foreach ($markers as $marker) {
		$pos = stripos($p, $marker);
		if ($pos !== false) {
			$p = substr($p, $pos + strlen($marker));
			break;
		}
	}
	$p = preg_replace("#^(?:\\./|\\../)+#", "", $p);
	$p = preg_replace("#^(?:quiz/)?images/#i", "", $p);
	return ltrim($p, "/");
}

/** Dateisystem-Root für Template-Diagramme (data/assess/image/). */
function rkrk_assess_template_diagram_fs_dir(): string {
	$dirs = rkrk_assess_template_diagram_fs_dirs();
	return $dirs !== [] ? $dirs[0] : "";
}

/**
 * Suchpfade für Template-Diagramme (Priorität: image/, dann templates/images/).
 *
 * @return list<string>
 */
function rkrk_assess_template_diagram_fs_dirs(): array {
	$dirs = array();
	$add = static function (string $dir) use (&$dirs): void {
		$dir = rtrim(str_replace("\\", "/", trim($dir)), "/") . "/";
		if ($dir === "/" || $dir === "./") {
			return;
		}
		$dirs[] = $dir;
	};
	if (isset($GLOBALS["QuizLoc"]) && trim((string) $GLOBALS["QuizLoc"]) !== "") {
		$ql = rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLoc"]), "/") . "/";
		$add($ql . "image/");
		$add($ql . "templates/images/");
	}
	if (isset($GLOBALS["QuizLocTemplates"]) && trim((string) $GLOBALS["QuizLocTemplates"]) !== "") {
		$add(rtrim(str_replace("\\", "/", (string) $GLOBALS["QuizLocTemplates"]), "/") . "/images/");
	}
	$pub = rtrim(str_replace("\\", "/", dirname(__DIR__)), "/");
	$add($pub . "/data/assess/image/");
	$add($pub . "/data/assess/templates/images/");
	$add($pub . "/../data/assess/image/");
	$add($pub . "/../data/assess/templates/images/");
	$add($pub . "/../rkrk/data/assess/image/");
	$add($pub . "/../rkrk/data/assess/templates/images/");
	$add($pub . "/../rkrs/data/assess/image/");
	$add($pub . "/../rkrs/data/assess/templates/images/");
	$seen = array();
	$out = array();
	foreach ($dirs as $dir) {
		$key = strtolower($dir);
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$out[] = $dir;
	}
	return $out;
}

/** Absoluter Pfad zu einem Diagramm (nur Dateiname oder flacher relativer Pfad). */
function rkrk_assess_template_diagram_fs_path(string $file): string {
	$file = ltrim(str_replace("\\", "/", trim($file)), "/");
	if ($file === "" || strpos($file, "..") !== false) {
		return "";
	}
	foreach (rkrk_assess_template_diagram_fs_dirs() as $base) {
		$path = rtrim(str_replace("\\", "/", $base), "/") . "/" . $file;
		$realBase = realpath($base);
		$realFile = realpath($path);
		if ($realBase === false || $realFile === false || strpos($realFile, $realBase) !== 0 || !is_file($realFile)) {
			continue;
		}
		return str_replace("\\", "/", $realFile);
	}
	return "";
}

/** data:-URI für Diagramm (Druck/PDF); sonst "". */
function rkrk_assess_template_diagram_data_uri(string $file): string {
	$path = rkrk_assess_template_diagram_fs_path($file);
	if ($path === "" || !is_readable($path)) {
		return "";
	}
	$bin = @file_get_contents($path);
	if ($bin === false || $bin === "") {
		return "";
	}
	$ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
	$types = array(
		"png" => "image/png",
		"jpg" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"gif" => "image/gif",
		"webp" => "image/webp",
		"svg" => "image/svg+xml",
	);
	$mime = isset($types[$ext]) ? $types[$ext] : "application/octet-stream";
	return "data:" . $mime . ";base64," . base64_encode($bin);
}

/**
 * Web-Pfad zu rkrk_assess_image.php neben dem aktuellen Assess-Skript.
 * …/assess/public/ (rkrs/Portal): dort. test/test27/v2/pub: direkt in assess/ (kein /public/).
 */
function rkrk_assess_image_proxy_script_web_path(): string {
	$scriptDir = str_replace("\\", "/", dirname((string) ($_SERVER["SCRIPT_NAME"] ?? "")));
	$scriptDir = rtrim($scriptDir, "/");
	if ($scriptDir === "" || $scriptDir === ".") {
		if (is_file(__DIR__ . "/rkrk_assess_image.php")) {
			return "/rkrk_assess_image.php";
		}
		$scriptDir = "/rkropen/rkrs/assess/public";
	}
	if (preg_match("#/public$#", $scriptDir) === 1) {
		return $scriptDir . "/rkrk_assess_image.php";
	}
	if (is_file(__DIR__ . "/rkrk_assess_image.php")) {
		return $scriptDir . "/rkrk_assess_image.php";
	}
	return $scriptDir . "/public/rkrk_assess_image.php";
}

/** Proxy-URL für Template-Diagramme (rkrk_assess_image.php?root=diagram). */
function rkrk_assess_template_diagram_image_proxy_url(string $file): string {
	$file = ltrim(str_replace("\\", "/", trim($file)), "/");
	if ($file === "" || strpos($file, "..") !== false) {
		return "";
	}
	$parts = explode("/", $file);
	$basename = array_pop($parts);
	$dir = implode("/", $parts);
	if ($basename === "" || preg_match('#^[\\p{L}\\p{N}_\\-\\. ]+$#u', $basename) !== 1) {
		return "";
	}
	if ($dir !== "" && (strpos($dir, "..") !== false || preg_match('#^[\\p{L}\\p{N}_\\-\\./ ]+$#u', $dir) !== 1)) {
		return "";
	}
	$proxy = rkrk_assess_image_proxy_script_web_path();
	$url = $proxy . "?f=" . rawurlencode($basename) . "&root=diagram";
	if ($dir !== "") {
		$url .= "&d=" . rawurlencode($dir);
	}
	return $url;
}

/** Web-Prefix für Template-Diagramme (data/assess/image/). */
function rkrk_assess_template_diagram_web_prefix(): string {
	$pfx = function_exists("rkrk_assess_quiz_data_assess_web_prefix")
		? rkrk_assess_quiz_data_assess_web_prefix()
		: "/rkropen/rkrs/data/assess/";
	return rtrim(str_replace("\\", "/", $pfx), "/") . "/image/";
}

/** true wenn norm ein Diagramm unter @diagram@/… ist. */
function rkrk_assess_is_template_diagram_rel(string $norm): bool {
	return strncmp($norm, "@diagram@/", 10) === 0;
}

/** Proxy-URL für Template-Bilder (interaktive Ansicht). */
function rkrk_assess_template_image_proxy_url(string $relUnderImages): string {
	$rel = rkrk_assess_normalize_template_image_rel($relUnderImages);
	if ($rel === "" || preg_match("#^https?://#i", $rel) === 1 || strncmp($rel, "data:", 5) === 0) {
		return $rel;
	}
	if (rkrk_assess_is_template_diagram_rel($rel)) {
		$file = substr($rel, 10);
		$url = rkrk_assess_template_diagram_image_proxy_url($file);
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($url)
			: $url;
	}
	if (function_exists("rkrk_dga_rh_assess_image_proxy_url")) {
		return rkrk_dga_rh_assess_image_proxy_url($relUnderImages);
	}
	$proxy = rkrk_assess_image_proxy_script_web_path();
	$parts = explode("/", $rel);
	$file = array_pop($parts);
	$dir = implode("/", $parts);
	$url = $proxy . "?f=" . rawurlencode((string) $file);
	if ($dir !== "") {
		$url .= "&d=" . rawurlencode($dir);
	}
	return $url;
}

/** Web-URL für Template-Bilder (Druck/PDF). */
function rkrk_assess_template_image_web_url(string $relUnderImages): string {
	$rel = rkrk_assess_normalize_template_image_rel($relUnderImages);
	if ($rel === "" || preg_match("#^https?://#i", $rel) === 1 || strncmp($rel, "data:", 5) === 0) {
		return $rel;
	}
	if (rkrk_assess_is_template_diagram_rel($rel)) {
		$file = substr($rel, 10);
		$dataUri = rkrk_assess_template_diagram_data_uri($file);
		if ($dataUri !== "") {
			return $dataUri;
		}
		$url = rkrk_assess_template_diagram_image_proxy_url($file);
		return function_exists("rkrk_assess_encode_url_for_request")
			? rkrk_assess_encode_url_for_request($url)
			: $url;
	}
	if (function_exists("rkrk_dga_rh_image_url") && function_exists("rkrk_dga_rh_web_image_base")) {
		return rkrk_dga_rh_image_url(rkrk_dga_rh_web_image_base(), $rel);
	}
	$pfx = function_exists("rkrk_assess_quiz_data_assess_web_prefix")
		? rkrk_assess_quiz_data_assess_web_prefix() . "images/"
		: "/rkropen/rkrs/data/assess/images/";
	$combined = rtrim(str_replace("\\", "/", $pfx), "/") . "/" . $rel;
	return function_exists("rkrk_assess_encode_url_for_request")
		? rkrk_assess_encode_url_for_request($combined)
		: $combined;
}

/**
 * Bild-URLs in Template-HTML (print/process/info) für Bildschirm oder Druck auflösen.
 *
 * @param "screen"|"print" $mode
 */
function rkrk_assess_format_template_html_fragment(string $html, string $mode = "screen"): string {
	if ($html === "" || (stripos($html, "<img") === false && stripos($html, "url(") === false)) {
		return $html;
	}
	$html = preg_replace_callback(
		'/<img\\b([^>]*)>/i',
		static function (array $m): string {
			$attrs = (string) $m[1];
			if (preg_match('/\\bclass\\s*=/i', $attrs) === 1) {
				return $m[0];
			}
			return '<img class="lernimage"' . $attrs . '>';
		},
		$html
	);
	$resolve = static function (string $src) use ($mode): string {
		$norm = rkrk_assess_normalize_template_image_rel($src);
		if ($norm === "" || preg_match("#^https?://#i", $norm) === 1 || strncmp($norm, "data:", 5) === 0) {
			return $src;
		}
		return $mode === "print"
			? rkrk_assess_template_image_web_url($norm)
			: rkrk_assess_template_image_proxy_url($norm);
	};
	$attrUrl = static function (string $attr, array $m) use ($resolve): string {
		return $attr . "=" . $m[2] . htmlspecialchars($resolve($m[3]), ENT_QUOTES, "UTF-8") . $m[2];
	};
	$html = preg_replace_callback(
		"/\\b(src|data-src)\\s*=\\s*([\"'])([^\"']*)\\2/i",
		static function (array $m) use ($attrUrl): string {
			return $attrUrl($m[1], $m);
		},
		$html
	);
	return $html;
}

/** Ablauf/process-HTML für Bildschirm (Bild-URLs auflösen). */
function rkrk_assess_format_process_html_for_screen(string $process): string {
	$process = trim($process);
	if ($process === "") {
		return "";
	}
	$isHtml = strip_tags($process) !== $process;
	if (!$isHtml) {
		if (function_exists("rkrk_render_process_html")) {
			return rkrk_render_process_html($process);
		}
		return "<div class=\"live-point-flow__prose\">" . nl2br(htmlspecialchars($process, ENT_QUOTES, "UTF-8")) . "</div>";
	}
	return rkrk_assess_format_template_html_fragment($process, "screen");
}

/**
 * Typische UTF-8-Mojibake in Bildpfaden korrigieren (z. B. PrÃ¼fung → Prüfung).
 *
 * @param array<int, array<string, mixed>> $rows
 */
function rkrk_assess_fixup_question_image_paths(array &$rows): void {
	$map = array(
		'Ã¤' => 'ä', 'Ã„' => 'Ä', 'Ã¶' => 'ö', 'Ã–' => 'Ö',
		'Ã¼' => 'ü', 'Ãœ' => 'Ü', 'ÃŸ' => 'ß',
	);
	foreach ($rows as $i => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach (array('Frage-Bild', 'Loesungs-Bild') as $col) {
			if (!isset($row[$col])) {
				continue;
			}
			$rows[$i][$col] = strtr((string) $row[$col], $map);
		}
	}
}

/**
 * Canonical column order for Fragen-*.csv under data/assess/ (semicolon-separated UTF-8),
 * matching e.g. RKRS Einsatzpruefung_*.csv in rkrk/data/assess/.
 *
 * @return list<string>
 */
function rkrk_assess_question_csv_headers(): array {
	return array(
		"Nr",
		"Status",
		"Frage",
		"Loesungsauswahl",
		"Bereich",
		"Herkunft",
		"Frage-Bild",
		"Bildformat",
		"Loesungs-Bild",
		"Loesungstext",
		"Loesung",
		"Antwort",
		"Drone",
		"Zeit",
		"Audienz",
		"Prioritaet",
		"Rueckmeldung",
		"RueckmeldungText",
		"Aktiv",
		"Locked",
		"Änderung",
	);
}

/**
 * Einzelwert für Semikolon-CSV (UTF-8) vorbereiten: gültiges UTF-8, keine NUL-Bytes.
 */
function rkrk_assess_csv_sanitize_cell($value): string {
	$s = (string) $value;
	if ($s === "") {
		return "";
	}
	$s = str_replace("\0", "", $s);
	if (function_exists("rkrk_csv_strings_to_utf8")) {
		$t = rkrk_csv_strings_to_utf8($s);
		if (is_string($t)) {
			$s = $t;
		}
	} elseif (function_exists("mb_check_encoding") && !mb_check_encoding($s, "UTF-8")) {
		$s = (string) @mb_convert_encoding($s, "UTF-8", "Windows-1252");
	}
	return $s;
}

/**
 * UTF-8-Mojibake (z. B. «fÃ¤llt») in lesbare Umlaute wandeln.
 */
function rkrk_assess_fix_mojibake($value): string {
	$value = (string) $value;
	if ($value === "") {
		return $value;
	}
	$map = array(
		"Ã¤" => "ä", "Ã„" => "Ä",
		"Ã¶" => "ö", "Ã–" => "Ö",
		"Ã¼" => "ü", "Ãœ" => "Ü",
		"ÃŸ" => "ß",
		"â€“" => "–", "â€”" => "—",
		"â€ž" => "„", "â€œ" => "“", "â€" => "”",
		"â€˜" => "‘", "â€™" => "’",
		"Â°" => "°", "Â" => "",
	);
	return strtr($value, $map);
}

/**
 * Textfelder in assoziativen CSV-Zeilen für Anzeige normalisieren.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function rkrk_assess_fixup_csv_assoc_rows(array $rows): array {
	foreach ($rows as $ri => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach ($row as $hk => $hv) {
			if (is_array($hv)) {
				foreach ($hv as $i => $part) {
					$row[$hk][$i] = rkrk_assess_fix_mojibake(rkrk_assess_csv_sanitize_cell($part));
				}
			} else {
				$row[$hk] = rkrk_assess_fix_mojibake(rkrk_assess_csv_sanitize_cell($hv));
			}
		}
		$rows[$ri] = $row;
	}
	return $rows;
}

/**
 * Ob ein Antwort-Wert wie eine Nutzerauswahl (A–H / Ja|Nein) aussieht.
 */
function rkrk_assess_antwort_looks_like_user_pick(string $antwort): bool {
	$antwort = trim($antwort);
	if ($antwort === "") {
		return false;
	}
	if (strcasecmp($antwort, "Ja") === 0 || strcasecmp($antwort, "Nein") === 0) {
		return true;
	}
	return preg_match('/^[A-Ha-h](\s*\|\s*[A-Ha-h])*$/', $antwort) === 1;
}

/**
 * Katalog-Spalte «Antwort» als AntwortText sichern und Antwort für Nutzerauswahl freimachen.
 * Anzeigetext bevorzugt später Loesungstext, sonst AntwortText.
 *
 * @param array<int, array<string, mixed>> $questions
 */
function rkrk_assess_normalize_question_antwort_fields(array &$questions): void {
	foreach ($questions as $i => $qRow) {
		if (!is_array($qRow)) {
			continue;
		}
		$ant = trim((string) ($qRow["Antwort"] ?? ""));
		$at = trim((string) ($qRow["AntwortText"] ?? ""));
		if ($at === "" && $ant !== "" && !rkrk_assess_antwort_looks_like_user_pick($ant)) {
			$qRow["AntwortText"] = $ant;
			$qRow["Antwort"] = "";
		} elseif ($at === "" && isset($qRow["AntwortText"])) {
			$qRow["AntwortText"] = "";
		}
		$questions[$i] = $qRow;
	}
}

/**
 * Anzeige-Antworttext: Loesungstext wenn gesetzt, sonst Katalog-AntwortText / Antwort.
 *
 * @param array<string, mixed> $qRow
 */
function rkrk_assess_question_solution_prose(array $qRow): string {
	$lt = trim((string) ($qRow["Loesungstext"] ?? ""));
	if ($lt !== "") {
		return $lt;
	}
	$at = trim((string) ($qRow["AntwortText"] ?? ""));
	if ($at !== "") {
		return $at;
	}
	$ant = trim((string) ($qRow["Antwort"] ?? ""));
	if ($ant !== "" && !rkrk_assess_antwort_looks_like_user_pick($ant)) {
		return $ant;
	}
	return "";
}

/**
 * Eine CSV-Zeile schreiben (RFC 4180: Anführungszeichen bei ; " Zeilenumbruch etc.).
 *
 * @param array<int, string> $fields
 */
function rkrk_assess_csv_fputcsv_row($fh, array $fields, string $delimiter = ";"): bool {
	$enclosure = '"';
	if (PHP_VERSION_ID >= 80100) {
		$escape = (PHP_VERSION_ID >= 80400) ? '"' : "\\";
		return fputcsv($fh, $fields, $delimiter, $enclosure, $escape) !== false;
	}
	return fputcsv($fh, $fields, $delimiter, $enclosure) !== false;
}

/**
 * Semikolon-getrennte UTF-8-CSV mit BOM schreiben; Sonderzeichen in Zellen werden escaped.
 *
 * @param list<string> $headers
 * @param list<array<string, mixed>> $rows
 */
function rkrk_assess_write_csv_file(string $path, array $headers, array $rows, string $delimiter = ";"): bool {
	$fh = @fopen($path, "wb");
	if ($fh === false) {
		return false;
	}
	fwrite($fh, "\xEF\xBB\xBF");
	$hdrOut = array();
	foreach ($headers as $h) {
		$hdrOut[] = rkrk_assess_csv_sanitize_cell($h);
	}
	if (!rkrk_assess_csv_fputcsv_row($fh, $hdrOut, $delimiter)) {
		fclose($fh);
		return false;
	}
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$out = array();
		foreach ($headers as $header) {
			$v = isset($row[$header]) ? $row[$header] : "";
			$out[] = rkrk_assess_csv_sanitize_cell($v);
		}
		if (!rkrk_assess_csv_fputcsv_row($fh, $out, $delimiter)) {
			fclose($fh);
			return false;
		}
	}
	fclose($fh);
	return true;
}

/**
 * Semikolon-CSV lesen; Spaltenzahl wird an die Kopfzeile angeglichen (gequotete ; bleiben erhalten).
 *
 * @param list<string>|null $expectedHeaders Wenn gesetzt: Zeilen nur mit diesen Keys (fehlend = leer).
 * @return array{0: list<string>, 1: list<array<string, string>>}
 */
function rkrk_assess_read_csv_file(string $path, ?array $expectedHeaders = null, string $delimiter = ";"): array {
	$headers = array();
	$rows = array();
	$fh = @fopen($path, "rb");
	if ($fh === false) {
		return array($headers, $rows);
	}
	$headerRow = fgetcsv($fh, 1048576, $delimiter);
	if (!is_array($headerRow) || count($headerRow) === 0) {
		fclose($fh);
		return array($headers, $rows);
	}
	if (isset($headerRow[0])) {
		$headerRow[0] = (string) preg_replace('/^\xEF\xBB\xBF/', "", (string) $headerRow[0]);
	}
	$headers = array();
	foreach ($headerRow as $h) {
		$headers[] = (string) $h;
	}
	$nH = count($headers);
	while (($data = fgetcsv($fh, 1048576, $delimiter)) !== false) {
		if ($data === array(null)) {
			continue;
		}
		$allEmpty = true;
		foreach ($data as $cell) {
			if (trim((string) $cell) !== "") {
				$allEmpty = false;
				break;
			}
		}
		if ($allEmpty) {
			continue;
		}
		$nD = count($data);
		if ($nD < $nH) {
			$data = array_pad($data, $nH, "");
		} elseif ($nD > $nH) {
			$tail = array_slice($data, $nH - 1);
			$data = array_merge(array_slice($data, 0, $nH - 1), array(implode($delimiter, $tail)));
		}
		$row = array();
		foreach ($headers as $i => $h) {
			$row[$h] = isset($data[$i]) ? rkrk_assess_csv_sanitize_cell($data[$i]) : "";
		}
		if ($expectedHeaders !== null) {
			$line = array();
			foreach ($expectedHeaders as $eh) {
				$line[$eh] = isset($row[$eh]) ? (string) $row[$eh] : "";
			}
			if (in_array("Drone", $expectedHeaders, true) && trim((string) $line["Drone"]) === "") {
				if (isset($row["Drohne"])) {
					$line["Drone"] = (string) $row["Drohne"];
				} elseif (isset($row["Drohnentyp"])) {
					$line["Drone"] = (string) $row["Drohnentyp"];
				}
			}
			$rows[] = $line;
		} else {
			$rows[] = $row;
		}
	}
	fclose($fh);
	if (function_exists("rkrk_csv_strings_to_utf8")) {
		$rows = rkrk_csv_strings_to_utf8($rows);
		if (!is_array($rows)) {
			$rows = array();
		}
	}
	return array($headers, $rows);
}

/**
 * Posted mapping output row index → original question index (JSON array), from hidden field dar_q_row_orig.
 *
 * @param array<string, mixed> $post
 * @return list<int>
 */
function rkrk_assess_decode_dar_q_row_orig_from_post(array $post): array {
	$out = array();
	if (!isset($post["dar_q_row_orig"]) || !is_string($post["dar_q_row_orig"])) {
		return $out;
	}
	$s = trim($post["dar_q_row_orig"]);
	if ($s === "") {
		return $out;
	}
	$j = json_decode($s, true);
	if (!is_array($j)) {
		return $out;
	}
	foreach ($j as $v) {
		$out[] = (int) $v;
	}
	return $out;
}

/**
 * Datum für CSV-Spalte «Änderung» (nur bei inhaltlichen Frage-Änderungen gesetzt).
 */
function rkrk_assess_aenderung_date_today(): string {
	return date("d.m.Y");
}

/**
 * Felder, deren Änderung einen neuen «Änderung»-Stempel auslösen (nicht Priorität/Status/Rückmeldung/Aktiv/Locked/Nr).
 *
 * @param array<string, string>|null $oldRow
 * @param array<string, string> $line
 * @param array<string, string>|null $lineCompareImages Wenn gesetzt: für Frage-Bild/Loesungs-Bild diese Werte statt $line (z. B. vor DriveLocation-Prefix beim Admin-Speichern).
 */
function rkrk_assess_question_content_changed_for_aenderung(?array $oldRow, array $line, ?array $lineCompareImages = null): bool {
	static $fields = null;
	if ($fields === null) {
		$fields = array(
			"Frage",
			"Loesungsauswahl",
			"Loesung",
			"Loesungstext",
			"Frage-Bild",
			"Loesungs-Bild",
			"Bildformat",
			"Bereich",
			"Herkunft",
			"Antwort",
			"Drone",
			"Zeit",
			"Audienz",
		);
	}
	$norm = static function ($s): string {
		return trim(str_replace("\\", "/", (string) $s));
	};
	if ($oldRow === null) {
		foreach ($fields as $f) {
			$src = ($lineCompareImages !== null && ($f === "Frage-Bild" || $f === "Loesungs-Bild"))
				? ($lineCompareImages[$f] ?? "")
				: ($line[$f] ?? "");
			if ($norm($src) !== "") {
				return true;
			}
		}
		return false;
	}
	foreach ($fields as $f) {
		$newRaw = ($lineCompareImages !== null && ($f === "Frage-Bild" || $f === "Loesungs-Bild"))
			? ($lineCompareImages[$f] ?? "")
			: ($line[$f] ?? "");
		if ($norm($oldRow[$f] ?? "") !== $norm($newRaw)) {
			return true;
		}
	}
	return false;
}

/**
 * Setzt «Änderung» auf heutiges Datum bei inhaltlicher Änderung, sonst Wert aus $oldRow bzw. unverändert aus $line.
 *
 * @param array<string, string>|null $oldRow
 * @param array<string, string> $line
 * @param array<string, string>|null $lineCompareImages
 */
function rkrk_assess_apply_aenderung_stempel(?array $oldRow, array &$line, ?array $lineCompareImages = null): void {
	$h = "Änderung";
	if (rkrk_assess_question_content_changed_for_aenderung($oldRow, $line, $lineCompareImages)) {
		$line[$h] = rkrk_assess_aenderung_date_today();
	} elseif ($oldRow !== null && array_key_exists($h, $oldRow)) {
		$line[$h] = (string) $oldRow[$h];
	}
}

/**
 * Decode posted question rows. Prefers one JSON field (dar_q_json) so large quizzes stay under PHP max_input_vars (often 1000).
 *
 * @param array<int, string> $questionHeaders
 * @param array<string, mixed> $post
 * @return array<string, array<int|string, string>>
 */
function rkrk_assess_post_decode_questions(array $questionHeaders, array $post) {
	if (isset($post["dar_q_json"]) && is_string($post["dar_q_json"])) {
		$s = $post["dar_q_json"];
		if ($s !== "" && strlen($s) < 50000000) {
			$decoded = json_decode($s, true);
			if (is_array($decoded)) {
				$raw = array();
				foreach ($questionHeaders as $h) {
					$raw[$h] = array();
				}
				foreach ($decoded as $i => $row) {
					if (!is_array($row)) {
						continue;
					}
					$ii = (int) $i;
					foreach ($questionHeaders as $h) {
						$raw[$h][$ii] = isset($row[$h]) ? (string) $row[$h] : "";
					}
				}
				return $raw;
			}
		}
	}
	if (isset($post["q"]) && is_array($post["q"])) {
		return $post["q"];
	}
	return array();
}

/**
 * Infer images subfolder under data/assess/images/ from any row that already uses
 * a relative path such as "Einsatzprüfung/temperatur.png".
 *
 * @param array<int, array<string, mixed>> $questions
 */
function rkrk_assess_infer_drive_location_from_questions(array $questions): string {
	foreach ($questions as $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach (array("Frage-Bild", "Loesungs-Bild") as $col) {
			$p = isset($row[$col]) ? trim(str_replace("\\", "/", (string) $row[$col])) : "";
			if ($p !== "" && strpos($p, "/") !== false) {
				$dir = dirname($p);
				if ($dir !== "" && $dir !== "." && $dir !== "/") {
					return $dir;
				}
			}
		}
	}
	return "";
}

/**
 * DriveLocation nur aus AssessmentListe.csv (Spalte DriveLocation), nicht aus Fragen-Pfaden ableiten.
 *
 * @param array<string, mixed> $selectedAssessment
 * @param array<int, array<string, mixed>> $questions
 */
function rkrk_assess_effective_drive_location(array $selectedAssessment, array $questions = array()): string {
	unset($questions);
	return rkrk_assess_clean_drive_location_rel(
		isset($selectedAssessment["DriveLocation"]) ? (string) $selectedAssessment["DriveLocation"] : ""
	);
}

/**
 * DriveLocation relativ unter data/assess/images/ normalisieren.
 */
function rkrk_assess_clean_drive_location_rel(string $driveLocation): string {
	$driveLocation = str_replace("\\", "/", trim($driveLocation));
	if (preg_match('#/(?:rkrk|rkrs|rkrsplusassessment)/data/assess/images/(.*)$#i', $driveLocation, $m) === 1) {
		$driveLocation = (string) $m[1];
	}
	return trim($driveLocation, "/");
}

/**
 * Ordnernamen vergleichen (Umlaute/ae-Varianten, Gross/Klein).
 */
function rkrk_assess_folder_name_equivalent(string $a, string $b): bool {
	$norm = static function (string $s): string {
		$s = mb_strtolower(trim($s), "UTF-8");
		$map = array(
			"ä" => "a", "ö" => "o", "ü" => "u", "ß" => "ss",
			"ae" => "a", "oe" => "o", "ue" => "u",
		);
		$s = strtr($s, $map);
		return preg_replace("/[^a-z0-9]+/", "", $s) ?? "";
	};
	return $norm($a) === $norm($b);
}

/**
 * Frage-/Lösungsbild auf Dateinamen reduzieren (Ordner nur via DriveLocation in QuizIconLoc).
 */
function rkrk_assess_question_image_basename(string $path, string $driveLocation = ""): string {
	$path = trim(str_replace("\\", "/", $path));
	if ($path === "") {
		return "";
	}
	for ($i = 0; $i < 6; $i++) {
		$decoded = rawurldecode($path);
		if ($decoded === $path) {
			break;
		}
		$path = $decoded;
	}
	foreach (rkrk_assess_quiz_image_url_markers() as $marker) {
		$pos = stripos($path, $marker);
		if ($pos !== false) {
			$path = substr($path, $pos + strlen($marker));
			break;
		}
	}
	$path = preg_replace('#^(?:quiz/)?images/#i', "", $path);
	$path = ltrim((string) $path, "/");
	$driveLocation = rkrk_assess_clean_drive_location_rel($driveLocation);
	if ($driveLocation !== "") {
		$pfx = rtrim($driveLocation, "/") . "/";
		if (strpos($path, $pfx) === 0) {
			$path = substr($path, strlen($pfx));
		}
	}
	if (strpos($path, "/") !== false) {
		$dir = dirname($path);
		$file = basename($path);
		if ($driveLocation === "" || rkrk_assess_folder_name_equivalent($dir, $driveLocation)) {
			return $file;
		}
		return $file;
	}
	return $path;
}

/**
 * Frage-Bild / Loesungs-Bild auf Dateinamen reduzieren; Ordner kommt aus AssessmentListe DriveLocation.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function rkrk_assess_strip_question_image_paths(array &$rows, string $driveLocation = ""): void {
	foreach ($rows as $i => $row) {
		if (!is_array($row)) {
			continue;
		}
		foreach (array("Frage-Bild", "Loesungs-Bild") as $col) {
			if (!isset($row[$col])) {
				continue;
			}
			$rows[$i][$col] = rkrk_assess_question_image_basename(
				(string) $row[$col],
				$driveLocation
			);
		}
	}
}

/**
 * @deprecated Nur noch strip — Ordner nicht mehr aus Fragen-CSV ableiten.
 * @param array<int, array<string, mixed>> $rows
 */
function rkrk_assess_normalize_bare_question_image_paths(array &$rows): void {
	rkrk_assess_strip_question_image_paths($rows, "");
}

/**
 * Drohnentyp-String normalisieren (wie im Quiz).
 */
function rkrk_assess_normalize_drone($str): string {
	if ($str === null) {
		return "";
	}
	$str = (string) $str;
	$str = urldecode($str);
	$str = str_replace("\xC2\xA0", " ", $str);
	$str = preg_replace("/[\x{200B}-\x{200D}\x{FEFF}]/u", "", $str);
	$str = preg_replace("/\s+/", " ", $str);
	return trim($str);
}

/** Gewicht fuer Zufallsziehung: w(Prioritaet) = (P + 1)^2 */
function rkrk_dga_quiz_prio_weight($prio): float {
	$p = (int) $prio;
	if ($p < 0) {
		$p = 0;
	}
	return (float) (($p + 1) * ($p + 1));
}

/**
 * Zieht bis zu $k verschiedene Keys aus $poolItems gewichtet nach Prioritaet (ohne Zuruecklegen).
 *
 * @param array<int, array{key: string|int, prio: int}> $poolItems
 * @return list<string>
 */
function rkrk_dga_weighted_sample_keys(array $poolItems, $k): array {
	$pool = array_values($poolItems);
	$out = array();
	$k = (int) $k;
	if ($k < 1 || count($pool) < 1) {
		return $out;
	}
	$k = min($k, count($pool));
	$maxRand = (int) mt_getrandmax();
	if ($maxRand < 1) {
		$maxRand = 1;
	}
	for ($t = 0; $t < $k; $t++) {
		$sum = 0.0;
		foreach ($pool as $it) {
			$sum += rkrk_dga_quiz_prio_weight(isset($it["prio"]) ? $it["prio"] : 0);
		}
		if ($sum <= 0) {
			$ix = (int) floor((mt_rand(0, $maxRand) / $maxRand) * count($pool));
			if ($ix >= count($pool)) {
				$ix = count($pool) - 1;
			}
		} else {
			$r = (mt_rand(0, $maxRand) / $maxRand) * $sum;
			$acc = 0.0;
			$ix = 0;
			for ($i = 0; $i < count($pool); $i++) {
				$acc += rkrk_dga_quiz_prio_weight(isset($pool[$i]["prio"]) ? $pool[$i]["prio"] : 0);
				if ($r <= $acc) {
					$ix = $i;
					break;
				}
			}
		}
		$out[] = (string) $pool[$ix]["key"];
		array_splice($pool, $ix, 1);
	}
	return $out;
}

/** Kapitel mit hoechstens so vielen Pool-Fragen gelten als «klein» (max. 1 Platz pro Durchgang). */
function rkrk_dga_small_bereich_pool_threshold(): int {
	return 5;
}

/** Max. Fragen pro Durchgang aus einem Bereich (Legacy; neue Formel nutzt rkrk_dga_bereich_block_cap). */
function rkrk_dga_bereich_max_slots_per_run(int $poolSize): int {
	return rkrk_dga_bereich_block_cap($poolSize);
}

/** Obergrenze Ziel-Plaetze je Frageblock (= Poolgroesse des Blocks). */
function rkrk_dga_bereich_block_cap(int $poolSize): int {
	if ($poolSize < 1) {
		return 0;
	}
	return $poolSize;
}

/**
 * Block-Gewicht fuer Platzverteilung: Summe w(Prioritaet) = Summe (P+1)^2.
 *
 * @param array<int, array{prio?: int}> $items
 */
function rkrk_dga_bereich_block_weight(array $items): float {
	$sum = 0.0;
	foreach ($items as $it) {
		if (!is_array($it)) {
			continue;
		}
		$sum += rkrk_dga_quiz_prio_weight(isset($it["prio"]) ? $it["prio"] : 0);
	}
	return $sum;
}

/**
 * Mindest-Plaetze je Bereich: 2 wenn pickN es erlaubt, sonst 1 (1-Fragen-Block: 1).
 *
 * @param array<string, array<int, mixed>> $byBereich
 * @return array<string, int>
 */
function rkrk_dga_bereich_min_floors(array $byBereich, int $pickN): array {
	$floors = array();
	$needAll = 0;
	foreach ($byBereich as $g => $items) {
		$n = count($items);
		if ($n >= 2) {
			$needAll += 2;
		} elseif ($n >= 1) {
			$needAll += 1;
		}
	}
	$useMin2 = ($needAll <= $pickN);
	foreach ($byBereich as $g => $items) {
		$n = count($items);
		if ($n < 1) {
			$floors[$g] = 0;
		} elseif ($n === 1) {
			$floors[$g] = 1;
		} else {
			$floors[$g] = $useMin2 ? 2 : 1;
		}
	}
	return $floors;
}

/**
 * Reduziert Ziel-Plaetze oberhalb des Mindestbodens, bis Summe = pickN.
 *
 * @param list<string> $groups
 * @param array<string, array<int, mixed>> $byBereich
 * @param array<string, int> $targets
 * @param array<string, int> $floors
 */
function rkrk_dga_rr_targets_trim_excess(array $groups, array $byBereich, array &$targets, array $floors, int $pickN): void {
	$sum = array_sum($targets);
	while ($sum > $pickN) {
		$bestG = null;
		$bestExcess = -1;
		$bestWeight = INF;
		foreach ($groups as $g) {
			$floor = isset($floors[$g]) ? (int) $floors[$g] : 0;
			$excess = (int) $targets[$g] - $floor;
			if ($excess <= 0) {
				continue;
			}
			$w = rkrk_dga_bereich_block_weight($byBereich[$g]);
			if ($excess > $bestExcess || ($excess === $bestExcess && $w < $bestWeight)) {
				$bestG = $g;
				$bestExcess = $excess;
				$bestWeight = $w;
			}
		}
		if ($bestG === null) {
			break;
		}
		$targets[$bestG]--;
		$sum--;
	}
}

/** @deprecated Nur noch fuer Rueckwaertskompatibilitaet in Alt-Texten. */
function rkrk_dga_targets_blend_equal_share(): float {
	return 0.0;
}

/**
 * @param list<string> $groups
 * @param array<string, array<int, mixed>> $byBereich
 * @param array<string, int> $targets
 * @param array<string, float> $fracs
 */
function rkrk_dga_distribute_remainder_slots(array $groups, array $byBereich, array &$targets, array &$fracs, int &$remain): void {
	while ($remain > 0) {
		$bestG = null;
		$bestFrac = -1.0;
		foreach ($groups as $g) {
			$cap = rkrk_dga_bereich_block_cap(count($byBereich[$g]));
			if ($targets[$g] >= $cap) {
				continue;
			}
			if ($fracs[$g] > $bestFrac) {
				$bestFrac = $fracs[$g];
				$bestG = $g;
			}
		}
		if ($bestG === null) {
			break;
		}
		$targets[$bestG]++;
		$fracs[$bestG] = 0.0;
		$remain--;
	}
	while ($remain > 0) {
		$progress = false;
		foreach ($groups as $g) {
			if ($remain <= 0) {
				break 2;
			}
			$cap = rkrk_dga_bereich_block_cap(count($byBereich[$g]));
			if ($targets[$g] < $cap) {
				$targets[$g]++;
				$remain--;
				$progress = true;
			}
		}
		if (!$progress) {
			break;
		}
	}
}

/**
 * Verteilt $pickN Plaetze je Bereich: proportional zu Summe (P+1)^2, Mindestboden 2/1.
 *
 * @param array<string, array<int, mixed>> $byBereich
 * @return array{0: array<string, int>, 1: list<string>}
 */
function rkrk_dga_rr_targets_per_bereich(array $byBereich, $pickN): array {
	$groups = array_keys($byBereich);
	sort($groups, SORT_STRING);
	$targets = array();
	foreach ($groups as $g) {
		$targets[$g] = 0;
	}
	$pickN = (int) $pickN;
	if ($pickN < 1 || count($groups) < 1) {
		return array($targets, $groups);
	}
	$total = 0;
	foreach ($groups as $g) {
		$total += count($byBereich[$g]);
	}
	if ($total < 1) {
		return array($targets, $groups);
	}
	$weights = array();
	$weightTotal = 0.0;
	foreach ($groups as $g) {
		$w = rkrk_dga_bereich_block_weight($byBereich[$g]);
		if ($w <= 0.0) {
			$w = (float) count($byBereich[$g]);
		}
		$weights[$g] = $w;
		$weightTotal += $w;
	}
	if ($weightTotal <= 0.0) {
		$weightTotal = (float) $total;
		foreach ($groups as $g) {
			$weights[$g] = (float) count($byBereich[$g]);
		}
	}
	$floors = rkrk_dga_bereich_min_floors($byBereich, $pickN);
	$fracs = array();
	foreach ($groups as $g) {
		$n = count($byBereich[$g]);
		$cap = rkrk_dga_bereich_block_cap($n);
		$exact = ($pickN * $weights[$g]) / $weightTotal;
		$base = (int) floor($exact);
		$targets[$g] = min($base, $cap);
		$fracs[$g] = $exact - $base;
	}
	foreach ($groups as $g) {
		$cap = rkrk_dga_bereich_block_cap(count($byBereich[$g]));
		$floor = min(isset($floors[$g]) ? (int) $floors[$g] : 0, $cap);
		if ($targets[$g] < $floor) {
			$targets[$g] = $floor;
		}
	}
	if (array_sum($targets) > $pickN) {
		rkrk_dga_rr_targets_trim_excess($groups, $byBereich, $targets, $floors, $pickN);
	}
	$remain = $pickN - array_sum($targets);
	if ($remain > 0) {
		rkrk_dga_distribute_remainder_slots($groups, $byBereich, $targets, $fracs, $remain);
	}
	return array($targets, $groups);
}

/** Einklappbare Hilfe: Auswahlregeln im Fragen-Manager / Review. */
function rkrk_dga_echo_quiz_selection_rules_help(): void {
	$smallMax = rkrk_dga_small_bereich_pool_threshold();
	echo "<ol>\n";
	echo "<li><strong>Pool:</strong> Nur Fragen mit Status «aktiv» und passender Audienz. "
		. "Pro Fragebogen wählt der Teilnehmer <strong>genau einen</strong> Drohnentyp; im Pool sind dann universelle Fragen "
		. "plus Fragen dieses Typs (konkreter Filter im Manager = nur dieser Typ; «Alle Typen» = Mittelwert über alle im Fragebogen vorkommenden Typen).</li>\n";
	echo "<li><strong>Anzahl:</strong> Pro Durchgang werden höchstens «Fragen pro Durchgang» aus dem Setup gezogen "
		. "(begrenzt durch die Poolgrösse).</li>\n";
	echo "<li><strong>Kapitel (Spalte Bereich):</strong> Die Ziel-Plätze verteilen sich proportional zur Block-Gewichtung "
		. "<code>Σ(P+1)²</code> (Anzahl und Wichtigkeit der Fragen im Kapitel). "
		. "Jeder Block mit mindestens zwei Fragen erhält Ziel <strong>mindestens zwei</strong> Plätze, sofern «Fragen pro Durchgang» das erlaubt; "
		. "sonst mindestens <strong>eine</strong> Frage pro nicht-leerem Block.</li>\n";
	echo "<li><strong>Kleine Kapitel (≤ " . (int) $smallMax . " Fragen im Pool):</strong> Kein Zwang, alle Fragen dieses Kapitels "
		. "in einem Durchgang abzufragen — die Gewichtung begrenzt kleine Blöcke von selbst.</li>\n";
	echo "<li><strong>Innerhalb eines Kapitels:</strong> Gewichtete Zufallsziehung nach Priorität "
		. "<code>w = (P+1)²</code> (höhere Priorität = häufiger).</li>\n";
	echo "<li><strong>Reihenfolge im Quiz:</strong> Zuerst gemischt nach Kapiteln, Restplätze aus dem gesamten Pool.</li>\n";
	echo "<li><strong>Wahrscheinlichkeitstabelle:</strong> Monte-Carlo-Schätzung mit derselben Formel wie im Live-Quiz.</li>\n";
	echo "</ol>\n";
}

/**
 * Quiz-Reihenfolge: Bereiche nach Block-Gewichtung, innerhalb Bereich gewichtete Zufallsziehung.
 *
 * @param array<int, array{key: string|int, prio: int, bereich: string}> $eligible
 * @return list<string>
 */
function rkrk_dga_build_stochastic_quiz_order(array $eligible, $pickN): array {
	$pickN = (int) $pickN;
	if ($pickN < 1 || count($eligible) < 1) {
		return array();
	}
	$pickN = min($pickN, count($eligible));
	$byB = array();
	foreach ($eligible as $e) {
		$b = isset($e["bereich"]) ? (string) $e["bereich"] : "_";
		if (!isset($byB[$b])) {
			$byB[$b] = array();
		}
		$byB[$b][] = $e;
	}
	list($targets, $groups) = rkrk_dga_rr_targets_per_bereich($byB, $pickN);
	$selectedByG = array();
	$taken = array();
	foreach ($groups as $g) {
		$want = isset($targets[$g]) ? (int) $targets[$g] : 0;
		$want = min($want, count($byB[$g]));
		$keys = rkrk_dga_weighted_sample_keys($byB[$g], $want);
		$selectedByG[$g] = $keys;
		foreach ($keys as $kk) {
			$taken[(string) $kk] = true;
		}
	}
	while (count($taken) < $pickN) {
		$rest = array();
		foreach ($eligible as $e) {
			$kk = (string) $e["key"];
			if (!isset($taken[$kk])) {
				$rest[] = $e;
			}
		}
		if (count($rest) < 1) {
			break;
		}
		$need = $pickN - count($taken);
		$more = rkrk_dga_weighted_sample_keys($rest, $need);
		foreach ($more as $kk) {
			$taken[(string) $kk] = true;
		}
		if (!isset($selectedByG["_rest"])) {
			$selectedByG["_rest"] = array();
		}
		foreach ($more as $kk) {
			$selectedByG["_rest"][] = (string) $kk;
		}
		break;
	}
	$queues = array();
	foreach ($groups as $g) {
		if (!empty($selectedByG[$g])) {
			$q = array_values($selectedByG[$g]);
			shuffle($q);
			$queues[$g] = $q;
		}
	}
	$order = array();
	$guard = 0;
	while (count($order) < $pickN && $guard < $pickN * 4) {
		$guard++;
		$step = false;
		foreach ($groups as $g) {
			if (count($order) >= $pickN) {
				break 2;
			}
			if (!empty($queues[$g])) {
				$order[] = (string) array_shift($queues[$g]);
				$step = true;
			}
		}
		if (!$step) {
			break;
		}
	}
	if (!empty($selectedByG["_rest"])) {
		$tail = array_values($selectedByG["_rest"]);
		shuffle($tail);
		foreach ($tail as $kk) {
			if (count($order) >= $pickN) {
				break;
			}
			if (!in_array((string) $kk, $order, true)) {
				$order[] = (string) $kk;
			}
		}
	}
	return array_slice($order, 0, $pickN);
}

/**
 * Antwortoptionen einer Frage mischen (Loesungsauswahl) und Loesung-Buchstaben (A–H) anpassen.
 * Ja/Nein-Fragen und weniger als zwei Optionen bleiben unveraendert.
 *
 * @param array<string, mixed> $qRow
 * @return array<string, mixed>
 */
function rkrk_assess_shuffle_question_answer_options(array $qRow): array {
	$loes = trim((string) ($qRow["Loesung"] ?? ""));
	if ($loes === "Ja" || $loes === "Nein") {
		return $qRow;
	}
	$opts = $qRow["Loesungsauswahl"] ?? array();
	if (!is_array($opts)) {
		$opts = $opts !== "" ? array_map("trim", explode("|", (string) $opts)) : array();
	}
	$opts = array_values(array_filter($opts, static function ($v) {
		return trim((string) $v) !== "";
	}));
	if (count($opts) < 2) {
		return $qRow;
	}
	$n = count($opts);
	$perm = range(0, $n - 1);
	shuffle($perm);
	$newOpts = array();
	$oldToNewLetter = array();
	for ($newIdx = 0; $newIdx < $n; $newIdx++) {
		$oldIdx = (int) $perm[$newIdx];
		$newOpts[$newIdx] = $opts[$oldIdx];
		$oldToNewLetter[$oldIdx] = chr(65 + $newIdx);
	}
	$qRow["Loesungsauswahl"] = $newOpts;
	if ($loes === "") {
		return $qRow;
	}
	// Ungültige Codes (z. B. F bei nur 5 Optionen) verwerfen — nicht als Fallback behalten.
	$remap = static function ($token) use ($oldToNewLetter, $n) {
		$t = trim((string) $token);
		if ($t === "") {
			return "";
		}
		if (preg_match("/^[A-H]$/i", $t) === 1) {
			$oldIdx = ord(strtoupper($t)) - 65;
			if ($oldIdx < 0 || $oldIdx >= $n || !isset($oldToNewLetter[$oldIdx])) {
				return "";
			}
			return $oldToNewLetter[$oldIdx];
		}
		return $t;
	};
	$parts = array_values(array_filter(array_map("trim", explode("|", $loes)), static function ($v) {
		return $v !== "";
	}));
	if (count($parts) === 0) {
		return $qRow;
	}
	$mapped = array();
	$maxLetter = chr(64 + $n); // A..(n) z. B. n=5 → E
	foreach (array_map($remap, $parts) as $letter) {
		$letter = trim((string) $letter);
		if ($letter === "") {
			continue;
		}
		if (preg_match("/^[A-H]$/i", $letter) === 1) {
			$up = strtoupper($letter);
			if ($up > $maxLetter) {
				continue;
			}
			if (!in_array($up, $mapped, true)) {
				$mapped[] = $up;
			}
			continue;
		}
		if (!in_array($letter, $mapped, true)) {
			$mapped[] = $letter;
		}
	}
	$qRow["Loesung"] = implode("|", $mapped);
	return $qRow;
}

/**
 * Antwortoptionen aller Fragen mischen, wenn Assessment «AntwortMischen» = Ja.
 *
 * @param array<int, array<string, mixed>> $questions
 * @param array<string, mixed> $assessment
 */
function rkrk_assess_apply_answer_option_shuffle(array &$questions, array $assessment): void {
	if (strtolower(trim((string) ($assessment["AntwortMischen"] ?? ""))) !== "ja") {
		return;
	}
	foreach ($questions as $i => $qRow) {
		if (!is_array($qRow)) {
			continue;
		}
		$questions[$i] = rkrk_assess_shuffle_question_answer_options($qRow);
	}
}

/**
 * Kanal fuer Fragen-Auswahl: ADMIN ist nur UI-Modus, nicht Audienz.
 * Live-Quiz ohne ADMIN nutzt RKRS / RKRSplus / RKRSprev (Pfad oder Query).
 */
function rkrk_dga_quiz_setting_for_selection(string $setting): string {
	$setting = trim($setting);
	if (strcasecmp($setting, "ADMIN") !== 0) {
		return $setting !== "" ? $setting : "RKRS";
	}
	$g = (isset($_GET) && is_array($_GET)) ? $_GET : array();
	if (array_key_exists("TEST", $g)) {
		return "TEST";
	}
	if (array_key_exists("RKRSplus", $g)) {
		return "RKRSplus";
	}
	if (array_key_exists("RKRSprev", $g)) {
		return "RKRSprev";
	}
	if (array_key_exists("RKRS", $g)) {
		return "RKRS";
	}
	if (array_key_exists("RKRK", $g)) {
		return "RKRK";
	}
	if (!empty($GLOBALS["onRKRS"])) {
		return "RKRS";
	}
	return "RKRK";
}

/**
 * Fragen-Pool fuer die stochastische Quiz-Zusammenstellung (Status aktiv, Audienz, Drohne).
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @return list<array{key: string, prio: int, bereich: string}>
 */
function rkrk_dga_build_quiz_eligible_pool(array $questions, string $setting, string $droneFilter = "Alle"): array {
	$eligible = array();
	$droneFilter = rkrk_assess_normalize_drone($droneFilter);
	foreach ($questions as $csvKey => $qRow) {
		if (!is_array($qRow)) {
			continue;
		}
		$drone = rkrk_assess_normalize_drone(isset($qRow["Drone"]) ? $qRow["Drone"] : "");
		if ($droneFilter === "" || $droneFilter === "Alle") {
			$droneMatch = ($drone === "" || $drone === "Alle");
		} else {
			$droneMatch = ($drone === "" || $drone === "Alle" || $drone === $droneFilter);
		}
		if (
			rkrk_assess_question_is_quiz_live($qRow)
			&& (
				rkrk_assess_setting_sees_audience($setting, (string) ($qRow["Audienz"] ?? ""))
				|| $setting === "TEST"
			)
			&& $droneMatch
		) {
			$prRaw = isset($qRow["Prioritaet"]) ? trim((string) $qRow["Prioritaet"]) : "";
			$prio = ($prRaw !== "" && is_numeric($prRaw)) ? (int) $prRaw : 0;
			$br = trim((string) ($qRow["Bereich"] ?? ""));
			if ($br === "") {
				$br = "_";
			}
			$eligible[] = array(
				"key" => (string) $csvKey,
				"prio" => $prio,
				"bereich" => $br,
			);
		}
	}
	return $eligible;
}

/**
 * Monte-Carlo-Schaetzung: P(Frage im Durchgang), gleiche Auswahlformel wie im Live-Quiz.
 *
 * @param array<int, array{key: string|int, prio: int, bereich: string}> $eligible
 * @return array<string, float> key => Wahrscheinlichkeit 0..1
 */
function rkrk_dga_simulate_selection_probabilities(array $eligible, int $pickN, int $iterations = 5000): array {
	$counts = array();
	foreach ($eligible as $e) {
		$counts[(string) $e["key"]] = 0;
	}
	if ($pickN < 1 || count($eligible) < 1 || $iterations < 1) {
		$out = array();
		foreach ($counts as $k => $c) {
			$out[$k] = 0.0;
		}
		return $out;
	}
	$pickN = min($pickN, count($eligible));
	for ($t = 0; $t < $iterations; $t++) {
		$order = rkrk_dga_build_stochastic_quiz_order($eligible, $pickN);
		foreach ($order as $key) {
			$ks = (string) $key;
			if (isset($counts[$ks])) {
				$counts[$ks]++;
			}
		}
	}
	$out = array();
	foreach ($counts as $k => $c) {
		$out[$k] = $c / $iterations;
	}
	return $out;
}

/**
 * Eindeutige Drohnentypen im Fragebogen (ohne leer / «Alle»).
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @return list<string>
 */
function rkrk_dga_quiz_distinct_drone_types(array $questions): array {
	$types = array();
	foreach ($questions as $qx) {
		if (!is_array($qx)) {
			continue;
		}
		$d = rkrk_assess_normalize_drone(isset($qx["Drone"]) ? $qx["Drone"] : "");
		if ($d !== "" && $d !== "Alle" && !in_array($d, $types, true)) {
			$types[] = $d;
		}
	}
	sort($types, SORT_STRING);
	return $types;
}

/**
 * @param array<string, mixed> $qRow
 * @return array{key: string, prio: int, bereich: string}|null
 */
function rkrk_dga_eligible_entry_from_question_row(string $setting, $csvKey, array $qRow) {
	if (!rkrk_assess_question_is_quiz_live($qRow)) {
		return null;
	}
	if (
		!rkrk_assess_setting_sees_audience($setting, (string) ($qRow["Audienz"] ?? ""))
		&& $setting !== "TEST"
	) {
		return null;
	}
	$prRaw = isset($qRow["Prioritaet"]) ? trim((string) $qRow["Prioritaet"]) : "";
	$prio = ($prRaw !== "" && is_numeric($prRaw)) ? (int) $prRaw : 0;
	$br = trim((string) ($qRow["Bereich"] ?? ""));
	if ($br === "") {
		$br = "_";
	}
	return array(
		"key" => (string) $csvKey,
		"prio" => $prio,
		"bereich" => $br,
	);
}

/**
 * Monte-Carlo P(Anzeige) je Frage — bei Filter «Alle» und mehreren Drohnentypen Mittelwert
 * (pro Fragebogen wird im Live-Quiz genau ein Typ gewählt).
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @return array{
 *   prob_map: array<string, float>,
 *   eligible_keys: array<string, bool>,
 *   eligible: list<array{key: string, prio: int, bereich: string}>,
 *   pick_effective: int,
 *   pool_count: int,
 *   blended: bool,
 *   drone_filter: string,
 *   drone_types: list<string>,
 *   iterations: int
 * }
 */
function rkrk_dga_build_question_probability_preview(array $questions, string $setting, int $pickN, string $droneFilter = "Alle", int $iterations = 5000): array {
	$droneFilter = rkrk_assess_normalize_drone($droneFilter);
	$droneTypes = rkrk_dga_quiz_distinct_drone_types($questions);
	$blended = ($droneFilter === "" || $droneFilter === "Alle") && count($droneTypes) > 0;
	$probMap = array();
	$eligibleKeys = array();
	$eligible = array();
	$pickEffective = 0;
	$poolCount = 0;

	if ($blended) {
		$nTypes = count($droneTypes);
		$sumProb = array();
		$poolSizes = array();
		foreach ($droneTypes as $dt) {
			$pool = rkrk_dga_build_quiz_eligible_pool($questions, $setting, $dt);
			$poolSizes[] = count($pool);
			$pick = min(count($pool), $pickN);
			if ($pick < 1) {
				continue;
			}
			$pm = rkrk_dga_simulate_selection_probabilities($pool, $pick, $iterations);
			foreach ($pool as $e) {
				$k = (string) $e["key"];
				$eligibleKeys[$k] = true;
				if (!isset($sumProb[$k])) {
					$sumProb[$k] = 0.0;
				}
				$sumProb[$k] += isset($pm[$k]) ? (float) $pm[$k] : 0.0;
			}
		}
		foreach ($sumProb as $k => $sum) {
			$probMap[$k] = $sum / $nTypes;
		}
		if (count($poolSizes) > 0) {
			$poolCount = (int) round(array_sum($poolSizes) / count($poolSizes));
			$pickEffective = $poolCount > 0 ? min($poolCount, $pickN) : 0;
		}
		$eligible = array();
		if ($pickEffective > 0) {
			foreach ($droneTypes as $dt) {
				$pool = rkrk_dga_build_quiz_eligible_pool($questions, $setting, $dt);
				if (count($pool) < 1) {
					continue;
				}
				$eligible = $pool;
				if (count($pool) >= $pickEffective) {
					break;
				}
			}
		}
	} else {
		$df = ($droneFilter === "" || $droneFilter === "Alle") ? "Alle" : $droneFilter;
		$eligible = rkrk_dga_build_quiz_eligible_pool($questions, $setting, $df);
		$poolCount = count($eligible);
		$pickEffective = $poolCount > 0 ? min($poolCount, $pickN) : 0;
		if ($pickEffective > 0) {
			$probMap = rkrk_dga_simulate_selection_probabilities($eligible, $pickEffective, $iterations);
			foreach ($eligible as $e) {
				$eligibleKeys[(string) $e["key"]] = true;
			}
		}
	}

	return array(
		"prob_map" => $probMap,
		"eligible_keys" => $eligibleKeys,
		"eligible" => $eligible,
		"pick_effective" => $pickEffective,
		"pool_count" => $poolCount,
		"blended" => $blended,
		"drone_filter" => $droneFilter,
		"drone_types" => $droneTypes,
		"iterations" => $iterations,
	);
}

/** Anzeigename für Spalte Bereich / Kapitel in Auswahl-Grafiken. */
function rkrk_dga_topic_label(string $bereich): string {
	$b = trim($bereich);
	if ($b === "" || $b === "_") {
		return "Allgemein";
	}
	return $b;
}

/**
 * Aggregiert Pool, Ziel-Plätze (Kapitel-Formel) und Monte-Carlo-Erwartungen je Thema (Bereich).
 *
 * @param list<array{key: string|int, prio: int, bereich: string}> $eligible
 * @param array<string, float> $probMap key => P(Anzeige) 0..1
 * @return array<string, mixed>
 */
function rkrk_dga_aggregate_topic_distribution_stats(array $eligible, int $pickN, array $probMap): array {
	$byB = array();
	foreach ($eligible as $e) {
		$b = isset($e["bereich"]) ? (string) $e["bereich"] : "_";
		if (!isset($byB[$b])) {
			$byB[$b] = array();
		}
		$byB[$b][] = $e;
	}
	list($targets, $groups) = rkrk_dga_rr_targets_per_bereich($byB, $pickN);
	$topics = array();
	$totalPool = count($eligible);
	$maxPool = 0;
	$maxTarget = 0;
	$maxExp = 0.0;
	foreach ($groups as $g) {
		$pool = count($byB[$g]);
		$target = isset($targets[$g]) ? (int) $targets[$g] : 0;
		$expDraw = 0.0;
		$prioW = 0.0;
		foreach ($byB[$g] as $e) {
			$k = (string) $e["key"];
			$expDraw += isset($probMap[$k]) ? (float) $probMap[$k] : 0.0;
			if (function_exists("rkrk_dga_quiz_prio_weight")) {
				$prioW += (float) rkrk_dga_quiz_prio_weight((int) ($e["prio"] ?? 0));
			}
		}
		$maxPool = max($maxPool, $pool);
		$maxTarget = max($maxTarget, $target);
		$maxExp = max($maxExp, $expDraw);
		$topics[] = array(
			"bereich" => $g,
			"label" => rkrk_dga_topic_label($g),
			"pool" => $pool,
			"target_slots" => $target,
			"expected_draws" => $expDraw,
			"avg_p" => $pool > 0 ? ($expDraw / $pool) : 0.0,
			"prio_weight_sum" => $prioW,
		);
	}
	usort($topics, static function ($a, $b) {
		if ($a["expected_draws"] !== $b["expected_draws"]) {
			return $b["expected_draws"] <=> $a["expected_draws"];
		}
		return strcmp((string) $a["label"], (string) $b["label"]);
	});
	$totalExp = 0.0;
	foreach ($topics as $t) {
		$totalExp += (float) $t["expected_draws"];
	}
	foreach ($topics as $ti => $t) {
		$topics[$ti]["pool_share"] = $totalPool > 0 ? ((int) $t["pool"]) / $totalPool : 0.0;
		$topics[$ti]["slot_share"] = $pickN > 0 ? ((int) $t["target_slots"]) / $pickN : 0.0;
		$topics[$ti]["exp_share"] = $totalExp > 0.0001 ? ((float) $t["expected_draws"]) / $totalExp : 0.0;
	}
	$scaleMax = max(1, $maxPool, $maxTarget, (int) ceil($maxExp));
	return array(
		"topics" => $topics,
		"pick_n" => $pickN,
		"total_pool" => $totalPool,
		"scale_max" => $scaleMax,
		"blend_equal_pct" => 0,
	);
}

/**
 * Mittelt Themen-Statistiken über mehrere Drohnentypen (Admin «Alle Typen»).
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @param list<string> $droneTypes
 */
function rkrk_dga_aggregate_blended_topic_distribution_stats(array $questions, string $setting, int $pickN, array $droneTypes, int $iterations = 5000): array {
	$merged = array();
	$nTypes = 0;
	foreach ($droneTypes as $dt) {
		$pool = rkrk_dga_build_quiz_eligible_pool($questions, $setting, $dt);
		$pick = min(count($pool), $pickN);
		if ($pick < 1 || count($pool) < 1) {
			continue;
		}
		$pm = rkrk_dga_simulate_selection_probabilities($pool, $pick, $iterations);
		$stats = rkrk_dga_aggregate_topic_distribution_stats($pool, $pick, $pm);
		$topics = isset($stats["topics"]) && is_array($stats["topics"]) ? $stats["topics"] : array();
		foreach ($topics as $t) {
			if (!is_array($t)) {
				continue;
			}
			$b = (string) ($t["bereich"] ?? "_");
			if (!isset($merged[$b])) {
				$merged[$b] = array(
					"bereich" => $b,
					"label" => (string) ($t["label"] ?? rkrk_dga_topic_label($b)),
					"pool" => 0.0,
					"target_slots" => 0.0,
					"expected_draws" => 0.0,
					"prio_weight_sum" => 0.0,
					"count" => 0,
				);
			}
			$merged[$b]["pool"] += (float) ($t["pool"] ?? 0);
			$merged[$b]["target_slots"] += (float) ($t["target_slots"] ?? 0);
			$merged[$b]["expected_draws"] += (float) ($t["expected_draws"] ?? 0);
			$merged[$b]["prio_weight_sum"] += (float) ($t["prio_weight_sum"] ?? 0);
			$merged[$b]["count"]++;
		}
		$nTypes++;
	}
	if ($nTypes < 1) {
		return array(
			"topics" => array(),
			"pick_n" => $pickN,
			"total_pool" => 0,
			"scale_max" => 1,
			"blend_equal_pct" => 0,
		);
	}
	$topics = array();
	$totalPool = 0;
	$maxPool = 0;
	$maxTarget = 0;
	$maxExp = 0.0;
	foreach ($merged as $row) {
		$c = max(1, (int) $row["count"]);
		$pool = (int) round($row["pool"] / $c);
		$target = (int) round($row["target_slots"] / $c);
		$expDraw = $row["expected_draws"] / $c;
		$prioW = $row["prio_weight_sum"] / $c;
		$totalPool += $pool;
		$maxPool = max($maxPool, $pool);
		$maxTarget = max($maxTarget, $target);
		$maxExp = max($maxExp, $expDraw);
		$topics[] = array(
			"bereich" => $row["bereich"],
			"label" => $row["label"],
			"pool" => $pool,
			"target_slots" => $target,
			"expected_draws" => $expDraw,
			"avg_p" => $pool > 0 ? ($expDraw / $pool) : 0.0,
			"prio_weight_sum" => $prioW,
		);
	}
	usort($topics, static function ($a, $b) {
		if ($a["expected_draws"] !== $b["expected_draws"]) {
			return $b["expected_draws"] <=> $a["expected_draws"];
		}
		return strcmp((string) $a["label"], (string) $b["label"]);
	});
	$totalExp = 0.0;
	foreach ($topics as $t) {
		$totalExp += (float) $t["expected_draws"];
	}
	foreach ($topics as $ti => $t) {
		$topics[$ti]["pool_share"] = $totalPool > 0 ? ((int) $t["pool"]) / $totalPool : 0.0;
		$topics[$ti]["slot_share"] = $pickN > 0 ? ((int) $t["target_slots"]) / $pickN : 0.0;
		$topics[$ti]["exp_share"] = $totalExp > 0.0001 ? ((float) $t["expected_draws"]) / $totalExp : 0.0;
	}
	return array(
		"topics" => $topics,
		"pick_n" => $pickN,
		"total_pool" => $totalPool,
		"scale_max" => max(1, $maxPool, $maxTarget, (int) ceil($maxExp)),
		"blend_equal_pct" => 0,
	);
}

/** Farbpalette für Kuchendiagramme je Bereich (konsistent über alle Torten). */
function rkrk_dga_pie_chart_palette(): array {
	return array("#3d6b3a", "#5a8f57", "#7a9e78", "#94b894", "#166534", "#1e5a3a", "#0f766e", "#0284c7", "#64748b", "#475569");
}

/**
 * @param array<int, array<string, mixed>> $topics
 */
function rkrk_dga_topics_conic_gradient(array $topics, string $shareKey, array $palette, float $minPct = 0.05): string {
	$angle = 0.0;
	$parts = array();
	foreach ($topics as $i => $t) {
		if (!is_array($t)) {
			continue;
		}
		$share = isset($t[$shareKey]) ? (float) $t[$shareKey] * 100.0 : 0.0;
		if ($share < $minPct) {
			continue;
		}
		$col = $palette[$i % count($palette)];
		$parts[] = $col . " " . round($angle, 2) . "% " . round($angle + $share, 2) . "%";
		$angle += $share;
	}
	if (count($parts) < 1) {
		return "#cbd5e1 0% 100%";
	}
	return implode(", ", $parts);
}

/**
 * Ein Kuchendiagramm + Legende für einen Anteil-Schlüssel (pool_share, slot_share, exp_share).
 *
 * @param array<int, array<string, mixed>> $topics
 */
function rkrk_dga_echo_topic_pie_card(callable $h, array $topics, array $palette, string $shareKey, string $heading, string $caption, string $ariaLabel): void {
	$conic = rkrk_dga_topics_conic_gradient($topics, $shareKey, $palette);
	echo "<div class=\"da-prob-pie-card\">";
	echo "<h5 class=\"da-prob-pie-card-title\">" . $h($heading) . "</h5>";
	echo "<p class=\"da-prob-pie-card-cap\">" . $h($caption) . "</p>";
	echo "<div class=\"da-prob-pie\" style=\"background:conic-gradient(" . $h($conic) . ");\" role=\"img\" aria-label=\"" . $h($ariaLabel) . "\"></div>";
	echo "<ul class=\"da-prob-pie-legend\">";
	foreach ($topics as $i => $t) {
		if (!is_array($t)) {
			continue;
		}
		$col = $palette[$i % count($palette)];
		$pct = round((float) ($t[$shareKey] ?? 0) * 100, 1);
		echo "<li><span class=\"da-prob-pie-dot\" style=\"background:" . $h($col) . ";\"></span>";
		echo "<span>" . $h((string) ($t["label"] ?? "")) . " <strong>" . $h(number_format($pct, 1, ",", ".")) . " %</strong></span></li>";
	}
	echo "</ul></div>";
}

/**
 * SVG/HTML-Grafiken: Themenverteilung und Wichtigkeit (Monte-Carlo) im Fragen-Manager.
 *
 * @param list<array{key: string|int, prio: int, bereich: string}> $eligible
 * @param array<string, float> $probMap
 * @param array<string, mixed> $chartOpts blended, questions, setting, drone_types
 */
function rkrk_dga_echo_quiz_topic_distribution_charts(array $eligible, int $pickN, array $probMap, int $iterations = 5000, array $chartOpts = array()): void {
	$h = static function ($v) {
		return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
	};
	if ($pickN < 1 || count($eligible) < 1) {
		echo "<p class=\"da-prob-chart-empty\">Keine Daten für die Grafik (Pool leer oder «Fragen pro Durchgang» = 0).</p>";
		return;
	}
	$blendedChart = !empty($chartOpts["blended"])
		&& !empty($chartOpts["questions"])
		&& is_array($chartOpts["questions"])
		&& !empty($chartOpts["drone_types"])
		&& is_array($chartOpts["drone_types"])
		&& function_exists("rkrk_dga_aggregate_blended_topic_distribution_stats");
	if ($blendedChart) {
		$stats = rkrk_dga_aggregate_blended_topic_distribution_stats(
			$chartOpts["questions"],
			isset($chartOpts["setting"]) ? (string) $chartOpts["setting"] : "RKRS",
			$pickN,
			$chartOpts["drone_types"],
			$iterations
		);
	} else {
		$stats = rkrk_dga_aggregate_topic_distribution_stats($eligible, $pickN, $probMap);
	}
	$topics = isset($stats["topics"]) && is_array($stats["topics"]) ? $stats["topics"] : array();
	if (count($topics) < 1) {
		echo "<p class=\"da-prob-chart-empty\">Keine Themen/Kapitel im Auswahl-Pool.</p>";
		return;
	}
	$scaleMax = (int) ($stats["scale_max"] ?? 1);
	$barMaxW = 360;
	$rowH = 34;
	$topPad = 52;
	$chartH = $topPad + count($topics) * $rowH + 16;
	$palette = rkrk_dga_pie_chart_palette();

	echo "<div class=\"da-prob-charts\" role=\"region\" aria-label=\"Grafik Quiz-Auswahl nach Themen\">";
	echo "<h4 class=\"da-prob-chart-title\">Verteilung nach Themen (Kapitel / Bereich)</h4>";
	echo "<p class=\"da-prob-chart-intro\">Vergleich aus dem aktuellen Pool: <strong>Fragen im Pool</strong>, "
		. "<strong>Ziel-Plätze</strong> laut Gewichtung <code>Σ(P+1)²</code> je Bereich (Mindestboden 2 wenn möglich, sonst 1), "
		. "und <strong>erwartete Ziehungen</strong> pro Durchgang (Summe der Monte-Carlo-P(Anzeige), "
		. (int) $iterations . " Läufe — entspricht in der Summe etwa " . (int) $pickN . " Fragen)."
		. ($blendedChart ? " Bei «Alle Typen»: Mittelwert über die Drohnentypen." : "") . "</p>";

	echo "<div class=\"da-prob-chart-legend\">";
	echo "<span><i class=\"da-prob-swatch da-prob-swatch-pool\"></i> Fragen im Pool</span>";
	echo "<span><i class=\"da-prob-swatch da-prob-swatch-target\"></i> Ziel-Plätze (Formel)</span>";
	echo "<span><i class=\"da-prob-swatch da-prob-swatch-exp\"></i> Erwartete Ziehungen (MC)</span>";
	echo "</div>";

	echo "<svg class=\"da-prob-chart-svg\" viewBox=\"0 0 720 " . (int) $chartH . "\" role=\"img\" aria-labelledby=\"daProbChartBarsTitle\">";
	echo "<title id=\"daProbChartBarsTitle\">Balken: Pool, Ziel-Plätze und erwartete Ziehungen je Thema</title>";
	$labelX = 8;
	$barX = 168;
	foreach ($topics as $i => $t) {
		$y = $topPad + $i * $rowH;
		$lab = (string) $t["label"];
		if (function_exists("mb_strlen") && mb_strlen($lab) > 22) {
			$lab = mb_substr($lab, 0, 20) . "…";
		} elseif (strlen($lab) > 22) {
			$lab = substr($lab, 0, 20) . "…";
		}
		echo "<text x=\"" . $labelX . "\" y=\"" . ($y + 14) . "\" class=\"da-prob-svg-label\">" . $h($lab) . "</text>";
		$pool = (int) $t["pool"];
		$target = (int) $t["target_slots"];
		$exp = (float) $t["expected_draws"];
		$wPool = $scaleMax > 0 ? (int) round($barMaxW * $pool / $scaleMax) : 0;
		$wTarget = $scaleMax > 0 ? (int) round($barMaxW * $target / $scaleMax) : 0;
		$wExp = $scaleMax > 0 ? (int) round($barMaxW * $exp / $scaleMax) : 0;
		$tip = $h((string) $t["label"]) . ": Pool " . $pool . ", Ziel " . $target . ", Erwartet " . number_format($exp, 2, ",", ".");
		echo "<g class=\"da-prob-bar-group\" aria-label=\"" . $tip . "\">";
		echo "<rect x=\"" . $barX . "\" y=\"" . ($y + 2) . "\" width=\"" . max(0, $wPool) . "\" height=\"8\" class=\"da-prob-svg-bar-pool\" rx=\"2\"/>";
		echo "<rect x=\"" . $barX . "\" y=\"" . ($y + 13) . "\" width=\"" . max(0, $wTarget) . "\" height=\"8\" class=\"da-prob-svg-bar-target\" rx=\"2\"/>";
		echo "<rect x=\"" . $barX . "\" y=\"" . ($y + 24) . "\" width=\"" . max(0, $wExp) . "\" height=\"8\" class=\"da-prob-svg-bar-exp\" rx=\"2\"/>";
		echo "</g>";
		echo "<text x=\"" . ($barX + $barMaxW + 8) . "\" y=\"" . ($y + 10) . "\" class=\"da-prob-svg-val\">" . $pool . "</text>";
		echo "<text x=\"" . ($barX + $barMaxW + 8) . "\" y=\"" . ($y + 21) . "\" class=\"da-prob-svg-val\">" . $target . "</text>";
		echo "<text x=\"" . ($barX + $barMaxW + 8) . "\" y=\"" . ($y + 32) . "\" class=\"da-prob-svg-val\">" . $h(number_format($exp, 2, ",", ".")) . "</text>";
	}
	echo "</svg>";

	echo "<h4 class=\"da-prob-chart-title\" style=\"margin-top:20px;\">Kuchendiagramme je Bereich (Kapitel)</h4>";
	echo "<p class=\"da-prob-chart-intro\">Drei Torten mit derselben Farbzuordnung pro Thema: Anteil am <strong>Fragenpool</strong>, an den <strong>Ziel-Plätzen</strong> pro Durchgang und an der <strong>erwarteten Anzeige</strong> (Summe P(Anzeige) aus " . (int) $iterations . " Monte-Carlo-Läufen).</p>";
	echo "<div class=\"da-prob-pie-grid\">";
	rkrk_dga_echo_topic_pie_card(
		$h,
		$topics,
		$palette,
		"pool_share",
		"Fragen im Pool",
		"Anteil der aktiven Pool-Fragen je Bereich",
		"Anteil Fragen im Pool je Bereich"
	);
	rkrk_dga_echo_topic_pie_card(
		$h,
		$topics,
		$palette,
		"slot_share",
		"Ziel-Plätze",
		"Anteil der " . (int) $pickN . " Plätze laut Kapitel-Regel",
		"Anteil Ziel-Plätze je Bereich"
	);
	rkrk_dga_echo_topic_pie_card(
		$h,
		$topics,
		$palette,
		"exp_share",
		"P(Anzeige) im Durchgang",
		"Erwarteter Anteil gezogener Fragen (Monte-Carlo)",
		"Anteil erwartete Anzeige je Bereich"
	);
	echo "</div>";
	echo "<p class=\"da-prob-chart-intro\" style=\"margin-top:8px;\">ø P(Anzeige) pro einzelne Frage im Bereich (Priorität w=(P+1)² innerhalb des Kapitels) — siehe Spalte in der Tabelle.</p>";

	echo "<div class=\"da-prob-chart-table-wrap\"><table class=\"da-prob-chart-table\"><thead><tr>";
	echo "<th>Thema</th><th>Pool</th><th>Ziel-Plätze</th><th>Erw. Ziehungen</th><th>ø P(Anzeige)</th><th>Anteil MC</th></tr></thead><tbody>";
	foreach ($topics as $t) {
		echo "<tr><td>" . $h((string) $t["label"]) . "</td>";
		echo "<td>" . (int) $t["pool"] . "</td>";
		echo "<td>" . (int) $t["target_slots"] . "</td>";
		echo "<td>" . $h(number_format((float) $t["expected_draws"], 2, ",", ".")) . "</td>";
		echo "<td>" . $h(number_format((float) $t["avg_p"] * 100, 1, ",", ".")) . " %</td>";
		echo "<td>" . $h(number_format((float) $t["exp_share"] * 100, 1, ",", ".")) . " %</td></tr>";
	}
	echo "</tbody></table></div>";
	echo "</div>";
}

/**
 * quiz=… aus URL auf den kanonischen Key aus AssessmentListe.csv abbilden.
 * Zuerst exakter Key (inkl. Groß/Kleinschreibung), sonst Treffer auf Titel.
 *
 * @param array<int|string, array<string, mixed>> $quizList
 */
function rkrk_dga_resolve_quiz_param_to_key(array $quizList, string $rawQuiz): string {
	$rq = trim((string) $rawQuiz);
	if ($rq === "") {
		return "";
	}
	foreach ($quizList as $row) {
		if (!is_array($row) || !isset($row["Key"])) {
			continue;
		}
		$k = trim((string) $row["Key"]);
		if ($k !== "" && ($k === $rq || strcasecmp($k, $rq) === 0)) {
			return $k;
		}
	}
	foreach ($quizList as $row) {
		if (!is_array($row) || !isset($row["Key"])) {
			continue;
		}
		$k = trim((string) $row["Key"]);
		$t = trim((string) ($row["Titel"] ?? ""));
		if ($k !== "" && $t !== "" && strcasecmp($t, $rq) === 0) {
			return $k;
		}
	}
	return $rq;
}

/**
 * Query-Parameter für Papierformular (Quiz/Schulung).
 * Anzahl kommt aus AnzQuestions im Assessment, nicht aus der URL.
 *
 * @param array<string, scalar|null> $extra drone, paper_mode (sample|pool), paper_seed, show_solutions
 * @return array<string, string>
 */
function rkrk_dga_paper_form_query(string $quizKey, array $extra = array()): array {
	$q = array(
		"quiz" => trim($quizKey),
		"paper" => "1",
	);
	foreach ($extra as $k => $v) {
		if ($v === null || $v === "") {
			continue;
		}
		if ((string) $k === "pick_n") {
			continue;
		}
		$q[(string) $k] = (string) $v;
	}
	return rkrk_assess_merge_query_with_setting($q);
}

/** URL zum druckbaren Quiz-Papierformular. */
function rkrk_dga_paper_form_href(string $quizKey, array $extra = array()): string {
	return rkrk_assess_append_setting_href("rkrk_digitalassess.php?" . http_build_query(rkrk_dga_paper_form_query($quizKey, $extra)));
}

/**
 * Ob Quiz-Papier eingebettete Bilder zeigen soll.
 * Ohne paper_images in der URL: ein (Prüfungs- und Lösungspapier); explizit paper_images=0 zum Abschalten (Toner).
 *
 * @param array<string, mixed> $getParams typisch $_GET
 */
function rkrk_dga_paper_quiz_images_wanted(array $getParams): bool {
	if (isset($getParams["paper_images"])) {
		return trim((string) $getParams["paper_images"]) === "1";
	}
	return true;
}

/**
 * Toolbar-Links Mit/Ohne Bilder (Frage- und ggf. Lösungsbilder) für Quiz-Papier.
 *
 * @param array<string, mixed> $getParams typisch $_GET
 * @return array{with_href: string, without_href: string, mode: string}
 */
function rkrk_dga_paper_quiz_image_toggle_hrefs(array $getParams): array {
	$script = "rkrk_digitalassess.php";
	$base = $getParams;
	unset($base["pick_n"]);
	$with = rkrk_assess_merge_query_with_setting(array_merge($base, array("paper" => "1", "paper_images" => "1")));
	$without = rkrk_assess_merge_query_with_setting(array_merge($base, array("paper" => "1", "paper_images" => "0")));
	$on = rkrk_dga_paper_quiz_images_wanted($getParams);
	return array(
		"with_href" => rkrk_assess_append_setting_href($script . "?" . http_build_query($with)),
		"without_href" => rkrk_assess_append_setting_href($script . "?" . http_build_query($without)),
		"mode" => $on ? "with" : "without",
	);
}

/**
 * Toolbar-Links Prüfungsbogen / Lösungspapier für Quiz-Papier.
 *
 * @param array<string, mixed> $getParams typisch $_GET
 * @return array{exam_href: string, solution_href: string, mode: string}
 */
function rkrk_dga_paper_quiz_mode_toggle_hrefs(array $getParams): array {
	$script = "rkrk_digitalassess.php";
	$exam = $getParams;
	unset($exam["show_solutions"], $exam["pick_n"]);
	$exam = rkrk_assess_merge_query_with_setting($exam);
	if (!isset($exam["paper"])) {
		$exam["paper"] = "1";
	}
	$solBase = $getParams;
	unset($solBase["pick_n"]);
	$solution = rkrk_assess_merge_query_with_setting(array_merge($solBase, array("paper" => "1", "show_solutions" => "1")));
	$isSol = !empty($getParams["show_solutions"]);
	return array(
		"exam_href" => rkrk_assess_append_setting_href($script . "?" . http_build_query($exam)),
		"solution_href" => rkrk_assess_append_setting_href($script . "?" . http_build_query($solution)),
		"mode" => $isSol ? "solution" : "exam",
	);
}

/**
 * Drohnenfilter aus Paper- oder Quiz-Query (drone / Drone / Drohne).
 *
 * @param array<string, mixed> $getParams
 */
function rkrk_dga_paper_quiz_drone_from_request(array $getParams): string {
	foreach (array("drone", "Drone", "Drohne") as $k) {
		if (!isset($getParams[$k])) {
			continue;
		}
		$d = rkrk_assess_normalize_drone($getParams[$k]);
		if ($d !== "") {
			return $d;
		}
	}
	return "Alle";
}

/**
 * Drohnenfilter an Katalog-Schreibweise anbinden (wie Live-Quiz, Gross/Klein egal).
 * «Alle» / leer bleibt allgemein-only.
 *
 * @param array<int|string, array<string, mixed>> $questions
 */
function rkrk_dga_resolve_drone_filter_to_catalog(string $droneFilter, array $questions): string {
	$droneFilter = rkrk_assess_normalize_drone($droneFilter);
	if ($droneFilter === "" || $droneFilter === "Alle") {
		return "Alle";
	}
	$types = rkrk_dga_quiz_distinct_drone_types($questions);
	if (in_array($droneFilter, $types, true)) {
		return $droneFilter;
	}
	foreach ($types as $t) {
		if (strcasecmp((string) $t, $droneFilter) === 0) {
			return (string) $t;
		}
	}
	return $droneFilter;
}

/**
 * Drohnentyp-Optionen aus Fragen-CSV (Alle + eindeutige Drone-Werte), sortiert.
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @return list<string>
 */
function rkrk_dga_paper_quiz_drone_options_from_questions(array $questions): array {
	$opts = array("Alle");
	foreach ($questions as $qx) {
		if (!is_array($qx)) {
			continue;
		}
		$d = rkrk_assess_normalize_drone(isset($qx["Drone"]) ? $qx["Drone"] : "");
		if ($d !== "" && $d !== "Alle" && !in_array($d, $opts, true)) {
			$opts[] = $d;
		}
	}
	sort($opts, SORT_STRING);
	return $opts;
}

/**
 * Auswahlzeilen für Papier-Toolbar &lt;select&gt; (jeweils voller URL mit GET inkl. drone).
 *
 * @param array<string, mixed> $getParams
 * @param list<string> $droneOptions
 * @return list<array{drone: string, href: string, active: bool}>
 */
function rkrk_dga_paper_quiz_drone_picker_items(array $getParams, array $droneOptions): array {
	$script = "rkrk_digitalassess.php";
	$cur = function_exists("rkrk_dga_paper_quiz_drone_from_request")
		? rkrk_dga_paper_quiz_drone_from_request($getParams)
		: (isset($getParams["drone"]) ? rkrk_assess_normalize_drone($getParams["drone"]) : "Alle");
	if ($cur === "") {
		$cur = "Alle";
	}
	$out = array();
	foreach ($droneOptions as $d) {
		$dn = rkrk_assess_normalize_drone($d);
		$q = array_merge($getParams, array("paper" => "1", "drone" => $d));
		unset($q["Drone"], $q["Drohne"], $q["pick_n"]);
		$q = rkrk_assess_merge_query_with_setting($q);
		$out[] = array(
			"drone" => $d,
			"href" => rkrk_assess_append_setting_href($script . "?" . http_build_query($q)),
			"active" => (strcasecmp($dn, $cur) === 0),
		);
	}
	return $out;
}

/**
 * Fragenliste für Papierformular (gleicher Pool + Auswahlformel wie Live-Quiz).
 *
 * @param array<int|string, array<string, mixed>> $questions
 * @param array<string, mixed> $assessment
 * Anzahl = AnzQuestions im Assessment (wie Live-Quiz). opts: sample, full_pool, seed.
 * @return array{questions: list<array<string, mixed>>, pick_n: int, pool_count: int, mode: string, setting: string, drone: string}
 */
function rkrk_dga_build_paper_quiz_questions(array $questions, array $assessment, string $setting, string $droneFilter = "Alle", array $opts = array()): array {
	$setting = function_exists("rkrk_dga_quiz_setting_for_selection")
		? rkrk_dga_quiz_setting_for_selection($setting)
		: (trim($setting) !== "" ? trim($setting) : "RKRS");
	$droneFilter = function_exists("rkrk_dga_resolve_drone_filter_to_catalog")
		? rkrk_dga_resolve_drone_filter_to_catalog($droneFilter, $questions)
		: rkrk_assess_normalize_drone($droneFilter);
	if ($droneFilter === "") {
		$droneFilter = "Alle";
	}
	$eligible = rkrk_dga_build_quiz_eligible_pool($questions, $setting, $droneFilter);
	$poolCount = count($eligible);

	// Anzahl wie Digital-Quiz: AnzQuestions aus Assessment-Setup (Digital Assessment Manager).
	$anz = max(0, (int) ($assessment["AnzQuestions"] ?? 0));
	if ($anz < 1) {
		$anz = $poolCount;
	}
	$pickN = $poolCount > 0 ? min($poolCount, max(0, $anz)) : 0;

	$fullPool = !empty($opts["full_pool"]);
	$useSample = !$fullPool && (!isset($opts["sample"]) || !empty($opts["sample"]));

	$outRows = array();
	$mode = "empty";

	if ($poolCount < 1 || $pickN < 1) {
		return array(
			"questions" => array(),
			"pick_n" => 0,
			"pool_count" => $poolCount,
			"mode" => "empty",
			"setting" => $setting,
			"drone" => $droneFilter,
		);
	}

	if ($fullPool || !$useSample) {
		$mode = "pool";
		$sorted = $eligible;
		usort($sorted, static function ($a, $b) {
			$ba = isset($a["bereich"]) ? (string) $a["bereich"] : "_";
			$bb = isset($b["bereich"]) ? (string) $b["bereich"] : "_";
			if ($ba !== $bb) {
				return strcmp($ba, $bb);
			}
			$pa = isset($a["prio"]) ? (int) $a["prio"] : 0;
			$pb = isset($b["prio"]) ? (int) $b["prio"] : 0;
			if ($pa !== $pb) {
				return $pb <=> $pa;
			}
			return strcmp((string) $a["key"], (string) $b["key"]);
		});
		foreach ($sorted as $e) {
			$csvKey = $e["key"];
			if (!isset($questions[$csvKey]) || !is_array($questions[$csvKey])) {
				continue;
			}
			$row = $questions[$csvKey];
			$row["QID"] = $csvKey;
			$outRows[] = $row;
		}
	} else {
		$mode = "sample";
		$seed = isset($opts["seed"]) ? trim((string) $opts["seed"]) : "";
		if ($seed !== "" && is_numeric($seed)) {
			mt_srand((int) $seed);
		}
		$keysOrder = rkrk_dga_build_stochastic_quiz_order($eligible, $pickN);
		if ($seed !== "" && is_numeric($seed)) {
			mt_srand();
		}
		foreach ($keysOrder as $csvKey) {
			if (!isset($questions[$csvKey]) || !is_array($questions[$csvKey])) {
				continue;
			}
			$row = $questions[$csvKey];
			$row["QID"] = $csvKey;
			$outRows[] = $row;
		}
	}

	return array(
		"questions" => $outRows,
		"pick_n" => $useSample ? $pickN : count($outRows),
		"pool_count" => $poolCount,
		"mode" => $mode,
		"setting" => $setting,
		"drone" => $droneFilter,
	);
}

require_once __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_fragenkatalog_archive.inc.php";

/**
 * RKRS / RKRSplus / RKRSprev: graue CI (rkrk_assess_theme_rkrs.css), Query-Parameter für Navigation.
 */

/**
 * Skripte unter …/rkrs/assess/public/ (ohne gemeinsames Portal-template/ neben dem Skript).
 */
function rkrk_assess_is_detached_public_script(): bool {
	$sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$sf = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
	return (strpos($sn, '/rkrs/assess') !== false)
		|| (strpos($sf, '/rkrs/assess/') !== false);
}

/** flex.css / main_green / main_scratch — durch rkrk_assess_theme*.css ersetzt. */
function rkrk_assess_skip_legacy_portal_css(): bool {
	return rkrk_assess_is_detached_public_script();
}

function rkrk_assess_ci_rkrs_mode(): bool {
	if (isset($GLOBALS["onRKRS"]) && (bool)$GLOBALS["onRKRS"] === true) {
		return true;
	}
	// Backward compatibility in case some entry point sets the flag with different casing.
	if (isset($GLOBALS["OnRKRS"]) && (bool)$GLOBALS["OnRKRS"] === true) {
		return true;
	}
	if (isset($GLOBALS["AddURL"]) && in_array((string)$GLOBALS["AddURL"], ["RKRS", "RKRSplus", "RKRSprev"], true)) {
		return true;
	}
	$g = isset($_GET) && is_array($_GET) ? $_GET : [];
	foreach (["RKRS", "RKRSplus", "RKRSprev"] as $rkrsKey) {
		if (array_key_exists($rkrsKey, $g)) {
			return true;
		}
	}
	return false;
}

/**
 * NOM in der URL (?NOM / &NOM, auch POST): Seite ohne Menüs/Navigation.
 * Kein Hash-Gate-Bypass (im Gegensatz zu NOH).
 */
function rkrk_assess_nom_mode(): bool {
	if (isset($GLOBALS["onNom"]) && (bool)$GLOBALS["onNom"] === true) {
		return true;
	}
	if (isset($_GET) && is_array($_GET) && (isset($_GET["NOM"]) || array_key_exists("NOM", $_GET))) {
		return true;
	}
	if (isset($_POST) && is_array($_POST) && (isset($_POST["NOM"]) || array_key_exists("NOM", $_POST))) {
		return true;
	}
	return false;
}

/**
 * URL/Request hat ADMIN oder INSTR (Query, POST oder gesetzte Globals).
 */
function rkrk_assess_url_has_staff_flag(): bool {
	if (isset($_GET["ADMIN"]) || (isset($GLOBALS["onAdmin"]) && (bool)$GLOBALS["onAdmin"] === true)) {
		return true;
	}
	if (isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($GLOBALS["onInstr"]) && (bool)$GLOBALS["onInstr"] === true)) {
		return true;
	}
	return false;
}

/**
 * «Zurück zur Übersicht» (listall) ausblenden:
 * Mit URL-Parameter ADMIN oder INSTR immer anzeigen.
 * Sonst: NOM blendet Navigation aus; auf portal.rehkitzrettung.ch für Prüflinge ohne Staff-Flag.
 */
function rkrk_assess_hide_overview_back_link(): bool {
	if (function_exists("rkrk_assess_url_has_staff_flag") && rkrk_assess_url_has_staff_flag()) {
		return false;
	}
	if (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode()) {
		return true;
	}
	if (function_exists("rkrk_assess_hash_gate_is_portal_host") && rkrk_assess_hash_gate_is_portal_host()) {
		return true;
	}
	return false;
}

/** @return array<int, string> */
function rkrk_assess_carry_query_keys(): array {
	$keys = array();
	$channelKey = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
	if (in_array($channelKey, ["RKRS", "RKRSplus", "RKRSprev"], true)) {
		$keys[] = $channelKey;
	}
	if ((isset($_GET["ADMIN"]) || (isset($GLOBALS["onAdmin"]) && (bool)$GLOBALS["onAdmin"] === true))) {
		$keys[] = "ADMIN";
	}
	if ((isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($GLOBALS["onInstr"]) && (bool)$GLOBALS["onInstr"] === true))) {
		$keys[] = "INSTR";
	}
	if (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode()) {
		$keys[] = "NOM";
	}
	// NOH nur mittragen, wenn explizit in der URL — nicht automatisch wegen Schulung.
	if (
		function_exists("rkrk_assess_hash_gate_is_portal_host")
		&& rkrk_assess_hash_gate_is_portal_host()
		&& array_key_exists("NOH", $_GET)
	) {
		$keys[] = "NOH";
	}
	if (function_exists("rkrk_assess_carry_perspick_value") && rkrk_assess_carry_perspick_value() !== null) {
		$keys[] = "PersPick";
	}
	return array_values(array_unique($keys));
}

/**
 * @return list<string>
 */
function rkrk_assess_audience_segments($audienz): array {
	$aud = trim((string)$audienz);
	if ($aud === "") {
		return array();
	}
	$parts = preg_split("/[,;|\\/]+/", $aud, -1, PREG_SPLIT_NO_EMPTY);
	$out = array();
	foreach ($parts as $p) {
		$t = trim((string)$p);
		if ($t !== "") {
			$out[] = $t;
		}
	}
	return $out;
}

/**
 * Sichtbarkeit laut Spalte «Audienz» für den aktuellen Kanal ($setting).
 * Mehrere Gruppen: kommasepariert (optional ; | /). Pro Token exakter Abgleich (ohne Berücksichtigung der Gross/Kleinschreibung).
 * RKRSplus/RKRSprev: Token «RKRS» gilt CH-weit auch für abgeleitete Portale.
 */
function rkrk_assess_setting_sees_audience($setting, $audienz): bool {
	$setting = trim((string)$setting);
	$aud = trim((string)$audienz);
	// Leer fehlt in älteren CSV oft — gleiche Bedeutung wie «Alle»: für alle Gruppen / Kanäle (Quiz und Schulung).
	if ($aud === "") {
		return true;
	}
	$segments = rkrk_assess_audience_segments($aud);
	if (count($segments) === 0) {
		$segments = array($aud);
	}
	foreach ($segments as $seg) {
		if (strcasecmp($seg, "Alle") === 0) {
			return true;
		}
		if ($setting !== "" && strcasecmp($seg, $setting) === 0) {
			return true;
		}
	}
	if (preg_match("/^RKRS(prev|plus)?$/i", $setting) === 1) {
		foreach ($segments as $seg) {
			if (strcasecmp($seg, "RKRS") === 0) {
				return true;
			}
		}
		/* CH-Portal: RKRS, RKRSplus und RKRSprev gehören zur gleichen Familie (Deep-Links ohne ?RKRSplus=). */
		foreach ($segments as $seg) {
			if (preg_match("/^RKRS(prev|plus)?$/i", $seg) === 1) {
				return true;
			}
		}
	}
	return false;
}

/** Erlaubte Werte der Spalte «Status» in Fragen-CSV (Digital Assess). */
function rkrk_assess_question_status_options(): array {
	return array("neu", "prüfen", "geprüft", "überarbeitet", "aktiv", "inaktiv");
}

/**
 * Ob die Frage im interaktiven Quiz/Schulungsprüfung gezogen wird.
 * «Status» leer oder Spalte fehlt = wie «aktiv». Gesetzt: nur «aktiv» ist live (neu/prüfen/geprüft/inaktiv nicht).
 */
function rkrk_assess_question_is_quiz_live(array $row): bool {
	$st = strtolower(trim((string)($row["Status"] ?? "")));
	if ($st === "") {
		return true;
	}
	return $st === "aktiv";
}

/** Trägt aktive Query-Parameter über Links/Forms weiter (RKRS*, ADMIN, INSTR, PersPick bei explizitem on/off). */
function rkrk_assess_has_carry_setting(): bool {
	return !empty(rkrk_assess_carry_query_keys());
}

/** Hängt den aktuellen Setting-Query-Schlüssel an (?RKRS / &RKRSplus …), idempotent. */
/** Hidden-Feld für GET-Forms (Filter), damit ?RKRS bei Submit erhalten bleibt. */
function rkrk_assess_echo_setting_hidden_input(): void {
	if (!rkrk_assess_has_carry_setting()) {
		return;
	}
	foreach (rkrk_assess_carry_query_keys() as $kRaw) {
		$k = htmlspecialchars((string)$kRaw, ENT_QUOTES, "UTF-8");
		// Nicht-leerer Wert: einige Proxys/OS streichen RKRS=&… ; Parameter bleibt sonst bestehen.
		if (in_array((string)$kRaw, array("RKRS", "RKRSplus", "RKRSprev"), true)) {
			$hv = htmlspecialchars("1", ENT_QUOTES, "UTF-8");
		} elseif ((string)$kRaw === "PersPick") {
			$pv = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
			$hv = htmlspecialchars($pv !== null ? $pv : "", ENT_QUOTES, "UTF-8");
		} else {
			$hv = htmlspecialchars("", ENT_QUOTES, "UTF-8");
		}
		echo "<input type='hidden' name='" . $k . "' value='" . $hv . "'>\n";
	}
}

/**
 * Für http_build_query: Setting-Schlüssel (RKRS …) mit leerem Wert ergänzen.
 * @param array<string, string|int|float|bool|null> $params
 * @return array<string, string|int|float|bool|null>
 */
function rkrk_assess_merge_query_with_setting(array $params): array {
	if (!rkrk_assess_has_carry_setting()) {
		return $params;
	}
	foreach (rkrk_assess_carry_query_keys() as $key) {
		if ($key === "" || array_key_exists($key, $params)) {
			continue;
		}
		if (in_array((string)$key, array("RKRS", "RKRSplus", "RKRSprev"), true)) {
			$params[$key] = "1";
		} elseif ((string)$key === "PersPick") {
			$pv = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
			if ($pv !== null) {
				$params[$key] = $pv;
			}
		} else {
			$params[$key] = "";
		}
	}
	return $params;
}

function rkrk_assess_append_setting_href(string $href): string {
	$keys = rkrk_assess_carry_query_keys();
	if (empty($keys)) {
		return $href;
	}
	$hash = "";
	$u = $href;
	$p = strpos($u, "#");
	if ($p !== false) {
		$hash = substr($u, $p);
		$u = substr($u, 0, $p);
	}
	if ($u === "") {
		$u = "?";
	}
	foreach ($keys as $key) {
		if (preg_match("/[?&]" . preg_quote((string)$key, "/") . "(?:=|&|$)/", $u) === 1) {
			continue;
		}
		$sep = (strpos($u, "?") !== false) ? "&" : "?";
		if (in_array((string)$key, array("RKRS", "RKRSplus", "RKRSprev"), true)) {
			$u .= $sep . $key . "=1";
		} elseif ((string)$key === "PersPick") {
			$pv = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
			if ($pv !== null) {
				$u .= $sep . "PersPick=" . rawurlencode($pv);
			}
		} else {
			$u .= $sep . $key;
		}
	}
	return $u . $hash;
}

/**
 * Hängt fehlende Carry-Parameter (ADMIN, INSTR, NOM, RKRS*, PersPick) an urladder-Strings an.
 * Wird u. a. in rkrk_digitalassess.php für JS-generierte Quiz-Links genutzt.
 */
function rkrk_assess_urladder_merge_carry(string $urladder): string {
	if (!function_exists("rkrk_assess_carry_query_keys")) {
		return $urladder;
	}
	$urladder = (string) $urladder;
	foreach (rkrk_assess_carry_query_keys() as $keyRaw) {
		$key = (string) $keyRaw;
		if ($key === "") {
			continue;
		}
		if (preg_match("/(?:^|[?&])" . preg_quote($key, "/") . "(?:=|&|$)/", $urladder) === 1) {
			continue;
		}
		$fragment = "";
		if (in_array($key, array("RKRS", "RKRSplus", "RKRSprev"), true)) {
			$fragment = $key;
		} elseif ($key === "PersPick") {
			$pv = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
			if ($pv === null) {
				continue;
			}
			$fragment = "PersPick=" . rawurlencode($pv);
		} else {
			$fragment = $key;
		}
		$urladder .= ($urladder !== "" ? "&" : "") . $fragment;
	}
	return $urladder;
}

/** &ADMIN&INSTR&RKRS=1 … für clientseitige URL-Bausteine (Quiz-Link-Generator). */
function rkrk_assess_carry_url_suffix(): string {
	if (!function_exists("rkrk_assess_carry_query_keys") || !rkrk_assess_has_carry_setting()) {
		return "";
	}
	$parts = array();
	foreach (rkrk_assess_carry_query_keys() as $keyRaw) {
		$key = (string) $keyRaw;
		if ($key === "") {
			continue;
		}
		if (in_array($key, array("RKRS", "RKRSplus", "RKRSprev"), true)) {
			$parts[] = $key . "=1";
		} elseif ($key === "PersPick") {
			$pv = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
			if ($pv !== null) {
				$parts[] = "PersPick=" . rawurlencode($pv);
			}
		} else {
			$parts[] = $key;
		}
	}
	if (count($parts) === 0) {
		return "";
	}
	return "&" . implode("&", $parts);
}

/**
 * Listenansicht: N-Flag-Felder (Name) und Standard-Pilot(en) in einer Spalte,
 * ohne doppelte Anzeige wenn der Pilot-Text schon in einem Namens-Feld vorkommt.
 *
 * @param list<string> $nameParts
 */
function rkrk_assess_list_merge_name_pilot_cell(array $nameParts, string $pilot): string {
	$pilotTrim = trim((string)$pilot);
	$segments = array();
	foreach ($nameParts as $p) {
		$p = trim((string)$p);
		if ($p !== "") {
			$segments[] = $p;
		}
	}
	if ($pilotTrim !== "") {
		$dup = false;
		foreach ($segments as $seg) {
			if (mb_strtolower($seg, "UTF-8") === mb_strtolower($pilotTrim, "UTF-8")) {
				$dup = true;
				break;
			}
			if (mb_stripos($seg, $pilotTrim, 0, "UTF-8") !== false || mb_stripos($pilotTrim, $seg, 0, "UTF-8") !== false) {
				$dup = true;
				break;
			}
		}
		if (!$dup) {
			$segments[] = $pilotTrim;
		}
	}
	return implode(" · ", $segments);
}

/** Für Filter „Pilot enthält“ / „Name/Pilot enthält“: alle relevanten Texte. */
function rkrk_assess_list_name_pilot_filter_haystack(array $nameParts, string $pilot): string {
	return trim(implode(" ", array_map("strval", $nameParts)) . " " . trim((string)$pilot));
}

/**
 * Listen-Spalte «Information»: (g)/(o)/(r) ausblenden, Statuswort fett und farbig (HTML).
 */
function rkrk_assess_format_list_info_ampel_html(string $plain): string {
	$s = htmlspecialchars((string)$plain, ENT_QUOTES, "UTF-8");
	if ($s === "") {
		return "";
	}
	$re = "/(NICHT\\s+[A-Za-zÄÖÜäöüß0-9]+(?:\\s+[A-Za-zÄÖÜäöüß0-9]+){0,2}|[A-Za-zÄÖÜäöüß0-9]+(?:\\s+[A-Za-zÄÖÜäöüß0-9]+){0,3})\\s*\\(([gro])\\)/iu";
	$out = preg_replace_callback(
		$re,
		static function (array $m): string {
			$word = $m[1];
			$code = strtolower((string)$m[2]);
			$class = $code === "g" ? "rk-list-info--g" : ($code === "r" ? "rk-list-info--r" : "rk-list-info--o");
			return "<span class=\"rk-list-info-status " . $class . "\">" . $word . "</span>";
		},
		$s
	);
	return is_string($out) ? $out : $s;
}

/**
 * Pfadabsoluter URL-Pfad (beginnt mit /) aus SCRIPT_NAME-Verzeichnis + relativem Pfad.
 * Verhindert falsche Auflösung, wenn ein gemeinsamer Header &lt;base href&gt; setzt.
 */
function rkrk_assess_resolve_web_path_from_script(string $relative): string {
	$sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$sn = preg_replace('#/+#', '/', $sn);
	if ($sn === '' || $sn === '/') {
		return $relative;
	}
	$dir = dirname($sn);
	if ($dir === '/' || $dir === '.' || $dir === '\\') {
		return $relative;
	}
	$trim = trim($dir, '/');
	$base = $trim === '' ? array() : explode('/', $trim);
	$rel = str_replace('\\', '/', $relative);
	$relParts = preg_split('#/#', $rel, -1, PREG_SPLIT_NO_EMPTY);
	$stack = $base;
	foreach ($relParts as $p) {
		if ($p === '.') {
			continue;
		}
		if ($p === '..') {
			if (!empty($stack)) {
				array_pop($stack);
			}
			continue;
		}
		$stack[] = $p;
	}
	$out = '/' . implode('/', $stack);
	return preg_replace('#/+#', '/', $out);
}

/**
 * Unter …/rkropen/rkrs/assess/… verweist ../template auf anderes flex.css als test/assess.
 * Relativer Pfad zur gleichen Datei wie von test/assess (../template/flex.css).
 */
function rkrk_assess_rkrs_assess_parity_flex_relative(): string {
	$sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	if (strpos($sn, '/rkrs/assess') === false) {
		return '';
	}
	$dir = trim(dirname($sn), '/');
	$pos = stripos($dir, '/rkropen/');
	if ($pos === false) {
		return '';
	}
	$suffix = substr($dir, $pos + strlen('/rkropen/'));
	$segments = $suffix === '' ? array() : explode('/', $suffix);
	$n = count($segments);
	if ($n < 1) {
		return '';
	}
	return str_repeat('../', $n) . 'test/template/flex.css';
}

/**
 * Web-Pfad zum zentralen favicon-Ordner (…/rkropen/favicon/), unabhängig von test/assess vs. rkrs/assess/public.
 */
function rkrk_assess_favicon_base_web_path(): string {
	static $cached = null;
	if ($cached !== null) {
		return $cached;
	}
	$sn = preg_replace('#/+#', '/', str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
	$parts = $sn === '' ? array() : explode('/', trim($sn, '/'));
	foreach ($parts as $i => $p) {
		if (strcasecmp($p, 'rkropen') === 0) {
			$cached = '/' . implode('/', array_slice($parts, 0, $i + 1)) . '/favicon/';
			return $cached;
		}
	}
	if (function_exists('rkrk_assess_resolve_web_path_from_script')) {
		$cached = rkrk_assess_resolve_web_path_from_script('../../favicon/');
		return $cached;
	}
	$cached = '../favicon/';
	return $cached;
}

/**
 * Absoluter href für Favicon-Datei (Leerzeichen in Dateinamen werden encodiert).
 */
function rkrk_assess_favicon_asset_href(string $relativePath): string {
	$relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
	if (preg_match('#^images/#i', $relativePath)) {
		global $folderoffset;
		$fo = isset($folderoffset) ? (string)$folderoffset : '../';
		return rkrk_assess_resolve_web_path_from_script($fo . $relativePath);
	}
	$base = rkrk_assess_favicon_base_web_path();
	if ($base !== '' && $base[0] === '/') {
		$segments = explode('/', $relativePath);
		$enc = array();
		foreach ($segments as $seg) {
			if ($seg === '') {
				continue;
			}
			$enc[] = rawurlencode($seg);
		}
		return $base . implode('/', $enc);
	}
	return rkrk_assess_resolve_web_path_from_script($base . $relativePath);
}

/**
 * RKRS-Favicon-Datei (liegt unter …/rkropen/favicon/, nicht unter assess/../images/).
 */
function rkrk_assess_rkrs_favicon_ico_basename(): string {
	$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
	if (stripos($host, 'tchr-frog.synology.me') !== false) {
		return 'RKRS_onrkrk.ico';
	}
	return 'RKRS.ico';
}

/**
 * Portal: Favicon liegt eine Ebene über public/ → ../RKRS.ico
 * (absolut über SCRIPT_NAME, damit <base href> es nicht verbiegt).
 */
function rkrk_assess_portal_rkrs_favicon_href(): string {
	if (function_exists('rkrk_assess_resolve_web_path_from_script')) {
		return rkrk_assess_resolve_web_path_from_script('../RKRS.ico');
	}
	$scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
	$scriptDir = rtrim($scriptDir, '/');
	if ($scriptDir === '' || $scriptDir === '.') {
		return '../RKRS.ico';
	}
	return preg_replace('#/[^/]+$#', '', $scriptDir) . '/RKRS.ico';
}

/**
 * Favicon-<link>-Tags als String (für Druck-/Papier-/Report-HTML ohne headerpage()).
 * Portal: ../RKRS.ico · Frog/RKRS: RKRS_onrkrk.ico · sonst RKRK (rkrk.ico + PNG).
 */
function rkrk_assess_favicon_link_tags_html(): string {
	if (!function_exists('rkrk_favicon_link_tags_html')) {
		$fav = dirname(__DIR__) . '/scripts/rkrk_favicon.php';
		if (is_file($fav)) {
			include_once $fav;
		}
	}
	if (function_exists('rkrk_favicon_link_tags_html')) {
		return rkrk_favicon_link_tags_html();
	}
	$h = static function (string $path): string {
		return htmlspecialchars(rkrk_assess_favicon_asset_href($path), ENT_QUOTES, 'UTF-8');
	};
	if (rkrk_assess_ci_rkrs_mode() || (!empty($GLOBALS['onRKRS']) && (bool)$GLOBALS['onRKRS'])) {
		if (function_exists('rkrk_assess_is_portal_rkrs_host') && rkrk_assess_is_portal_rkrs_host()) {
			$href = htmlspecialchars(rkrk_assess_portal_rkrs_favicon_href(), ENT_QUOTES, 'UTF-8');
			return '<link rel="icon" type="image/x-icon" href="' . $href . '">' . "\n";
		}
		$ico = rkrk_assess_rkrs_favicon_ico_basename();
		return '<link rel="icon" type="image/x-icon" href="' . $h($ico) . '">' . "\n"
			. '<link rel="icon" type="image/png" sizes="16x16" href="' . $h('RKRS_onrkrs.png') . '">' . "\n";
	}
	return '<link rel="icon" type="image/x-icon" href="' . $h('rkrk.ico') . '">' . "\n"
		. '<link rel="icon" type="image/png" sizes="32x32" href="' . $h('favicon-32x32.png') . '">' . "\n"
		. '<link rel="icon" type="image/png" sizes="16x16" href="' . $h('favicon-16x16.png') . '">' . "\n";
}

/**
 * Favicon mit absolutem Pfad (SCRIPT_NAME), damit <base href> in headerpage() die Icons nicht bricht.
 * Einmal pro Request; Standalone-HTML nutzt rkrk_assess_favicon_link_tags_html().
 */
function rkrk_assess_emit_favicon_link_tags(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	$GLOBALS['rkrk_assess_favicon_emitted'] = true;
	if (function_exists('rkrk_favicon_emit_link_tags')) {
		rkrk_favicon_emit_link_tags();
		return;
	}
	echo rkrk_assess_favicon_link_tags_html();
}


/**
 * Dokument-/HTTP-Zeichensatz auf UTF-8 (Umlaute korrekt anzeigen).
 * Einmal pro Request; vor headerpage() und HTML-Ausgabe wirksam.
 */
function rkrk_assess_emit_document_utf8(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	@ini_set("default_charset", "UTF-8");
	if (function_exists("mb_internal_encoding")) {
		@mb_internal_encoding("UTF-8");
	}
	if (function_exists("mb_http_output")) {
		@mb_http_output("UTF-8");
	}
	if (!headers_sent()) {
		$ct = "";
		foreach (headers_list() as $h) {
			if (stripos($h, "Content-Type:") === 0) {
				$ct = $h;
				break;
			}
		}
		if ($ct === "" || stripos($ct, "charset=") === false) {
			header("Content-Type: text/html; charset=UTF-8");
		}
	}
}


function rkrk_assess_host_is_rehkitzrettung(): bool {
	// Nicht strpos "rehkitzrettung.ch" allein — matcht auch drohnenrehkitzrettung.ch.
	if (function_exists('rkrk_assess_is_portal_rkrs_host') && rkrk_assess_is_portal_rkrs_host()) {
		return true;
	}
	if (function_exists('rkrk_assess_is_rkrs_copyright_path') && rkrk_assess_is_rkrs_copyright_path()) {
		return true;
	}
	$host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
	if ($host === '') {
		return false;
	}
	if (strpos($host, 'drohnenrehkitzrettung.ch') !== false) {
		return false;
	}
	return (bool) preg_match('/(^|\.)rehkitzrettung\.ch$/i', $host);
}

/** Kontextmenü / DevTools-Kürzel (wie body oncontextmenu in digitalassess). */
function rkrk_assess_should_block_context_menu(): bool {
	if (!rkrk_assess_host_is_rehkitzrettung()) {
		return false;
	}
	if (isset($_GET['TEST'])) {
		return false;
	}
	return true;
}

function rkrk_assess_emit_context_menu_block_script(): void {
	static $done = false;
	if ($done || !rkrk_assess_should_block_context_menu()) {
		return;
	}
	$done = true;
	echo "<script>\n";
	echo "(function(){\n";
	echo "document.addEventListener('contextmenu',function(e){e.preventDefault();},{capture:true});\n";
	echo "document.addEventListener('keydown',function(e){\n";
	echo "var k=e.key||'';\n";
	echo "if(k==='F12'){e.preventDefault();return;}\n";
	echo "if(e.ctrlKey&&e.shiftKey&&(k==='I'||k==='J'||k==='C'||k==='i'||k==='j'||k==='c')){e.preventDefault();return;}\n";
	echo "if(e.ctrlKey&&(k==='u'||k==='U')){e.preventDefault();return;}\n";
	echo "},{capture:true});\n";
	echo "})();\n";
	echo "</script>\n";
}
function rkrk_assess_theme_link_tags(): void {
	// Favicon früh setzen: Login/GrantAccess endet oft vor headerpage(); sonst 404 auf …/assess/favicon.ico.
	// emit_favicon ist einmalig (static) — headerpage() überspringt danach doppelte Tags.
	if (function_exists('rkrk_assess_emit_favicon_link_tags')) {
		rkrk_assess_emit_favicon_link_tags();
	}
	$th = htmlspecialchars(rkrk_assess_resolve_web_path_from_script('rkrk_assess_theme.css'), ENT_QUOTES, 'UTF-8');
	echo "<link href=\"" . $th . "?v=20260923menu\" rel=\"stylesheet\" type=\"text/css\">\n";
	if (rkrk_assess_ci_rkrs_mode()) {
		$thr = htmlspecialchars(rkrk_assess_resolve_web_path_from_script('rkrk_assess_theme_rkrs.css'), ENT_QUOTES, 'UTF-8');
		echo "<link href=\"" . $thr . "\" rel=\"stylesheet\" type=\"text/css\">\n";
	}
	rkrk_assess_emit_context_menu_block_script();
}


function rkrk_assess_template_asset_href(string $assetFile): string {
	$assetFile = ltrim($assetFile, "/");
	// flex.css: test/assess und rkrs/assess/public sollen dieselbe …/test/template/flex.css nutzen
	if ($assetFile === "flex.css") {
		$rel = '../template/' . $assetFile;
		if (rkrk_assess_ci_rkrs_mode()) {
			$parity = rkrk_assess_rkrs_assess_parity_flex_relative();
			if ($parity !== '') {
				$rel = $parity;
			}
		}
		return rkrk_assess_resolve_web_path_from_script($rel);
	}
	$prefix = rkrk_assess_ci_rkrs_mode() ? "../../template/" : "../template/";
	return $prefix . $assetFile;
}

/**
 * Nach template/flex.css oder nach großen Inline-style-Blöcken erneut einbinden,
 * damit RKRS-Grautöne im Cascade gewinnen (flex/digital setzen sonst :root wieder auf Grün).
 */
function rkrk_assess_theme_rkrs_layer_link(): void {
	if (!rkrk_assess_ci_rkrs_mode()) {
		return;
	}
	$href = htmlspecialchars(rkrk_assess_resolve_web_path_from_script('rkrk_assess_theme_rkrs.css'), ENT_QUOTES, 'UTF-8');
	echo "<link href=\"" . $href . "\" rel=\"stylesheet\" type=\"text/css\">\n";
}

/**
 * Client: hidden-Felder in allen Forms + interne Links mit Setting — ergänzt serverseitige Links.
 */
function rkrk_assess_emit_setting_carry_script(): void {
	if (
		(isset($_GET['mode']) && (string) $_GET['mode'] === 'paperform')
		|| (isset($_GET['paper']) && (string) $_GET['paper'] === '1' && trim((string) ($_GET['quiz'] ?? '')) !== '')
	) {
		return;
	}
	if (!rkrk_assess_has_carry_setting()) {
		return;
	}
	$keys = rkrk_assess_carry_query_keys();
	$keysJson = json_encode(array_values($keys), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
	$ppCarry = function_exists("rkrk_assess_carry_perspick_value") ? rkrk_assess_carry_perspick_value() : null;
	$ppJson = json_encode($ppCarry, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
	echo "<script>\n";
	echo "(function(){var P=" . $keysJson . ";var PP=" . $ppJson . ";if(!Array.isArray(P)||!P.length)return;\n";
	echo "function escRe(s){return s.replace(/[\\\\^$*+?.()|[\\]{}]/g,'\\\\$&');}\n";
	echo "function appendU(u){if(!u||u.charAt(0)==='#')return u;var i=u.indexOf('#'),h='';if(i>=0){h=u.substring(i);u=u.substring(0,i);}\n";
	echo "if(u===''){u='?';}P.forEach(function(k){if(!k)return;if(new RegExp('[?&]'+escRe(k)+'(?:=|&|$)').test(u))return;var s=u.indexOf('?')>=0?'&':'?',suf=(k==='RKRS'||k==='RKRSplus'||k==='RKRSprev')?'=1':(k==='PersPick'&&(PP==='on'||PP==='off'))?('='+encodeURIComponent(PP)):'';u=u+s+k+suf;});return u+h;}\n";
	echo "function internal(h){if(!h)return false;if(h.charAt(0)==='?')return true;var x=String(h).toLowerCase();\n";
	echo "if(x.indexOf('mailto:')===0||x.indexOf('javascript:')===0)return false;\n";
	echo "if(/:\\/\\//.test(x)&&x.indexOf(location.origin)!==0)return false;\n";
	echo "return /(^|\\.\\/|\\/)(rkrk_[a-z0-9_]+\\.php)(\\?|$|#)/i.test(h)||x.indexOf('rkrk_')>=0;}\n";
	echo "window.rkrkAssessAppendCarryHref=appendU;\n";
	echo "document.addEventListener('DOMContentLoaded',function(){\n";
	echo "document.querySelectorAll('form').forEach(function(f){\n";
	echo "P.forEach(function(k){if(!k)return;if(f.querySelector(\"input[name='\"+k+\"']\"))return;var hi=document.createElement('input');hi.type='hidden';hi.name=k;hi.value=(k==='RKRS'||k==='RKRSplus'||k==='RKRSprev')?'1':(k==='PersPick'&&(PP==='on'||PP==='off')?PP:'');f.appendChild(hi);});\n";
	echo "});\n";
	echo "document.querySelectorAll('a[href]').forEach(function(a){\n";
	echo "var h=a.getAttribute('href');if(!internal(h))return;a.setAttribute('href',appendU(h));\n";
	echo "});\n";
	echo "});\n";
	echo "})();\n";
	echo "</script>\n";
}

/**
 * Daten-Tier für RKRK-Assess-/Live-Daten (Pfad-Matrix in rkrk_assess_data_path_relative_suffix).
 * Entspricht $setting aus den GET-Blöcken oben: TEST | RKRK | sonst default (RKRS*, PUB, …).
 *
 * @return 'test'|'rkrk'|'default'
 */
function rkrk_assess_data_tier_key(): string {
	$s = isset($GLOBALS["setting"]) ? (string)$GLOBALS["setting"] : "RKRK";
	if ($s === "TEST") {
		return "test";
	}
	if ($s === "RKRK") {
		return "rkrk";
	}
	return "default";
}

/**
 * RKRS-Einbettung: welcher Query-Kanal aktiv ist (leer = Drohnen-/Standard-Assess ohne RKRS-GET).
 *
 * @return ''|'RKRS'|'RKRSplus'|'RKRSprev'
 */
function rkrk_assess_rkrs_channel_key(): string {
	$k = isset($GLOBALS["AddURL"]) ? (string)$GLOBALS["AddURL"] : "";
	if ($k === "RKRS" || $k === "RKRSplus" || $k === "RKRSprev") {
		return $k;
	}
	return "";
}

/**
 * Relatives Suffix unterhalb von $RKRKRelRKRKDataLocation (…/rkrk/) für Assess-/Live-Daten.
 * Leerstring = bisheriges Verhalten (alles direkt unter …/rkrk/).
 *
 * Matrix: [Daten-Tier][RKRS-Kanal] → Pfadfragment mit trailing slash oder ''.
 * Hier zentral pflegen, wenn TEST / RKRSplus / … eigene Verzeichnisse erhalten.
 *
 * @return string z. B. "" oder "data-instances/rkrsplus/"
 */
function rkrk_assess_data_path_relative_suffix(): string {
	$tier = rkrk_assess_data_tier_key();
	$ch = rkrk_assess_rkrs_channel_key();
	static $matrix = [
		"test" => [
			"" => "",
			"RKRS" => "",
			"RKRSplus" => "",
			"RKRSprev" => "",
		],
		"rkrk" => [
			"" => "",
			"RKRS" => "",
			"RKRSplus" => "",
			"RKRSprev" => "",
		],
		"default" => [
			"" => "",
			"RKRS" => "",
			"RKRSplus" => "",
			"RKRSprev" => "",
		],
	];
	if (!isset($matrix[$tier][$ch])) {
		return "";
	}
	return (string)$matrix[$tier][$ch];
}

/**
 * Filesystem-Wurzel für RKRK data/assess und data/liveassess.
 * Nur von PHP-Dateien unter assess/ zu verwenden ($rkrkAssessFsDataRoot); andere Eintritte
 * (z. B. rkrk_quiz_admin.php außerhalb assess/) bleiben bei $RKRKRelRKRKDataLocation aus rkrk_gen.
 */
function rkrk_assess_resolve_fs_data_root(): string {
	$base = "";
	if (isset($GLOBALS["RKRKRelRKRKDataLocation"])) {
		$base = (string)$GLOBALS["RKRKRelRKRKDataLocation"];
	}
	$suffix = rkrk_assess_data_path_relative_suffix();
	if ($suffix !== "") {
		$base = $base . $suffix;
	}
	if (function_exists("rkrk_normalize_rkropen_path")) {
		return rkrk_normalize_rkropen_path($base);
	}
	return rtrim(str_replace("\\", "/", $base), "/") . "/";
}

/**
 * Einmaliges CSS: horizontale Portal-Leiste mit Dropdowns (Mega-Menü-Stil), Farben über Theme-:root (RKRS via rkrk_assess_theme_rkrs.css).
 */
function rkrk_assess_portal_nav_styles_once(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	echo "<style>\n";
	echo ".rkrk-portal-nav-slot{width:100%;box-sizing:border-box;margin:0 0 14px 0;position:relative;z-index:200;}\n";
	echo ".rkrk-portal-mega{width:100%;box-sizing:border-box;position:relative;z-index:200;margin:0;}\n";
	echo ".rkrk-portal-bar{display:flex;flex-wrap:wrap;align-items:stretch;list-style:none;margin:0;padding:0 2px;background:var(--brand-dark,#48673A);border-radius:0;box-shadow:0 1px 0 rgba(255,255,255,.08) inset;gap:0;overflow:visible;}\n";
	echo ".rkrk-portal-cell{flex:0 0 auto;margin:0;padding:0;position:relative;min-width:max-content;z-index:1;}\n";
	echo ".rkrk-portal-cell:hover,.rkrk-portal-cell:focus-within,.rkrk-portal-cell--open{z-index:220;}\n";
	echo ".rkrk-portal-dd{margin:0;display:flex;flex-direction:column;width:100%;min-width:max-content;}\n";
	echo ".rkrk-portal-tab{cursor:pointer;margin:0;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 12px;font-weight:700;font-size:0.8rem;line-height:1.15;color:rgba(255,255,255,.95);background:transparent;border:0;border-radius:0;border-bottom:2px solid transparent;box-sizing:border-box;text-align:center;user-select:none;-webkit-user-select:none;transition:color .12s ease,border-color .12s ease,background .12s ease;font-family:inherit;white-space:nowrap;}\n";
	echo ".rkrk-portal-tab::after{content:'';width:0;height:0;border-left:3px solid transparent;border-right:3px solid transparent;border-top:4px solid rgba(255,255,255,.72);flex-shrink:0;transition:transform .15s ease;}\n";
	echo ".rkrk-portal-cell:hover > .rkrk-portal-dd > .rkrk-portal-tab,.rkrk-portal-cell:focus-within > .rkrk-portal-dd > .rkrk-portal-tab,.rkrk-portal-cell--open > .rkrk-portal-dd > .rkrk-portal-tab{background:transparent;color:#fff;border-bottom-color:rgba(255,255,255,.85);}\n";
	/* Pfeil nur bei wirklich geöffnetem Panel drehen (nicht nur :focus-within) */
	echo ".rkrk-portal-cell--open > .rkrk-portal-dd > .rkrk-portal-tab::after{transform:rotate(180deg);}\n";
	echo ".rkrk-portal-tab:hover,.rkrk-portal-tab:focus-visible{background:rgba(255,255,255,.06);outline:none;border-radius:0;}\n";
	echo ".rkrk-portal-home{cursor:pointer;margin:0;display:inline-flex;align-items:center;justify-content:center;padding:8px 12px;font-weight:700;font-size:0.8rem;line-height:1.15;color:rgba(255,255,255,.95);background:transparent;border:0;border-radius:0;border-bottom:2px solid transparent;box-sizing:border-box;text-align:center;text-decoration:none;font-family:inherit;white-space:nowrap;min-height:100%;}\n";
	echo ".rkrk-portal-home:hover,.rkrk-portal-home:focus-visible{background:rgba(255,255,255,.06);outline:none;color:#fff;border-bottom-color:rgba(255,255,255,.85);}\n";
	echo ".rkrk-portal-panel{position:absolute;left:0;top:100%;margin:0;z-index:230;width:max-content;min-width:100%;max-width:min(100vw - 16px,28rem);padding:2px 0;background:var(--card,#fff);border:1px solid var(--toolbar-bd,var(--border,#d5e0d2));border-radius:0;box-shadow:var(--shadow,0 6px 18px rgba(0,0,0,.12));opacity:0;visibility:hidden;pointer-events:none;transform:translateY(-2px);transition:opacity .1s ease,visibility .1s ease,transform .1s ease;list-style:none;}\n";
	echo ".rkrk-portal-cell:hover .rkrk-portal-panel,.rkrk-portal-cell:focus-within .rkrk-portal-panel,.rkrk-portal-cell--open .rkrk-portal-panel,.rkrk-portal-tab[aria-expanded=\"true\"] + .rkrk-portal-panel{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(0);}\n";
	echo ".rkrk-portal-cell:last-child .rkrk-portal-panel{left:auto;right:0;}\n";
	echo ".rkrk-portal-item{display:flex;align-items:center;gap:6px;padding:4px 10px 4px 8px;font-size:0.78rem;font-weight:600;color:var(--footer-link,var(--toolbar-text));text-decoration:none;border:0;background:transparent;width:100%;box-sizing:border-box;text-align:left;cursor:pointer;line-height:1.2;white-space:nowrap;border-radius:0;}\n";
	echo ".rkrk-portal-item:hover,.rkrk-portal-item:focus-visible{background:var(--toolbar-hover-bg);color:var(--toolbar-text);outline:none;}\n";
	echo ".rkrk-portal-ico{width:14px;height:14px;min-width:14px;min-height:14px;flex-shrink:0;border-radius:3px;opacity:1;background:linear-gradient(145deg,var(--brand,#98B489) 0%,var(--brand-dark,#48673A) 100%);box-shadow:0 0 0 1px rgba(0,0,0,.14),inset 0 1px 0 rgba(255,255,255,.35);}\n";
	echo "@media (max-width:900px){\n";
	echo ".rkrk-portal-nav-slot,.rkrk-portal-mega{overflow:visible;}\n";
	echo ".rkrk-portal-bar{flex-wrap:wrap;border-radius:0;}\n";
	echo ".rkrk-portal-cell{flex:1 1 100%;min-width:0;max-width:100%;}\n";
	echo ".rkrk-portal-cell--open{position:relative;z-index:35;}\n";
	echo ".rkrk-portal-dd{min-width:0;width:100%;}\n";
	echo ".rkrk-portal-tab,.rkrk-portal-home{justify-content:flex-start;padding:12px 14px;min-height:44px;width:100%;box-sizing:border-box;}\n";
	echo ".rkrk-portal-panel{position:static;left:auto;right:auto;width:100%;max-width:none;max-height:none;border-radius:0;box-shadow:none;border-top:1px solid var(--toolbar-bd,var(--border));border-bottom:1px solid var(--toolbar-bd,var(--border));transform:none!important;display:none;opacity:0;visibility:hidden;pointer-events:none;overflow:visible;background:var(--card,#fff);}\n";
	/* Schmal: kein Hover/Focus-Öffnen — nur explizit geöffnet (verhindert Phantom-Pfeil ohne Panel) */
	echo ".rkrk-portal-cell:hover .rkrk-portal-panel,.rkrk-portal-cell:focus-within .rkrk-portal-panel{display:none;opacity:0;visibility:hidden;pointer-events:none;}\n";
	echo ".rkrk-portal-cell.rkrk-portal-cell--open > .rkrk-portal-dd > .rkrk-portal-panel,.rkrk-portal-tab[aria-expanded=\"true\"] + .rkrk-portal-panel{display:block!important;opacity:1!important;visibility:visible!important;pointer-events:auto!important;max-height:min(70vh,32rem);overflow-y:auto;-webkit-overflow-scrolling:touch;}\n";
	echo ".rkrk-portal-item{white-space:normal;min-height:44px;padding:10px 14px;font-size:0.85rem;}\n";
	echo ".rkrk-portal-cell:last-child .rkrk-portal-panel{left:auto;right:auto;}\n";
	echo "}\n";
	echo ".rkrk-portal-mega .rkrk-portal-bar,.rkrk-portal-mega .rkrk-portal-panel,.rkrk-portal-mega button.rkrk-portal-tab,.rkrk-portal-mega .rkrk-portal-item{border-radius:0!important;}\n";
	echo "</style>\n";
}

/**
 * Exklusives Öffnen eines Dropdowns + optional localStorage (welcher Tab zuletzt offen war).
 */
function rkrk_assess_portal_nav_script_once(): void {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	echo "<script>\n";
	echo "(function(){var k=\"rkrk_portal_mega_tab\";\n";
	echo "function closeAll(root){root.querySelectorAll(\".rkrk-portal-cell--open\").forEach(function(c){c.classList.remove(\"rkrk-portal-cell--open\");var b=c.querySelector(\".rkrk-portal-tab\");if(b){b.setAttribute(\"aria-expanded\",\"false\");}});}\n";
	echo "function setOpen(cell,open){var root=cell.closest(\".rkrk-portal-mega\");if(!root)return;var btn=cell.querySelector(\".rkrk-portal-tab\");if(open){root.querySelectorAll(\".rkrk-portal-cell\").forEach(function(c){if(c!==cell){c.classList.remove(\"rkrk-portal-cell--open\");var ob=c.querySelector(\".rkrk-portal-tab\");if(ob)ob.setAttribute(\"aria-expanded\",\"false\");}});cell.classList.add(\"rkrk-portal-cell--open\");if(btn)btn.setAttribute(\"aria-expanded\",\"true\");try{var id=cell.getAttribute(\"data-rkrk-portal-tab\");if(id)localStorage.setItem(k,id);}catch(e2){}}else{cell.classList.remove(\"rkrk-portal-cell--open\");if(btn)btn.setAttribute(\"aria-expanded\",\"false\");try{if(!root.querySelector(\".rkrk-portal-cell--open\"))localStorage.removeItem(k);}catch(e4){}}}\n";
	echo "document.querySelectorAll(\".rkrk-portal-mega[data-rkrk-portal-persist]\").forEach(function(root){\n";
	echo "try{var v=localStorage.getItem(k);if(v&&/^[a-z-]+$/.test(v)){var t=root.querySelector('.rkrk-portal-cell[data-rkrk-portal-tab=\"'+v+'\"]');if(t){t.classList.add(\"rkrk-portal-cell--open\");var b=t.querySelector(\".rkrk-portal-tab\");if(b)b.setAttribute(\"aria-expanded\",\"true\");}}}catch(e){}\n";
	echo "});\n";
	echo "function rkrkPortalTapToggle(){\n";
	echo "if(typeof window.matchMedia!==\"function\")return true;\n";
	echo "if(window.matchMedia(\"(max-width:900px)\").matches)return true;\n";
	echo "if(window.matchMedia(\"(hover:none)\").matches)return true;\n";
	echo "if(window.matchMedia(\"(pointer:coarse)\").matches)return true;\n";
	echo "return false;\n";
	echo "}\n";
	echo "function rkrkPortalTabToggle(ev,btn){\n";
	echo "if(ev){ev.preventDefault();ev.stopPropagation();}\n";
	echo "if(!rkrkPortalTapToggle())return;\n";
	echo "var cell=btn.closest(\".rkrk-portal-cell\");if(!cell)return;\n";
	echo "var now=Date.now();var last=parseInt(btn.getAttribute(\"data-rkrk-portal-tog\")||\"0\",10)||0;\n";
	echo "if(now-last<350)return;\n";
	echo "btn.setAttribute(\"data-rkrk-portal-tog\",String(now));\n";
	echo "var is=cell.classList.contains(\"rkrk-portal-cell--open\");\n";
	echo "setOpen(cell,!is);\n";
	echo "}\n";
	echo "document.querySelectorAll(\".rkrk-portal-mega\").forEach(function(root){\n";
	echo "root.querySelectorAll(\".rkrk-portal-tab\").forEach(function(btn){\n";
	echo "btn.addEventListener(\"click\",function(ev){rkrkPortalTabToggle(ev,btn);});\n";
	echo "});\n";
	echo "root.querySelectorAll(\".rkrk-portal-panel a\").forEach(function(a){a.addEventListener(\"click\",function(){var cell=a.closest(\".rkrk-portal-cell\");if(cell)setOpen(cell,false);});});\n";
	echo "});\n";
	echo "document.addEventListener(\"click\",function(ev){if(!ev)return;var t=ev.target;if(!t||!t.closest)return;if(t.closest(\".rkrk-portal-tab\")||t.closest(\".rkrk-portal-panel\"))return;document.querySelectorAll(\".rkrk-portal-mega\").forEach(function(root){closeAll(root);});});\n";
	echo "})();\n";
	echo "</script>\n";
}

/**
 * Gruppierte Links: Home (Staff) 1) Schulung & Quiz 2) Assessments 3) Beurteilungen 4) Personal 5) Administration.
 * Sichtbarkeit: auf portal.rehkitzrettung.ch nur mit ADMIN oder INSTR (sonst kein Menübalken).
 * Sonst: Abschnitt 1 für alle; Home und 2–5 nur wenn is_admin oder is_instr. Fragen Manager, Quiz-URL-Generator und URL-Generatoren (Checklisten) nur is_admin.
 *
 * @param array<string, mixed> $o schulung_mode, is_admin, is_instr, selected_quiz, portal_open (bool: erstes Dropdown initial offen), persist_open (bool: localStorage; Standard an). btn_class optional, derzeit ohne Wirkung (Mega-Menü-Zeilen nutzen .rkrk-portal-item).
 */
function rkrk_assess_portal_nav_html(array $o): string {
	if (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode()) {
		return "";
	}
	if (!function_exists("rkrk_assess_append_setting_href") || !function_exists("rkrk_assess_merge_query_with_setting")) {
		return "";
	}
	$schul = !empty($o["schulung_mode"]);
	$adm = !empty($o["is_admin"]);
	$instr = !empty($o["is_instr"]);
	if (!$adm && (isset($_GET["ADMIN"]) || (isset($GLOBALS["onAdmin"]) && (bool)$GLOBALS["onAdmin"] === true))) {
		$adm = true;
	}
	if (!$instr && (isset($_GET["INSTR"]) || isset($_POST["INSTR"]) || (isset($GLOBALS["onInstr"]) && (bool)$GLOBALS["onInstr"] === true))) {
		$instr = true;
	}
	$staff = $adm || $instr;
	// portal.rehkitzrettung.ch: Menübalken nur mit ADMIN oder INSTR (Prüflinge ohne Zusatzparameter).
	if (
		!$staff
		&& function_exists("rkrk_assess_hash_gate_is_portal_host")
		&& rkrk_assess_hash_gate_is_portal_host()
	) {
		return "";
	}
	$sel = isset($o["selected_quiz"]) ? trim((string)$o["selected_quiz"]) : "";
	$portalOpen = !empty($o["portal_open"]);
	$persistAttr = (!array_key_exists("persist_open", $o) || !empty($o["persist_open"])) ? ' data-rkrk-portal-persist="1"' : "";

	$h = static function ($v) {
		return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
	};
	$u = static function ($path) {
		return rkrk_assess_append_setting_href((string)$path);
	};

	$quizQ = array();
	if ($sel !== "") {
		$gated = function_exists("rkrk_assess_hash_gate_quiz_is_gated")
			&& rkrk_assess_hash_gate_quiz_is_gated($sel);
		$portalHost = function_exists("rkrk_assess_hash_gate_is_portal_host")
			&& rkrk_assess_hash_gate_is_portal_host();
		// Geschützte Quiz: auf dem Portal nur mit Staff/NOH/cs (nicht quiz+ADMIN ohne cs).
		if (!$gated || $portalHost) {
			$quizQ["quiz"] = $sel;
		}
	}
	/* Portal → Digital Assess: Personal-Namensliste aktivieren (Default auf der Seite ist PersPick=off).
	 * cs/EXAM/Name mitnehmen, sonst wird aus «Quiz» im Menü quiz+PersPick — hash_mismatch. */
	$dgaNavPick = array_merge($quizQ, array("PersPick" => "on"));
	if (function_exists("rkrk_assess_hash_gate_carry_fields_from_request")) {
		$carryHash = rkrk_assess_hash_gate_carry_fields_from_request();
		$curQuiz = trim((string) ($carryHash["quiz"] ?? ""));
		if ($sel === "" || $curQuiz === "" || strcasecmp($curQuiz, $sel) === 0) {
			$dgaNavPick = array_merge($carryHash, $dgaNavPick);
		}
	}
	$topQuizPath = "rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($dgaNavPick));
	$topQuiz = $u($topQuizPath);
	$schulParams = array_merge($dgaNavPick, array("Schulung" => ""));
	$topSchul = $u("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($schulParams)));

	$readBase = array_merge($dgaNavPick, array("read_only" => "1"));
	$readQuiz = $u("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($readBase)));
	$readSchulP = array_merge($readBase, array("Schulung" => ""));
	$readSchul = $u("rkrk_digitalassess.php?" . http_build_query(rkrk_assess_merge_query_with_setting($readSchulP)));

	$pair1 = array(
		array("Quiz", $topQuiz),
		array("Quiz - Lesemodus", $readQuiz),
		array("Schulung", $topSchul),
		array("Schulung - Lesemodus", $readSchul),
	);

	$firstCellClass = "rkrk-portal-cell" . ($portalOpen ? " rkrk-portal-cell--open" : "");
	$firstAria = $portalOpen ? "true" : "false";

	$out = '<nav class="rkrk-portal-mega" aria-label="Bereiche"' . $persistAttr . ">";
	$out .= '<ul class="rkrk-portal-bar">';
	if ($staff) {
		$out .= '<li class="rkrk-portal-cell rkrk-portal-cell--home">';
		$out .= '<a class="rkrk-portal-home" href="' . $h($u("rkrk_assess_admin.php")) . '">Home</a>';
		$out .= "</li>";
		$out .= '<li class="rkrk-portal-cell rkrk-portal-cell--home">';
		$out .= '<a class="rkrk-portal-home" href="' . $h($u("rkrk_assess_sessions.php")) . '">Tagesübersicht</a>';
		$out .= "</li>";
	}
	$out .= '<li class="' . $h($firstCellClass) . '" data-rkrk-portal-tab="schulung">';
	$out .= '<div class="rkrk-portal-dd"><button type="button" class="rkrk-portal-tab" aria-expanded="' . $firstAria . '" aria-haspopup="true">Schulung &amp; Quiz</button>';
	$out .= '<div class="rkrk-portal-panel" role="menu">';
	foreach ($pair1 as $it) {
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($it[1]) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>' . $h($it[0]) . "</a>";
	}
	if ($staff) {
		if ($adm) {
			$learnEditPath = "rkrk_digitalassess_learning_edit.php";
			if ($sel !== "") {
				$learnEditPath .= "?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $sel)));
			}
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($learnEditPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Schulungstext bearbeiten</a>';
		}
		$revPath = "rkrk_digitalassess_review.php";
		if ($sel !== "") {
			$revPath .= "?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $sel)));
		}
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($revPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Fragen Review</a>';
		if ($adm) {
			$mergeQs = array("ADMIN" => "");
			if ($sel !== "") {
				$mergeQs["base_quiz"] = $sel;
			}
			$mergePath = "rkrk_digitalassess_review_merge.php?" . http_build_query(rkrk_assess_merge_query_with_setting($mergeQs));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($mergePath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Review zusammenführen</a>';
			$mPath = "rkrk_digitalassess_admin.php";
			if ($sel !== "") {
				$mPath .= "?" . http_build_query(rkrk_assess_merge_query_with_setting(array("selected_quiz" => $sel)));
			}
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($mPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Fragen Manager</a>';
			$quizUrlGenPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "digitalquizlinkbuilder")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($quizUrlGenPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Quiz-URL Generator</a>';
			$epUrlGenPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "einsatzpruefunglinkbuilder")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($epUrlGenPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Einsatzprüfung Link Generator</a>';
		}
	}
	$out .= "</div></div></li>";

	if ($staff) {
		$tplAccess = !function_exists("GetAccessByFunction") || GetAccessByFunction("AssessmentTemplate");
		$out .= '<li class="rkrk-portal-cell" data-rkrk-portal-tab="assessments">';
		$out .= '<div class="rkrk-portal-dd"><button type="button" class="rkrk-portal-tab" aria-expanded="false" aria-haspopup="true">Assessments</button>';
		$out .= '<div class="rkrk-portal-panel" role="menu">';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess.php")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Assessments</a>';
		if ($adm && $tplAccess) {
			$assessTplPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "templates")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($assessTplPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Assessment-Templates</a>';
			$assessStoryPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "storyboards")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($assessStoryPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Storyboards</a>';
		}
		if ($adm) {
			$assessLbPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "linkbuilder")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($assessLbPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>URL Generator</a>';
			$epLbPath = "rkrk_assess.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "einsatzpruefunglinkbuilder")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($epLbPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Einsatzprüfung Links</a>';
		}
		$out .= "</div></div></li>";

		$out .= '<li class="rkrk-portal-cell" data-rkrk-portal-tab="beurteilungen">';
		$out .= '<div class="rkrk-portal-dd"><button type="button" class="rkrk-portal-tab" aria-expanded="false" aria-haspopup="true">Beurteilungen</button>';
		$out .= '<div class="rkrk-portal-panel" role="menu">';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_live.php")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Beurteilungen</a>';
		if ($adm && $tplAccess) {
			$liveTplPath = "rkrk_assess_live.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "templates")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($liveTplPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Beurteilungs-Templates</a>';
		}
		if ($adm) {
			$liveLbPath = "rkrk_assess_live.php?" . http_build_query(rkrk_assess_merge_query_with_setting(array("mode" => "linkbuilder")));
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($liveLbPath)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>URL Generator</a>';
		}
		$out .= "</div></div></li>";

		$out .= '<li class="rkrk-portal-cell" data-rkrk-portal-tab="personal">';
		$out .= '<div class="rkrk-portal-dd"><button type="button" class="rkrk-portal-tab" aria-expanded="false" aria-haspopup="true">Personal</button>';
		$out .= '<div class="rkrk-portal-panel" role="menu">';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_live_personel.php?view=list")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Personal</a>';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_live_personel_map.php")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Pilotenkarte</a>';
		$esNav = function_exists("rkrk_es_page_href") ? rkrk_es_page_href() : "rkrk_assess_einsatzstatus.php";
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($esNav)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Einsatzberechtigung</a>';
		$trNav = function_exists("rkrk_tr_page_href") ? rkrk_tr_page_href() : "rkrk_assess_trainingstatus.php";
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u($trNav)) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Teilnehmerliste</a>';
		$out .= "</div></div></li>";

		$out .= '<li class="rkrk-portal-cell" data-rkrk-portal-tab="admin">';
		$out .= '<div class="rkrk-portal-dd"><button type="button" class="rkrk-portal-tab" aria-expanded="false" aria-haspopup="true">Administration</button>';
		$out .= '<div class="rkrk-portal-panel" role="menu">';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_sessions.php")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Tagesübersicht / Recovery</a>';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_admin.php?page=planning")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Planungsübersicht</a>';
		$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_admin.php?page=results")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Ergebnisübersicht</a>';
		if ($adm) {
			$out .= '<a class="rkrk-portal-item" role="menuitem" href="' . $h($u("rkrk_assess_admin_access.php")) . '"><span class="rkrk-portal-ico" aria-hidden="true"></span>Zugriffsrechte</a>';
		}
		$out .= "</div></div></li>";
	}

	$out .= "</ul></nav>";
	return $out;
}

/**
 * @param array<string, mixed> $o siehe rkrk_assess_portal_nav_html
 */
function rkrk_assess_portal_nav_echo(array $o): void {
	if (function_exists("rkrk_assess_nom_mode") && rkrk_assess_nom_mode()) {
		return;
	}
	rkrk_assess_portal_nav_styles_once();
	$html = rkrk_assess_portal_nav_html($o);
	if ($html !== "") {
		echo $html;
		rkrk_assess_portal_nav_script_once();
	}
}

/** Nur unter dem Ordner assess: zentraler FS-Datenanker (siehe rkrk_assess_resolve_fs_data_root). Nicht für Skripte ausserhalb davon. */
	$rkrkAssessFsDataRoot = rkrk_assess_resolve_fs_data_root();

	rkrk_assess_emit_document_utf8();

	$__rkrkStemMigrateInc = __DIR__ . DIRECTORY_SEPARATOR . "rkrk_assess_stem_migrate.inc.php";
	if (is_readable($__rkrkStemMigrateInc)) {
		include_once $__rkrkStemMigrateInc;
		if (function_exists("rkrk_assess_stem_migrate_all")) {
			rkrk_assess_stem_migrate_all();
		}
	}

	if ((isset($_GET["legacy_reports"]) || isset($_GET["regenerate_reports"]))
		&& function_exists("rkrk_assess_report_rebuild_legacy_exam_reports")
	) {
		if (function_exists("set_time_limit")) {
			@set_time_limit(240);
		}
		$deadline = time() + 90;
		while (time() < $deadline) {
			$n = rkrk_assess_report_rebuild_legacy_exam_reports(4);
			if ($n < 1) {
				break;
			}
		}
	}

	if (function_exists("rkrk_assess_access_policy_auto_enforce")) {
		rkrk_assess_access_policy_auto_enforce();
	}

	//echo "<br><br>".$logo . "/".$logopic;
