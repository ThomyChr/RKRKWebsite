<?php
declare(strict_types=1);

/**
 * Dual-run environment for mapll (test) and mapII (rkrs).
 *
 * Same idea as test/map + rkrs/map:
 *   Mode A (RKRS): hostname tools.rehkitzrettung.ch OR URL/path contains /rkrs/
 *   Mode B (RKRK): everything else — keep /test/mapll integrated with the test site.
 *
 * This file lives next to index.php in both:
 *   W:\rkropen\test\mapll
 *   W:\rkropen\rkrs\mapII
 */

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('mapll_root')) {
    function mapll_root(): string
    {
        return __DIR__;
    }
}

if (!function_exists('mapll_is_tools_host')) {
    function mapll_is_tools_host(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        return (strpos($host, 'tools.rehkitzrettung.ch') !== false);
    }
}

if (!function_exists('mapll_is_rkrs_env')) {
    function mapll_is_rkrs_env(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
        $script = strtolower(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
        $pathHint = $uri !== '' ? $uri : $script;
        $parentName = strtolower(basename(dirname(mapll_root())));
        $isRkrsPath = (strpos($pathHint, '/rkrs/') !== false) || $parentName === 'rkrs';
        $cached = (mapll_is_tools_host() || $isRkrsPath);
        return $cached;
    }
}

if (!function_exists('mapll_rkropen_root')) {
    /** Parent of test/ or rkrs/ — W:\rkropen */
    function mapll_rkropen_root(): string
    {
        return dirname(mapll_root(), 2);
    }
}

if (!function_exists('mapll_first_existing_dir')) {
    /** @param list<string> $candidates */
    function mapll_first_existing_dir(array $candidates): ?string
    {
        foreach ($candidates as $dir) {
            $real = realpath($dir);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }
        return null;
    }
}

if (!function_exists('mapll_rkrk_site_root')) {
    /**
     * Directory that contains rkrk_access_control.php and scripts/ (normally /test).
     */
    function mapll_rkrk_site_root(): ?string
    {
        static $cached = false;
        if ($cached !== false) {
            return $cached;
        }

        $rkropen = mapll_rkropen_root();
        $candidates = [
            dirname(mapll_root()),
            $rkropen . DIRECTORY_SEPARATOR . 'test',
            $rkropen . DIRECTORY_SEPARATOR . 'v2',
        ];
        foreach ($candidates as $dir) {
            if (is_file($dir . DIRECTORY_SEPARATOR . 'rkrk_access_control.php')
                && is_dir($dir . DIRECTORY_SEPARATOR . 'scripts')) {
                $real = realpath($dir);
                $cached = ($real !== false) ? $real : $dir;
                return $cached;
            }
        }
        $cached = null;
        return null;
    }
}

if (!function_exists('mapll_scripts_dir')) {
    function mapll_scripts_dir(): ?string
    {
        $site = mapll_rkrk_site_root();
        $candidates = [];
        if ($site !== null) {
            $candidates[] = $site . DIRECTORY_SEPARATOR . 'scripts';
        }
        $candidates[] = dirname(mapll_root()) . DIRECTORY_SEPARATOR . 'scripts';
        $candidates[] = mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'scripts';
        $candidates[] = mapll_root() . DIRECTORY_SEPARATOR . 'scripts';
        return mapll_first_existing_dir($candidates);
    }
}

if (!function_exists('mapll_scripts_file')) {
    function mapll_scripts_file(string $basename): ?string
    {
        $dir = mapll_scripts_dir();
        if ($dir === null) {
            return null;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $basename;
        return is_file($path) ? $path : null;
    }
}

if (!function_exists('mapll_boot_shared_favicon')) {
    function mapll_boot_shared_favicon(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $php80 = function_exists('mapll_scripts_file') ? mapll_scripts_file('rkrk_php80.php') : null;
        if ($php80 && !function_exists('rkrk_str')) {
            require_once $php80;
        }
        $fav = function_exists('mapll_scripts_file') ? mapll_scripts_file('rkrk_favicon.php') : null;
        if ($fav && function_exists('rkrk_str') && !function_exists('rkrk_favicon_resolved')) {
            require_once $fav;
        }
    }
}

if (!function_exists('mapll_theme_name')) {
    function mapll_theme_name(): string
    {
        mapll_boot_shared_favicon();
        if (function_exists('rkrk_ci_theme_name')) {
            return rkrk_ci_theme_name();
        }
        return mapll_is_rkrs_env() ? 'rkrs' : 'rkrk';
    }
}

if (!function_exists('mapll_html_open_attrs')) {
    function mapll_html_open_attrs(array $extra = []): string
    {
        $theme = htmlspecialchars(mapll_theme_name(), ENT_QUOTES, 'UTF-8');
        $attrs = 'lang="de" data-rkrk-theme="' . $theme . '"';
        foreach ($extra as $k => $v) {
            $attrs .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . '="'
                . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $attrs;
    }
}

if (!function_exists('mapll_theme_css_link')) {
    function mapll_theme_css_link(): string
    {
        return '  <link rel="stylesheet" href="../template/rkrk_theme.css?v=20260907ci2" />' . "\n";
    }
}

if (!function_exists('mapll_mission_catalog_3t_root')) {
    /** Aktiver 3T-Feldkatalog: rkropen/Missionen (nicht «Missionen Intern»). */
    function mapll_mission_catalog_3t_root(): string
    {
        return mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'Missionen';
    }
}

if (!function_exists('mapll_mission_intern_legacy_roots')) {
    /**
     * Historische «Missionen Intern»-Ordner, nur zum Lesen.
     * @return list<string>
     */
    function mapll_mission_intern_legacy_roots(): array
    {
        return [
            mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'Missionen Intern',
            mapll_root() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Missionen Intern',
        ];
    }
}

if (!function_exists('mapll_mission_intern_root')) {
    /**
     * Export und Pfad-Nachschlagen zeigen auf den 3T-Katalog.
     * Fehlt dieser Ordner, bleibt der historische Intern-Pfad lesbar.
     */
    function mapll_mission_intern_root(): string
    {
        $canonical = mapll_mission_catalog_3t_root();
        if (is_dir($canonical)) {
            return $canonical;
        }
        foreach (mapll_mission_intern_legacy_roots() as $legacy) {
            if (is_dir($legacy)) {
                return $legacy;
            }
        }
        return $canonical;
    }
}

if (!function_exists('mapll_mission_intern_paths')) {
    /** @return array{root:string,xml_dir:string,xml_path:string,csv_dir:string,temp_dir:string,history_dir:string,log_dir:string,deleted_dir:string,region_dir:string} */
    function mapll_mission_intern_paths(): array
    {
        $root = mapll_mission_intern_root();
        return [
            'root' => $root,
            'xml_dir' => $root . DIRECTORY_SEPARATOR . 'xmlfiles',
            'xml_path' => $root . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml',
            'json_dir' => $root . DIRECTORY_SEPARATOR . 'jsonfiles',
            'json_path' => $root . DIRECTORY_SEPARATOR . 'jsonfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.json',
            'csv_dir' => $root . DIRECTORY_SEPARATOR . 'csvfiles',
            'temp_dir' => $root . DIRECTORY_SEPARATOR . '_temp',
            'history_dir' => $root . DIRECTORY_SEPARATOR . '_history',
            'log_dir' => $root . DIRECTORY_SEPARATOR . '_log',
            'deleted_dir' => $root . DIRECTORY_SEPARATOR . '_deleted',
            'region_dir' => $root . DIRECTORY_SEPARATOR . 'TF_Testfelder',
        ];
    }
}

if (!function_exists('mapll_fields_xml_path')) {
    function mapll_fields_xml_path(): string
    {
        $candidates = [
            mapll_mission_catalog_3t_root() . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml',
            mapll_root() . DIRECTORY_SEPARATOR . 'RKRKFelder.xml',
        ];
        foreach (mapll_mission_intern_legacy_roots() as $legacy) {
            $candidates[] = $legacy . DIRECTORY_SEPARATOR . 'xmlfiles' . DIRECTORY_SEPARATOR . 'RKRKFelder.xml';
        }
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return $candidates[0];
    }
}

if (!function_exists('mapll_ensure_dir')) {
    function mapll_ensure_dir(string $dir): ?string
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                return null;
            }
        }
        $real = realpath($dir);
        return ($real !== false) ? $real : $dir;
    }
}

