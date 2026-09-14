<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/AnalyticsService.php';
include_once __DIR__ . '/../../scripts/rkrk_gen_ext.php';

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$analytics = new AnalyticsService();

$yearFilter = null;
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $yearFilter = (int)$_GET['year'];
}

$transactions = $repo->getTransactions();
$budgetLines = $repo->getBudgetLines();
$inventoryItems = $repo->getInventoryItems();
$categoryMap = $repo->getCategoryMapByCode();

$dashboard = $analytics->buildDashboardData(
    $transactions,
    $budgetLines,
    $inventoryItems,
    $categoryMap,
    $yearFilter
);

$formatCurrency = static function (float $amount): string {
    return 'CHF ' . number_format($amount, 2, '.', "'");
};

$kpis = $dashboard['kpis'];
$effectiveYear = $dashboard['effectiveYear'];
$availableYears = $dashboard['availableYears'];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rehkitzrettung Finanzen Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a class="active" href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="transactions.php">Analyse</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
        <a href="cashflow.php">Cashflow</a>
        <a href="manage.php">Legacy Eingaben</a>
    </nav>

    <header class="header">
        <div>
            <h1>Rehkitzrettung Finanzen</h1>
            <p>JSON-basierte Auswertung aus Excel-Import.</p>
            <!--
            <p class="muted"><strong>dataDir:</strong> <?= htmlspecialchars($dataDir, ENT_QUOTES) ?></p>
            -->
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
            <h2>Einnahmen</h2>
            <p class="value positive"><?= htmlspecialchars($formatCurrency((float)$kpis['income']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Ausgaben</h2>
            <p class="value negative"><?= htmlspecialchars($formatCurrency((float)$kpis['expense']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Netto</h2>
            <p class="value <?= ((float)$kpis['net']) >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency((float)$kpis['net']), ENT_QUOTES) ?>
            </p>
        </article>
        <article class="card">
            <h2>Budget Netto</h2>
            <p class="value"><?= htmlspecialchars($formatCurrency((float)$kpis['budgetNet']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Abweichung zu Budget</h2>
            <p class="value <?= ((float)$kpis['budgetGap']) >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency((float)$kpis['budgetGap']), ENT_QUOTES) ?>
            </p>
        </article>
        <article class="card">
            <h2>Buchungen</h2>
            <p class="value"><?= htmlspecialchars((string)$kpis['transactionCount'], ENT_QUOTES) ?></p>
        </article>
    </section>

    <section class="chart-grid">
        <article class="card chart-card">
            <h2>Monatsverlauf <?= htmlspecialchars((string)$effectiveYear, ENT_QUOTES) ?></h2>
            <canvas id="monthlyChart"></canvas>
        </article>
        <article class="card chart-card">
            <h2>Kostenarten Netto</h2>
            <canvas id="categoryChart"></canvas>
        </article>
    </section>

    <section class="kpi-grid">
        <article class="card">
            <h2>Inventar Positionen</h2>
            <p class="value"><?= htmlspecialchars((string)$dashboard['inventoryStats']['itemCount'], ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Inventar Neuwert</h2>
            <p class="value"><?= htmlspecialchars($formatCurrency((float)$dashboard['inventoryStats']['purchaseValue']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Inventar Zeitwert</h2>
            <p class="value"><?= htmlspecialchars($formatCurrency((float)$dashboard['inventoryStats']['currentValue']), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Inventar Verkaufswert</h2>
            <p class="value"><?= htmlspecialchars($formatCurrency((float)$dashboard['inventoryStats']['disposalValue']), ENT_QUOTES) ?></p>
        </article>
    </section>
</div>

<script>
const monthlySeries = <?= json_encode($dashboard['monthlySeries'], JSON_UNESCAPED_UNICODE) ?>;
const categoryTotals = <?= json_encode($dashboard['categoryTotals'], JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: {
    labels: monthlySeries.labels,
    datasets: [
      { label: 'Einnahmen', data: monthlySeries.income, borderColor: '#1f9d55', tension: 0.3 },
      { label: 'Ausgaben', data: monthlySeries.expense, borderColor: '#cc1f1a', tension: 0.3 },
      { label: 'Netto', data: monthlySeries.net, borderColor: '#3b82f6', tension: 0.3 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('categoryChart'), {
  type: 'bar',
  data: {
    labels: categoryTotals.labels,
    datasets: [{ label: 'Netto CHF', data: categoryTotals.values, backgroundColor: '#4f46e5' }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y'
  }
});
</script>
</body>
</html>

