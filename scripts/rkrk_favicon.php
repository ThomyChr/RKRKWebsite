<?php
/**
 * One favicon resolution + a short, stable <link> set for every page.
 * Paths are absolute under /rkropen/favicon/… so assess/ and nested folders do not 404.
 */

if (!function_exists('rkrk_favicon_slot_defaults')) {
	function rkrk_favicon_slot_defaults(): array {
		return array(
			'pub' => array(
				'label' => 'pub',
				'kind' => 'rkrk',
				'picdir' => '../favicon/',
			),
			'test' => array(
				'label' => 'test',
				'kind' => 'rkrk',
				'picdir' => '../favicon/Reh_FFABAB_red/',
			),
			'v2' => array(
				'label' => 'v2 (intern)',
				'kind' => 'rkrk',
				'picdir' => '../favicon/Reh_AFCEEB_blue/',
			),
			'test27' => array(
				'label' => 'test27 (orange)',
				'kind' => 'rkrk',
				'picdir' => '../favicon/Reh_FA840B_orange/',
			),
			'extern' => array(
				'label' => 'extern',
				'kind' => 'rkrk',
				'picdir' => '../favicon/Reh_FA840B_orange/',
			),
			'partner' => array(
				'label' => 'partner',
				'kind' => 'rkrk',
				'picdir' => '../favicon/Reh_885833_brown/',
			),
			'rkrs' => array(
				'label' => 'rkrs',
				'kind' => 'rkrs',
				'picdir' => '../favicon/',
			),
			'portal' => array(
				'label' => 'portal.rehkitzrettung.ch',
				'kind' => 'rkrs',
				'picdir' => '/rkrsplusassessment/favicon/',
			),
			'tools' => array(
				'label' => 'tools.rehkitzrettung.ch',
				'kind' => 'rkrs',
				'picdir' => 'images/',
			),
		);
	}
}

if (!function_exists('rkrk_favicon_slots')) {
	function rkrk_favicon_slots(): array {
		$slots = rkrk_favicon_slot_defaults();
		$brand = function_exists('rkrk_config_branding') ? rkrk_config_branding() : array();
		$overlay = (isset($brand['favicons']) && is_array($brand['favicons'])) ? $brand['favicons'] : array();
		foreach ($overlay as $key => $picdir) {
			$key = strtolower(rkrk_str($key));
			if (!isset($slots[$key])) {
				continue;
			}
			$clean = rkrk_favicon_sanitize_picdir($picdir);
			if ($clean !== '') {
				$slots[$key]['picdir'] = $clean;
			}
		}
		return $slots;
	}
}

if (!function_exists('rkrk_favicon_sanitize_picdir')) {
	function rkrk_favicon_sanitize_picdir($picdir): string {
		$s = str_replace('\\', '/', rkrk_str($picdir));
		$s = trim($s);
		if ($s === '' || stripos($s, 'javascript:') === 0) {
			return '';
		}
		if (!preg_match('#^(../favicon/|/rkropen/favicon/|/rkrsplusassessment/favicon/|images/)[A-Za-z0-9_./\-]*$#', $s)) {
			return '';
		}
		if (substr($s, -1) !== '/') {
			$s .= '/';
		}
		return $s;
	}
}

