<?php
/**
 * DJI Pilot 2 WPML KMZ (template.kml + waylines.wpml).
 *
 * Spec:
 *   https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/overview.html
 *   https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/template-kml.html
 *   https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/waylines-wpml.html
 *   https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/common-element.html
 *
 * Enums / limits: scripts/rkrk_dji_kmz_interface.php ($DJI_DroneInformation, $DJI_Commands).
 */

if (!function_exists('wpml_xml_esc')) {
    function wpml_xml_esc($value): string
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wpml_xml_leaf')) {
    function wpml_xml_leaf(string $tag, $value, int $indent = 2): string
    {
        $pad = str_repeat('  ', $indent);
        return $pad . '<' . $tag . '>' . wpml_xml_esc($value) . '</' . $tag . '>' . "\n";
    }
}

if (!function_exists('wpml_kml_wrap')) {
    function wpml_kml_wrap(string $innerDocument): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:wpml="http://www.dji.com/wpmz/1.0.2">' . "\n"
            . '<Document>' . "\n"
            . $innerDocument
            . '</Document>' . "\n"
            . '</kml>' . "\n";
    }
}

if (!function_exists('wpml_fmt_coord')) {
    function wpml_fmt_coord(float $v): string
    {
        return rtrim(rtrim(sprintf('%.8F', $v), '0'), '.');
    }
}

if (!function_exists('wpml_fmt_num')) {
    function wpml_fmt_num(float $v, int $decimals = 1): string
    {
        return rtrim(rtrim(sprintf('%.' . $decimals . 'F', $v), '0'), '.');
    }
}

if (!function_exists('wpml_heading_block_xml')) {
    function wpml_heading_block_xml(array $wp, int $indent = 4, bool $includeUseGlobal = false): string
    {
        $pad = str_repeat('  ', $indent);
        $mode = (string)($wp['heading_mode'] ?? 'followWayline');
        $allowed = ['followWayline', 'manually', 'fixed', 'smoothTransition', 'towardPOI'];
        if (!in_array($mode, $allowed, true)) {
            $mode = 'followWayline';
        }
        if ($mode === 'towardPOI' && empty($wp['poi_point'])) {
            $mode = 'followWayline';
        }
        $pathMode = (string)($wp['heading_path_mode'] ?? 'followBadArc');
        if (!in_array($pathMode, ['clockwise', 'counterClockwise', 'followBadArc'], true)) {
            $pathMode = 'followBadArc';
        }
        $xml = $pad . '<wpml:waypointHeadingParam>' . "\n";
        if ($includeUseGlobal) {
            $xml .= wpml_xml_leaf('wpml:useGlobalHeadingParam', 0, $indent + 1);
        }
        $xml .= wpml_xml_leaf('wpml:waypointHeadingMode', $mode, $indent + 1);
        if ($mode === 'smoothTransition' && array_key_exists('heading_angle', $wp) && $wp['heading_angle'] !== null && $wp['heading_angle'] !== '' && is_numeric($wp['heading_angle'])) {
            $xml .= wpml_xml_leaf('wpml:waypointHeadingAngle', wpml_fmt_num(floatval($wp['heading_angle']), 1), $indent + 1);
        }
        if ($mode === 'towardPOI') {
            $xml .= wpml_xml_leaf('wpml:waypointPoiPoint', (string)$wp['poi_point'], $indent + 1);
        }
        $xml .= wpml_xml_leaf('wpml:waypointHeadingYawPathMode', $pathMode, $indent + 1);
        $xml .= $pad . '</wpml:waypointHeadingParam>' . "\n";
        return $xml;
    }
}

if (!function_exists('wpml_turn_block_xml')) {
    function wpml_turn_block_xml(array $wp, int $indent = 4): string
    {
        $pad = str_repeat('  ', $indent);
        $mode = (string)($wp['turn_mode'] ?? 'coordinateTurn');
        $allowed = [
            'coordinateTurn',
            'toPointAndStopWithDiscontinuityCurvature',
            'toPointAndStopWithContinuityCurvature',
            'toPointAndPassWithContinuityCurvature',
            'toPointAndPassWithContinuityCurvature2',
        ];
        if (!in_array($mode, $allowed, true)) {
            $mode = 'coordinateTurn';
        }
        $damp = floatval($wp['turn_damping_m'] ?? 0.0);
        if (!is_finite($damp) || $damp < 0.0) {
            $damp = 0.0;
        }
        $xml = $pad . '<wpml:waypointTurnParam>' . "\n";
        $xml .= wpml_xml_leaf('wpml:waypointTurnDampingDist', wpml_fmt_num($damp, 1), $indent + 1);
        $xml .= wpml_xml_leaf('wpml:waypointTurnMode', $mode, $indent + 1);
        $xml .= $pad . '</wpml:waypointTurnParam>' . "\n";
        return $xml;
    }
}

