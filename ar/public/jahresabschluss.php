<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);

$transactions = $repo->getTransactions();
$budget = $repo->getBudgetLines();
$categoryMap = $repo->getCategoryMapByCode();

$years = [];
foreach ($transactions as $t) {
    $d = (string)($t['booking_date'] ?? '');
    if (preg_match('/^\d{4}/', $d)) {
        $years[(int)substr($d, 0, 4)] = true;
    }
}
$availableYears = array_keys($years);
sort($availableYears);
$selectedYear = isset($_GET['year']) && ctype_digit((string)$_GET['year']) ? (int)$_GET['year'] : (empty($availableYears) ? (int)date('Y') : max($availableYears));
$previousYear = $selectedYear - 1;

$txYear = array_values(array_filter($transactions, static fn(array $r): bool => (int)substr((string)($r['booking_date'] ?? ''), 0, 4) === $selectedYear));
$budgetYear = array_values(array_filter($budget, static fn(array $r): bool => (int)substr((string)($r['period_date'] ?? ''), 0, 4) === $selectedYear));
$txPrevYear = array_values(array_filter($transactions, static fn(array $r): bool => (int)substr((string)($r['booking_date'] ?? ''), 0, 4) === $previousYear));

$sumIn = 0.0;
$sumOut = 0.0;
$actualByCat = [];
$expenseByCatCurrent = [];
foreach ($txYear as $r) {
    $in = (float)($r['amount_in'] ?? 0);
    $out = (float)($r['amount_out'] ?? 0);
    $sumIn += $in;
    $sumOut += $out;
    $code = trim((string)($r['cost_category_code'] ?? ''));
    $name = $categoryMap[$code] ?? ((string)($r['cost_category_name_raw'] ?? 'Unmapped'));
    $name = trim($name) !== '' ? $name : 'Unmapped';
    if (!isset($actualByCat[$name])) {
        $actualByCat[$name] = 0.0;
    }
    $actualByCat[$name] += $in - $out;
    if (!isset($expenseByCatCurrent[$name])) {
        $expenseByCatCurrent[$name] = 0.0;
    }
    $expenseByCatCurrent[$name] += $out;
}

$sumInPrev = 0.0;
$sumOutPrev = 0.0;
$expenseByCatPrevious = [];
foreach ($txPrevYear as $r) {
    $in = (float)($r['amount_in'] ?? 0);
    $out = (float)($r['amount_out'] ?? 0);
    $sumInPrev += $in;
    $sumOutPrev += $out;
    $code = trim((string)($r['cost_category_code'] ?? ''));
    $name = $categoryMap[$code] ?? ((string)($r['cost_category_name_raw'] ?? 'Unmapped'));
    $name = trim($name) !== '' ? $name : 'Unmapped';
    if (!isset($expenseByCatPrevious[$name])) {
        $expenseByCatPrevious[$name] = 0.0;
    }
    $expenseByCatPrevious[$name] += $out;
}

$budgetByCat = [];
foreach ($budgetYear as $r) {
    $code = trim((string)($r['cost_category_code'] ?? ''));
    $name = $categoryMap[$code] ?? ('Code ' . $code);
    if (!isset($budgetByCat[$name])) {
        $budgetByCat[$name] = 0.0;
    }
    $budgetByCat[$name] += (float)($r['amount'] ?? 0);
}

$names = array_unique(array_merge(array_keys($actualByCat), array_keys($budgetByCat)));
sort($names);
$rows = [];
$totalBudget = 0.0;
foreach ($names as $n) {
    $actual = $actualByCat[$n] ?? 0.0;
    $b = $budgetByCat[$n] ?? 0.0;
    $rows[] = ['name' => $n, 'actual' => $actual, 'budget' => $b, 'gap' => $actual - $b];
    $totalBudget += $b;
}

$net = $sumIn - $sumOut;
$netPrev = $sumInPrev - $sumOutPrev;
$deltaIn = $sumIn - $sumInPrev;
$deltaOut = $sumOut - $sumOutPrev;
$deltaNet = $net - $netPrev;

$expenseNames = array_unique(array_merge(array_keys($expenseByCatCurrent), array_keys($expenseByCatPrevious)));
sort($expenseNames);
$expenseCompareRows = [];
foreach ($expenseNames as $name) {
    $cur = $expenseByCatCurrent[$name] ?? 0.0;
    $prev = $expenseByCatPrevious[$name] ?? 0.0;
    $expenseCompareRows[] = [
        'name' => $name,
        'current' => $cur,
        'previous' => $prev,
        'delta' => $cur - $prev,
    ];
}

