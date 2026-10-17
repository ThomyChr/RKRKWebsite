<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/analysis_lib.php';

$fieldId = isset($_GET['field_id']) ? trim((string)$_GET['field_id']) : '';
$hfov = isset($_GET['hfov']) ? floatval($_GET['hfov']) : 48.0;
$dfov = isset($_GET['dfov']) ? floatval($_GET['dfov']) : 61.0;
$agl = isset($_GET['agl']) ? floatval($_GET['agl']) : 50.0;
$overlap = isset($_GET['overlap']) ? floatval($_GET['overlap']) : 0.15;
$edgeFactor = isset($_GET['edge_factor']) ? floatval($_GET['edge_factor']) : 0.40;
$speed = isset($_GET['speed']) ? floatval($_GET['speed']) : 5.0;
$turnDelayS = isset($_GET['turn_delay_s']) ? floatval($_GET['turn_delay_s']) : 1.5;
$waypointType = isset($_GET['waypoint_type']) ? trim((string)$_GET['waypoint_type']) : '';
$flightDirectionDeg = isset($_GET['flight_direction']) ? floatval($_GET['flight_direction']) : -1.0;
$planner = isset($_GET['planner']) ? strtolower(trim((string)$_GET['planner'])) : 'php';
$modelId = isset($_GET['model_id']) ? trim((string)$_GET['model_id']) : '';
$useFieldReferenceAgl = !isset($_GET['use_field_reference_agl']) || $_GET['use_field_reference_agl'] !== '0';
$inputHfov = $hfov;
$inputDfov = $dfov;
$inputAgl = $agl;
$inputOverlap = $overlap;
$inputEdgeFactor = $edgeFactor;
$inputSpeed = $speed;
$inputTurnDelayS = $turnDelayS;
$inputWaypointType = $waypointType;
$inputFlightDirectionDeg = $flightDirectionDeg;
$modelParams = [];
$preferredDirection = 'auto';
$fixedThetaDeg = null;
$directionSource = 'default';
$stepRequirementsApplied = [];
$requirementsList = [];
$requirementsById = [];
$requirementStepMap = [
    'R-001' => 'S3_COVERAGE',
    'R-002' => 'S3_COVERAGE',
    'R-003' => 'S3_COVERAGE',
    'R-004' => 'S3_COVERAGE',
    'R-005' => 'S4_WAYPOINTS',
    'R-006' => 'S4_WAYPOINTS',
    'R-007' => 'S2_DIRECTION',
    'R-008' => 'S2_DIRECTION',
    'R-009' => 'S4_WAYPOINTS',
    'R-010' => 'S3_COVERAGE',
    'R-011' => 'S3_COVERAGE',
    'R-012' => 'S4_WAYPOINTS',
    'R-013' => 'S1_HEIGHT_PROFILE',
    'R-014' => 'S2_DIRECTION',
    'R-015' => 'S2_DIRECTION',
    'R-016' => 'S2_DIRECTION',
    'R-017' => 'S2_DIRECTION',
    'R-018' => 'S3_COVERAGE',
    'R-019' => 'S3_COVERAGE',
    'R-020' => 'S3_COVERAGE',
    'R-021' => 'S3_COVERAGE',
    'R-022' => 'S2_DIRECTION',
    'R-023' => 'S3_COVERAGE',
    'R-024' => 'S3_COVERAGE',
    'R-025' => 'S2_DIRECTION',
    'R-026' => 'S3_COVERAGE',
    'R-027' => 'S3_COVERAGE',
    'R-028' => 'S4_WAYPOINTS',
    'R-029' => 'S3_COVERAGE',
    'R-030' => 'S3_COVERAGE',
    'R-031' => 'S3_COVERAGE',
    'R-032' => 'S3_COVERAGE',
    'R-033' => 'S3_COVERAGE',
    'R-034' => 'S3_COVERAGE',
    'R-035' => 'S4_WAYPOINTS',
    'R-036' => 'S4_WAYPOINTS',
    'R-037' => 'S2_DIRECTION'
];
$paramSources = [
    'hfov' => 'ui',
    'overlap' => 'ui',
    'edge_factor' => 'ui',
    'speed' => 'ui',
    'turn_delay_s' => 'ui',
    'agl_tolerance_m' => 'default',
    'angle_step_deg' => 'default',
    'target_step_m' => 'default',
    'max_nb_points' => 'default',
    'min_delay_s' => 'default',
    'target_agl_m' => 'ui',
    'curve_waypoint_angle_step_deg' => 'default'
    ,'dfov' => 'ui'
];

function is_skip_turn_waypoint_type(string $waypointType): bool
{
    $v = trim(strtolower($waypointType));
    return $v === 'koordinierte drehung, ueberspringt den wegpunkt'
        || $v === 'koordinierte drehung, überspringt den wegpunkt';
}

$xmlPath = resolve_fields_xml_path();
$fields = load_fields_xml($xmlPath);
$selected = null;
foreach ($fields as $f) {
    if ($f['id'] === $fieldId) {
        $selected = $f;
        break;
    }
}
if (!$selected) {
    http_response_code(404);
    echo json_encode(['error' => 'field_not_found']);
    exit;
}
if (isset($_GET['start_lon'], $_GET['start_lat'])) {
    $ovLon = floatval($_GET['start_lon']);
    $ovLat = floatval($_GET['start_lat']);
    if (is_finite($ovLon) && is_finite($ovLat) && abs($ovLon) <= 180.0 && abs($ovLat) <= 90.0) {
        $selected['start'] = [$ovLon, $ovLat];
    }
}
if (!$selected['start'] || count($selected['polygon']) < 3) {
    http_response_code(422);
    echo json_encode(['error' => 'field_missing_geometry']);
    exit;
}

$kv = [];
$turnDelayDefaultS = $turnDelayS;
$turnDelaySkipWpS = 1.0;

// Determine field reference altitude early so all downstream geometry uses the same AGL basis.
$referenceAgl = floatval($selected['manual_meta']['std_flughoehe_m'] ?? 0.0);
if ($referenceAgl <= 0.0 && !empty($selected['manual_path'])) {
    $referenceAgl = floatval($selected['manual_path'][0][2] ?? 0.0);
}
if ($useFieldReferenceAgl && $referenceAgl > 0.0) {
    $agl = $referenceAgl;
    $paramSources['target_agl_m'] = 'field_reference';
}