if (!function_exists('wpml_mission_config_xml')) {
    function wpml_mission_config_xml(array $ctx, bool $forWaylines, int $indent = 2): string
    {
        $xml = str_repeat('  ', $indent) . '<wpml:missionConfig>' . "\n";
        $i = $indent + 1;
        $xml .= str_repeat('  ', $i) . '<wpml:droneInfo>' . "\n";
        $xml .= wpml_xml_leaf('wpml:droneEnumValue', intval($ctx['drone_enum'] ?? 77), $i + 1);
        $xml .= wpml_xml_leaf('wpml:droneSubEnumValue', intval($ctx['drone_sub_enum'] ?? 1), $i + 1);
        $xml .= str_repeat('  ', $i) . '</wpml:droneInfo>' . "\n";
        $xml .= str_repeat('  ', $i) . '<wpml:payloadInfo>' . "\n";
        $xml .= wpml_xml_leaf('wpml:payloadEnumValue', intval($ctx['payload_enum'] ?? 67), $i + 1);
        $xml .= wpml_xml_leaf('wpml:payloadSubEnumValue', intval($ctx['payload_sub_enum'] ?? 0), $i + 1);
        $xml .= wpml_xml_leaf('wpml:payloadPositionIndex', intval($ctx['payload_index'] ?? 0), $i + 1);
        $xml .= str_repeat('  ', $i) . '</wpml:payloadInfo>' . "\n";
        $xml .= wpml_xml_leaf('wpml:finishAction', (string)($ctx['finish_action'] ?? 'noAction'), $i);
        $xml .= wpml_xml_leaf('wpml:exitOnRCLost', (string)($ctx['exit_on_rc_lost'] ?? 'executeLostAction'), $i);
        $xml .= wpml_xml_leaf('wpml:executeRCLostAction', (string)($ctx['execute_rc_lost'] ?? 'goBack'), $i);
        $xml .= wpml_xml_leaf('wpml:flyToWaylineMode', (string)($ctx['fly_to_wayline_mode'] ?? 'safely'), $i);
        $xml .= wpml_xml_leaf('wpml:takeOffSecurityHeight', wpml_fmt_num(floatval($ctx['takeoff_security_height'] ?? 20.0), 1), $i);
        if ($forWaylines && !empty($ctx['takeoff_ref']) && is_array($ctx['takeoff_ref']) && count($ctx['takeoff_ref']) >= 3) {
            $ref = wpml_fmt_coord(floatval($ctx['takeoff_ref'][0])) . ','
                . wpml_fmt_coord(floatval($ctx['takeoff_ref'][1])) . ','
                . wpml_fmt_num(floatval($ctx['takeoff_ref'][2]), 1);
            $xml .= wpml_xml_leaf('wpml:takeOffRefPoint', $ref, $i);
            $xml .= wpml_xml_leaf('wpml:takeOffRefPointAGLHeight', wpml_fmt_num(floatval($ctx['takeoff_ref'][2]), 1), $i);
        }
        $xml .= wpml_xml_leaf('wpml:globalTransitionalSpeed', wpml_fmt_num(floatval($ctx['global_transitional_speed'] ?? 5.0), 1), $i);
        if ($forWaylines) {
            $xml .= wpml_xml_leaf('wpml:globalRTHHeight', wpml_fmt_num(floatval($ctx['global_rth_height'] ?? 20.0), 1), $i);
        }
        $xml .= str_repeat('  ', $indent) . '</wpml:missionConfig>' . "\n";
        return $xml;
    }
}

