<?php
declare(strict_types=1);

/**
 * Loader for the internal DJI WPML specification (German labels + WPML values).
 * Data file: dji_wpml_internal_spec.json
 */

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('dji_internal_spec_path')) {
    function dji_internal_spec_path(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'dji_wpml_internal_spec.json';
    }
}

if (!function_exists('dji_internal_spec')) {
    function dji_internal_spec(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }
        $path = dji_internal_spec_path();
        if (!is_file($path)) {
            $cached = [];
            return $cached;
        }
        $raw = @file_get_contents($path);
        $json = $raw ? json_decode($raw, true) : null;
        $cached = is_array($json) ? $json : [];
        return $cached;
    }
}

if (!function_exists('dji_spec_element')) {
    function dji_spec_element(string $wpmlTag): array
    {
        $spec = dji_internal_spec();
        $el = $spec['elements'][$wpmlTag] ?? null;
        return is_array($el) ? $el : [];
    }
}

if (!function_exists('dji_spec_values')) {
    /** @return list<array<string, mixed>> */
    function dji_spec_values(string $wpmlTag): array
    {
        $el = dji_spec_element($wpmlTag);
        $vals = $el['values'] ?? [];
        return is_array($vals) ? array_values($vals) : [];
    }
}

if (!function_exists('dji_spec_normalize_key')) {
    function dji_spec_normalize_key(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        return preg_replace('/\s+/', ' ', $s) ?? $s;
    }
}

if (!function_exists('dji_spec_find_value')) {
    /**
     * Match an internal German label, alias, or WPML token to a spec value row.
     *
     * @return array<string, mixed>|null
     */
    function dji_spec_find_value(string $wpmlTag, string $internalOrWpml): ?array
    {
        $needle = dji_spec_normalize_key($internalOrWpml);
        if ($needle === '') {
            foreach (dji_spec_values($wpmlTag) as $row) {
                if (!empty($row['default'])) {
                    return $row;
                }
            }
            $vals = dji_spec_values($wpmlTag);
            return $vals[0] ?? null;
        }
        foreach (dji_spec_values($wpmlTag) as $row) {
            $candidates = [
                (string)($row['wpml'] ?? ''),
                (string)($row['de'] ?? ''),
                (string)($row['de_short'] ?? ''),
            ];
            if (isset($row['aliases']) && is_array($row['aliases'])) {
                foreach ($row['aliases'] as $a) {
                    $candidates[] = (string)$a;
                }
            }
            foreach ($candidates as $c) {
                if ($c !== '' && dji_spec_normalize_key($c) === $needle) {
                    return $row;
                }
            }
        }
        foreach (dji_spec_values($wpmlTag) as $row) {
            $de = dji_spec_normalize_key((string)($row['de'] ?? ''));
            if ($de !== '' && (str_contains($needle, $de) || str_contains($de, $needle))) {
                return $row;
            }
            foreach (($row['match_contains'] ?? []) as $frag) {
                $f = dji_spec_normalize_key((string)$frag);
                if ($f !== '' && str_contains($needle, $f)) {
                    return $row;
                }
            }
        }
        return null;
    }
}

if (!function_exists('dji_spec_to_wpml')) {
    function dji_spec_to_wpml(string $wpmlTag, string $internalOrWpml, string $fallback = ''): string
    {
        $row = dji_spec_find_value($wpmlTag, $internalOrWpml);
        if ($row && isset($row['wpml']) && (string)$row['wpml'] !== '') {
            return (string)$row['wpml'];
        }
        return $fallback;
    }
}

if (!function_exists('dji_spec_ui_options')) {
    /**
     * Options for a <select>: value = internal German label (de).
     *
     * @return list<array{value:string,label:string,help:string,wpml:string,default:bool}>
     */
    function dji_spec_ui_options(string $wpmlTag): array
    {
        $out = [];
        foreach (dji_spec_values($wpmlTag) as $row) {
            if (!empty($row['hidden_in_ui'])) {
                continue;
            }
            $de = trim((string)($row['de'] ?? ''));
            $wpml = trim((string)($row['wpml'] ?? ''));
            if ($de === '' && $wpml === '') {
                continue;
            }
            $out[] = [
                'value' => $de !== '' ? $de : $wpml,
                'label' => $de !== '' ? $de : $wpml,
                'help' => trim((string)($row['de_help'] ?? '')),
                'wpml' => $wpml,
                'default' => !empty($row['default']),
                'extra' => is_array($row['extra'] ?? null) ? $row['extra'] : [],
            ];
        }
        return $out;
    }
}

if (!function_exists('dji_spec_waypoint_bundle_from_internal')) {
    /** @return array{global_wpml_turn:string,ch_waypoint_type:string,path_mode:string,use_straight_line:int} */
    function dji_spec_waypoint_bundle_from_internal(string $internal): array
    {
        $row = dji_spec_find_value('wpml:globalWaypointTurnMode', $internal);
        if (!$row) {
            return [
                'global_wpml_turn' => 'coordinateTurn',
                'ch_waypoint_type' => 'coordinateTurn',
                'path_mode' => 'straight',
                'use_straight_line' => 1,
            ];
        }
        return [
            'global_wpml_turn' => (string)($row['wpml'] ?? 'coordinateTurn'),
            'ch_waypoint_type' => (string)($row['ch_waypoint_type'] ?? $row['wpml'] ?? 'coordinateTurn'),
            'path_mode' => (string)($row['path_mode'] ?? 'straight'),
            'use_straight_line' => intval($row['use_straight_line'] ?? 1),
        ];
    }
}

if (!function_exists('dji_spec_ui_bundle')) {
    /**
     * Payload for the field editor: all dropdowns + package layout.
     *
     * @return array<string, mixed>
     */
    function dji_spec_ui_bundle(): array
    {
        $spec = dji_internal_spec();
        return [
            'meta' => is_array($spec['meta'] ?? null) ? $spec['meta'] : [],
            'kmz_package' => is_array($spec['kmz_package'] ?? null) ? $spec['kmz_package'] : [],
            'selects' => [
                'end_action' => dji_spec_ui_options('wpml:finishAction'),
                'heading_mode' => dji_spec_ui_options('wpml:waypointHeadingMode'),
                'waypoint_type' => dji_spec_ui_options('wpml:globalWaypointTurnMode'),
                'gimbal_mode' => dji_spec_ui_options('wpml:gimbalPitchMode'),
                'heading_path_mode' => dji_spec_ui_options('wpml:waypointHeadingPathMode'),
                'fly_to_wayline_mode' => dji_spec_ui_options('wpml:flyToWaylineMode'),
                'exit_on_rc_lost' => dji_spec_ui_options('wpml:exitOnRCLost'),
                'execute_rc_lost_action' => dji_spec_ui_options('wpml:executeRCLostAction'),
            ],
            'form_map' => [
                'msEndAction' => 'end_action',
                'msHeadingMode' => 'heading_mode',
                'msWaypointType' => 'waypoint_type',
                'msGimbalMode' => 'gimbal_mode',
                'msAltitudeMode' => 'altitude_mode',
            ],
        ];
    }
}