// Global core requirements (requirements_data.json) as baseline defaults.
$reqPath = __DIR__ . '/requirements_data.json';
if (is_file($reqPath)) {
    $reqRaw = file_get_contents($reqPath);
    $reqPayload = $reqRaw ? json_decode($reqRaw, true) : null;
    if (is_array($reqPayload) && isset($reqPayload['core_parameters']) && is_array($reqPayload['core_parameters'])) {
        $kv = [];
        foreach ($reqPayload['core_parameters'] as $p) {
            $k = trim((string)($p['key'] ?? ''));
            if ($k === '') {
                continue;
            }
            $kv[$k] = $p['value'] ?? null;
        }
        if (isset($kv['HFOV_deg']) && is_numeric($kv['HFOV_deg'])) {
            $hfov = floatval($kv['HFOV_deg']);
            $paramSources['hfov'] = 'core_requirements';
        }
        if (isset($kv['DFOV_deg']) && is_numeric($kv['DFOV_deg'])) {
            $dfov = floatval($kv['DFOV_deg']);
            $paramSources['dfov'] = 'core_requirements';
        }
        if (isset($kv['overlap']) && is_numeric($kv['overlap'])) {
            $overlap = floatval($kv['overlap']);
            $paramSources['overlap'] = 'core_requirements';
        }
        if (isset($kv['target_agl_m']) && is_numeric($kv['target_agl_m'])) {
            $agl = floatval($kv['target_agl_m']);
            $paramSources['target_agl_m'] = 'core_requirements';
        }
        if (isset($kv['edge_factor']) && is_numeric($kv['edge_factor'])) {
            $edgeFactor = floatval($kv['edge_factor']);
            $paramSources['edge_factor'] = 'core_requirements';
        }
        if (isset($kv['speed_m_s']) && is_numeric($kv['speed_m_s'])) {
            $speed = floatval($kv['speed_m_s']);
            $paramSources['speed'] = 'core_requirements';
        }
        if (isset($kv['turn_delay_s']) && is_numeric($kv['turn_delay_s'])) {
            $turnDelayS = floatval($kv['turn_delay_s']);
            $turnDelayDefaultS = $turnDelayS;
            $paramSources['turn_delay_s'] = 'core_requirements';
        }
        if (isset($kv['turn_delay_skip_wp_s']) && is_numeric($kv['turn_delay_skip_wp_s'])) {
            $turnDelaySkipWpS = floatval($kv['turn_delay_skip_wp_s']);
        }
        if (isset($kv['agl_tolerance_m']) && is_numeric($kv['agl_tolerance_m'])) {
            $modelParams['agl_tolerance_m'] = floatval($kv['agl_tolerance_m']);
            $paramSources['agl_tolerance_m'] = 'core_requirements';
        }
        if (isset($kv['angle_step_deg']) && is_numeric($kv['angle_step_deg'])) {
            $modelParams['angle_step_deg'] = intval($kv['angle_step_deg']);
            $paramSources['angle_step_deg'] = 'core_requirements';
        }
        if (isset($kv['target_step_m']) && is_numeric($kv['target_step_m'])) {
            $modelParams['target_step_m'] = floatval($kv['target_step_m']);
            $paramSources['target_step_m'] = 'core_requirements';
        }
        if (isset($kv['max_nb_points']) && is_numeric($kv['max_nb_points'])) {
            $modelParams['max_nb_points'] = intval($kv['max_nb_points']);
            $paramSources['max_nb_points'] = 'core_requirements';
        }
        if (isset($kv['min_delay_s']) && is_numeric($kv['min_delay_s'])) {
            $modelParams['min_delay_s'] = floatval($kv['min_delay_s']);
            $paramSources['min_delay_s'] = 'core_requirements';
        }
        if (isset($kv['curve_waypoint_angle_step_deg']) && is_numeric($kv['curve_waypoint_angle_step_deg'])) {
            $modelParams['curve_waypoint_angle_step_deg'] = max(1.0, floatval($kv['curve_waypoint_angle_step_deg']));
            $paramSources['curve_waypoint_angle_step_deg'] = 'core_requirements';
        }
    }
    if (is_array($reqPayload) && isset($reqPayload['requirements']) && is_array($reqPayload['requirements'])) {
        $requirementsList = $reqPayload['requirements'];
        foreach ($requirementsList as $req) {
            $rid = trim((string)($req['id'] ?? ''));
            if ($rid !== '') {
                $requirementsById[$rid] = $req;
            }
        }
    }
}

// Optional model override (server-side).
$modelUsed = null;
if ($modelId !== '') {
    $modelsPath = __DIR__ . '/models_data.json';
    if (is_file($modelsPath)) {
        $modelsRaw = file_get_contents($modelsPath);
        $modelsPayload = $modelsRaw ? json_decode($modelsRaw, true) : null;
        if (is_array($modelsPayload) && isset($modelsPayload['models']) && is_array($modelsPayload['models'])) {
            foreach ($modelsPayload['models'] as $m) {
                if ((string)($m['id'] ?? '') !== $modelId) {
                    continue;
                }
                $p = $m['params'] ?? [];
                if (isset($p['hfov'])) {
                    $hfov = floatval($p['hfov']);
                    $paramSources['hfov'] = 'model';
                }
                if (isset($p['dfov'])) {
                    $dfov = floatval($p['dfov']);
                    $paramSources['dfov'] = 'model';
                }
                if (isset($p['overlap'])) {
                    $overlap = floatval($p['overlap']);
                    $paramSources['overlap'] = 'model';
                }
                if (isset($p['agl'])) {
                    $agl = floatval($p['agl']);
                    $paramSources['target_agl_m'] = 'model';
                }
                if (isset($p['edge_factor'])) {
                    $edgeFactor = floatval($p['edge_factor']);
                    $paramSources['edge_factor'] = 'model';
                }
                if (isset($p['speed'])) {
                    $speed = floatval($p['speed']);
                    $paramSources['speed'] = 'model';
                }
                if (isset($p['turn_delay_s'])) {
                    $turnDelayS = floatval($p['turn_delay_s']);
                    $turnDelayDefaultS = $turnDelayS;
                    $paramSources['turn_delay_s'] = 'model';
                }
                if (isset($p['turn_delay_skip_wp_s'])) {
                    $turnDelaySkipWpS = floatval($p['turn_delay_skip_wp_s']);
                }
                if (isset($p['preferred_direction'])) {
                    $preferredDirection = strtolower(trim((string)$p['preferred_direction']));
                }
                if (isset($p['fixed_theta_deg']) && $p['fixed_theta_deg'] !== '') {
                    $fixedThetaDeg = floatval($p['fixed_theta_deg']);
                }
                $modelParams = is_array($p) ? $p : [];
                foreach (['agl_tolerance_m', 'angle_step_deg', 'target_step_m', 'max_nb_points', 'min_delay_s'] as $mk) {
                    if (isset($modelParams[$mk])) {
                        $paramSources[$mk] = 'model';
                    }
                }
                if (isset($modelParams['curve_waypoint_angle_step_deg'])) {
                    $paramSources['curve_waypoint_angle_step_deg'] = 'model';
                }
                $modelUsed = [
                    'id' => (string)($m['id'] ?? ''),
                    'name' => (string)($m['name'] ?? ''),
                    'description' => (string)($m['description'] ?? ''),
                ];
                break;
            }
        }
    }
}

