<?php
$corePath = __DIR__ . '/requirements_data.json';
$stepPath = __DIR__ . '/step_requirements_data.json';
$core = is_file($corePath) ? json_decode((string)file_get_contents($corePath), true) : [];
$step = is_file($stepPath) ? json_decode((string)file_get_contents($stepPath), true) : [];
if (!is_array($core)) { $core = []; }
if (!is_array($step)) { $step = []; }

$calcUsedCoreParamKeys = [
    'HFOV_deg', 'DFOV_deg', 'target_agl_m', 'overlap', 'edge_factor', 'speed_m_s',
    'turn_delay_s', 'agl_tolerance_m', 'agl_display_deviation_m', 'angle_step_deg', 'target_step_m', 'max_nb_points',
    'min_delay_s', 'low_relief_height_threshold_m', 'min_path_coverage_ratio', 'curve_waypoint_angle_step_deg',
];
$calcUsedCoreParamKeyMap = array_fill_keys($calcUsedCoreParamKeys, true);

$calcUsedRequirementIds = [
    'R-001', 'R-002', 'R-003', 'R-004', 'R-005', 'R-008', 'R-009', 'R-011', 'R-012', 'R-013',
    'R-014', 'R-015', 'R-016', 'R-017', 'R-018', 'R-019', 'R-023', 'R-024', 'R-025', 'R-026',
    'R-027', 'R-028', 'R-029', 'R-030', 'R-031', 'R-032', 'R-033', 'R-035', 'R-036', 'R-037',
];
$calcUsedRequirementIdMap = array_fill_keys($calcUsedRequirementIds, true);
?><!doctype html>
<html lang="de" data-rkrk-layout="off">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Requirements Report (PDF/Print)</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; color: #1f2937; background: #fff; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 18px; }
    h1, h2, h3 { margin: 0 0 10px; }
    .muted { color: #5f6b76; }
    .toolbar { display: flex; gap: 10px; margin: 0 0 14px; }
    .btn { display: inline-block; text-decoration: none; border-radius: 8px; padding: 9px 12px; background: var(--brand-dark, #48673A); color: #fff; border: 0; cursor: pointer; }
    .card { border: 1px solid #d9dde2; border-radius: 10px; padding: 12px; margin: 0 0 12px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { border: 1px solid #d9dde2; padding: 7px; text-align: left; vertical-align: top; }
    th { background: #eef2f6; }
    .page-break { page-break-before: always; }
    .unused-red td { color: #b42318 !important; }
    .merged-strike td { text-decoration: line-through; opacity: 0.85; }
    @media print {
      .toolbar { display: none; }
      .card { break-inside: avoid; }
      a { color: #1f2937; text-decoration: none; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toolbar">
      <button class="btn" onclick="window.print()">Drucken / Als PDF speichern</button>
      <a class="btn" href="requirements.php">Zurueck zu Kern-Requirements</a>
      <a class="btn" href="step_requirements.php">Zurueck zu Schritt-Requirements</a>
    </div>

    <div class="card">
      <h1>Requirements Report</h1>
      <div class="muted">
        Stand: <?= htmlspecialchars((string)($core['meta']['last_updated'] ?? date('Y-m-d'))) ?> |
        Core-Version: <?= htmlspecialchars((string)($core['meta']['version'] ?? '-')) ?> |
        Step-Version: <?= htmlspecialchars((string)($step['meta']['version'] ?? '-')) ?>
      </div>
      <div class="muted" style="margin-top:6px;">
        Rot = aktuell nicht direkt in der Berechnung verdrahtet, Durchgestrichen = zusammengefuehrt/invalid.
      </div>
    </div>

    <div class="card">
      <h2>Kernparameter</h2>
      <table>
        <thead>
          <tr><th>Key</th><th>Label</th><th>Wert</th><th>Einheit</th><th>Beschreibung</th></tr>
        </thead>
        <tbody>
          <?php foreach (($core['core_parameters'] ?? []) as $p): ?>
            <?php
              $pKey = trim((string)($p['key'] ?? ''));
              $paramClass = isset($calcUsedCoreParamKeyMap[$pKey]) ? '' : 'unused-red';
            ?>
            <tr class="<?= $paramClass ?>">
              <td><?= htmlspecialchars((string)($p['key'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($p['label'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($p['value'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($p['unit'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($p['description'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Kern-Requirements</h2>
      <table>
        <thead>
          <tr><th>ID</th><th>Name</th><th>Prioritaet</th><th>Formel</th><th>Beschreibung</th></tr>
        </thead>
        <tbody>
          <?php foreach (($core['requirements'] ?? []) as $r): ?>
            <?php
              $rid = trim((string)($r['id'] ?? ''));
              $rStatus = trim((string)($r['status'] ?? 'valid'));
              $formula = strtolower((string)($r['formula'] ?? ''));
              $isMerged = (strpos($formula, 'merged_into(') !== false) || $rStatus === 'invalid';
              $classes = [];
              if (!isset($calcUsedRequirementIdMap[$rid])) { $classes[] = 'unused-red'; }
              if ($isMerged) { $classes[] = 'merged-strike'; }
              $rowClass = implode(' ', $classes);
            ?>
            <tr class="<?= htmlspecialchars($rowClass) ?>">
              <td><?= htmlspecialchars((string)($r['id'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['priority'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['formula'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['description'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="page-break"></div>
    <div class="card">
      <h2>Schritt-Requirements</h2>
      <?php foreach (($step['steps'] ?? []) as $s): ?>
        <h3 style="margin-top:14px;"><?= htmlspecialchars((string)($s['id'] ?? '-')) ?> - <?= htmlspecialchars((string)($s['name'] ?? '')) ?></h3>
        <div class="muted" style="margin-bottom:8px;">Aktiv: <?= !empty($s['enabled']) ? 'ja' : 'nein' ?> | <?= htmlspecialchars((string)($s['description'] ?? '')) ?></div>
        <table style="margin-bottom:8px;">
          <thead>
            <tr><th colspan="4">Hilfsparameter</th></tr>
            <tr><th>Key</th><th>Label</th><th>Wert</th><th>Einheit</th></tr>
          </thead>
          <tbody>
            <?php foreach (($s['helper_parameters'] ?? []) as $p): ?>
              <tr>
                <td><?= htmlspecialchars((string)($p['key'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($p['label'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($p['value'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($p['unit'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <table>
          <thead>
            <tr><th colspan="4">Requirements</th></tr>
            <tr><th>ID</th><th>Prioritaet</th><th>Formel</th><th>Beschreibung</th></tr>
          </thead>
          <tbody>
            <?php foreach (($s['requirements'] ?? []) as $r): ?>
              <tr>
                <td><?= htmlspecialchars((string)($r['id'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['priority'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['formula'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['description'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
