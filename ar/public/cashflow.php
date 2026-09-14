<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/AnalyticsService.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$analytics = new AnalyticsService();

$transactions = $repo->getTransactions();
$availableYears = $analytics->getAvailableYears($transactions);

$yearFilter = null;
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $yearFilter = (int)$_GET['year'];
}
$effectiveYear = $analytics->getResolvedYear($yearFilter, $availableYears);

$incomeByMonth = array_fill(1, 12, 0.0);
$expenseByMonth = array_fill(1, 12, 0.0);
$expenseByCategoryMonth = [];
$categoryMap = $repo->getCategoryMapByCode();

foreach ($transactions as $row) {
    $date = (string)($row['booking_date'] ?? '');
    if (strlen($date) < 7) {
        continue;
    }
    $year = (int)substr($date, 0, 4);
    if ($effectiveYear !== null && $year !== $effectiveYear) {
        continue;
    }
    $month = (int)substr($date, 5, 2);
    if ($month < 1 || $month > 12) {
        continue;
    }
    $incomeByMonth[$month] += (float)($row['amount_in'] ?? 0);
    $out = (float)($row['amount_out'] ?? 0);
    $expenseByMonth[$month] += $out;
    if ($out > 0.0) {
        $code = trim((string)($row['cost_category_code'] ?? ''));
        $name = $categoryMap[$code] ?? trim((string)($row['cost_category_name_raw'] ?? ''));
        if ($name === '') {
            $name = 'Unmapped';
        }
        if (!isset($expenseByCategoryMonth[$name])) {
            $expenseByCategoryMonth[$name] = array_fill(1, 12, 0.0);
        }
        $expenseByCategoryMonth[$name][$month] += $out;
    }
}

$labels = array_map(static fn(int $m): string => str_pad((string)$m, 2, '0', STR_PAD_LEFT), range(1, 12));
$monthKeys = array_map(static fn(int $m): string => substr((string)$effectiveYear, 2, 2) . str_pad((string)$m, 2, '0', STR_PAD_LEFT), range(1, 12));
$incomeSeries = array_values($incomeByMonth);
$expenseSeries = array_values($expenseByMonth);
$netSeries = [];
$cumulativeSeries = [];
$running = 0.0;
for ($i = 0; $i < 12; $i++) {
    $net = $incomeSeries[$i] - $expenseSeries[$i];
    $netSeries[] = $net;
    $running += $net;
    $cumulativeSeries[] = $running;
}