if (!function_exists('wpml_template_placemark_xml')) {
    function wpml_template_placemark_xml(array $wp, int $index, array $ctx): string
    {
        $lng = floatval($wp['lng'] ?? 0.0);
        $lat = floatval($wp['lat'] ?? 0.0);
        $h = floatval($wp['height'] ?? ($ctx['global_height'] ?? 60.0));
        $useH = !empty($wp['use_global_height']) ? 1 : 0;
        $useS = !empty($wp['use_global_speed']) ? 1 : 0;
        $useHead = !empty($wp['use_global_heading']) ? 1 : 0;
        $useTurn = !empty($wp['use_global_turn']) ? 1 : 0;
        $xml = '    <Placemark>' . "\n";
        $xml .= '      <Point>' . "\n";
        $xml .= '        <coordinates>' . wpml_fmt_coord($lng) . ',' . wpml_fmt_coord($lat) . '</coordinates>' . "\n";
        $xml .= '      </Point>' . "\n";
        $xml .= wpml_xml_leaf('wpml:index', $index, 3);
        $xml .= wpml_xml_leaf('wpml:useGlobalHeight', $useH, 3);
        $xml .= wpml_xml_leaf('wpml:ellipsoidHeight', wpml_fmt_num($h, 1), 3);
        $xml .= wpml_xml_leaf('wpml:height', wpml_fmt_num($h, 1), 3);
        $xml .= wpml_xml_leaf('wpml:useGlobalSpeed', $useS, 3);
        if ($useS === 0) {
            $xml .= wpml_xml_leaf('wpml:waypointSpeed', wpml_fmt_num(floatval($wp['speed'] ?? ($ctx['auto_flight_speed'] ?? 5.0)), 1), 3);
        }
        $xml .= wpml_xml_leaf('wpml:useGlobalTurnParam', $useTurn, 3);
        $xml .= wpml_xml_leaf('wpml:useStraightLine', intval($wp['use_straight_line'] ?? 1) ? 1 : 0, 3);
        $xml .= wpml_turn_block_xml($wp, 3);
        if ($useHead === 1) {
            $xml .= wpml_xml_leaf('wpml:useGlobalHeadingParam', 1, 3);
        } else {
            $xml .= wpml_heading_block_xml($wp, 3, true);
        }
        if ((string)($ctx['gimbal_pitch_mode'] ?? '') === 'usePointSetting') {
            $pitch = floatval($wp['gimbal_pitch'] ?? -90.0);
            $xml .= wpml_xml_leaf('wpml:gimbalPitchAngle', wpml_fmt_num($pitch, 1), 3);
        }
        $xml .= '    </Placemark>' . "\n";
        return $xml;
    }
}

if (!function_exists('wpml_wayline_placemark_xml')) {
    function wpml_wayline_placemark_xml(array $wp, int $index, array $ctx): string
    {
        $lng = floatval($wp['lng'] ?? 0.0);
        $lat = floatval($wp['lat'] ?? 0.0);
        $h = floatval($wp['height'] ?? ($ctx['global_height'] ?? 60.0));
        $speed = floatval($wp['speed'] ?? ($ctx['auto_flight_speed'] ?? 5.0));
        $xml = '    <Placemark>' . "\n";
        $xml .= '      <Point>' . "\n";
        $xml .= '        <coordinates>' . wpml_fmt_coord($lng) . ',' . wpml_fmt_coord($lat) . '</coordinates>' . "\n";
        $xml .= '      </Point>' . "\n";
        $xml .= wpml_xml_leaf('wpml:index', $index, 3);
        $xml .= wpml_xml_leaf('wpml:executeHeight', wpml_fmt_num($h, 1), 3);
        $xml .= wpml_xml_leaf('wpml:waypointSpeed', wpml_fmt_num($speed, 1), 3);
        $xml .= wpml_heading_block_xml($wp, 3);
        $xml .= wpml_xml_leaf('wpml:useStraightLine', intval($wp['use_straight_line'] ?? 1) ? 1 : 0, 3);
        $xml .= wpml_turn_block_xml($wp, 3);
        $xml .= '    </Placemark>' . "\n";
        return $xml;
    }
}

