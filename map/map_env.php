<?php
declare(strict_types=1);

/**
 * Shared environment + map-data directory helpers for the overlay editor.
 *
 * Detection (kept in sync with rkrk_env_branding.js / rkrk_map_overlay.php):
 *   Mode A: hostname contains tools.rehkitzrettung.ch OR path contains /rkrs/
 *   Mode B: everything else (internal RKRK) — legacy paths unchanged.
 *
 * Mode A storage:
 *   - /rkrs/ path → rkrs/data/map (fallback map/data/)
 *   - tools.rehkitzrettung.ch → map/data/ (fallback rkrs/data/map)
 * Mode B storage: rkrk/data/map (unchanged).
 */

if (!function_exists('map_is_tools_host')) {
    function map_is_tools_host(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        return (strpos($host, 'tools.rehkitzrettung.ch') !== false);
    }
}

if (!function_exists('map_brand_images_base')) {
    /**
     * Public URL prefix for branding assets (favicon / logo / map inlay).
     * Always the local images/ folder next to this map deployment.
     */
    function map_brand_images_base(): string
    {
        return 'images/';
    }
}

if (!function_exists('map_brand_asset_url')) {
    /** Build a branding asset URL; encodes spaces for Synology-safe paths. */
    function map_brand_asset_url(string $file, string $version = ''): string
    {
        $base = rtrim(map_brand_images_base(), '/');
        $file = ltrim(str_replace('\\', '/', $file), '/');
        if (strpos($file, 'images/') === 0) {
            $file = substr($file, strlen('images/'));
        }
        $parts = explode('/', $file);
        $encoded = implode('/', array_map('rawurlencode', $parts));
        $url = $base . '/' . $encoded;
        if ($version !== '') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'v=' . rawurlencode($version);
        }
        return $url;
    }
}

if (!function_exists('map_boot_shared_favicon')) {
    function map_boot_shared_favicon(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $scripts = dirname(__DIR__) . '/scripts';
        if (!function_exists('rkrk_str') && is_file($scripts . '/rkrk_php80.php')) {
            require_once $scripts . '/rkrk_php80.php';
        }
        if (function_exists('rkrk_str') && !function_exists('rkrk_favicon_resolved') && is_file($scripts . '/rkrk_favicon.php')) {
            require_once $scripts . '/rkrk_favicon.php';
        }
    }
}
map_boot_shared_favicon();