$sumIncome = array_sum($incomeSeries);
$sumExpense = array_sum($expenseSeries);
$sumNet = array_sum($netSeries);
$formatCurrency = static fn(float $amount): string => 'CHF ' . number_format($amount, 2, '.', "'");
$formatAmountCell = static fn(float $amount): string => abs($amount) < 0.00001 ? '' : number_format($amount, 2, '.', "'");
ksort($expenseByCategoryMonth);
$expenseCategoryTotals = [];
$expenseTotalByMonth = array_fill(1, 12, 0.0);
foreach ($expenseByCategoryMonth as $cat => $monthMap) {
    $expenseCategoryTotals[$cat] = array_sum($monthMap);
    for ($m = 1; $m <= 12; $m++) {
        $expenseTotalByMonth[$m] += $monthMap[$m] ?? 0.0;
    }
}
$expenseGrandTotal = array_sum($expenseTotalByMonth);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cashflow | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="transactions.php">Analyse</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
        <a class="active" href="cashflow.php">Cashflow</a>
        <a href="manage.php">Legacy Eingaben</a>
    </nav>

    <header class="header">
        <div>
            <h1>Cashflow</h1>
            <p>Monatliche Liquiditätsentwicklung inkl. kumuliertem Verlauf.</p>
        </div>
        <form method="get" class="filter-form">
            <label for="year">Jahr</label>
            <select id="year" name="year" onchange="this.form.submit()">
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?= htmlspecialchars((string)$year, ENT_QUOTES) ?>" <?= $year === $effectiveYear ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$year, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <section class="kpi-grid">
        <article class="card">
            <h2>Einzahlungen</h2>
            <p class="value positive"><?= htmlspecialchars($formatCurrency($sumIncome), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Auszahlungen</h2>
            <p class="value negative"><?= htmlspecialchars($formatCurrency($sumExpense), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Netto Cashflow</h2>
            <p class="value <?= $sumNet >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency($sumNet), ENT_QUOTES) ?>
            </p>
        </article>
        <article class="card">
            <h2>Kumuliert Jahresende</h2>
            <p class="value <?= $running >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency($running), ENT_QUOTES) ?>
            </p>
        </article>
    </section>

    <section class="chart-grid">
        <article class="card chart-card">
            <h2>Monatlicher Cashflow (<?= htmlspecialchars((string)$effectiveYear, ENT_QUOTES) ?>)</h2>
            <canvas id="cashflowChart"></canvas>
        </article>
        <article class="card chart-card">
            <h2>Kumuliert</h2>
            <canvas id="cumulativeChart"></canvas>
        </article>
    </section>

    <section class="card">
        <h2>Monatsübersicht</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Monat</th>
                    <th>Einzahlungen</th>
                    <th>Auszahlungen</th>
                    <th>Netto</th>
                    <th>Kumuliert</th>
                </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < 12; $i++): ?>
                    <tr>
                        <td><?= htmlspecialchars($labels[$i], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCurrency($incomeSeries[$i]), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCurrency($expenseSeries[$i]), ENT_QUOTES) ?></td>
                        <td class="<?= $netSeries[$i] >= 0 ? 'positive' : 'negative' ?>">
                            <?= htmlspecialchars($formatCurrency($netSeries[$i]), ENT_QUOTES) ?>
                        </td>
                        <td class="<?= $cumulativeSeries[$i] >= 0 ? 'positive' : 'negative' ?>">
                            <?= htmlspecialchars($formatCurrency($cumulativeSeries[$i]), ENT_QUOTES) ?>
                        </td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Ausgabenliste pro Kostenart und Monat</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Kostenart</th>
                    <?php foreach ($monthKeys as $mk): ?>
                        <th><?= htmlspecialchars($mk, ENT_QUOTES) ?></th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenseByCategoryMonth as $cat => $monthMap): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat, ENT_QUOTES) ?></td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="amount-out"><?= htmlspecialchars($formatAmountCell((float)($monthMap[$m] ?? 0.0)), ENT_QUOTES) ?></td>
                        <?php endfor; ?>
                        <td class="amount-out"><?= htmlspecialchars($formatAmountCell((float)($expenseCategoryTotals[$cat] ?? 0.0)), ENT_QUOTES) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Total</strong></td>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <td class="amount-out"><strong><?= htmlspecialchars($formatAmountCell((float)($expenseTotalByMonth[$m] ?? 0.0)), ENT_QUOTES) ?></strong></td>
                    <?php endfor; ?>
                    <td class="amount-out"><strong><?= htmlspecialchars($formatAmountCell($expenseGrandTotal), ENT_QUOTES) ?></strong></td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const incomeSeries = <?= json_encode($incomeSeries, JSON_UNESCAPED_UNICODE) ?>;
const expenseSeries = <?= json_encode($expenseSeries, JSON_UNESCAPED_UNICODE) ?>;
const netSeries = <?= json_encode($netSeries, JSON_UNESCAPED_UNICODE) ?>;
const cumulativeSeries = <?= json_encode($cumulativeSeries, JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('cashflowChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      { label: 'Einzahlungen', data: incomeSeries, backgroundColor: '#16a34a' },
      { label: 'Auszahlungen', data: expenseSeries, backgroundColor: '#dc2626' },
      { label: 'Netto', data: netSeries, type: 'line', borderColor: '#1d4ed8', tension: 0.3 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('cumulativeChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Kumulierter Cashflow', data: cumulativeSeries, borderColor: '#7c3aed', tension: 0.3 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});
</script>
</body>
</html>