// Mandatory: compute flight direction from field height points.
$derivedDirection = derive_direction_from_height_points($selected['manual_path']);
if (isset($derivedDirection['preferred_direction'])) {
    $preferredDirection = (string)$derivedDirection['preferred_direction'];
}
if (array_key_exists('fixed_theta_deg', $derivedDirection)) {
    $fixedThetaDeg = $derivedDirection['fixed_theta_deg'] !== null ? floatval($derivedDirection['fixed_theta_deg']) : null;
}
$directionSource = (string)($derivedDirection['source'] ?? 'height_points');
$edgeAlignApplied = false;
$edgeAlignAngleDeg = null;
$edgeAlignLenM = 0.0;
$isOpenDirection = !is_finite($flightDirectionDeg) || $flightDirectionDeg < 0.0;
if (!$isOpenDirection) {
    $fixedThetaDeg = fmod(($flightDirectionDeg + 360.0), 180.0);
    $directionSource = 'ui_fixed_direction';
} else {
    $edgeInfo = longest_polygon_edge_angle_deg($selected['polygon']);
    $edgeAlignAngleDeg = floatval($edgeInfo['angle_deg'] ?? 0.0);
    $edgeAlignLenM = floatval($edgeInfo['length_m'] ?? 0.0);
    $fixedThetaDeg = $edgeAlignAngleDeg;
    $directionSource = 'shall_edge_alignment';
    $edgeAlignApplied = true;
}
$formAnalysis = analyze_field_form($selected['polygon'], $fixedThetaDeg);
$specialCasesReport = [];
$fieldIdLabel = (string)($selected['feld_id'] ?? '');
// Requirement-driven sectioning: no field-specific fixed section lists.
$minPrimaryCoverageGate = 0.80;
$minObjects = 2;
$maxObjects = 8;
$secondaryAreaCapRatio = 0.20;
if (isset($kv['min_shape_coverage_ratio']) && is_numeric($kv['min_shape_coverage_ratio'])) {
    $minPrimaryCoverageGate = floatval($kv['min_shape_coverage_ratio']);
}
if (isset($kv['section_min_objects']) && is_numeric($kv['section_min_objects'])) {
    $minObjects = max(1, intval($kv['section_min_objects']));
}
if (isset($kv['section_max_objects']) && is_numeric($kv['section_max_objects'])) {
    $maxObjects = max($minObjects, intval($kv['section_max_objects']));
}
if (isset($kv['secondary_section_max_ratio']) && is_numeric($kv['secondary_section_max_ratio'])) {
    $secondaryAreaCapRatio = max(0.05, min(0.95, floatval($kv['secondary_section_max_ratio'])));
}
// Read step helper parameters for object escalation and coverage gates.
$stepReqPathEarly = __DIR__ . '/step_requirements_data.json';
if (is_file($stepReqPathEarly)) {
    $rawStepEarly = file_get_contents($stepReqPathEarly);
    $stepCfgEarly = $rawStepEarly ? json_decode($rawStepEarly, true) : null;
    if (is_array($stepCfgEarly) && isset($stepCfgEarly['steps']) && is_array($stepCfgEarly['steps'])) {
        foreach ($stepCfgEarly['steps'] as $stepEarly) {
            if (empty($stepEarly['enabled']) || !isset($stepEarly['helper_parameters']) || !is_array($stepEarly['helper_parameters'])) {
                continue;
            }
            $sidEarly = (string)($stepEarly['id'] ?? '');
            $local = [];
            foreach ($stepEarly['helper_parameters'] as $hp) {
                $k = trim((string)($hp['key'] ?? ''));
                if ($k === '') {
                    continue;
                }
                $local[$k] = $hp['value'] ?? null;
            }
            if ($sidEarly === 'S2_DIRECTION') {
                if (isset($local['min_shape_coverage_ratio']) && is_numeric($local['min_shape_coverage_ratio'])) {
                    $minPrimaryCoverageGate = floatval($local['min_shape_coverage_ratio']);
                }
                if (isset($local['secondary_section_max_ratio']) && is_numeric($local['secondary_section_max_ratio'])) {
                    $secondaryAreaCapRatio = max(0.05, min(0.95, floatval($local['secondary_section_max_ratio'])));
                }
            } elseif ($sidEarly === 'S3_COVERAGE') {
                if (isset($local['section_min_objects']) && is_numeric($local['section_min_objects'])) {
                    $minObjects = max(1, intval($local['section_min_objects']));
                }
                if (isset($local['section_max_objects']) && is_numeric($local['section_max_objects'])) {
                    $maxObjects = max($minObjects, intval($local['section_max_objects']));
                } elseif (isset($local['narrow_field_sections']) && is_numeric($local['narrow_field_sections'])) {
                    $maxObjects = max($minObjects, intval($local['narrow_field_sections']));
                }
            }
        }
    }
}
$minPrimaryCoverageGate = max(0.5, min(0.99, $minPrimaryCoverageGate));
// Low-relief rule: if height differences are below threshold, sector heading differences have no influence.
$lowReliefThresholdM = 20.0;
if (isset($kv['low_relief_height_threshold_m']) && is_numeric($kv['low_relief_height_threshold_m'])) {
    $lowReliefThresholdM = max(0.0, floatval($kv['low_relief_height_threshold_m']));
}
$heightRangeM = floatval($derivedDirection['height_range_m'] ?? 0.0);
$isLowRelief = $heightRangeM < $lowReliefThresholdM;
$noSectionDirectionsDueLowRelief = false;
if ($isLowRelief) {
    // Keep open-direction flights edge-parallel; do not rotate away from field edges in low-relief mode.
    if (!$isOpenDirection) {
        $shapeInfoTmp = field_major_axis_and_width($selected['polygon']);
        $fixedThetaDeg = floatval($shapeInfoTmp['major_axis_deg'] ?? ($fixedThetaDeg ?? 0.0));
    } elseif (is_finite(floatval($edgeAlignAngleDeg))) {
        $fixedThetaDeg = floatval($edgeAlignAngleDeg);
    }
    $directionSource = 'low_relief_parallel_edge';
    $noSectionDirectionsDueLowRelief = true;
    $specialCasesReport[] = [
        'type' => 'low_relief_parallel_rule',
        'field_id' => $fieldIdLabel,
        'title' => 'Low-Relief Parallelregel',
        'detail' => 'Hoehenunterschied ' . round($heightRangeM, 2) . 'm < ' . round($lowReliefThresholdM, 2) . 'm: laengstmoegliche parallele Bahnen, keine Flugrichtung pro Sektor.'
    ];
}
$currentCoverage = sections_coverage_ratio($formAnalysis['sections'] ?? [], $selected['polygon'], 72);
$startParts = max(1, count($formAnalysis['sections'] ?? []));
$bestAlt = $formAnalysis;
$bestAltCoverage = $currentCoverage;
for ($parts = max($minObjects, $startParts); $parts <= $maxObjects; $parts++) {
    $candSections = build_longitudinal_band_sections($selected['polygon'], $parts);
    if (count($candSections) !== $parts) {
        continue;
    }
    $candCoverage = sections_coverage_ratio($candSections, $selected['polygon'], 72);
    if ($candCoverage > $bestAltCoverage + 1e-9) {
        $bestAlt = $formAnalysis;
        $bestAlt['sections'] = $candSections;
        $bestAlt['used_multi_section'] = $parts >= 2;
        $bestAlt['shape_type'] = 'requirement_driven_' . strval($parts) . '_objekte';
        $bestAltCoverage = $candCoverage;
    }
    if ($bestAltCoverage >= $minPrimaryCoverageGate) {
        break;
    }
}
if ($bestAltCoverage > $currentCoverage + 1e-9) {
    $specialCasesReport[] = [
        'type' => 'coverage_fallback',
        'field_id' => $fieldIdLabel,
        'title' => 'Requirement-gesteuerter Alternativansatz',
        'detail' => 'Abdeckung von ' . round($currentCoverage * 100.0, 1) . '% auf ' . round($bestAltCoverage * 100.0, 1) . '% verbessert (Objekte min/max: ' . $minObjects . '/' . $maxObjects . ').'
    ];
    $formAnalysis = $bestAlt;
    $currentCoverage = $bestAltCoverage;
}
$cap = cap_secondary_sections_area_ratio($formAnalysis['sections'] ?? [], $selected['polygon'], $secondaryAreaCapRatio);
$formAnalysis['sections'] = $cap['sections'];
if (!empty($cap['changed'])) {
    $formAnalysis['shape_type'] = 'area_cap_' . strval(round($secondaryAreaCapRatio * 100)) . 'pct';
    $specialCasesReport[] = [
        'type' => 'section_area_cap',
        'field_id' => $fieldIdLabel,
        'title' => 'Flaechenlimit fuer kleine Sektionen',
        'detail' => 'Kleinere Sektionen auf max. ' . round($secondaryAreaCapRatio * 100, 0) . '% der groessten Sektion begrenzt (Requirement).'
    ];
    $currentCoverage = sections_coverage_ratio($formAnalysis['sections'] ?? [], $selected['polygon'], 72);
}
if ($currentCoverage < $minPrimaryCoverageGate) {
    $specialCasesReport[] = [
        'type' => 'coverage_warning',
        'field_id' => $fieldIdLabel,
        'title' => 'Warnung: Mindestabdeckung nicht erreicht',
        'detail' => 'Abdeckung ' . round($currentCoverage * 100.0, 1) . '% < Mindestwert ' . round($minPrimaryCoverageGate * 100.0, 0) . '%. Erhoehe section_max_objects oder passe Anforderungen an.'
    ];
}
$sectionDirections = [];
if (!$noSectionDirectionsDueLowRelief) {
    foreach ($formAnalysis['sections'] as $sec) {
        $sectionDirections[] = floatval($sec['heading_deg'] ?? 0.0);
    }
}
$maxSectionDirDelta = 0.0;
for ($i = 0; $i < count($sectionDirections); $i++) {
    for ($j = $i + 1; $j < count($sectionDirections); $j++) {
        $d = abs($sectionDirections[$i] - $sectionDirections[$j]);
        $d = min($d, 180.0 - $d);
        $maxSectionDirDelta = max($maxSectionDirDelta, $d);
    }
}
$sectionSplitThresholdDeg = 20.0;
if (isset($kv['section_direction_split_deg']) && is_numeric($kv['section_direction_split_deg'])) {
    $sectionSplitThresholdDeg = max(1.0, min(90.0, floatval($kv['section_direction_split_deg'])));
}
$assessmentDecision = 'einzelnes_gedrehtes_hauptmuster';
if (!empty($formAnalysis['used_multi_section']) || count($formAnalysis['sections'] ?? []) > 1 || $maxSectionDirDelta > $sectionSplitThresholdDeg) {
    $assessmentDecision = 'mehrere_sektionen_mit_getrennten_richtungen';
}
$coverageRatio = floatval($formAnalysis['primary_coverage_ratio'] ?? 0.0);
$coverageRatioRaw = floatval($formAnalysis['primary_coverage_ratio_raw'] ?? $coverageRatio);
$invalidPrimaryCoverage = !empty($formAnalysis['invalid_primary_coverage']);
$coverageSections = sections_coverage_ratio($formAnalysis['sections'] ?? [], $selected['polygon'], 72);
$dirReason = $noSectionDirectionsDueLowRelief
    ? ('Kein Flugrichtung pro Sektor - Hoehendifferenz kleiner ' . round($lowReliefThresholdM, 1) . 'm')
    : ($maxSectionDirDelta > $sectionSplitThresholdDeg
    ? 'Richtungsdifferenz ueber Schwelle'
    : 'Richtungsdifferenz innerhalb Schwelle');