$format = static fn(float $v): string => 'CHF ' . number_format($v, 2, '.', "'");
$formatCell = static fn(float $v): string => abs($v) < 0.00001 ? '' : ('CHF ' . number_format($v, 2, '.', "'"));
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jahresabschluss | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="transactions.php">Buchungen (Analyse)</a>
        <a href="reports.php">Reports</a>
        <a class="active" href="jahresabschluss.php">Jahresabschluss</a>
        <a href="cashflow.php">Cashflow</a>
        <a href="manage.php">Legacy Eingaben</a>
    </nav>

    <header class="header">
        <div>
            <h1>Jahresabschluss</h1>
            <p>Übersicht wie im Excel: Jahresergebnis und Budgetabweichung je Kostenart.</p>
        </div>
        <form method="get" class="filter-form">
            <label for="year">Jahr</label>
            <select id="year" name="year" onchange="this.form.submit()">
                <?php foreach ($availableYears as $y): ?>
                    <option value="<?= htmlspecialchars((string)$y, ENT_QUOTES) ?>" <?= $y === $selectedYear ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$y, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <section class="kpi-grid">
        <article class="card">
            <h2>Einnahmen</h2>
            <p class="value positive"><?= htmlspecialchars($format($sumIn), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Ausgaben</h2>
            <p class="value negative"><?= htmlspecialchars($format($sumOut), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Jahresergebnis</h2>
            <p class="value <?= $net >= 0 ? 'positive' : 'negative' ?>"><?= htmlspecialchars($format($net), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Budget Netto</h2>
            <p class="value"><?= htmlspecialchars($format($totalBudget), ENT_QUOTES) ?></p>
        </article>
    </section>

    <section class="card">
        <h2>Vergleich mit letzter Periode (<?= htmlspecialchars((string)$previousYear, ENT_QUOTES) ?>)</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Kennzahl</th>
                    <th>Aktuelle Periode (<?= htmlspecialchars((string)$selectedYear, ENT_QUOTES) ?>)</th>
                    <th>Letzte Periode (<?= htmlspecialchars((string)$previousYear, ENT_QUOTES) ?>)</th>
                    <th>Delta</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Einnahmen</td>
                    <td><?= htmlspecialchars($formatCell($sumIn), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($formatCell($sumInPrev), ENT_QUOTES) ?></td>
                    <td class="<?= $deltaIn >= 0 ? 'positive' : 'negative' ?>"><?= htmlspecialchars($formatCell($deltaIn), ENT_QUOTES) ?></td>
                </tr>
                <tr>
                    <td>Ausgaben</td>
                    <td><?= htmlspecialchars($formatCell($sumOut), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($formatCell($sumOutPrev), ENT_QUOTES) ?></td>
                    <td class="<?= $deltaOut <= 0 ? 'positive' : 'negative' ?>"><?= htmlspecialchars($formatCell($deltaOut), ENT_QUOTES) ?></td>
                </tr>
                <tr>
                    <td>Netto</td>
                    <td><?= htmlspecialchars($formatCell($net), ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($formatCell($netPrev), ENT_QUOTES) ?></td>
                    <td class="<?= $deltaNet >= 0 ? 'positive' : 'negative' ?>"><?= htmlspecialchars($formatCell($deltaNet), ENT_QUOTES) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="chart-grid">
        <article class="card chart-card">
            <h2>Aktuelle vs letzte Periode</h2>
            <canvas id="vergleichChart"></canvas>
        </article>
    </section>

    <section class="card">
        <h2>Abschluss je Kostenart</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Kostenart</th>
                    <th>Ist</th>
                    <th>Budget</th>
                    <th>Abweichung</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCell((float)$r['actual']), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCell((float)$r['budget']), ENT_QUOTES) ?></td>
                        <td class="<?= ((float)$r['gap']) >= 0 ? 'positive' : 'negative' ?>">
                            <?= htmlspecialchars($formatCell((float)$r['gap']), ENT_QUOTES) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="4">Keine Daten für dieses Jahr vorhanden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Ausgabenvergleich je Kostenart (<?= htmlspecialchars((string)$selectedYear, ENT_QUOTES) ?> vs <?= htmlspecialchars((string)$previousYear, ENT_QUOTES) ?>)</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Kostenart</th>
                    <th>Ausgaben <?= htmlspecialchars((string)$selectedYear, ENT_QUOTES) ?></th>
                    <th>Ausgaben <?= htmlspecialchars((string)$previousYear, ENT_QUOTES) ?></th>
                    <th>Delta</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenseCompareRows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCell((float)$r['current']), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCell((float)$r['previous']), ENT_QUOTES) ?></td>
                        <td class="<?= ((float)$r['delta']) <= 0 ? 'positive' : 'negative' ?>">
                            <?= htmlspecialchars($formatCell((float)$r['delta']), ENT_QUOTES) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($expenseCompareRows === []): ?>
                    <tr><td colspan="4">Keine Vergleichsdaten verfügbar.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
const vergleich = {
  labels: ['Einnahmen', 'Ausgaben', 'Netto'],
  current: [<?= json_encode($sumIn) ?>, <?= json_encode($sumOut) ?>, <?= json_encode($net) ?>],
  previous: [<?= json_encode($sumInPrev) ?>, <?= json_encode($sumOutPrev) ?>, <?= json_encode($netPrev) ?>]
};

new Chart(document.getElementById('vergleichChart'), {
  type: 'bar',
  data: {
    labels: vergleich.labels,
    datasets: [
      { label: 'Aktuelle Periode (<?= htmlspecialchars((string)$selectedYear, ENT_QUOTES) ?>)', data: vergleich.current, backgroundColor: '#3b82f6' },
      { label: 'Letzte Periode (<?= htmlspecialchars((string)$previousYear, ENT_QUOTES) ?>)', data: vergleich.previous, backgroundColor: '#9ca3af' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});
</script>
</body>
</html>