if (!function_exists('mapll_legacy_data_dir')) {
    function mapll_legacy_data_dir(): string
    {
        return mapll_rkropen_root() . DIRECTORY_SEPARATOR . 'rkrk' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'map';
    }
}

if (!function_exists('mapll_rkrs_preferred_data_dir')) {
    function mapll_rkrs_preferred_data_dir(): string
    {
        return dirname(mapll_root()) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mapll';
    }
}

if (!function_exists('mapll_rkrs_local_data_dir')) {
    function mapll_rkrs_local_data_dir(): string
    {
        return mapll_root() . DIRECTORY_SEPARATOR . 'data';
    }
}

if (!function_exists('mapll_rkrs_write_dirs')) {
    /** @return list<string> */
    function mapll_rkrs_write_dirs(): array
    {
        $preferred = mapll_rkrs_preferred_data_dir();
        $local = mapll_rkrs_local_data_dir();
        if (mapll_is_tools_host()) {
            return [$local, $preferred];
        }
        return [$preferred, $local];
    }
}

if (!function_exists('mapll_data_dir')) {
    function mapll_data_dir(bool $create = true): ?string
    {
        if (!mapll_is_rkrs_env()) {
            $legacy = mapll_legacy_data_dir();
            return $create ? mapll_ensure_dir($legacy) : ((is_dir($legacy) ? (realpath($legacy) ?: $legacy) : null));
        }

        if ($create) {
            foreach (mapll_rkrs_write_dirs() as $candidate) {
                $ensured = mapll_ensure_dir($candidate);
                if ($ensured !== null) {
                    return $ensured;
                }
            }
            return null;
        }

        foreach (mapll_rkrs_write_dirs() as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }
        return null;
    }
}