if (!function_exists('build_dji_wpml_template_kml')) {
    function build_dji_wpml_template_kml(array $ctx): string
    {
        $ms = (string)round(microtime(true) * 1000);
        $inner = '';
        if (trim((string)($ctx['mission_name'] ?? '')) !== '') {
            $inner .= '  <name>' . wpml_xml_esc((string)$ctx['mission_name']) . '</name>' . "\n";
        }
        $inner .= wpml_xml_leaf('wpml:author', (string)($ctx['author'] ?? 'RKRK'), 1);
        $inner .= wpml_xml_leaf('wpml:createTime', $ms, 1);
        $inner .= wpml_xml_leaf('wpml:updateTime', $ms, 1);
        $inner .= wpml_mission_config_xml($ctx, false, 1);
        $inner .= '  <Folder>' . "\n";
        $inner .= wpml_xml_leaf('wpml:globalHeight', wpml_fmt_num(floatval($ctx['global_height'] ?? 60.0), 1), 2);
        $inner .= wpml_xml_leaf('wpml:caliFlightEnable', 0, 2);
        $inner .= wpml_xml_leaf('wpml:autoFlightSpeed', wpml_fmt_num(floatval($ctx['auto_flight_speed'] ?? 5.0), 1), 2);
        $inner .= '    <wpml:globalWaypointHeadingParam>' . "\n";
        $inner .= wpml_xml_leaf('wpml:waypointHeadingMode', (string)($ctx['heading_mode'] ?? 'followWayline'), 3);
        if ((string)($ctx['heading_mode'] ?? '') === 'towardPOI' && trim((string)($ctx['heading_poi_point'] ?? '')) !== '') {
            $inner .= wpml_xml_leaf('wpml:waypointPoiPoint', (string)$ctx['heading_poi_point'], 3);
            $inner .= wpml_xml_leaf('wpml:waypointHeadingYawPathMode', 'followBadArc', 3);
        }
        $inner .= '    </wpml:globalWaypointHeadingParam>' . "\n";
        $inner .= wpml_xml_leaf('wpml:globalWaypointTurnMode', (string)($ctx['turn_mode'] ?? 'coordinateTurn'), 2);
        $inner .= wpml_xml_leaf('wpml:globalUseStraightLine', intval($ctx['use_straight_line'] ?? 1), 2);
        $inner .= '    <wpml:waylineCoordinateSysParam>' . "\n";
        $inner .= wpml_xml_leaf('wpml:coordinateMode', 'WGS84', 3);
        $inner .= wpml_xml_leaf('wpml:heightMode', (string)($ctx['height_mode'] ?? 'relativeToStartPoint'), 3);
        $inner .= wpml_xml_leaf('wpml:positioningType', 'GPS', 3);
        $inner .= '    </wpml:waylineCoordinateSysParam>' . "\n";
        $inner .= wpml_xml_leaf('wpml:gimbalPitchMode', (string)($ctx['gimbal_pitch_mode'] ?? 'manual'), 2);
        $inner .= wpml_xml_leaf('wpml:templateType', 'waypoint', 2);
        $inner .= wpml_xml_leaf('wpml:templateId', 0, 2);
        if (!empty($ctx['include_m30_payload_param'])) {
            $inner .= '    <wpml:payloadParam>' . "\n";
            $inner .= wpml_xml_leaf('wpml:payloadPositionIndex', 0, 3);
            $inner .= wpml_xml_leaf('wpml:meteringMode', 'average', 3);
            $inner .= wpml_xml_leaf('wpml:dewarpingEnable', 0, 3);
            $inner .= wpml_xml_leaf('wpml:returnMode', 'singleReturnStrongest', 3);
            $inner .= wpml_xml_leaf('wpml:samplingRate', 240000, 3);
            $inner .= wpml_xml_leaf('wpml:scanningMode', 'nonRepetitive', 3);
            $inner .= wpml_xml_leaf('wpml:modelColoringEnable', 0, 3);
            $inner .= '    </wpml:payloadParam>' . "\n";
        }
        foreach (($ctx['waypoints'] ?? []) as $i => $wp) {
            if (!is_array($wp)) {
                continue;
            }
            $inner .= wpml_template_placemark_xml($wp, intval($i), $ctx);
        }
        $inner .= '  </Folder>' . "\n";
        return wpml_kml_wrap($inner);
    }
}

if (!function_exists('build_dji_wpml_waylines_wpml')) {
    function build_dji_wpml_waylines_wpml(array $ctx): string
    {
        $inner = wpml_mission_config_xml($ctx, true, 1);
        $inner .= '  <Folder>' . "\n";
        $inner .= wpml_xml_leaf('wpml:templateId', 0, 2);
        $inner .= wpml_xml_leaf('wpml:executeHeightMode', (string)($ctx['execute_height_mode'] ?? 'relativeToStartPoint'), 2);
        $inner .= wpml_xml_leaf('wpml:waylineId', 0, 2);
        $inner .= wpml_xml_leaf('wpml:autoFlightSpeed', wpml_fmt_num(floatval($ctx['auto_flight_speed'] ?? 5.0), 1), 2);
        foreach (($ctx['waypoints'] ?? []) as $i => $wp) {
            if (!is_array($wp)) {
                continue;
            }
            $inner .= wpml_wayline_placemark_xml($wp, intval($i), $ctx);
        }
        $inner .= '  </Folder>' . "\n";
        return wpml_kml_wrap($inner);
    }
}

if (!function_exists('pack_dji_pilot2_kmz_file')) {
    /**
     * Write a Pilot-2 KMZ: ZIP with wpmz/template.kml + wpmz/waylines.wpml.
     */
    function pack_dji_pilot2_kmz_file(string $kmzPath, string $templateKml, string $waylinesWpml): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($kmzPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $zip->addFromString('wpmz/template.kml', $templateKml);
        $zip->addFromString('wpmz/waylines.wpml', $waylinesWpml);
        $zip->close();
        return is_file($kmzPath) && filesize($kmzPath) > 0;
    }
}
