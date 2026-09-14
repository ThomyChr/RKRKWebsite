<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/AnalyticsService.php';

$dataDir = realpath(__DIR__ . '/../data') ?: (__DIR__ . '/../data');
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
    $expenseByMonth[$month] += (float)($row['amount_out'] ?? 0);
}

$labels = array_map(static fn(int $m): string => str_pad((string)$m, 2, '0', STR_PAD_LEFT), range(1, 12));
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
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="transactions.php">Buchungen (Analyse)</a>
        <a href="reports.php">Reports</a>
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
        <div class="table-wrapper">
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