if (!function_exists('map_is_rkrs_chrome')) {
    /** Logos/favicon/theme only — does not change map storage paths. */
    function map_is_rkrs_chrome(): bool
    {
        if (function_exists('rkrk_favicon_is_rkrs_chrome')) {
            return rkrk_favicon_is_rkrs_chrome();
        }
        $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        if (strpos($host, 'portal.rehkitzrettung.ch') !== false || strpos($host, 'tools.rehkitzrettung.ch') !== false) {
            return true;
        }
        $uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
        $script = strtolower(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
        if (strpos($uri, '/rkrs/') !== false || strpos($script, '/rkrs/') !== false) {
            return true;
        }
        foreach (['RKRS', 'RKRSplus', 'RKRSprev'] as $k) {
            if (isset($_GET[$k])) {
                return true;
            }
        }
        return !empty($GLOBALS['onRKRS']);
    }
}

if (!function_exists('map_is_rkrs_env')) {
    function map_is_rkrs_env(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $uri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
        $script = strtolower(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
        $pathHint = $uri !== '' ? $uri : $script;
        $isRkrsPath = (strpos($pathHint, '/rkrs/') !== false);
        $cached = (map_is_tools_host() || $isRkrsPath);
        return $cached;
    }
}

if (!function_exists('map_overlay_editor_title')) {
    /** Product name for the host this copy is running on. */
    function map_overlay_editor_title(): string
    {
        return map_is_rkrs_env() ? 'RKRS Overlay-Editor' : 'RKRK Overlay-Editor';
    }
}

if (!function_exists('map_legacy_data_dir')) {
    /** Internal RKRK storage (unchanged). */
    function map_legacy_data_dir(): string
    {
        return __DIR__ . '/../../rkrk/data/map';
    }
}

if (!function_exists('map_rkrs_preferred_data_dir')) {
    /**
     * Preferred Mode A dir: sibling rkrs/data/map (same convention as prior save_kml).
     */
    function map_rkrs_preferred_data_dir(): string
    {
        return __DIR__ . '/../data/map';
    }
}

if (!function_exists('map_rkrs_local_data_dir')) {
    /** Mode A fallback when parent rkrs/data cannot be used (e.g. tools FTP copy of map only). */
    function map_rkrs_local_data_dir(): string
    {
        return __DIR__ . '/data';
    }
}

if (!function_exists('map_ensure_dir')) {
    function map_ensure_dir(string $dir): ?string
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

if (!function_exists('map_rkrs_write_dirs')) {
    /**
     * Ordered Mode A write candidates.
     * tools host → map/data first; /rkrs/ path → rkrs/data/map first.
     *
     * @return list<string>
     */
    function map_rkrs_write_dirs(): array
    {
        $preferred = map_rkrs_preferred_data_dir();
        $local = map_rkrs_local_data_dir();
        if (map_is_tools_host()) {
            return [$local, $preferred];
        }
        return [$preferred, $local];
    }
}

if (!function_exists('map_data_dir')) {
    /**
     * Primary writable data directory for the current environment.
     * Creates the folder when missing.
     */
    function map_data_dir(bool $create = true): ?string
    {
        if (!map_is_rkrs_env()) {
            $legacy = map_legacy_data_dir();
            if ($create) {
                return map_ensure_dir($legacy);
            }
            $real = realpath($legacy);
            return ($real !== false && is_dir($real)) ? $real : null;
        }

        if ($create) {
            foreach (map_rkrs_write_dirs() as $candidate) {
                $ensured = map_ensure_dir($candidate);
                if ($ensured !== null) {
                    return $ensured;
                }
            }
            return null;
        }

        foreach (map_rkrs_write_dirs() as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_dir($real)) {
                return $real;
            }
        }
        return null;
    }
}

if (!function_exists('map_data_dir_candidates')) {
    /**
     * Ordered dirs to search when loading (Mode A includes legacy fallback).
     *
     * @return list<string>
     */
    function map_data_dir_candidates(bool $ensurePrimary = false): array
    {
        $dirs = [];
        $primary = map_data_dir($ensurePrimary);
        if ($primary !== null) {
            $dirs[] = $primary;
        }

        if (map_is_rkrs_env()) {
            foreach (array_merge(map_rkrs_write_dirs(), [map_legacy_data_dir()]) as $candidate) {
                $real = realpath($candidate);
                if ($real !== false && is_dir($real) && !in_array($real, $dirs, true)) {
                    $dirs[] = $real;
                }
            }
        }

        return $dirs;
    }
}

if (!function_exists('map_public_data_base')) {
    /**
     * Public URL path prefix for files in the primary data dir (no trailing slash).
     */
    function map_public_data_base(?string $resolvedDir = null): string
    {
        $dir = $resolvedDir ?? map_data_dir(false);
        $localReal = realpath(map_rkrs_local_data_dir());
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

        if ($dir !== null && $localReal !== false && realpath($dir) === $localReal) {
            return $scriptDir . '/data';
        }

        if (map_is_rkrs_env()) {
            return '/rkropen/rkrs/data/map';
        }

        return '/rkropen/rkrk/data/map';
    }
}

if (!function_exists('map_flighthub_config')) {
    /**
     * Server-only FlightHub 2 OpenAPI settings. Never expose org_key to the browser.
     *
     * Sources (later wins): defaults, map/flighthub.local.php, getenv / $_ENV.
     *
     * @return array{org_key:string,project_uuid:string,api_base:string,configured:bool}
     */
    function map_flighthub_config(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }
        $cfg = [
            'org_key' => '',
            'project_uuid' => '',
            'api_base' => 'https://es-flight-api-eu.djigate.com',
        ];
        $local = __DIR__ . '/flighthub.local.php';
        if (is_file($local)) {
            $loaded = include $local;
            if (is_array($loaded)) {
                foreach (['org_key', 'project_uuid', 'api_base'] as $key) {
                    if (isset($loaded[$key]) && is_string($loaded[$key])) {
                        $cfg[$key] = trim($loaded[$key]);
                    }
                }
            }
        }
        $envMap = [
            'org_key' => ['FLIGHTHUB_ORG_KEY', 'FLIGHTHUB_ORGANIZATION_KEY'],
            'project_uuid' => ['FLIGHTHUB_PROJECT_UUID'],
            'api_base' => ['FLIGHTHUB_API_BASE'],
        ];
        foreach ($envMap as $field => $names) {
            foreach ($names as $name) {
                $fromEnv = getenv($name);
                if ($fromEnv === false || $fromEnv === '') {
                    $fromEnv = $_ENV[$name] ?? $_SERVER[$name] ?? '';
                }
                if (is_string($fromEnv) && trim($fromEnv) !== '') {
                    $cfg[$field] = trim($fromEnv);
                    break;
                }
            }
        }
        $cfg['api_base'] = rtrim($cfg['api_base'], '/');
        $cfg['configured'] = ($cfg['org_key'] !== '' && $cfg['project_uuid'] !== '' && $cfg['api_base'] !== '');
        $cached = $cfg;
        return $cached;
    }
}

if (!function_exists('map_flighthub_public_flags')) {
    /** Browser-safe flags only (no secrets). */
    function map_flighthub_public_flags(): array
    {
        $cfg = map_flighthub_config();
        return ['configured' => !empty($cfg['configured'])];
    }
}