if (!function_exists('rkrk_favicon_is_rkrs_chrome')) {
	/**
	 * Logos / favicon / titles only. Not storage paths.
	 * RKRS: onRKRS, portal.rehkitzrettung.ch, tools.rehkitzrettung.ch, /rkrs/, query RKRS|RKRSplus|RKRSprev.
	 * drohnenrehkitzrettung.ch is not RKRS.
	 */
	function rkrk_favicon_is_rkrs_chrome(): bool {
		if (!empty($GLOBALS['onRKRS'])) {
			return true;
		}
		$host = strtolower(preg_replace('/:\d+$/', '', rkrk_str($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
		if ($host !== '' && (
			strpos($host, 'portal.rehkitzrettung.ch') !== false
			|| strpos($host, 'tools.rehkitzrettung.ch') !== false
		)) {
			return true;
		}
		$script = str_replace('\\', '/', strtolower(
			rkrk_str($_SERVER['PHP_SELF'] ?? '') . "\n" .
			rkrk_str($_SERVER['SCRIPT_NAME'] ?? '') . "\n" .
			rkrk_str($_SERVER['REQUEST_URI'] ?? '') . "\n" .
			rkrk_str($_SERVER['SCRIPT_FILENAME'] ?? '')
		));
		if (preg_match('#/rkrs(/|$)#', $script)) {
			return true;
		}
		if (isset($GLOBALS['AddURL']) && in_array((string)$GLOBALS['AddURL'], array('RKRS', 'RKRSplus', 'RKRSprev'), true)) {
			return true;
		}
		if (isset($_GET) && is_array($_GET)) {
			foreach (array('RKRS', 'RKRSplus', 'RKRSprev') as $k) {
				if (array_key_exists($k, $_GET)) {
					return true;
				}
			}
		}
		return false;
	}
}

if (!function_exists('rkrk_ci_is_rkrs')) {
	function rkrk_ci_is_rkrs() {
		return rkrk_favicon_is_rkrs_chrome();
	}
}

if (!function_exists('rkrk_ci_theme_name')) {
	/** CSS key for html[data-rkrk-theme]: rkrk (Grün) | rkrs (Grau). Favicon-Slots sind unabhängig. */
	function rkrk_ci_theme_name() {
		return rkrk_ci_is_rkrs() ? 'rkrs' : 'rkrk';
	}
}

if (!function_exists('rkrk_favicon_current_slot')) {
	function rkrk_favicon_current_slot(): string {
		$host = strtolower(preg_replace('/:\d+$/', '', rkrk_str($_SERVER['HTTP_HOST'] ?? '')));
		$script = str_replace('\\', '/', strtolower(
			rkrk_str($_SERVER['PHP_SELF'] ?? '') . "\n" .
			rkrk_str($_SERVER['SCRIPT_NAME'] ?? '') . "\n" .
			rkrk_str($_SERVER['REQUEST_URI'] ?? '')
		));
		if (rkrk_favicon_is_rkrs_chrome()) {
			if (strpos($host, 'portal.rehkitzrettung.ch') !== false) {
				return 'portal';
			}
			if (strpos($host, 'tools.rehkitzrettung.ch') !== false) {
				return 'tools';
			}
			return 'rkrs';
		}
		if (preg_match('#/test27(/|$)#', $script)) {
			return 'test27';
		}
		if (preg_match('#/test(/|$)#', $script)) {
			return 'test';
		}
		if (preg_match('#/v2(/|$)#', $script)) {
			return 'v2';
		}
		if (preg_match('#/pub(/|$)#', $script)) {
			return 'pub';
		}
		if (preg_match('#/ex(/|$)#', $script)) {
			return 'extern';
		}
		if (preg_match('#/v5(/|$)#', $script)) {
			return 'partner';
		}
		return 'pub';
	}
}

if (!function_exists('rkrk_favicon_rkropen_web_base')) {
	function rkrk_favicon_rkropen_web_base(): string {
		$sn = preg_replace('#/+#', '/', str_replace('\\', '/', rkrk_str($_SERVER['SCRIPT_NAME'] ?? '')));
		$parts = $sn === '' ? array() : explode('/', trim($sn, '/'));
		foreach ($parts as $i => $seg) {
			if (strcasecmp($seg, 'rkropen') === 0) {
				return '/' . implode('/', array_slice($parts, 0, $i + 1)) . '/favicon/';
			}
		}
		return '/rkropen/favicon/';
	}
}

if (!function_exists('rkrk_favicon_web_dir')) {
	function rkrk_favicon_web_dir(string $picdir): string {
		$picdir = str_replace('\\', '/', trim($picdir));
		if ($picdir === '') {
			$picdir = '../favicon/';
		}
		if (substr($picdir, -1) !== '/') {
			$picdir .= '/';
		}
		if (preg_match('#^https?://#i', $picdir) || (isset($picdir[0]) && $picdir[0] === '/')) {
			return $picdir;
		}
		// tools overlay: icons live next to the map (./images), not /rkropen/favicon.
		if (strpos($picdir, 'images/') === 0) {
			return $picdir;
		}
		if (preg_match('#(?:^|/)favicon/(.*)$#', $picdir, $m)) {
			return rkrk_favicon_rkropen_web_base() . $m[1];
		}
		return rkrk_favicon_rkropen_web_base();
	}
}

if (!function_exists('rkrk_favicon_join')) {
	function rkrk_favicon_join(string $dir, string $file): string {
		$dir = str_replace('\\', '/', $dir);
		if (substr($dir, -1) !== '/') {
			$dir .= '/';
		}
		$segments = explode('/', $file);
		$enc = array();
		foreach ($segments as $seg) {
			if ($seg === '') {
				continue;
			}
			$enc[] = rawurlencode($seg);
		}
		return $dir . implode('/', $enc);
	}
}

if (!function_exists('rkrk_favicon_resolved')) {
	function rkrk_favicon_resolved(): array {
		$slot = rkrk_favicon_current_slot();
		$slots = rkrk_favicon_slots();
		$row = $slots[$slot] ?? $slots['pub'];
		$dir = rkrk_favicon_web_dir((string)$row['picdir']);
		$kind = ((string)($row['kind'] ?? 'rkrk') === 'rkrs') ? 'rkrs' : 'rkrk';
		return array(
			'slot' => $slot,
			'kind' => $kind,
			'dir' => $dir,
			'picdir' => (string)$row['picdir'],
		);
	}
}

if (!function_exists('rkrk_favicon_apply_to_globals')) {
	function rkrk_favicon_apply_to_globals(): void {
		global $picdir, $RKRSFaviconLocation;
		$res = rkrk_favicon_resolved();
		$picdir = $res['dir'];
		if ($res['kind'] === 'rkrs') {
			$RKRSFaviconLocation = $res['dir'];
		}
	}
}

if (!function_exists('rkrk_favicon_assets')) {
	/**
	 * @return array{kind:string,slot:string,dir:string,ico:string,png:string,png16:?string,apple:string,tile:?string}
	 */
	function rkrk_favicon_assets(): array {
		$res = rkrk_favicon_resolved();
		$dir = $res['dir'];
		if ($res['kind'] === 'rkrs') {
			$host = strtolower(rkrk_str($_SERVER['HTTP_HOST'] ?? ''));
			$isTools = ($res['slot'] === 'tools') || (strpos($host, 'tools.rehkitzrettung.ch') !== false);
			if ($isTools) {
				$ico = rkrk_favicon_join($dir, 'RKRS.ico');
				return array(
					'kind' => 'rkrs',
					'slot' => (string)$res['slot'],
					'dir' => $dir,
					'ico' => $ico,
					'png' => null,
					'png16' => null,
					'apple' => $ico,
					'tile' => null,
				);
			}
			$ico = (strpos($host, 'tchr-frog.synology.me') !== false) ? 'RKRS_onrkrk.ico' : 'RKRS.ico';
			$png = rkrk_favicon_join($dir, 'RKRS_onrkrs.png');
			return array(
				'kind' => 'rkrs',
				'slot' => (string)$res['slot'],
				'dir' => $dir,
				'ico' => rkrk_favicon_join($dir, $ico),
				'png' => $png,
				'png16' => null,
				'apple' => $png,
				'tile' => null,
			);
		}
		return array(
			'kind' => 'rkrk',
			'slot' => (string)$res['slot'],
			'dir' => $dir,
			'ico' => rkrk_favicon_join($dir, 'rkrk.ico'),
			'png' => rkrk_favicon_join($dir, 'favicon-32x32.png'),
			'png16' => rkrk_favicon_join($dir, 'favicon-16x16.png'),
			'apple' => rkrk_favicon_join($dir, 'apple-icon-180x180.png'),
			'tile' => rkrk_favicon_join($dir, 'ms-icon-144x144.png'),
		);
	}
}

if (!function_exists('rkrk_favicon_link_tags_html')) {
	function rkrk_favicon_link_tags_html(): string {
		$a = rkrk_favicon_assets();
		$h = static function (string $href): string {
			return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
		};
		$out = '';
		if ($a['kind'] === 'rkrs') {
			$out .= "  <link rel='icon' type='image/x-icon' href='" . $h($a['ico']) . "'>\n";
			$out .= "  <link rel='shortcut icon' type='image/x-icon' href='" . $h($a['ico']) . "'>\n";
			if (!empty($a['png'])) {
				$out .= "  <link rel='icon' type='image/png' sizes='32x32' href='" . $h($a['png']) . "'>\n";
			}
			if (!empty($a['apple'])) {
				$out .= "  <link rel='apple-touch-icon' href='" . $h($a['apple']) . "'>\n";
			}
		} else {
			$out .= "  <link rel='icon' type='image/png' sizes='32x32' href='" . $h($a['png']) . "?v=2'>\n";
			if (!empty($a['png16'])) {
				$out .= "  <link rel='icon' type='image/png' sizes='16x16' href='" . $h((string)$a['png16']) . "?v=2'>\n";
			}
			$out .= "  <link rel='icon' type='image/x-icon' href='" . $h($a['ico']) . "?v=2'>\n";
			$out .= "  <link rel='shortcut icon' href='" . $h($a['ico']) . "?v=2'>\n";
			$out .= "  <link rel='apple-touch-icon' sizes='180x180' href='" . $h($a['apple']) . "?v=2'>\n";
			if (!empty($a['tile'])) {
				$out .= "  <meta name='msapplication-TileImage' content='" . $h((string)$a['tile']) . "?v=2'>\n";
			}
		}
		$out .= "  <meta name='theme-color' content='#ffffff'>\n";
		return $out;
	}
}

if (!function_exists('rkrk_favicon_emit_link_tags')) {
	function rkrk_favicon_emit_link_tags(): void {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		echo rkrk_favicon_link_tags_html();
	}
}

if (!function_exists('rkrk_favicon_js_bootstrap')) {
	function rkrk_favicon_js_bootstrap(): string {
		$a = rkrk_favicon_assets();
		$payload = array(
			'theme' => rkrk_ci_theme_name(),
			'chromeRkrs' => rkrk_favicon_is_rkrs_chrome(),
			'slot' => $a['slot'],
			'kind' => $a['kind'],
			'ico' => $a['ico'],
			'png' => $a['png'],
			'apple' => $a['apple'],
		);
		$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if ($json === false) {
			return '';
		}
		return '<script>window.__RKR_FAVICON__=' . $json . ';</script>' . "\n";
	}
}

if (!function_exists('rkrk_favicon_primary_href')) {
	function rkrk_favicon_primary_href(): string {
		$a = rkrk_favicon_assets();
		return ($a['kind'] === 'rkrs') ? $a['ico'] : $a['png'];
	}
}

if (!function_exists('rkrk_favicon_emit_http_headers')) {
	function rkrk_favicon_emit_http_headers(): void {
		static $done = false;
		if ($done || headers_sent()) {
			return;
		}
		$done = true;
		$href = rkrk_favicon_primary_href();
		if ($href === '') {
			return;
		}
		header('Link: <' . $href . '>; rel="icon"; type="image/png"', false);
		$res = rkrk_favicon_resolved();
		if ($res['kind'] !== 'rkrs') {
			$ico = rkrk_favicon_join($res['dir'], 'rkrk.ico');
			header('Link: <' . $ico . '>; rel="shortcut icon"', false);
		}
	}
}

if (!function_exists('rkrk_pdf_is_raw_request')) {
	function rkrk_pdf_is_raw_request(): bool {
		$raw = $_GET['rkrk_pdf_raw'] ?? '';
		return ($raw !== '' && $raw !== '0');
	}
}

if (!function_exists('rkrk_pdf_maybe_emit_html_shell')) {
	function rkrk_pdf_maybe_emit_html_shell(string $title = 'PDF'): void {
		if (PHP_SAPI === 'cli' || rkrk_pdf_is_raw_request()) {
			rkrk_favicon_emit_http_headers();
			return;
		}
		$uri = rkrk_str($_SERVER['REQUEST_URI'] ?? ('/' . basename((string)($_SERVER['SCRIPT_NAME'] ?? 'pdf.php'))));
		$sep = (strpos($uri, '?') === false) ? '?' : '&';
		$rawUrl = $uri . $sep . 'rkrk_pdf_raw=1';
		if (!headers_sent()) {
			header('Content-Type: text/html; charset=UTF-8');
			header('Cache-Control: private, max-age=0, must-revalidate');
		}
		$t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
		$u = htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8');
		echo "<!DOCTYPE html>\n<html lang='de'>\n<head>\n";
		echo "<meta charset='utf-8'>\n";
		echo "<meta name='viewport' content='width=device-width, initial-scale=1'>\n";
		echo "<title>" . $t . "</title>\n";
		rkrk_favicon_emit_link_tags();
		echo "<style>html,body{margin:0;height:100%;background:#525659;}iframe{border:0;width:100%;height:100%;display:block;}</style>\n";
		echo "</head>\n<body>\n";
		echo "<iframe src='" . $u . "' title='" . $t . "'></iframe>\n";
		echo "</body>\n</html>\n";
		exit;
	}
}
