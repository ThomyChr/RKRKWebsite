<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
$folderoffset = "";
include_once($folderoffset.'scripts/rkrk_gen.php');
include_once($folderoffset.'scripts/rkrk_menu.php');
include_once($folderoffset.'scripts/rkrk_csvfiles.php');
include_once($folderoffset.'rkrk_access_control.php');
$_rkrkAssessCommon = __DIR__ . DIRECTORY_SEPARATOR . 'assess' . DIRECTORY_SEPARATOR . 'rkrk_assess_common.php';
include_once(is_file($_rkrkAssessCommon) ? $_rkrkAssessCommon : (__DIR__ . DIRECTORY_SEPARATOR . 'rkrk_assess_common.php'));

//check impropriate IP addresses accessing page
CheckIPandExit();

//Write to LOGfile
WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","");

$rkrkAssessThemeCss = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'assess' . DIRECTORY_SEPARATOR . 'rkrk_assess_theme.css')
	? 'assess/rkrk_assess_theme.css'
	: 'rkrk_assess_theme.css';
echo "<link href='" . $rkrkAssessThemeCss . "' rel='stylesheet' type='text/css'>" . chr(10);
headerpage("RKRK Pruefungs-Admin", false, $folderoffset);
GrantAccess();
if (empty($LoggedOn)) {
	if (function_exists('RegisterForm')) {
		RegisterForm(getIPAddress(), "Login", true);
	}
	exit;
}

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
	$currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
	$currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');
if ($showTopMenu) {
	CreateTopMenu($folderoffset);
	echo "<br><br>";
}