if (!function_exists('mapll_require_tools_auth')) {
    function mapll_require_tools_auth(): void
    {
        $candidates = [
            dirname(mapll_root()) . DIRECTORY_SEPARATOR . 'rkrk_tools_auth.php',
            mapll_root() . DIRECTORY_SEPARATOR . 'rkrk_tools_auth.php',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                require_once $path;
                return;
            }
        }
    }
}

if (!function_exists('mapll_bootstrap_rkrk')) {
    /**
     * Load test-site chrome (menu / access) when running under /test.
     * Returns false for standalone rkrs/mapII (same split as rkrs/map overlay).
     */
    function mapll_bootstrap_rkrk(string $folderoffset): bool
    {
        mapll_require_tools_auth();
        if (mapll_is_rkrs_env()) {
            return false;
        }

        $site = mapll_rkrk_site_root();
        $scripts = mapll_scripts_dir();
        if ($site === null || $scripts === null) {
            return false;
        }

        $gen = $scripts . DIRECTORY_SEPARATOR . 'rkrk_gen.php';
        $menu = $scripts . DIRECTORY_SEPARATOR . 'rkrk_menu.php';
        $acl = $site . DIRECTORY_SEPARATOR . 'rkrk_access_control.php';
        if (!is_file($gen) || !is_file($menu) || !is_file($acl)) {
            return false;
        }

        @chdir($site);
        include_once $gen;
        include_once $menu;
        include_once $acl;
        $GLOBALS['MenuDirOffset'] = $folderoffset;
        $GLOBALS['folderoffset'] = $folderoffset;
        return true;
    }
}