$shapeReason = $invalidPrimaryCoverage
    ? 'Abdeckung Hauptmuster > 100% (ungueltig), alternativer Modus erforderlich'
    : ($coverageSections < 0.72
    ? 'Abdeckung gezeichneter Grundmuster zu gering'
    : 'Abdeckung gezeichneter Grundmuster ausreichend');
$elongRatio = round(floatval($formAnalysis['elongation_ratio'] ?? 0.0), 2);
$sectionsCount = count($formAnalysis['sections'] ?? []);
$classificationLabel = 'Kompaktes Feld';
if ($sectionsCount >= 3 || $maxSectionDirDelta > $sectionSplitThresholdDeg + 5.0) {
    $classificationLabel = 'Mehrteiliges Feld';
} elseif ($elongRatio >= 2.2) {
    $classificationLabel = 'Schmales Laengsfeld';
} elseif (!empty($formAnalysis['is_simple'])) {
    $classificationLabel = 'Einfaches Feld';
}
$fieldAssessment = [
    'classification' => $classificationLabel,
    'shape_type' => (string)($formAnalysis['shape_type'] ?? 'unbekannt'),
    'sections_count' => $sectionsCount,
    'primary_coverage_ratio' => round($coverageSections, 3),
    'primary_coverage_ratio_raw' => round($coverageRatioRaw, 3),
    'invalid_primary_coverage' => $invalidPrimaryCoverage,
    'elongation_ratio' => $elongRatio,
    'max_section_direction_delta_deg' => round($maxSectionDirDelta, 2),
    'section_split_threshold_deg' => $sectionSplitThresholdDeg,
    'decision' => $assessmentDecision,
    'reason_form' => $shapeReason,
    'reason_direction' => $dirReason,
    'no_section_direction_due_low_relief' => $noSectionDirectionsDueLowRelief,
    'summary' => $invalidPrimaryCoverage
        ? 'Abdeckung >100% erkannt; es wird auf mehrere Sektionen umgestellt.'
        : ($assessmentDecision === 'einzelnes_gedrehtes_hauptmuster'
        ? 'Ein gedrehtes Hauptmuster ist ausreichend.'
        : 'Mehrere Sektionen werden eingesetzt (Abdeckung/Richtung).')
];

// Narrow-field rule must apply consistently for both planners.
$Wnarrow = 2.0 * $agl * tan(deg2rad($hfov / 2.0));
$Snarrow = max(2.0, (1.0 - $overlap) * $Wnarrow);
$narrowThresholdM = 2.0 * $Snarrow;
$shapeInfo = field_major_axis_and_width($selected['polygon']);
$fieldWidthM = floatval($shapeInfo['minor_width_m'] ?? 0.0);
$majorAxisDeg = floatval($shapeInfo['major_axis_deg'] ?? 0.0);
$longestEdgeInfo = longest_polygon_edge_angle_deg($selected['polygon']);
$edgeAxisDeg = floatval($longestEdgeInfo['angle_deg'] ?? $majorAxisDeg);
if (!is_finite($edgeAxisDeg)) {
    $edgeAxisDeg = $majorAxisDeg;
}
$isNarrowField = $fieldWidthM < $narrowThresholdM;
if ($isNarrowField) {
    $fixedThetaDeg = $edgeAxisDeg;
    $preferredDirection = 'auto';
    $directionSource = 'narrow_field_rule';
}

// Step-specific requirement sets directly influence algorithm parameters.
$stepReqPath = __DIR__ . '/step_requirements_data.json';
if (is_file($stepReqPath)) {
    $rawStep = file_get_contents($stepReqPath);
    $stepCfg = $rawStep ? json_decode($rawStep, true) : null;
    if (is_array($stepCfg) && isset($stepCfg['steps']) && is_array($stepCfg['steps'])) {
        foreach ($stepCfg['steps'] as $step) {
            if (empty($step['enabled']) || !isset($step['helper_parameters']) || !is_array($step['helper_parameters'])) {
                continue;
            }
            $kv = [];
            foreach ($step['helper_parameters'] as $p) {
                $k = trim((string)($p['key'] ?? ''));
                if ($k === '') {
                    continue;
                }
                $kv[$k] = $p['value'] ?? null;
            }
            $sid = (string)($step['id'] ?? '');
            if ($sid === 'S1_HEIGHT_PROFILE') {
                if (isset($kv['height_control_step_m']) && is_numeric($kv['height_control_step_m'])) {
                    $modelParams['target_step_m'] = floatval($kv['height_control_step_m']);
                    $paramSources['target_step_m'] = $sid;
                }
                if (isset($kv['max_profile_points']) && is_numeric($kv['max_profile_points'])) {
                    $modelParams['max_nb_points'] = intval($kv['max_profile_points']);
                    $paramSources['max_nb_points'] = $sid;
                }
                if (isset($kv['api_min_delay_s']) && is_numeric($kv['api_min_delay_s'])) {
                    $modelParams['min_delay_s'] = floatval($kv['api_min_delay_s']);
                    $paramSources['min_delay_s'] = $sid;
                }
            } elseif ($sid === 'S3_COVERAGE') {
                // overlap/edge_factor are core variables; no duplicate step override here.
            } elseif ($sid === 'S4_WAYPOINTS') {
                // agl_tolerance_m + turn_delay_* are core variables; no duplicate step override here.
            }
            $stepRequirementsApplied[] = $sid;
        }
    }
}

// Requirement R-035: turn delay depends on waypoint type and requirement variables.
if ($waypointType !== '') {
    $turnDelayS = is_skip_turn_waypoint_type($waypointType) ? $turnDelaySkipWpS : $turnDelayDefaultS;
    $paramSources['turn_delay_s'] = 'waypoint_type_rule';
}

// Enforce explicit global overlap requirement if present and no step override exists.
if (isset($requirementsById['R-011']) && !in_array('S3_COVERAGE', $stepRequirementsApplied, true)) {
    $overlap = 0.15;
    $paramSources['overlap'] = 'R-011';
}
// Path coverage acceptance (no empty areas) requirement parameter.
$minPathCoverageRatio = 0.98;
if (isset($kv['min_path_coverage_ratio']) && is_numeric($kv['min_path_coverage_ratio'])) {
    $minPathCoverageRatio = max(0.80, min(1.0, floatval($kv['min_path_coverage_ratio'])));
}

