<?php
/**
 * Tools-Schutz für tools.rehkitzrettung.ch — analog zum Portal-Hash-Gate in
 * assess/public/rkrk_assess_common.php (Host-Scope, cs/Session, Kontextmenü).
 *
 * Aktiv nur auf tools.rehkitzrettung.ch. Synology-/interne RKRK-Hosts und
 * reine /rkrs/-Pfade ohne Tools-Host bleiben unberührt.
 */
declare(strict_types=1);

if (defined('RKRK_TOOLS_AUTH_LOADED')) {
	return;
}
define('RKRK_TOOLS_AUTH_LOADED', true);

(static function (): void {
	$candidates = array(
		__DIR__ . '/scripts/hash_assessment.php',
		__DIR__ . '/assess/scripts/hash_assessment.php',
		dirname(__DIR__) . '/rkrs/assess/scripts/hash_assessment.php',
		__DIR__ . '/../assess/scripts/hash_assessment.php',
	);
	foreach ($candidates as $path) {
		if (is_file($path)) {
			include_once $path;
			break;
		}
	}
})();

if (!function_exists('rkrk_tools_is_tools_host')) {
	function rkrk_tools_is_tools_host(): bool {
		$host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
		return $host !== '' && strpos($host, 'tools.rehkitzrettung.ch') !== false;
	}
}

if (!function_exists('rkrk_tools_auth_enabled')) {
	function rkrk_tools_auth_enabled(): bool {
		// Hash-/cs-Schutz auf tools.rehkitzrettung.ch deaktiviert (2026-07-22).
		return false;
	}
}