if (!function_exists('mapll_favicon_base_web_path')) {
    /** Web-Pfad zum zentralen Ordner …/rkropen/favicon/. */
    function mapll_favicon_base_web_path(): string
    {
        $sn = preg_replace('#/+#', '/', str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
        $parts = $sn === '' ? [] : explode('/', trim($sn, '/'));
        foreach ($parts as $i => $p) {
            if (strcasecmp((string)$p, 'rkropen') === 0) {
                return '/' . implode('/', array_slice($parts, 0, $i + 1)) . '/favicon/';
            }
        }
        return '/rkropen/favicon/';
    }
}

if (!function_exists('mapll_favicon_asset_url')) {
    function mapll_favicon_asset_url(string $file, string $version = '20260824'): string
    {
        $file = ltrim(str_replace('\\', '/', $file), '/');
        $enc = implode('/', array_map('rawurlencode', array_filter(explode('/', $file), static function ($s) {
            return $s !== '';
        })));
        $url = mapll_favicon_base_web_path() . $enc;
        if ($version !== '') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'v=' . rawurlencode($version);
        }
        return $url;
    }
}

if (!function_exists('mapll_favicon_brand')) {
    /**
     * Favicon je Host/Pfad (gleiche Unterscheidung wie Assess / Overlay-Editor):
     *   tools.rehkitzrettung.ch → RKRS.ico
     *   tchr-frog.synology.me + /rkrs/ → RKRS_onrkrk.ico
     *   andere /rkrs/-Pfade → RKRS_onrkrs.ico
     *   sonst (test/mapll) → rkrk.ico
     *
     * @return array{mode:string,title:string,heading:string,ico:string,png:?string,alt:string}
     */
    function mapll_favicon_brand(): array
    {
        mapll_boot_shared_favicon();
        if (function_exists('rkrk_favicon_assets')) {
            $a = rkrk_favicon_assets();
            $rkrs = (($a['kind'] ?? '') === 'rkrs');
            return [
                'mode' => $rkrs ? 'rkrs' : 'rkrk',
                'label' => $rkrs ? 'RKRS' : 'RKRK',
                'ico' => (string)$a['ico'],
                'png' => (string)$a['png'],
                'apple' => (string)($a['apple'] ?? ''),
                'alt' => $rkrs ? 'RKRS' : 'RKRK',
                'slot' => (string)($a['slot'] ?? ''),
            ];
        }
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $isTools = mapll_is_tools_host();
        $isRkrs = mapll_is_rkrs_env();
        $isFrog = (strpos($host, 'tchr-frog.synology.me') !== false);
        if ($isRkrs || $isTools) {
            if ($isFrog) {
                $ico = 'RKRS_onrkrk.ico';
            } elseif ($isTools) {
                $ico = 'RKRS.ico';
            } else {
                $ico = 'RKRS_onrkrs.ico';
            }
            return [
                'mode' => 'rkrs',
                'label' => 'RKRS',
                'ico' => mapll_favicon_asset_url($ico),
                'png' => mapll_favicon_asset_url('RKRS_onrkrs.png'),
                'alt' => 'RKRS',
            ];
        }
        $sub = '';
        $sn = strtolower(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '') . (string)($_SERVER['REQUEST_URI'] ?? '')));
        if (preg_match('#/test27(/|$)#', $sn)) {
            $sub = 'Reh_FA840B_orange/';
        } elseif (preg_match('#/test(/|$)#', $sn)) {
            $sub = 'Reh_FFABAB_red/';
        } elseif (preg_match('#/v2(/|$)#', $sn)) {
            $sub = 'Reh_AFCEEB_blue/';
        } elseif (preg_match('#/v5(/|$)#', $sn)) {
            $sub = 'Reh_885833_brown/';
        }
        return [
            'mode' => 'rkrk',
            'label' => 'RKRK',
            'ico' => mapll_favicon_asset_url($sub . 'rkrk.ico'),
            'png' => mapll_favicon_asset_url($sub . 'favicon-32x32.png'),
            'alt' => 'RKRK',
        ];
    }
}