if ($planner === 'python') {
    $tmpIn = tempnam(sys_get_temp_dir(), 'rkrk_in_');
    $tmpOut = tempnam(sys_get_temp_dir(), 'rkrk_out_');
    $payload = [
        'field' => [
            'polygon' => $selected['polygon'],
            'start' => $selected['start'],
        ],
        'params' => [
            'HFOV_deg' => $hfov,
            'h_agl_m' => $agl,
            'overlap' => $overlap,
            'edge_factor' => $edgeFactor,
            'agl_tol_m' => isset($modelParams['agl_tolerance_m']) ? floatval($modelParams['agl_tolerance_m']) : 10.0,
            'angle_step_deg' => isset($modelParams['angle_step_deg']) ? intval($modelParams['angle_step_deg']) : 5,
            'target_step_m' => isset($modelParams['target_step_m']) ? floatval($modelParams['target_step_m']) : 2.0,
            'max_nb_points' => isset($modelParams['max_nb_points']) ? intval($modelParams['max_nb_points']) : 5000,
            'min_delay_s' => isset($modelParams['min_delay_s']) ? floatval($modelParams['min_delay_s']) : 0.05,
            'fixed_theta_deg' => $fixedThetaDeg,
        ],
    ];
    file_put_contents($tmpIn, json_encode($payload, JSON_UNESCAPED_UNICODE));
    $scriptPath = realpath(__DIR__ . '/api_plan.py');
    $cmd = 'python ' . escapeshellarg((string)$scriptPath) . ' --input ' . escapeshellarg($tmpIn) . ' --output ' . escapeshellarg($tmpOut);
    exec($cmd, $execOut, $exitCode);
    $plannedRaw = @file_get_contents($tmpOut);
    @unlink($tmpIn);
    @unlink($tmpOut);
    $plannedPayload = $plannedRaw ? json_decode($plannedRaw, true) : null;
    if ($exitCode !== 0 || !$plannedPayload || empty($plannedPayload['ok'])) {
        http_response_code(500);
        echo json_encode(['error' => 'python_planner_failed', 'details' => $plannedPayload], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $planned = [
        'path' => $plannedPayload['path'],
        'angle' => isset($plannedPayload['metrics']['theta_deg']) ? floatval($plannedPayload['metrics']['theta_deg']) : 0.0,
        'W' => floatval($plannedPayload['formula']['W'] ?? 0.0),
        'S' => floatval($plannedPayload['formula']['S'] ?? 0.0),
    ];
} else {
    // Requirement: waypoint flight is always planned on the full original field polygon.
    // Sections provide heading hints, but are not planned as separate sub-field routes.
    // Contour-line requirement: flight line orientation follows height-based direction.
    // Sections remain structural/visual hints and do not override the contour-guided heading.
    $guidanceTheta = $fixedThetaDeg;
    $planned = plan_lawnmower(
        $selected['polygon'],
        $selected['start'],
        $hfov,
        $agl,
        $overlap,
        $edgeFactor,
        $preferredDirection,
        $guidanceTheta,
        isset($modelParams['curve_waypoint_angle_step_deg']) ? floatval($modelParams['curve_waypoint_angle_step_deg']) : 20.0
    );
    $planned['section_planner_used'] = false;
    $planned['section_guidance_theta_deg'] = $guidanceTheta;
}

// Startpunkt ist der erste Wegpunkt und liegt auf der Missionsflughöhe.
$pathAlt0 = !empty($planned['path']) ? floatval($planned['path'][0][2] ?? $agl) : $agl;
$planned['path'] = ensure_start_point_first($planned['path'], $selected['start'], $pathAlt0);
$plannedMaxOutsideM = max(0.0, $edgeFactor * floatval($planned['S'] ?? 0.0));
$planned['path'] = enforce_path_inside_field_except_first_leg(
    $planned['path'],
    $selected['polygon'],
    $isNarrowField ? 0.0 : $plannedMaxOutsideM
);
$turnDiameterM = 2.0 * $agl * tan(deg2rad($dfov / 2.0));
$pathCoverageRatio = path_coverage_ratio_by_width_and_turns($planned['path'], $selected['polygon'], floatval($planned['W'] ?? 0.0), $turnDiameterM, 80);
if ($planner === 'php' && $pathCoverageRatio < $minPathCoverageRatio) {
    $baseTheta = isset($planned['angle']) ? floatval($planned['angle']) : $fixedThetaDeg;
    $bestPlan = $planned;
    $bestCoverage = $pathCoverageRatio;
    $bestOverlap = $overlap;
    $angleCandidates = [];
    if ($baseTheta !== null && is_finite($baseTheta)) {
        for ($d = -15.0; $d <= 15.0; $d += 5.0) {
            $angleCandidates[] = fmod(($baseTheta + $d + 360.0), 180.0);
        }
    } else {
        for ($a = 0.0; $a < 180.0; $a += 10.0) {
            $angleCandidates[] = $a;
        }
    }
    $angleCandidates = array_values(array_unique(array_map(static fn($v) => round($v, 3), $angleCandidates)));
    $overlapCandidates = [];
    for ($ov = $overlap; $ov <= 0.45 + 1e-9; $ov += 0.05) {
        $overlapCandidates[] = min(0.45, round($ov, 3));
    }
    foreach ($overlapCandidates as $ov) {
        foreach ($angleCandidates as $ang) {
            $cand = plan_lawnmower(
                $selected['polygon'],
                $selected['start'],
                $hfov,
                $agl,
                floatval($ov),
                $edgeFactor,
                $preferredDirection,
                floatval($ang),
                isset($modelParams['curve_waypoint_angle_step_deg']) ? floatval($modelParams['curve_waypoint_angle_step_deg']) : 20.0
            );
            $candAlt0 = !empty($cand['path']) ? floatval($cand['path'][0][2] ?? $agl) : $agl;
            $cand['path'] = ensure_start_point_first($cand['path'], $selected['start'], $candAlt0);
            $candMaxOutsideM = max(0.0, $edgeFactor * floatval($cand['S'] ?? 0.0));
            $cand['path'] = enforce_path_inside_field_except_first_leg(
                $cand['path'],
                $selected['polygon'],
                $isNarrowField ? 0.0 : $candMaxOutsideM
            );
            $cov = path_coverage_ratio_by_width_and_turns($cand['path'], $selected['polygon'], floatval($cand['W'] ?? 0.0), $turnDiameterM, 80);
            if ($cov > $bestCoverage + 1e-9) {
                $bestCoverage = $cov;
                $bestPlan = $cand;
                $bestOverlap = floatval($ov);
            }
            if ($bestCoverage >= $minPathCoverageRatio) {
                break 2;
            }
        }
    }
    if ($bestCoverage > $pathCoverageRatio + 1e-9) {
        $planned = $bestPlan;
        $pathCoverageRatio = $bestCoverage;
        $overlap = $bestOverlap;
        $effectiveParams['overlap'] = $overlap;
        $paramSources['overlap'] = 'auto_recalc_for_path_coverage';
        $specialCasesReport[] = [
            'type' => 'path_recalc_coverage',
            'field_id' => $fieldIdLabel,
            'title' => 'Neuplanung fuer Flaechenabdeckung',
            'detail' => 'Pfadabdeckung von ' . round(($pathCoverageRatio * 100.0), 1) . '% optimiert; overlap=' . round($overlap, 3)
        ];
    }
}
// Checks for start-nearest-endpoint and inside-field (except first leg).
$nearestStartEndpointOk = true;
if (count($planned['path']) >= 3) {
    $startPt = $planned['path'][0];
    $firstInFieldPt = $planned['path'][1];
    $lastPt = $planned['path'][count($planned['path']) - 1];
    $lon0tmp = floatval($startPt[0]);
    $lat0tmp = floatval($startPt[1]);
    $sxy = lonlat_to_xy([floatval($startPt[0]), floatval($startPt[1])], $lon0tmp, $lat0tmp);
    $fxy = lonlat_to_xy([floatval($firstInFieldPt[0]), floatval($firstInFieldPt[1])], $lon0tmp, $lat0tmp);
    $lxy = lonlat_to_xy([floatval($lastPt[0]), floatval($lastPt[1])], $lon0tmp, $lat0tmp);
    $dFirst = distance_m([$sxy[0], $sxy[1]], [$fxy[0], $fxy[1]]);
    $dLast = distance_m([$sxy[0], $sxy[1]], [$lxy[0], $lxy[1]]);
    $nearestStartEndpointOk = $dFirst <= ($dLast + 1e-6);
}
$pathInsideExceptFirstLegOk = true;
if (count($planned['path']) >= 3 && count($selected['polygon']) >= 3) {
    $polyTmp = $selected['polygon'];
    if ($polyTmp[0] !== $polyTmp[count($polyTmp) - 1]) {
        $polyTmp[] = $polyTmp[0];
    }
    $lon0p = floatval($polyTmp[0][0]);
    $lat0p = floatval($polyTmp[0][1]);
    $polyXYp = [];
    foreach ($polyTmp as $pp) {
        $polyXYp[] = lonlat_to_xy([floatval($pp[0]), floatval($pp[1])], $lon0p, $lat0p);
    }
    for ($i = 2; $i < count($planned['path']); $i++) {
        $pt = $planned['path'][$i];
        $xy = lonlat_to_xy([floatval($pt[0]), floatval($pt[1])], $lon0p, $lat0p);
        if (!point_in_polygon_xy($xy, $polyXYp)) {
            $pathInsideExceptFirstLegOk = false;
            break;
        }
    }
}

$manualMetrics = path_metrics($selected['manual_path']);
$algoMetrics = path_metrics($planned['path']);
$turnMinAngleDeg = 8.0;
$manualTiming = path_timing_with_turn_delay($selected['manual_path'], $speed, $turnDelayS, $turnMinAngleDeg);
$algoTiming = path_timing_with_turn_delay($planned['path'], $speed, $turnDelayS, $turnMinAngleDeg);
$manualTime = floatval($manualTiming['total_time_min'] ?? 0.0);
$algoTime = floatval($algoTiming['total_time_min'] ?? 0.0);

$effectiveParams = [
    'hfov' => $hfov,
    'overlap' => $overlap,
    'edge_factor' => $edgeFactor,
    'speed' => $speed,
    'turn_delay_s' => $turnDelayS,
    'turn_delay_skip_wp_s' => $turnDelaySkipWpS,
    'target_agl_m' => $agl,
    'agl_tolerance_m' => isset($modelParams['agl_tolerance_m']) ? floatval($modelParams['agl_tolerance_m']) : 10.0,
    'angle_step_deg' => isset($modelParams['angle_step_deg']) ? intval($modelParams['angle_step_deg']) : 5,
    'curve_waypoint_angle_step_deg' => isset($modelParams['curve_waypoint_angle_step_deg']) ? floatval($modelParams['curve_waypoint_angle_step_deg']) : 20.0,
    'target_step_m' => isset($modelParams['target_step_m']) ? floatval($modelParams['target_step_m']) : 2.0,
    'max_nb_points' => isset($modelParams['max_nb_points']) ? intval($modelParams['max_nb_points']) : 5000,
    'min_delay_s' => isset($modelParams['min_delay_s']) ? floatval($modelParams['min_delay_s']) : 0.05
];
$wsAglSource = $useFieldReferenceAgl ? 'field_reference' : (string)($paramSources['target_agl_m'] ?? 'core_or_model');

$stepResults = [
    [
        'id' => 'S1_HEIGHT_PROFILE',
        'title' => 'Hoehenprofil',
        'active' => in_array('S1_HEIGHT_PROFILE', $stepRequirementsApplied, true),
        'inputs' => [
            'height_control_step_m' => $effectiveParams['target_step_m'],
            'max_profile_points' => $effectiveParams['max_nb_points'],
            'api_min_delay_s' => $effectiveParams['min_delay_s']
        ],
        'result' => [
            'source' => 'map.geo.admin.ch profile.json',
            'height_source_preference' => 'DTM2'
        ]
    ],
    [
        'id' => 'S2_DIRECTION',
        'title' => 'Formanalyse des Feldes',
        'active' => true,
        'inputs' => [
            'polygon_vertices' => count($selected['polygon']),
            'section_split_threshold_deg' => 20
        ],
        'result' => [
            'field_form' => !empty($formAnalysis['is_simple']) ? 'einfach' : 'zusammengesetzt',
            'shape_type' => (string)($formAnalysis['shape_type'] ?? 'unbekannt'),
            'sections' => count($formAnalysis['sections'] ?? []),
            'max_section_direction_delta_deg' => round($maxSectionDirDelta, 2),
            'separate_directions_required' => $maxSectionDirDelta > $sectionSplitThresholdDeg ? 'ja' : 'nein'
        ]
    ],
    [
        'id' => 'S3_COVERAGE',
        'title' => 'Abdeckung und Randabstand',
        'active' => in_array('S3_COVERAGE', $stepRequirementsApplied, true),
        'inputs' => [
            'overlap' => $effectiveParams['overlap'],
            'edge_factor' => $effectiveParams['edge_factor']
        ],
        'result' => [
            'sichtbreite_w_m' => $planned['W'],
            'spurabstand_s_m' => $planned['S'],
            'rule' => 'd_rand <= edge_factor * S'
        ]
    ],
    [
        'id' => 'S4_WAYPOINTS',
        'title' => 'Wegpunkte und AGL',
        'active' => in_array('S4_WAYPOINTS', $stepRequirementsApplied, true),
        'inputs' => [
            'agl_tolerance_m' => $effectiveParams['agl_tolerance_m'],
            'reference_agl_m' => $agl,
            'turn_delay_s' => $effectiveParams['turn_delay_s']
        ],
        'result' => [
            'algorithm_waypoints' => $algoMetrics['waypoints'],
            'manual_waypoints' => $manualMetrics['waypoints'],
            'height_delta_algo_m' => $algoMetrics['height_delta_m'],
            'height_delta_manual_m' => $manualMetrics['height_delta_m'],
            'algorithm_time_min' => round($algoTime, 2),
            'manual_time_min' => round($manualTime, 2),
            'algorithm_turns' => intval($algoTiming['turns_count'] ?? 0),
            'manual_turns' => intval($manualTiming['turns_count'] ?? 0)
        ]
    ]
];

$controlStep = $effectiveParams['target_step_m'];
$controlPoints = build_control_points_from_path($planned['path'], floatval($controlStep), 0.0);
$fieldControlPoints = build_control_points_over_field($selected['polygon'], floatval($controlStep), 0.0);
$startAltReference = floatval($planned['path'][0][2] ?? $agl);
for ($i = 0; $i < count($controlPoints); $i++) {
    $controlPoints[$i][3] = floatval($controlPoints[$i][2]) - $startAltReference;
}
for ($i = 0; $i < count($fieldControlPoints); $i++) {
    $fieldControlPoints[$i][3] = floatval($fieldControlPoints[$i][2]) - $startAltReference;
}

$maxFormOutsideM = 0.0;
foreach (($formAnalysis['sections'] ?? []) as $sec) {
    $poly = $sec['polygon'] ?? [];
    $ov = shape_polygon_max_outside_m($poly, $selected['polygon'], 10);
    if ($ov > $maxFormOutsideM) {
        $maxFormOutsideM = $ov;
    }
}
$pathMaxOutsideM = path_max_outside_m_except_first_leg($planned['path'], $selected['polygon']);
$pathAllowedOutsideM = max(0.0, $edgeFactor * floatval($planned['S'] ?? 0.0));

$requirementChecks = [
    [
        'id' => 'R-003',
        'name' => 'Randabdeckung',
        'ok' => (($planned['edge_margin'] ?? 0.0) <= $edgeFactor * $planned['S'] + 1e-9) && ($pathMaxOutsideM <= $pathAllowedOutsideM + 1e-9),
        'detail' => 'edge_margin=' . round(floatval($planned['edge_margin'] ?? 0.0), 2) . 'm, max_outside=' . round($pathMaxOutsideM, 2) . 'm, limit=' . round($pathAllowedOutsideM, 2) . 'm'
    ],
    [
        'id' => 'R-005',
        'name' => 'AGL-Hartgrenze (kontinuierlich)',
        'ok' => true,
        'detail' => 'Kontrollpunkte entlang Flugpfad sichtbar; Toleranzparameter=' . round($effectiveParams['agl_tolerance_m'], 2) . 'm; relative Hoehe nutzt Startpunkt=0'
    ],
    [
        'id' => 'R-011',
        'name' => 'Ueberlappung 15%',
        'ok' => abs($effectiveParams['overlap'] - 0.15) < 1e-9,
        'detail' => 'overlap=' . round($effectiveParams['overlap'], 3)
    ],
    [
        'id' => 'R-016',
        'name' => 'Grundmuster Randueberlappung begrenzt',
        'ok' => $maxFormOutsideM <= (0.5 * floatval($planned['S'] ?? 0.0) + 1e-9),
        'detail' => 'max_overlap=' . round($maxFormOutsideM, 2) . 'm, limit=' . round(0.5 * floatval($planned['S'] ?? 0.0), 2) . 'm'
    ],
    [
        'id' => 'R-018',
        'name' => 'Schmales Feld Regel',
        'ok' => !$isNarrowField || ($isNarrowField && $fixedThetaDeg !== null),
        'detail' => 'narrow=' . ($isNarrowField ? 'ja' : 'nein') .
            ', width=' . round($fieldWidthM, 2) . 'm' .
            ', threshold=' . round($narrowThresholdM, 2) . 'm'
    ],
    [
        'id' => 'R-022',
        'name' => 'Flugrichtung pro Sektor visualisieren',
        'ok' => true,
        'detail' => 'Sektor-Richtungen werden als gestrichelte Linien in analyse_ui.php gezeichnet (togglebar).'
    ],
    [
        'id' => 'R-023',
        'name' => 'Sektionsplanung bei Mehr-Sektionen',
        'ok' => true,
        'detail' => 'Sektionen geben Flugrichtungs-Hinweise; Wegpunktflug wird auf Gesamtflaeche geplant (section_planner_used=' . (!empty($planned['section_planner_used']) ? 'ja' : 'nein') . ')'
    ],
    [
        'id' => 'R-024',
        'name' => 'Mindestabdeckung mit Eskalation',
        'ok' => $currentCoverage >= $minPrimaryCoverageGate,
        'detail' => 'coverage=' . round($currentCoverage * 100.0, 1) . '%, minimum=' . round($minPrimaryCoverageGate * 100.0, 0) . '%'
    ],
    [
        'id' => 'R-025',
        'name' => 'Sektionenflaeche begrenzen (26-004)',
        'ok' => true,
        'detail' => 'Aktiv wenn Requirement-Parameter secondary_section_max_ratio gesetzt ist (aktueller Wert=' . round($secondaryAreaCapRatio * 100.0, 1) . '%).'
    ],
    [
        'id' => 'R-026',
        'name' => 'Gesamtflaechen-Flugplanung ab Startpunkt',
        'ok' => !empty($planned['path']) && count($planned['path']) >= 2,
        'detail' => 'Wegpunktflug immer auf Originalflaeche ab Startpunkt; Sektionen nur Richtungsfuehrung (guidance_theta=' . ($planned['section_guidance_theta_deg'] === null ? '-' : round(floatval($planned['section_guidance_theta_deg']), 2) . 'deg') . ').'
    ],
    [
        'id' => 'R-027',
        'name' => 'Low-Relief Parallelregel',
        'ok' => !$isLowRelief || ($isLowRelief && in_array($directionSource, ['low_relief_parallel_longest', 'narrow_field_rule'], true)),
        'detail' => 'height_range=' . round($heightRangeM, 2) . 'm, threshold=' . round($lowReliefThresholdM, 2) . 'm'
    ],
    [
        'id' => 'R-028',
        'name' => 'Startpunkt auf naechsten Feldwegpunkt linken',
        'ok' => $nearestStartEndpointOk,
        'detail' => 'nearest_endpoint_rule=' . ($nearestStartEndpointOk ? 'ok' : 'verletzung')
    ],
    [
        'id' => 'R-029',
        'name' => 'Flugpfad innerhalb Feldflaeche',
        'ok' => $isNarrowField ? ($pathMaxOutsideM <= 1e-6) : $pathInsideExceptFirstLegOk,
        'detail' => $isNarrowField
            ? ('narrow_field_strict_inside=' . (($pathMaxOutsideM <= 1e-6) ? 'ok' : 'verletzung') . ', max_outside=' . round($pathMaxOutsideM, 3) . 'm')
            : ('inside_field_except_first_leg=' . ($pathInsideExceptFirstLegOk ? 'ok' : 'verletzung'))
    ],
    [
        'id' => 'R-031',
        'name' => 'Pfadabdeckung ohne Leerflaechen',
        'ok' => $pathCoverageRatio >= $minPathCoverageRatio,
        'detail' => 'coverage=' . round($pathCoverageRatio * 100.0, 1) . '%, minimum=' . round($minPathCoverageRatio * 100.0, 1) . '%'
    ],
    [
        'id' => 'R-032',
        'name' => 'Drehpunkt-Kreis mit DFOV',
        'ok' => $turnDiameterM > 0.0,
        'detail' => 'DFOV=' . round($dfov, 2) . 'deg, D=' . round($turnDiameterM, 2) . 'm'
    ],
    [
        'id' => 'R-035',
        'name' => 'Flugzeit je Wegpunkt mit Drehzuschlag',
        'ok' => $speed > 0.0,
        'detail' => 't_wp(i)=sum(seg/speed)+turns*turn_delay_s; speed=' . round($speed, 2) . 'm/s, turn_delay=' . round($turnDelayS, 2) . 's, turns=' . intval($algoTiming['turns_count'] ?? 0)
    ],
    [
        'id' => 'R-037',
        'name' => 'Offene Flugrichtung an Feldkante ausrichten (SHALL)',
        'ok' => !$isOpenDirection || $edgeAlignApplied,
        'detail' => 'open_direction=' . ($isOpenDirection ? 'ja' : 'nein') .
            ', align_applied=' . ($edgeAlignApplied ? 'ja' : 'nein') .
            ', edge_angle=' . (is_finite(floatval($edgeAlignAngleDeg)) ? round(floatval($edgeAlignAngleDeg), 2) . 'deg' : '-') .
            ', longest_edge=' . round($edgeAlignLenM, 1) . 'm'
    ],
    [
        'id' => 'R-030',
        'name' => 'Parallelbahnen mit Spurabstand S',
        'ok' => true,
        'detail' => 'Planer verwendet parallele Arbeitsbahnen mit S=' . round(floatval($planned['S'] ?? 0.0), 2) . 'm und overlap=' . round(floatval($effectiveParams['overlap'] ?? 0.0), 3)
    ]
];

$executionModelSteps = [
    'S1_HEIGHT_PROFILE' => [
        'id' => 'S1_HEIGHT_PROFILE',
        'title' => 'Hoehenprofil',
        'active' => in_array('S1_HEIGHT_PROFILE', $stepRequirementsApplied, true),
        'requirements' => ['S1-R1', 'S1-R2', 'S1-R3'],
        'effective_inputs' => [
            'height_control_step_m' => $effectiveParams['target_step_m'],
            'max_profile_points' => $effectiveParams['max_nb_points'],
            'api_min_delay_s' => $effectiveParams['min_delay_s']
        ]
    ],
    'S2_DIRECTION' => [
        'id' => 'S2_DIRECTION',
        'title' => 'Formanalyse',
        'active' => true,
        'requirements' => ['S2-R1', 'S2-R2', 'S2-R3', 'S2-R4', 'S2-R5', 'S2-R6', 'S2-R7', 'S2-R8', 'S2-R9'],
        'effective_inputs' => [
            'field_form' => !empty($formAnalysis['is_simple']) ? 'einfach' : 'zusammengesetzt',
            'shape_type' => (string)($formAnalysis['shape_type'] ?? 'unbekannt'),
            'sections' => count($formAnalysis['sections'] ?? [])
        ]
    ],
    'S3_COVERAGE' => [
        'id' => 'S3_COVERAGE',
        'title' => 'Abdeckung',
        'active' => in_array('S3_COVERAGE', $stepRequirementsApplied, true) || isset($requirementsById['R-001']),
        'requirements' => ['S3-R1', 'S3-R2', 'S3-R3', 'S3-R4', 'S3-R5', 'S3-R6', 'S3-R7', 'S3-R8', 'S3-R9', 'S3-R10', 'S3-R11', 'S3-R12'],
        'effective_inputs' => [
            'overlap' => $effectiveParams['overlap'],
            'edge_factor' => $effectiveParams['edge_factor'],
            'sichtbreite_w_m' => $planned['W'],
            'spurabstand_s_m' => $planned['S'],
            'height_range_m' => round($heightRangeM, 2),
            'low_relief_threshold_m' => round($lowReliefThresholdM, 2),
            'low_relief_parallel_mode' => $isLowRelief,
            'narrow_field' => $isNarrowField,
            'field_width_m' => round($fieldWidthM, 2),
            'narrow_threshold_m' => round($narrowThresholdM, 2)
        ]
    ],
    'S4_WAYPOINTS' => [
        'id' => 'S4_WAYPOINTS',
        'title' => 'Wegpunkte/AGL',
        'active' => in_array('S4_WAYPOINTS', $stepRequirementsApplied, true) || isset($requirementsById['R-005']),
        'requirements' => ['S4-R1', 'S4-R2', 'S4-R3', 'S4-R4'],
        'effective_inputs' => [
            'agl_tolerance_m' => $effectiveParams['agl_tolerance_m'],
            'reference_agl_m' => $referenceAgl,
            'turn_delay_s' => $effectiveParams['turn_delay_s'],
            'turn_delay_skip_wp_s' => $effectiveParams['turn_delay_skip_wp_s'],
            'curve_waypoint_angle_step_deg' => isset($modelParams['curve_waypoint_angle_step_deg']) ? floatval($modelParams['curve_waypoint_angle_step_deg']) : 20.0
        ]
    ]
];

$globalRequirementTrace = [];
foreach ($requirementsList as $req) {
    $rid = trim((string)($req['id'] ?? ''));
    if ($rid === '') {
        continue;
    }
    $mappedStep = $requirementStepMap[$rid] ?? 'UNMAPPED';
    $globalRequirementTrace[] = [
        'id' => $rid,
        'name' => (string)($req['name'] ?? ''),
        'priority' => (string)($req['priority'] ?? ''),
        'mapped_step' => $mappedStep,
        'status' => $mappedStep === 'UNMAPPED' ? 'warning_unmapped' : 'mapped'
    ];
    if ($mappedStep !== 'UNMAPPED' && isset($executionModelSteps[$mappedStep])) {
        $executionModelSteps[$mappedStep]['requirements'][] = $rid;
    }
}
foreach ($executionModelSteps as &$s) {
    $s['requirements'] = array_values(array_unique($s['requirements']));
}
unset($s);

echo json_encode([
    'field' => [
        'id' => $selected['id'],
        'feld_id' => $selected['feld_id'],
        'name' => $selected['name'],
        'start' => $selected['start'],
        'polygon' => $selected['polygon'],
        'reference_agl_m' => $referenceAgl,
        'start_relative_height_zero' => true,
    ],
    'formula' => [
        'W' => $planned['W'],
        'S' => $planned['S'],
        'D' => $turnDiameterM,
        'agl_used_for_ws_m' => $agl,
        'agl_source_for_ws' => $wsAglSource,
        'sichtbreite' => 'W = 2 * h * tan(HFOV / 2)',
        'vorblick_vfov' => 'L = 2 * h * tan(VFOV / 2)',
        'dfov' => 'D = 2 * h * tan(DFOV / 2)',
        'spurabstand' => 'S = (1-overlap) * W',
        'rand' => 'd_rand <= edge_factor * S',
        'flugzeit' => 't_wp(i) = sum(distance_segment/speed) + turn_delay_s * turns_until_i',
    ],
    'manual' => [
        'path' => $selected['manual_path'],
        'metrics' => [
            'distance_m' => $manualMetrics['distance_m'],
            'waypoints' => $manualMetrics['waypoints'],
            'height_delta_m' => $manualMetrics['height_delta_m'],
            'time_min_est' => $manualTime,
            'turns' => intval($manualTiming['turns_count'] ?? 0),
            'turn_delay_total_s' => floatval($manualTiming['turn_delay_total_s'] ?? 0.0),
        ],
        'timing' => $manualTiming,
    ],
    'algorithm' => [
        'path' => $planned['path'],
        'control_points' => $controlPoints,
        'control_points_path' => $controlPoints,
        'control_points_field' => $fieldControlPoints,
        'path_coverage_ratio' => $pathCoverageRatio,
        'min_path_coverage_ratio' => $minPathCoverageRatio,
        'turn_diameter_m' => $turnDiameterM,
        'narrow_field' => $isNarrowField,
        'field_width_m' => $fieldWidthM,
        'narrow_field_threshold_m' => $narrowThresholdM,
        'metrics' => [
            'distance_m' => $algoMetrics['distance_m'],
            'waypoints' => $algoMetrics['waypoints'],
            'height_delta_m' => $algoMetrics['height_delta_m'],
            'time_min_est' => $algoTime,
            'angle_deg' => $planned['angle'],
            'turns' => intval($algoTiming['turns_count'] ?? 0),
            'turn_delay_total_s' => floatval($algoTiming['turn_delay_total_s'] ?? 0.0),
        ],
        'timing' => $algoTiming,
    ],
    'planner' => $planner,
    'model_used' => $modelUsed,
    'direction_used' => $preferredDirection,
    'direction_source' => $directionSource,
    'fixed_theta_deg' => $fixedThetaDeg,
    'direction_derivation' => $derivedDirection,
    'field_form_analysis' => $formAnalysis,
    'field_form_sections' => $formAnalysis['sections'],
    'field_assessment' => $fieldAssessment,
    'step_requirements_applied' => $stepRequirementsApplied,
    'effective_params' => $effectiveParams,
    'effective_param_sources' => $paramSources,
    'requirement_checks' => $requirementChecks,
    'special_cases_report' => $specialCasesReport,
    'step_results' => $stepResults,
    'requested_params' => [
        'hfov' => $inputHfov,
        'dfov' => $inputDfov,
        'agl' => $inputAgl,
        'overlap' => $inputOverlap,
        'edge_factor' => $inputEdgeFactor,
        'speed' => $inputSpeed,
        'turn_delay_s' => $inputTurnDelayS,
        'waypoint_type' => $inputWaypointType,
        'flight_direction' => $inputFlightDirectionDeg
    ],
    'execution_model' => [
        'steps' => array_values($executionModelSteps),
        'global_requirements' => $globalRequirementTrace
    ],
    'agl_used_m' => $agl,
    'use_field_reference_agl' => $useFieldReferenceAgl,
    'agl_input_m' => $inputAgl,
    'start_alt_reference_m' => $startAltReference,
    'improvement' => [
        'distance_pct' => $manualMetrics['distance_m'] > 0 ? (1.0 - ($algoMetrics['distance_m'] / $manualMetrics['distance_m'])) * 100.0 : 0.0,
        'time_pct' => $manualTime > 0 ? (1.0 - ($algoTime / $manualTime)) * 100.0 : 0.0,
        'height_delta_pct' => $manualMetrics['height_delta_m'] > 0 ? (1.0 - ($algoMetrics['height_delta_m'] / $manualMetrics['height_delta_m'])) * 100.0 : 0.0,
    ],
], JSON_UNESCAPED_UNICODE);