if (!function_exists('rkrk_tools_detect_tool_key')) {
	function rkrk_tools_detect_tool_key(): string {
		$explicit = strtolower(trim((string) ($_GET['tool'] ?? '')));
		if (in_array($explicit, array('map', 'calc', 'convert', 'tools'), true)) {
			return $explicit;
		}
		$uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
		$script = strtolower(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')));
		$path = $uri !== '' ? $uri : $script;
		if (strpos($path, '/map/') !== false || preg_match('#/map$#', $path)) {
			return 'map';
		}
		if (strpos($path, '/calc/') !== false || preg_match('#/calc$#', $path)) {
			return 'calc';
		}
		if (strpos($path, '/convert/') !== false || preg_match('#/convert$#', $path)) {
			return 'convert';
		}
		return 'tools';
	}
}

if (!function_exists('rkrk_tools_hash_fields')) {
	/** @return list<string> */
	function rkrk_tools_hash_fields(): array {
		return array('tool', 'PilotID', 'Name');
	}
}

if (!function_exists('rkrk_tools_build_payload')) {
	/**
	 * @param array<string, mixed> $params
	 */
	function rkrk_tools_build_payload(array $params): string {
		$payload = '';
		foreach (rkrk_tools_hash_fields() as $field) {
			if (!array_key_exists($field, $params)) {
				continue;
			}
			$payload .= (string) $params[$field];
		}
		return $payload;
	}
}

if (!function_exists('rkrk_tools_compute_cs')) {
	function rkrk_tools_compute_cs(string $payload): string {
		if (!class_exists('assessment') || !is_callable(array('assessment', 'entry'))) {
			return '';
		}
		return strtolower((string) assessment::entry($payload));
	}
}

if (!function_exists('rkrk_tools_build_cs_for_tool')) {
	/**
	 * Hilfsfunktion für Portal-Link-Generator / Tests.
	 *
	 * @param array<string, mixed> $params
	 * @return array{payload:string, cs:string, tool:string}
	 */
	function rkrk_tools_build_cs_for_tool(string $tool, array $params = array()): array {
		$tool = strtolower(trim($tool));
		$params['tool'] = $tool;
		$payload = rkrk_tools_build_payload($params);
		return array(
			'tool' => $tool,
			'payload' => $payload,
			'cs' => rkrk_tools_compute_cs($payload),
		);
	}
}

if (!function_exists('rkrk_tools_h')) {
	function rkrk_tools_h(string $value): string {
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

if (!function_exists('rkrk_tools_session_start')) {
	function rkrk_tools_session_start(): void {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
	}
}

if (!function_exists('rkrk_tools_auth_bypass')) {
	function rkrk_tools_auth_bypass(): bool {
		// Wie Assess-Hash-Gate: ADMIN / NOH / TEST
		if (isset($_GET['ADMIN']) || isset($_GET['NOH']) || isset($_GET['TEST'])) {
			return true;
		}
		if (isset($_POST['ADMIN']) || isset($_POST['NOH']) || isset($_POST['TEST'])) {
			return true;
		}
		return false;
	}
}

if (!function_exists('rkrk_tools_session_granted')) {
	function rkrk_tools_session_granted(): bool {
		rkrk_tools_session_start();
		$g = $_SESSION['rkrkToolsAuthGrant'] ?? null;
		if (!is_array($g)) {
			return false;
		}
		$exp = (int) ($g['exp'] ?? 0);
		if ($exp > 0 && time() > $exp) {
			unset($_SESSION['rkrkToolsAuthGrant']);
			return false;
		}
		return !empty($g['ok']);
	}
}

if (!function_exists('rkrk_tools_store_grant')) {
	function rkrk_tools_store_grant(string $via): void {
		rkrk_tools_session_start();
		$_SESSION['rkrkToolsAuthGrant'] = array(
			'ok' => true,
			'via' => $via,
			'tool' => rkrk_tools_detect_tool_key(),
			'at' => time(),
			'exp' => time() + 12 * 3600,
		);
	}
}

if (!function_exists('rkrk_tools_portal_referer_ok')) {
	function rkrk_tools_portal_referer_ok(): bool {
		$ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
		if ($ref === '') {
			return false;
		}
		$host = strtolower((string) (parse_url($ref, PHP_URL_HOST) ?? ''));
		return $host !== '' && strpos($host, 'portal.rehkitzrettung.ch') !== false;
	}
}

if (!function_exists('rkrk_tools_cs_ok')) {
	/**
	 * @return array{ok:bool, reason:string, payload:string, client_cs:string, expected_cs:string}
	 */
	function rkrk_tools_cs_ok(): array {
		$result = array(
			'ok' => false,
			'reason' => 'missing_cs',
			'payload' => '',
			'client_cs' => strtolower(trim((string) ($_GET['cs'] ?? $_POST['cs'] ?? ''))),
			'expected_cs' => '',
		);
		if ($result['client_cs'] === '') {
			return $result;
		}
		if (!class_exists('assessment') || !is_callable(array('assessment', 'entry'))) {
			$result['reason'] = 'assessment_class_missing';
			return $result;
		}
		$params = $_GET;
		if (!isset($params['tool']) || trim((string) $params['tool']) === '') {
			$params['tool'] = rkrk_tools_detect_tool_key();
		}
		$payload = rkrk_tools_build_payload($params);
		$result['payload'] = $payload;
		$result['expected_cs'] = rkrk_tools_compute_cs($payload);
		if ($result['expected_cs'] === '') {
			$result['reason'] = 'expected_empty';
			return $result;
		}
		if (!hash_equals($result['expected_cs'], $result['client_cs'])) {
			$result['reason'] = 'hash_mismatch';
			return $result;
		}
		$result['ok'] = true;
		$result['reason'] = 'ok';
		return $result;
	}
}

if (!function_exists('rkrk_tools_wants_json')) {
	function rkrk_tools_wants_json(): bool {
		if (!empty($GLOBALS['rkrk_tools_auth_json'])) {
			return true;
		}
		$accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
		if (strpos($accept, 'application/json') !== false) {
			return true;
		}
		$script = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
		$jsonish = array(
			'save_kml.php', 'save_drawing.php', 'delete_drawing.php', 'load_drawing.php',
			'list_drawings.php', 'storage_info.php', 'serve_kml.php',
		);
		return in_array($script, $jsonish, true);
	}
}

if (!function_exists('rkrk_tools_deny_and_exit')) {
	/**
	 * @param array<string, mixed> $detail
	 */
	function rkrk_tools_deny_and_exit(array $detail = array()): void {
		$portal = 'https://portal.rehkitzrettung.ch/';
		$reason = (string) ($detail['reason'] ?? 'unauthorized');
		if (rkrk_tools_wants_json()) {
			if (!headers_sent()) {
				http_response_code(401);
				header('Content-Type: application/json; charset=utf-8');
			}
			echo json_encode(array(
				'ok' => false,
				'error' => 'auth_required',
				'reason' => $reason,
				'portal' => $portal,
			), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			exit;
		}
		if (!headers_sent()) {
			http_response_code(403);
			header('Content-Type: text/html; charset=utf-8');
		}
		$title = 'Zugriff verweigert';
		$msg = 'Diese Tools-Seite ist geschützt (wie Portal-Assess). '
			. 'Bitte über das Portal anmelden und den freigegebenen Tools-Link öffnen '
			. '(gültiger cs-Parameter oder Aufruf aus dem Portal).';
		$html = '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>' . rkrk_tools_h($title) . '</title>'
			. '<style>'
			. 'body{font-family:system-ui,-apple-system,sans-serif;margin:0;line-height:1.5;background:#f7f7f7;color:#1a1a1a}'
			. '.wrap{max-width:40rem;margin:0 auto;padding:2rem 1.25rem}'
			. '.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}'
			. 'h1{color:#b00020;margin:0 0 .75rem;font-size:1.5rem}'
			. '.btn{display:inline-block;margin-top:1.25rem;padding:.55rem 1rem;border-radius:6px;text-decoration:none;background:#2e7d32;color:#fff}'
			. '.foot{margin-top:1.25rem;font-size:.85rem;color:#666}'
			. 'code{word-break:break-all}'
			. '</style></head><body><div class="wrap"><div class="card">'
			. '<h1>' . rkrk_tools_h($title) . '</h1>'
			. '<p>' . rkrk_tools_h($msg) . '</p>'
			. '<p><strong>Grund:</strong> <code>' . rkrk_tools_h($reason) . '</code></p>'
			. '<a class="btn" href="' . rkrk_tools_h($portal) . '">Zum Portal anmelden</a>'
			. '<p class="foot">Rehkitzrettung Schweiz — tools.rehkitzrettung.ch</p>'
			. '</div></div></body></html>';
		echo $html;
		exit;
	}
}

if (!function_exists('rkrk_tools_should_block_context_menu')) {
	function rkrk_tools_should_block_context_menu(): bool {
		if (!rkrk_tools_is_tools_host()) {
			return false;
		}
		if (isset($_GET['TEST'])) {
			return false;
		}
		return true;
	}
}

if (!function_exists('rkrk_tools_context_menu_block_script_html')) {
	/** Exakt wie rkrk_assess_emit_context_menu_block_script() in assess_common. */
	function rkrk_tools_context_menu_block_script_html(): string {
		if (!rkrk_tools_should_block_context_menu()) {
			return '';
		}
		return "<script>\n"
			. "(function(){\n"
			. "document.addEventListener('contextmenu',function(e){e.preventDefault();},{capture:true});\n"
			. "document.addEventListener('keydown',function(e){\n"
			. "var k=e.key||'';\n"
			. "if(k==='F12'){e.preventDefault();return;}\n"
			. "if(e.ctrlKey&&e.shiftKey&&(k==='I'||k==='J'||k==='C'||k==='i'||k==='j'||k==='c')){e.preventDefault();return;}\n"
			. "if(e.ctrlKey&&(k==='u'||k==='U')){e.preventDefault();return;}\n"
			. "},{capture:true});\n"
			. "})();\n"
			. "</script>\n";
	}
}

if (!function_exists('rkrk_tools_emit_context_menu_block_script')) {
	function rkrk_tools_emit_context_menu_block_script(): void {
		static $done = false;
		if ($done) {
			return;
		}
		$html = rkrk_tools_context_menu_block_script_html();
		if ($html === '') {
			return;
		}
		$done = true;
		echo $html;
	}
}

if (!function_exists('rkrk_tools_inject_context_menu_into_html')) {
	function rkrk_tools_inject_context_menu_into_html(string $html): string {
		$script = rkrk_tools_context_menu_block_script_html();
		if ($script === '') {
			return $html;
		}
		if (stripos($html, 'rkrk-tools-context-block') !== false) {
			return $html;
		}
		$tagged = str_replace('<script>', '<script data-rkrk-tools-context-block="1">', $script, $count);
		if ($count < 1) {
			$tagged = $script;
		}
		if (stripos($html, '</head>') !== false) {
			return preg_replace('/<\/head>/i', $tagged . '</head>', $html, 1) ?? ($html . $tagged);
		}
		if (stripos($html, '<body') !== false) {
			return preg_replace('/<body([^>]*)>/i', '<body$1>' . $tagged, $html, 1) ?? ($html . $tagged);
		}
		return $html . $tagged;
	}
}

if (!function_exists('rkrk_tools_auth_require')) {
	/**
	 * Gate: nur tools-Host. Erlaubt bei Bypass, Session-Grant, gültigem cs
	 * oder Referer vom Portal (erster Einstieg nach Moodle-Login).
	 */
	function rkrk_tools_auth_require(): void {
		if (!rkrk_tools_auth_enabled()) {
			return;
		}
		// KML-Auslieferung für map.geo.admin.ch muss öffentlich bleiben.
		$script = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
		if ($script === 'serve_kml.php') {
			return;
		}
		if (rkrk_tools_auth_bypass()) {
			rkrk_tools_store_grant('bypass');
			return;
		}
		if (rkrk_tools_session_granted()) {
			return;
		}
		$cs = rkrk_tools_cs_ok();
		if (!empty($cs['ok'])) {
			rkrk_tools_store_grant('cs');
			return;
		}
		if (rkrk_tools_portal_referer_ok()) {
			rkrk_tools_store_grant('portal_referer');
			return;
		}
		rkrk_tools_deny_and_exit(array(
			'reason' => (string) ($cs['reason'] ?? 'unauthorized'),
			'tool' => rkrk_tools_detect_tool_key(),
		));
	}
}

// Auto-bootstrap unless caller opts out (e.g. link builder).
if (!(defined('RKRK_TOOLS_SKIP_AUTH_BOOTSTRAP') && RKRK_TOOLS_SKIP_AUTH_BOOTSTRAP)) {
	rkrk_tools_auth_require();
}
