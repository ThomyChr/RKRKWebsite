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

$yearFilter = null;
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $yearFilter = (int)$_GET['year'];
}

$report = $analytics->buildReportData(
    $repo->getTransactions(),
    $repo->getBudgetLines(),
    $repo->getCategoryMapByCode(),
    $yearFilter
);

$formatCurrency = static fn(float $amount): string => 'CHF ' . number_format($amount, 2, '.', "'");
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports | Rehkitzrettung Finanzen</title>
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
        <a class="active" href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
        <a href="cashflow.php">Cashflow</a>
    </nav>

    <header class="header">
        <div>
            <h1>Reports</h1>
            <p>Budget-vs-Ist und Jahresabschluss auf Kategorienebene.</p>
        </div>
        <form method="get" class="filter-form">
            <label for="year">Jahr</label>
            <select id="year" name="year" onchange="this.form.submit()">
                <?php foreach ($report['availableYears'] as $year): ?>
                    <option value="<?= htmlspecialchars((string)$year, ENT_QUOTES) ?>" <?= $year === $report['effectiveYear'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$year, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <section class="kpi-grid">
        <article class="card">
            <h2>Ist Netto</h2>
            <p class="value <?= ((float)$report['totals']['actual']) >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency((float)$report['totals']['actual']), ENT_QUOTES) ?>
            </p>
        </article>
        <article class="card">
            <h2>Budget Netto</h2>
            <p class="value"><?= htmlspecialchars($formatCurrency((float)$report['totals']['budget']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Abweichung</h2>
            <p class="value <?= ((float)$report['totals']['gap']) >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency((float)$report['totals']['gap']), ENT_QUOTES) ?>
            </p>
        </article>
    </section>

    <section class="chart-grid">
        <article class="card chart-card">
            <h2>Monatlich: Budget vs Ist (<?= htmlspecialchars((string)$report['effectiveYear'], ENT_QUOTES) ?>)</h2>
            <canvas id="budgetActualChart"></canvas>
        </article>
    </section>

    <section class="card">
        <h2>Budget vs Ist je Kostenart</h2>
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
                <?php foreach ($report['categoryRows'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['category'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCurrency((float)$row['actual']), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($formatCurrency((float)$row['budget']), ENT_QUOTES) ?></td>
                        <td class="<?= ((float)$row['gap']) >= 0 ? 'positive' : 'negative' ?>">
                            <?= htmlspecialchars($formatCurrency((float)$row['gap']), ENT_QUOTES) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
const monthly = <?= json_encode($report['monthlySeries'], JSON_UNESCAPED_UNICODE) ?>;
new Chart(document.getElementById('budgetActualChart'), {
  type: 'bar',
  data: {
    labels: monthly.labels,
    datasets: [
      { label: 'Ist', data: monthly.actual, backgroundColor: '#3b82f6' },
      { label: 'Budget', data: monthly.budget, backgroundColor: '#a855f7' }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});
</script>
</body>
</html>