if (!function_exists('mapll_favicon_link_tags')) {
    function mapll_favicon_link_tags(?array $brand = null): string
    {
        mapll_boot_shared_favicon();
        if (function_exists('rkrk_favicon_link_tags_html')) {
            return rkrk_favicon_link_tags_html();
        }
        $b = is_array($brand) ? $brand : mapll_favicon_brand();
        $ico = htmlspecialchars((string)($b['ico'] ?? ''), ENT_QUOTES, 'UTF-8');
        $png = htmlspecialchars((string)($b['png'] ?? ''), ENT_QUOTES, 'UTF-8');
        $html = '<link rel="icon" type="image/x-icon" href="' . $ico . '" />' . "\n"
            . '  <link rel="shortcut icon" type="image/x-icon" href="' . $ico . '" />' . "\n";
        if ($png !== '') {
            $html .= '  <link rel="icon" type="image/png" sizes="32x32" href="' . $png . '" />' . "\n";
        }
        return $html;
    }
}

if (!function_exists('mapll_favicon_mark_html')) {
    function mapll_favicon_mark_html(?array $brand = null): string
    {
        $b = is_array($brand) ? $brand : mapll_favicon_brand();
        $src = (string)($b['png'] ?? $b['ico'] ?? '');
        if ($src === '') {
            return '';
        }
        return '<img class="rkr-brand-favicon" src="'
            . htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
            . '" alt="' . htmlspecialchars((string)($b['alt'] ?? ''), ENT_QUOTES, 'UTF-8')
            . '" width="28" height="28" />';
    }
}