function esc_html($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_key($v) {
    return strtolower(trim((string)$v));
}

function exam_admin_debug_log($runId, $hypothesisId, $location, $message, $data = array()) {
    $entry = array(
        'sessionId' => '1791ba',
        'runId' => (string)$runId,
        'hypothesisId' => (string)$hypothesisId,
        'location' => (string)$location,
        'message' => (string)$message,
        'data' => is_array($data) ? $data : array('value' => (string)$data),
        'timestamp' => round(microtime(true) * 1000),
    );
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $paths = array(
        __DIR__ . DIRECTORY_SEPARATOR . 'debug-1791ba.log',
        '\\\\tchr-frog.synology.me@ssl@5606\\DavWWWRoot\\web\\rkropen\\test\\exam\\debug-1791ba.log',
    );
    foreach ($paths as $path) {
        @file_put_contents($path, $line, FILE_APPEND);
    }
}

function parse_attempt_ts($dateText, $fallbackIndex) {
    $dateText = trim((string)$dateText);
    if ($dateText === '') {
        return $fallbackIndex;
    }
    $patterns = array('d.m.Y H:i', 'd.m.Y h:i', 'd.m.Y h:m');
    foreach ($patterns as $pattern) {
        $dt = DateTime::createFromFormat($pattern, $dateText);
        if ($dt instanceof DateTime) {
            return $dt->getTimestamp();
        }
    }
    $ts = strtotime($dateText);
    if ($ts !== false) {
        return $ts;
    }
    return $fallbackIndex;
}

function resolve_first_existing_file($paths) {
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return $paths[0];
}

function exam_admin_load_json_file($path) {
    if (!file_exists($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = @json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function exam_admin_load_template($templatesDir, $tplName) {
    $tplName = trim((string)$tplName);
    if ($tplName === '') return null;

    $candidates = array(
        $templatesDir . '/' . $tplName,
        $templatesDir . '/' . $tplName . '.json',
    );
    foreach ($candidates as $path) {
        $tpl = exam_admin_load_json_file($path);
        if (is_array($tpl)) return $tpl;
    }
    return null;
}

function exam_admin_format_datetime($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';

    $patterns = array('Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i');
    foreach ($patterns as $pattern) {
        $dt = DateTime::createFromFormat($pattern, $raw);
        if ($dt instanceof DateTime) {
            return ($dt->format('H:i') === '00:00') ? $dt->format('d.m.Y') : $dt->format('d.m.Y H:i');
        }
    }
    $ts = strtotime($raw);
    if ($ts !== false) {
        return (date('H:i', $ts) === '00:00') ? date('d.m.Y', $ts) : date('d.m.Y H:i', $ts);
    }
    return $raw;
}

function exam_admin_value_to_text($value) {
    if (is_array($value)) return implode('; ', array_map('strval', $value));
    return trim((string)$value);
}

function exam_admin_strip_markers($text) {
    $text = (string)$text;
    $text = preg_replace('/\s*\(([rgo])\)\s*/iu', ' ', $text);
    $text = preg_replace('/\s{2,}/u', ' ', $text);
    return trim((string)$text);
}

function exam_admin_find_first_field_value($fields, $labels) {
    if (!is_array($fields)) return '';
    foreach ($labels as $label) {
        if (isset($fields['Allgemein']) && is_array($fields['Allgemein']) && isset($fields['Allgemein'][$label])) {
            $val = trim((string)$fields['Allgemein'][$label]);
            if ($val !== '') return $val;
        }
    }
    foreach ($fields as $secFields) {
        if (!is_array($secFields)) continue;
        foreach ($labels as $label) {
            if (!isset($secFields[$label])) continue;
            $val = trim((string)$secFields[$label]);
            if ($val !== '') return $val;
        }
    }
    return '';
}

function exam_admin_parse_ts($raw, $fallback = 0) {
    $raw = trim((string)$raw);
    if ($raw === '') return (int)$fallback;
    $ts = strtotime(str_replace('T', ' ', $raw));
    return ($ts === false) ? (int)$fallback : (int)$ts;
}

function exam_admin_rating_badge($value) {
    $rawValue = trim((string)$value);
    if ($rawValue === '') return '';

    $bg = '#e5e7eb';
    $fg = '#374151';
    if (preg_match('/\(r\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'nicht', 0, 'UTF-8') !== false || mb_stripos($rawValue, 'fail', 0, 'UTF-8') !== false) {
        $bg = '#fee2e2';
        $fg = '#991b1b';
    } elseif (preg_match('/\(o\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'warn', 0, 'UTF-8') !== false) {
        $bg = '#ffedd5';
        $fg = '#9a3412';
    } elseif (preg_match('/\(g\)\s*$/i', $rawValue) || mb_stripos($rawValue, 'bestanden', 0, 'UTF-8') !== false || mb_stripos($rawValue, 'ok', 0, 'UTF-8') !== false) {
        $bg = '#dcfce7';
        $fg = '#166534';
    }

    $value = exam_admin_strip_markers($rawValue);

    return "<span style='display:inline-block;padding:3px 8px;border-radius:999px;background:" . esc_html($bg) . ";color:" . esc_html($fg) . ";font-weight:700;'>" . esc_html($value) . "</span>";
}

// Pfade aus rkrk_gen.php (SetMissionLocation): $RKRKRelRKRKDataLocation, $WebLocation, $csvDocLocation.
$QuizLoc = rkrk_normalize_rkropen_path($RKRKRelRKRKDataLocation . 'data/assess/');
// Logo/Icons per relativer URL (funktioniert unabhängig von hartcodiertem $WebLocation in rkrk_gen.php).
$rkrkAssessImagesUrlBase = (preg_match('#[/\\\\]assess$#i', str_replace('\\', '/', __DIR__)))
	? '../../rkrk/data/assess/images/'
	: '../rkrk/data/assess/images/';
$QuizIconLoc = rkrk_normalize_rkropen_path($rkrkAssessImagesUrlBase);
$CalcIconLoc = $QuizIconLoc;

// QuizLog im Missions-CSV-Ordner ($csvDocLocation aus rkrk_gen.php).
$quizLogPath = (isset($csvDocLocation) && trim((string)$csvDocLocation) !== '')
    ? rkrk_normalize_rkropen_path($csvDocLocation . 'QuizLog.csv')
    : '';

$quizListCandidates = array(
    $QuizLoc . '/QuizListe.csv',
);
$quizListPath = resolve_first_existing_file($quizListCandidates);

$quizRows = csv_to_array_ascII($quizListPath, ';');
if (!is_array($quizRows)) {
    $quizRows = array();
}
$quizTitleByKey = array();
foreach ($quizRows as $q) {
    $k = trim((string)(isset($q['Key']) ? $q['Key'] : ''));
    if ($k === '') continue;
    $quizTitleByKey[$k] = trim((string)(isset($q['Titel']) ? $q['Titel'] : $k));
}

$rows = csv_to_array_ascII($quizLogPath, ';');
if (!is_array($rows)) {
    $rows = array();
}

$attempts = array();
$idx = 0;
foreach ($rows as $row) {
    $idx++;
    $name = trim((string)(isset($row['Name']) ? $row['Name'] : ''));
    $quiz = trim((string)(isset($row['Quiz']) ? $row['Quiz'] : ''));
    $date = trim((string)(isset($row['Zeit']) ? $row['Zeit'] : ''));
    $anz = (int)(isset($row['AnzFragen']) ? $row['AnzFragen'] : 0);
    $richtig = (int)(isset($row['Richtig']) ? $row['Richtig'] : 0);
    $factor = (float)(isset($row['Erfuellungsfaktor']) ? $row['Erfuellungsfaktor'] : 0);
    $status = strtolower(trim((string)(isset($row['Erfuellung']) ? $row['Erfuellung'] : '')));
    $pct = ($anz > 0) ? round(($richtig / $anz) * 100, 1) : 0.0;
    $passed = ($status === 'ja') || (($anz > 0) && (($richtig / $anz) >= $factor));
    $attempts[] = array(
        'idx' => $idx,
        'name' => $name,
        'name_key' => normalize_key($name),
        'quiz' => $quiz,
        'quiz_title' => (isset($quizTitleByKey[$quiz]) ? $quizTitleByKey[$quiz] : $quiz),
        'date' => $date,
        'timestamp' => parse_attempt_ts($date, $idx),
        'anz' => $anz,
        'richtig' => $richtig,
        'factor' => $factor,
        'pct' => $pct,
        'passed' => $passed,
    );
}

usort($attempts, function ($a, $b) {
    if ($a['timestamp'] === $b['timestamp']) return $b['idx'] <=> $a['idx'];
    return $b['timestamp'] <=> $a['timestamp'];
});

$quizFilter = trim((string)(isset($_GET['quiz']) ? $_GET['quiz'] : ''));
$nameFilter = trim((string)(isset($_GET['name']) ? $_GET['name'] : ''));
$statusFilter = trim((string)(isset($_GET['status']) ? $_GET['status'] : 'all'));
$theoryFromFilter = trim((string)(isset($_GET['theory_from']) ? $_GET['theory_from'] : ''));
$theoryToFilter = trim((string)(isset($_GET['theory_to']) ? $_GET['theory_to'] : ''));
$theoryFromTs = ($theoryFromFilter !== '') ? strtotime($theoryFromFilter . ' 00:00:00') : null;
$theoryToTs = ($theoryToFilter !== '') ? strtotime($theoryToFilter . ' 23:59:59') : null;

$filtered = array_values(array_filter($attempts, function ($a) use ($quizFilter, $nameFilter, $statusFilter, $theoryFromTs, $theoryToTs) {
    if ($quizFilter !== '' && $a['quiz'] !== $quizFilter) return false;
    if ($nameFilter !== '' && mb_stripos((string)$a['name'], $nameFilter, 0, 'UTF-8') === false) return false;
    if ($statusFilter === 'passed' && !$a['passed']) return false;
    if ($statusFilter === 'failed' && $a['passed']) return false;
    if ($theoryFromTs !== null && $theoryFromTs !== false && (int)$a['timestamp'] < $theoryFromTs) return false;
    if ($theoryToTs !== null && $theoryToTs !== false && (int)$a['timestamp'] > $theoryToTs) return false;
    return true;
}));
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H21',
    'rkrk_assess_admin.php:theory-filter-state',
    'Theory filters current state (before date support)',
    array(
        'quiz' => $quizFilter,
        'name' => $nameFilter,
        'status' => $statusFilter,
        'theory_from' => $theoryFromFilter,
        'theory_to' => $theoryToFilter,
        'theory_from_ts' => $theoryFromTs,
        'theory_to_ts' => $theoryToTs,
        'filteredCount' => count($filtered),
    )
);
// #endregion
$theoryDisplayRows = array_slice($filtered, 0, 15);

$participantMap = array();
$quizStats = array();
$sumPct = 0.0;
$sumPassed = 0;
foreach ($filtered as $a) {
    $sumPct += $a['pct'];
    if ($a['passed']) $sumPassed++;

    if (!isset($participantMap[$a['name_key']])) {
        $participantMap[$a['name_key']] = array(
            'name' => $a['name'],
            'attempts' => 0,
            'best_pct' => $a['pct'],
            'passed' => 0,
            'latest' => $a['date'],
            'latest_ts' => $a['timestamp'],
        );
    }
    $participantMap[$a['name_key']]['attempts']++;
    $participantMap[$a['name_key']]['best_pct'] = max($participantMap[$a['name_key']]['best_pct'], $a['pct']);
    if ($a['passed']) $participantMap[$a['name_key']]['passed']++;
    if ($a['timestamp'] > $participantMap[$a['name_key']]['latest_ts']) {
        $participantMap[$a['name_key']]['latest_ts'] = $a['timestamp'];
        $participantMap[$a['name_key']]['latest'] = $a['date'];
    }

    $qKey = $a['quiz'];
    if (!isset($quizStats[$qKey])) {
        $quizStats[$qKey] = array('title' => $a['quiz_title'], 'attempts' => 0, 'sum_pct' => 0.0, 'passed' => 0);
    }
    $quizStats[$qKey]['attempts']++;
    $quizStats[$qKey]['sum_pct'] += $a['pct'];
    if ($a['passed']) $quizStats[$qKey]['passed']++;
}

$participantRows = array_values($participantMap);
usort($participantRows, function ($a, $b) {
    return $b['latest_ts'] <=> $a['latest_ts'];
});
ksort($quizStats);

$attemptCount = count($filtered);
$participantCount = count($participantRows);
$avgPct = ($attemptCount > 0) ? round($sumPct / $attemptCount, 1) : 0.0;
$passRate = ($attemptCount > 0) ? round(($sumPassed / $attemptCount) * 100, 1) : 0.0;

$allQuizOptions = array();
$allNameOptions = array();
foreach ($attempts as $a) {
    $allQuizOptions[$a['quiz']] = $a['quiz_title'];
    $allNameOptions[$a['name_key']] = $a['name'];
}
asort($allQuizOptions);
asort($allNameOptions);

$examResultsDir = $QuizLoc . '/results';
$examTemplatesDir = $QuizLoc . '/templates';
$examListRows = array();

if (is_dir($examResultsDir)) {
    $examFiles = glob($examResultsDir . '/*.json');
    if (is_array($examFiles)) {
        usort($examFiles, function($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $examFiles = array_slice($examFiles, 0, 50);

        foreach ($examFiles as $jsonFile) {
            $id = basename($jsonFile, '.json');
            $data = exam_admin_load_json_file($jsonFile);
            if (!is_array($data)) continue;

            $tplName = isset($data['template']) ? (string)$data['template'] : '';
            $tpl = exam_admin_load_template($examTemplatesDir, $tplName);
            if (!is_array($tpl)) {
                $tpl = array('title' => $tplName, 'sections' => array());
            }

            $title = isset($tpl['title']) ? (string)$tpl['title'] : $tplName;
            $fields = (isset($data['fields']) && is_array($data['fields'])) ? $data['fields'] : array();
            $sections = (isset($tpl['sections']) && is_array($tpl['sections'])) ? $tpl['sections'] : array();

            $flugzeitRaw = exam_admin_find_first_field_value($fields, array('Flugzeit'));
            $datumZeitRaw = exam_admin_find_first_field_value($fields, array('Datum & Zeit'));
            $pruefungsdatumRaw = exam_admin_find_first_field_value($fields, array('Prüfungsdatum', 'Quizdatum'));
            if (trim((string)$flugzeitRaw) !== '') {
                $dtRaw = $flugzeitRaw;
            } elseif (trim((string)$datumZeitRaw) !== '') {
                $dtRaw = $datumZeitRaw;
            } else {
                $dtRaw = $pruefungsdatumRaw;
            }
            $dt = exam_admin_format_datetime($dtRaw);

            $pilotRaw = isset($fields['Allgemein']['Pilot']) ? $fields['Allgemein']['Pilot'] : '';
            $pilot = exam_admin_value_to_text($pilotRaw);

            $nameParts = array();
            $infoParts = array();
            $overallRating = '';
            foreach ($sections as $secName => $secFields) {
                if (!is_array($secFields)) continue;
                foreach ($secFields as $label => $meta) {
                    if (!is_array($meta)) continue;
                    $val = isset($fields[$secName][$label]) ? exam_admin_value_to_text($fields[$secName][$label]) : '';
                    if ($val === '') continue;

                    if ((isset($meta['inname']) ? (string)$meta['inname'] : '0') === '1') {
                        $nameParts[] = exam_admin_strip_markers($val);
                    }
                    if ((isset($meta['inresult']) ? (string)$meta['inresult'] : '0') === '1') {
                        if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) === 1) {
                            $overallRating = $val;
                        } else {
                            $infoParts[] = (string)$label . ': ' . exam_admin_strip_markers($val);
                        }
                    }
                }
            }

            if ($overallRating === '') {
                foreach ($fields as $secName => $secFields) {
                    if (!is_array($secFields)) continue;
                    foreach ($secFields as $label => $rawVal) {
                        if (preg_match('/gesamtbewertung|gesamtentscheid/ui', (string)$label) === 1) {
                            $overallRating = exam_admin_value_to_text($rawVal);
                            break 2;
                        }
                    }
                }
            }

            $examListRows[] = array(
                'id' => $id,
                'template' => $title,
                'tpl_file' => $tplName,
                'datetime' => $dt,
                'timestamp' => exam_admin_parse_ts($dtRaw, filemtime($jsonFile)),
                'name' => implode(' | ', $nameParts),
                'pilot' => $pilot,
                'test_info' => implode(' | ', $infoParts),
                'overall_rating' => $overallRating,
            );
        }
    }
}

$examFilterTemplate = trim((string)(isset($_GET['assessment_template']) ? $_GET['assessment_template'] : ''));
$examFilterPilot = trim((string)(isset($_GET['assessment_pilot']) ? $_GET['assessment_pilot'] : ''));
$examFilterText = trim((string)(isset($_GET['assessment_text']) ? $_GET['assessment_text'] : ''));
$examFilterFrom = trim((string)(isset($_GET['assessment_from']) ? $_GET['assessment_from'] : ''));
$examFilterTo = trim((string)(isset($_GET['exam_to']) ? $_GET['exam_to'] : ''));
$examStatusFilter = trim((string)(isset($_GET['exam_status']) ? $_GET['exam_status'] : ''));
$examFromTs = ($examFilterFrom !== '') ? strtotime($examFilterFrom . ' 00:00:00') : null;
$examToTs = ($examFilterTo !== '') ? strtotime($examFilterTo . ' 23:59:59') : null;

$examTemplateOptions = array();
foreach ($examListRows as $row) {
    $examTemplateOptions[(string)$row['tpl_file']] = (string)$row['template'];
}
asort($examTemplateOptions, SORT_NATURAL | SORT_FLAG_CASE);

$normalizePracticalStatus = function($raw) {
    return mb_strtolower(exam_admin_strip_markers((string)$raw), 'UTF-8');
};
$practicalStatusOptions = array();
foreach ($examListRows as $row) {
    $statusKey = $normalizePracticalStatus($row['overall_rating'] ?? '');
    if ($statusKey === '') continue;
    if (!isset($practicalStatusOptions[$statusKey])) {
        $practicalStatusOptions[$statusKey] = exam_admin_strip_markers((string)($row['overall_rating'] ?? ''));
    }
}
asort($practicalStatusOptions, SORT_NATURAL | SORT_FLAG_CASE);

$examFilteredRows = array_values(array_filter($examListRows, function($row) use ($examFilterTemplate, $examFilterPilot, $examFilterText, $examFromTs, $examToTs, $examStatusFilter, $normalizePracticalStatus) {
    if ($examFilterTemplate !== '' && (string)$row['tpl_file'] !== $examFilterTemplate) return false;
    if ($examFilterPilot !== '' && mb_stripos((string)$row['pilot'], $examFilterPilot, 0, 'UTF-8') === false) return false;
    if ($examFilterText !== '') {
        $hay = (string)$row['name'] . ' ' . (string)$row['test_info'] . ' ' . (string)$row['overall_rating'];
        if (mb_stripos($hay, $examFilterText, 0, 'UTF-8') === false) return false;
    }
    if ($examStatusFilter !== '') {
        $rowStatus = $normalizePracticalStatus($row['overall_rating'] ?? '');
        if ($rowStatus !== $examStatusFilter) return false;
    }
    $ts = isset($row['timestamp']) ? (int)$row['timestamp'] : 0;
    if ($examFromTs !== null && $examFromTs !== false && $ts > 0 && $ts < $examFromTs) return false;
    if ($examToTs !== null && $examToTs !== false && $ts > 0 && $ts > $examToTs) return false;
    return true;
}));
$practicalDisplayRows = array_slice($examFilteredRows, 0, 15);

$practicalTemplateCount = 0;
$practicalRatingBuckets = array('green' => 0, 'orange' => 0, 'red' => 0, 'other' => 0);
if (!empty($examFilteredRows)) {
    $tplMap = array();
    foreach ($examFilteredRows as $row) {
        $tplMap[(string)$row['template']] = true;
        $ratingRaw = trim((string)($row['overall_rating'] ?? ''));
        if (preg_match('/\(r\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'nicht', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'fail', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['red']++;
        } elseif (preg_match('/\(o\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'warn', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['orange']++;
        } elseif (preg_match('/\(g\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'bestanden', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'ok', 0, 'UTF-8') !== false) {
            $practicalRatingBuckets['green']++;
        } else {
            $practicalRatingBuckets['other']++;
        }
    }
    $practicalTemplateCount = count($tplMap);
}
$practicalParticipantCount = 0;
if (!empty($examFilteredRows)) {
    $practicalParticipantMap = array();
    foreach ($examFilteredRows as $row) {
        $participantKey = mb_strtolower(trim((string)($row['name'] ?? '')), 'UTF-8');
        if ($participantKey === '') continue;
        $practicalParticipantMap[$participantKey] = true;
    }
    $practicalParticipantCount = count($practicalParticipantMap);
}
$practicalTotal = count($examFilteredRows);
$practicalRed = (int)$practicalRatingBuckets['red'];
$practicalPassRate = ($practicalTotal > 0) ? round((($practicalTotal - $practicalRed) / $practicalTotal) * 100, 1) : 0.0;
$practicalTemplateStats = array();
$practicalParticipantStats = array();
foreach ($examFilteredRows as $row) {
    $tplKey = (string)($row['template'] ?? '');
    if ($tplKey === '') $tplKey = '(ohne Template)';
    if (!isset($practicalTemplateStats[$tplKey])) {
        $practicalTemplateStats[$tplKey] = array(
            'title' => $tplKey,
            'count' => 0,
            'red' => 0,
        );
    }
    $practicalTemplateStats[$tplKey]['count']++;
    $ratingRaw = trim((string)($row['overall_rating'] ?? ''));
    if (preg_match('/\(r\)\s*$/i', $ratingRaw) || mb_stripos($ratingRaw, 'nicht', 0, 'UTF-8') !== false || mb_stripos($ratingRaw, 'fail', 0, 'UTF-8') !== false) {
        $practicalTemplateStats[$tplKey]['red']++;
    }

    $participantKey = mb_strtolower(trim((string)($row['name'] ?? '')), 'UTF-8');
    if ($participantKey === '') $participantKey = '(ohne Name)';
    if (!isset($practicalParticipantStats[$participantKey])) {
        $practicalParticipantStats[$participantKey] = array(
            'name' => trim((string)($row['name'] ?? '')) !== '' ? (string)$row['name'] : '(ohne Name)',
            'attempts' => 0,
            'latest' => (string)($row['datetime'] ?? ''),
            'latest_ts' => (int)($row['timestamp'] ?? 0),
        );
    }
    $practicalParticipantStats[$participantKey]['attempts']++;
    $rowTs = (int)($row['timestamp'] ?? 0);
    if ($rowTs > $practicalParticipantStats[$participantKey]['latest_ts']) {
        $practicalParticipantStats[$participantKey]['latest_ts'] = $rowTs;
        $practicalParticipantStats[$participantKey]['latest'] = (string)($row['datetime'] ?? '');
    }
}
ksort($practicalTemplateStats);
$practicalParticipantRows = array_values($practicalParticipantStats);
usort($practicalParticipantRows, function ($a, $b) {
    return $b['latest_ts'] <=> $a['latest_ts'];
});
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H22',
    'rkrk_assess_admin.php:practical-overview-candidates',
    'Practical overview candidate metrics',
    array(
        'practicalFilteredCount' => count($examFilteredRows),
        'practicalDisplayedCount' => count($practicalDisplayRows),
        'practicalTemplateCount' => $practicalTemplateCount,
        'ratingBuckets' => $practicalRatingBuckets,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H29',
    'rkrk_assess_admin.php:practical-overview-metrics',
    'Practical overview metrics candidates for UI block',
    array(
        'practicalTotal' => $practicalTotal,
        'practicalParticipantCount' => $practicalParticipantCount,
        'practicalTemplateCount' => $practicalTemplateCount,
        'practicalPassRate' => $practicalPassRate,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H32',
    'rkrk_assess_admin.php:practical-overview-block',
    'Practical overview block data prepared',
    array(
        'templateStatsCount' => count($practicalTemplateStats),
        'participantStatsCount' => count($practicalParticipantRows),
        'practicalTotal' => $practicalTotal,
        'practicalPassRate' => $practicalPassRate,
    )
);
// #endregion

$theoryParticipantLabel = 'Teilnehmer enthält';
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H24',
    'rkrk_quiz_admin.php:label-baseline',
    'Theory filter label',
    array(
        'theoryParticipantLabel' => $theoryParticipantLabel,
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H30',
    'rkrk_quiz_admin.php:quick-link-labels',
    'Header quick-link: Quiz only',
    array('quizHref' => 'rkrk_quiz.php')
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H25',
    'rkrk_assess_admin.php:theory-filters-updated',
    'Theory filter mode updated to contains+date-range',
    array(
        'nameFilterMode' => 'contains',
        'theory_from' => $theoryFromFilter,
        'theory_to' => $theoryToFilter,
        'filteredCount' => count($filtered),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H28',
    'rkrk_assess_admin.php:theory-filter-layout',
    'Theory filter layout updated: status moved back, reset next line',
    array(
        'order' => array('quiz', 'name', 'theory_from', 'theory_to', 'status', 'reset_next_line'),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H26',
    'rkrk_assess_admin.php:practical-status-options',
    'Practical status options derived from overall ratings',
    array(
        'selectedStatus' => $examStatusFilter,
        'statusOptionCount' => count($practicalStatusOptions),
        'statusOptions' => array_values($practicalStatusOptions),
    )
);
// #endregion
// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H27',
    'rkrk_assess_admin.php:practical-status-filter',
    'Practical status filter applied',
    array(
        'selectedStatus' => $examStatusFilter,
        'filteredCount' => count($examFilteredRows),
        'displayedCount' => count($practicalDisplayRows),
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H2',
    'rkrk_assess_admin.php:header-init',
    'Admin page render context',
    array(
        'script' => isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '',
        'requestUri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
        'examRows' => count($examListRows),
        'examFilteredRows' => count($examFilteredRows),
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H6',
    'rkrk_assess_admin.php:table-counts',
    'Table row counts before limiting',
    array(
        'theoryFilteredCount' => count($filtered),
        'practicalFilteredCount' => count($examFilteredRows),
        'theoryFirstTimestamp' => isset($filtered[0]) ? (int)$filtered[0]['timestamp'] : null,
        'practicalFirstTimestamp' => isset($examFilteredRows[0]) ? (int)$examFilteredRows[0]['timestamp'] : null,
    )
);
// #endregion

// #region agent log
exam_admin_debug_log(
    'post-fix',
    'H7',
    'rkrk_assess_admin.php:table-counts-limited',
    'Table row counts after limiting',
    array(
        'theoryFilteredCount' => count($filtered),
        'theoryDisplayedCount' => count($theoryDisplayRows),
        'practicalFilteredCount' => count($examFilteredRows),
        'practicalDisplayedCount' => count($practicalDisplayRows),
    )
);
// #endregion

$reportingTitle = 'Konsolidiertes Reporting';
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H1',
    'rkrk_assess_admin.php:reporting-title',
    'Reporting title before output',
    array('title' => $reportingTitle)
);
// #endregion

$practicalTestsTitle = 'Assessment-Liste (Daten aus rkrk_assess.php)';
// #region agent log
exam_admin_debug_log(
    'pre-fix',
    'H5',
    'rkrk_assess_admin.php:practical-tests-title',
    'Practical tests section title before output',
    array('title' => $practicalTestsTitle)
);
// #endregion
?>
<body>
<link rel="stylesheet" href="<?php echo esc_html($rkrkAssessThemeCss); ?>">
<script>
// #region agent log
(function () {
  fetch('http://127.0.0.1:7399/ingest/e812fd43-8782-44ad-9993-c030e649d93e',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'1791ba'},body:JSON.stringify({sessionId:'1791ba',runId:'pre-fix',hypothesisId:'H31',location:'rkrk_assess_admin.php:bootstrap-script',message:'Bootstrap script executed',data:{path:window.location.pathname,search:window.location.search},timestamp:Date.now()})}).catch(()=>{});
})();
// #endregion
</script>
<div class="wrap rkrk-wrap">
  <div class="card rkrk-card" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
    <div>
      <h2 style="margin-top:0;">Prüfungsplattform - Admin</h2>
      <div class="quick-links">
        <a class="ql-btn" href="rkrk_quiz.php">Quiz (Theorie)</a>
      </div>
    </div>
    <div>
      <img src="<?php echo esc_html($QuizIconLoc . $logopic); ?>" alt="RKRK Logo" style="height:56px;">
    </div>
  </div>

  <div class="card rkrk-card">
    <h3 style="margin-top:0;">Theorie Prüfungen</h3>
    <form id="theory-filters-form" class="filters" method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:10px;">
      <div>
        <label for="quiz"><b>Test</b></label><br>
        <select id="quiz" name="quiz">
          <option value="">Alle</option>
          <?php foreach ($allQuizOptions as $qKey => $qTitle): ?>
            <option value="<?php echo esc_html($qKey); ?>"<?php echo ($quizFilter===$qKey?' selected':''); ?>><?php echo esc_html($qTitle); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="name"><b><?php echo esc_html($theoryParticipantLabel); ?></b></label><br>
        <input id="name" type="text" name="name" value="<?php echo esc_html($nameFilter); ?>" placeholder="z.B. Müller">
      </div>
      <div>
        <label for="theory_from"><b>Von Datum</b></label><br>
        <input id="theory_from" type="date" name="theory_from" value="<?php echo esc_html($theoryFromFilter); ?>">
      </div>
      <div>
        <label for="theory_to"><b>Bis Datum</b></label><br>
        <input id="theory_to" type="date" name="theory_to" value="<?php echo esc_html($theoryToFilter); ?>">
      </div>
      <div>
        <label for="status"><b>Status</b></label><br>
        <select id="status" name="status">
          <option value="all"<?php echo ($statusFilter==='all'?' selected':''); ?>>Alle</option>
          <option value="passed"<?php echo ($statusFilter==='passed'?' selected':''); ?>>Bestanden</option>
          <option value="failed"<?php echo ($statusFilter==='failed'?' selected':''); ?>>Nicht bestanden</option>
        </select>
      </div>
      <div style="display:flex;align-items:end;gap:8px;grid-column:1 / -1;">
        <a class="ql-btn" href="?quiz=&amp;name=&amp;status=all&amp;theory_from=&amp;theory_to=&amp;assessment_template=<?php echo rawurlencode((string)$examFilterTemplate); ?>&amp;assessment_pilot=<?php echo rawurlencode((string)$examFilterPilot); ?>&amp;assessment_text=<?php echo rawurlencode((string)$examFilterText); ?>&amp;assessment_from=<?php echo rawurlencode((string)$examFilterFrom); ?>&amp;exam_to=<?php echo rawurlencode((string)$examFilterTo); ?>&amp;exam_status=<?php echo rawurlencode((string)$examStatusFilter); ?>">Reset</a>
      </div>
    </form>
    <script>
    // #region agent log
    (function () {
      const form = document.getElementById('theory-filters-form');
      const log = (hypothesisId, message, data) => {
        fetch('http://127.0.0.1:7399/ingest/e812fd43-8782-44ad-9993-c030e649d93e',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'1791ba'},body:JSON.stringify({sessionId:'1791ba',runId:'post-fix',hypothesisId,location:'rkrk_assess_admin.php:theory-filters-script',message,data,timestamp:Date.now()})}).catch(()=>{});
      };
      if (!form) {
        log('H1', 'Theory filters form not found', { formId: 'theory-filters-form' });
        return;
      }
      const controls = Array.from(form.querySelectorAll('select,input[type="text"],input[type="date"]'));
      log('H16', 'Theory filters mounted for instant apply', { controls: controls.map(c => c.name) });
      let timer = null;
      const submitNow = () => {
        const fd = new FormData(form);
        log('H17', 'Theory filters auto-submit', { query: new URLSearchParams(fd).toString() });
        form.submit();
      };
      controls.forEach(control => {
        control.addEventListener('change', () => {
          log('H4', 'Theory filter change event', { name: control.name, value: control.value });
          submitNow();
        });
        if (control.type === 'text') {
          control.addEventListener('input', () => {
            log('H5', 'Theory filter text input event', { name: control.name, valueLength: String(control.value || '').length });
            if (timer) clearTimeout(timer);
            timer = setTimeout(submitNow, 350);
          });
        }
      });
    })();
    // #endregion
    </script>

    <div class="kpis" style="margin-bottom:14px;">
      <div class="kpi"><b>Versuche</b><br><?php echo esc_html($attemptCount); ?></div>
      <div class="kpi"><b>Teilnehmer</b><br><?php echo esc_html($participantCount); ?></div>
      <div class="kpi"><b>Durchschnitt</b><br><?php echo esc_html($avgPct); ?>%</div>
      <div class="kpi"><b>Bestehensquote</b><br><?php echo esc_html($passRate); ?>%</div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(360px,1fr)); gap:12px; margin-bottom:14px;">
      <div>
        <h4 style="margin:0 0 8px 0;">Uebersicht pro Test</h4>
        <table>
          <tr><th>Test</th><th>Versuche</th><th>Durchschnitt</th><th>Bestehensquote</th></tr>
          <?php foreach ($quizStats as $q): $qAvg = ($q['attempts']>0)?round($q['sum_pct']/$q['attempts'],1):0; $qPass = ($q['attempts']>0)?round(($q['passed']/$q['attempts'])*100,1):0; ?>
          <tr>
            <td><?php echo esc_html($q['title']); ?></td>
            <td><?php echo esc_html($q['attempts']); ?></td>
            <td><?php echo esc_html($qAvg); ?>%</td>
            <td><?php echo esc_html($qPass); ?>%</td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div>
        <h4 style="margin:0 0 8px 0;">Teilnehmerliste</h4>
        <table>
          <tr><th>Name</th><th>Versuche</th><th>Bestanden</th><th>Bester Score</th><th>Letzter Versuch</th></tr>
          <?php foreach ($participantRows as $p): ?>
          <tr>
            <td><?php echo esc_html($p['name']); ?></td>
            <td><?php echo esc_html($p['attempts']); ?></td>
            <td><?php echo esc_html($p['passed']); ?></td>
            <td><?php echo esc_html($p['best_pct']); ?>%</td>
            <td><?php echo esc_html($p['latest']); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    <h4 style="margin:0 0 8px 0;">Resultate (letzte Versuche zuerst)</h4>
    <table style="margin-bottom:14px;">
      <tr><th>Zeit</th><th>Name</th><th>Test</th><th>Richtig</th><th>Fragen</th><th>Score</th><th>Status</th></tr>
      <?php foreach ($theoryDisplayRows as $a): ?>
      <tr>
        <td><?php echo esc_html($a['date']); ?></td>
        <td><?php echo esc_html($a['name']); ?></td>
        <td><?php echo esc_html($a['quiz_title']); ?></td>
        <td><?php echo esc_html($a['richtig']); ?></td>
        <td><?php echo esc_html($a['anz']); ?></td>
        <td><?php echo esc_html($a['pct']); ?>%</td>
        <td class="<?php echo ($a['passed']?'ok':'bad'); ?>"><?php echo ($a['passed']?'Bestanden':'Nicht bestanden'); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

  </div>
</div>
</body>
</html>