if (!function_exists('mapll_icon_link')) {
    /** Icon-only link with hover title. */
    function mapll_icon_link(string $href, string $icon, string $title, array $opts = []): string
    {
        $cls = 'icon-link' . (!empty($opts['class']) ? ' ' . (string)$opts['class'] : '');
        $id = !empty($opts['id']) ? ' id="' . htmlspecialchars((string)$opts['id'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $target = '';
        if (!empty($opts['target'])) {
            $target = ' target="' . htmlspecialchars((string)$opts['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener"';
        }
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return '<a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '"' . $id
            . ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"'
            . ' title="' . $t . '" aria-label="' . $t . '"' . $target . '>' . $icon . '</a>';
    }
}

if (!function_exists('mapll_icon_button')) {
    /** Icon-only button with hover title. */
    function mapll_icon_button(string $id, string $icon, string $title, array $opts = []): string
    {
        $cls = 'icon-btn' . (!empty($opts['class']) ? ' ' . (string)$opts['class'] : '');
        $type = htmlspecialchars((string)($opts['type'] ?? 'button'), ENT_QUOTES, 'UTF-8');
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
        return '<button type="' . $type . '"' . $idAttr
            . ' class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '"'
            . ' title="' . $t . '" aria-label="' . $t . '">' . $icon . '</button>';
    }
}

if (!function_exists('mapll_nav_catalog')) {
    /** @return array<string, array{href:string,icon:string,title:string,opts?:array}> */
    function mapll_nav_catalog(): array
    {
        return [
            'start' => ['href' => 'index.php', 'icon' => '&#127968;', 'title' => 'Start'],
            'analyse' => ['href' => 'analyse_ui.php', 'icon' => '&#128202;', 'title' => 'Analyse'],
            'editor' => ['href' => 'rkrk_feld_editor.php', 'icon' => '&#9998;', 'title' => 'Feld-Editor'],
            'requirements' => ['href' => 'requirements.php', 'icon' => '&#128203;', 'title' => 'Requirements'],
            'spec' => ['href' => 'rkrk_feldeditor_spec.php', 'icon' => '&#128214;', 'title' => 'Spec'],
            'steps' => ['href' => 'step_requirements.php', 'icon' => '&#128196;', 'title' => 'Schritt-Requirements'],
            'mapping' => ['href' => 'mapping_matrix.php', 'icon' => '&#128279;', 'title' => 'Mapping'],
            'layers' => ['href' => 'ref_layers_admin.php', 'icon' => '&#128205;', 'title' => 'Eigene Layer verwalten'],
            'colors' => ['href' => 'ref_layers_settings.php', 'icon' => '&#127912;', 'title' => 'Farben und Layer-Defaults'],
            'print' => ['href' => 'requirements_print.php', 'icon' => '&#128424;', 'title' => 'PDF / Drucken', 'opts' => ['target' => '_blank']],
            'help' => ['href' => 'rkrk_help.php', 'icon' => '?', 'title' => 'Hilfe'],
            'api_fields' => ['href' => 'api_fields.php', 'icon' => '&#128451;', 'title' => 'Feldliste (API)', 'opts' => ['target' => '_blank']],
            'api_status' => ['href' => 'api_status.php', 'icon' => '&#128225;', 'title' => 'Systemstatus (API)', 'opts' => ['target' => '_blank']],
        ];
    }
}

if (!function_exists('mapll_nav_toplinks')) {
    /**
     * @param list<string|array{key:string,opts?:array}> $keys
     */
    function mapll_nav_toplinks(array $keys, string $prefix = ''): string
    {
        $cat = mapll_nav_catalog();
        $html = '<div class="toplinks rkrk-sels rkrk-reg-pills">' . $prefix;
        $self = strtolower(basename((string)($_SERVER['PHP_SELF'] ?? '')));
        foreach ($keys as $spec) {
            $opts = [];
            $key = $spec;
            if (is_array($spec)) {
                $key = (string)($spec['key'] ?? '');
                $opts = is_array($spec['opts'] ?? null) ? $spec['opts'] : [];
            }
            if (!isset($cat[$key])) {
                continue;
            }
            $it = $cat[$key];
            $href = (string)($it['href'] ?? '');
            $title = (string)($it['title'] ?? $key);
            $merged = array_merge($it['opts'] ?? [], $opts);
            $cls = 'rkrk-sel rkrk-reg-pill';
            if ($self !== '' && strtolower(basename($href)) === $self) {
                $cls .= ' is-active';
            }
            $target = '';
            if (!empty($merged['target'])) {
                $target = ' target="' . htmlspecialchars((string)$merged['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener"';
            }
            $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $html .= '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"'
                . ' title="' . $t . '" aria-label="' . $t . '"' . $target . '>' . $t . '</a>';
        }
        return $html . '</div>';
    }
}

if (!function_exists('mapll_emit_standalone_header')) {
    function mapll_emit_standalone_header(string $title): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!doctype html>' . "\n";
        echo '<html ' . mapll_html_open_attrs() . '>' . "\n";
        echo '<head>' . "\n";
        echo '<meta charset="utf-8">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        echo '</head>' . "\n";
    }
}

if (!function_exists('mapll_begin_page')) {
    function mapll_begin_page(string $title, bool $integrated, string $folderoffset, bool $writeLog = true): void
    {
        if ($integrated) {
            if (function_exists('CheckIPandExit')) {
                CheckIPandExit();
            }
            if ($writeLog && function_exists('WriteToLog')) {
                WriteToLog('open', basename((string)($_SERVER['PHP_SELF'] ?? '')), '', '', '', '', $folderoffset);
            }
            if (function_exists('headerpage')) {
                headerpage($title, false, $folderoffset);
            }
            if (function_exists('GrantAccess')) {
                GrantAccess();
            }
            return;
        }
        mapll_emit_standalone_header($title);
    }
}
