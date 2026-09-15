<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/AnalyticsService.php';
require_once __DIR__ . '/../src/AccountHelpers.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$analytics = new AnalyticsService();

function tx_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

$transactions = $repo->getTransactions();
$categoryMap = $repo->getCategoryMapByCode();
$availableYears = $analytics->getAvailableYears($transactions);

$yearFilter = null;
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $yearFilter = (int)$_GET['year'];
}
$effectiveYear = $analytics->getResolvedYear($yearFilter, $availableYears);

$categoryFilter = trim((string)($_GET['category'] ?? ''));
$query = trim((string)($_GET['q'] ?? ''));
$accountFilter = trim((string)($_GET['account'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? 'all'));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$format = trim((string)($_GET['format'] ?? ''));

$filtered = [];
$accountOptions = [];
foreach ($transactions as $row) {
    $date = (string)($row['booking_date'] ?? '');
    $year = strlen($date) >= 4 ? (int)substr($date, 0, 4) : null;
    if ($effectiveYear !== null && $year !== $effectiveYear) {
        continue;
    }

    $account = trim((string)($row['account'] ?? ''));
    if ($account === '') {
        $account = '-';
    }
    $accountOptions[$account] = true;
    if ($accountFilter !== '' && account_normalize_for_match($account) !== account_normalize_for_match($accountFilter)) {
        continue;
    }

    $rawBookingStatus = trim((string)($row['booking_status'] ?? ''));
    $bookingStatusNorm = in_array($rawBookingStatus, ['n', 'v', 'x'], true) ? $rawBookingStatus : 'x';
    if ($statusFilter !== '' && $bookingStatusNorm !== $statusFilter) {
        continue;
    }

    $code = trim((string)($row['cost_category_code'] ?? ''));
    $categoryName = $categoryMap[$code] ?? (string)($row['cost_category_name_raw'] ?? 'Unmapped');
    $categoryName = trim($categoryName) !== '' ? trim($categoryName) : 'Unmapped';
    if ($categoryFilter !== '' && $categoryName !== $categoryFilter) {
        continue;
    }

    $amountIn = (float)($row['amount_in'] ?? 0);
    $amountOut = (float)($row['amount_out'] ?? 0);
    if ($typeFilter === 'in' && $amountIn <= 0) {
        continue;
    }
    if ($typeFilter === 'out' && $amountOut <= 0) {
        continue;
    }

    if ($query !== '') {
        $haystack = tx_lower(
            implode(
                ' ',
                [
                    (string)($row['external_id'] ?? ''),
                    (string)($row['counterparty'] ?? ''),
                    (string)($row['note'] ?? ''),
                    $account,
                    $categoryName,
                ]
            )
        );
        if (!str_contains($haystack, tx_lower($query))) {
            continue;
        }
    }

    $row['category_name'] = $categoryName;
    $row['net'] = $amountIn - $amountOut;
    $row['account_display'] = $account;
    $row['booking_status_display'] = $bookingStatusNorm;
    $filtered[] = $row;
}

usort($filtered, static function (array $a, array $b): int {
    $cmp = strcmp((string)($a['booking_date'] ?? ''), (string)($b['booking_date'] ?? ''));
    if ($cmp !== 0) {
        return $cmp;
    }
    $cmp2 = strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));
    if ($cmp2 !== 0) {
        return $cmp2;
    }
    return strcmp((string)($a['external_id'] ?? ''), (string)($b['external_id'] ?? ''));
});

$balances = [];
foreach ($filtered as &$row) {
    $account = trim((string)($row['account_display'] ?? '-'));
    if ($account === '') {
        $account = '-';
    }
    $key = account_normalize_for_match($account);
    if (!isset($balances[$key])) {
        $balances[$key] = 0.0;
    }
    $balances[$key] += (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
    $row['saldo'] = $balances[$key];
}
unset($row);

usort($filtered, static function (array $a, array $b): int {
    $cmp = strcmp((string)($b['booking_date'] ?? ''), (string)($a['booking_date'] ?? ''));
    if ($cmp !== 0) {
        return $cmp;
    }
    $cmp2 = strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    if ($cmp2 !== 0) {
        return $cmp2;
    }
    return strcmp((string)($b['external_id'] ?? ''), (string)($a['external_id'] ?? ''));
});

$categories = array_values(array_unique(array_map(static fn(array $r): string => $r['category_name'], $filtered)));
sort($categories);
$accountValues = array_keys($accountOptions);
sort($accountValues);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="transactions_' . ($effectiveYear ?? 'all') . '.csv"');
    $out = fopen('php://output', 'wb');
    if ($out !== false) {
        fputcsv($out, ['Datum', 'ID', 'Empfänger', 'Kostenart', 'Status', 'Ein', 'Aus', 'Netto', 'Notiz'], ';');
        foreach ($filtered as $row) {
            fputcsv(
                $out,
                [
                    (string)($row['booking_date'] ?? ''),
                    (string)($row['external_id'] ?? ''),
                    (string)($row['counterparty'] ?? ''),
                    (string)$row['category_name'],
                    (string)($row['booking_status_display'] ?? 'x'),
                    number_format((float)($row['amount_in'] ?? 0), 2, '.', ''),
                    number_format((float)($row['amount_out'] ?? 0), 2, '.', ''),
                    number_format((float)($row['net'] ?? 0), 2, '.', ''),
                    (string)($row['note'] ?? ''),
                ],
                ';'
            );
        }
        fclose($out);
    }
    exit;
}

$formatCurrency = static fn(float $amount): string => 'CHF ' . number_format($amount, 2, '.', "'");
$formatAmountCell = static fn(float $amount): string => abs($amount) < 0.00001 ? '' : number_format($amount, 2, '.', "'");
$shortAccountDisplay = static function (string $account): string {
    $s = trim($account);
    if ($s === '' || $s === '-') {
        return $s;
    }
    $compact = preg_replace('/\s+/u', '', $s);
    if ($compact === null || $compact === '') {
        return $s;
    }
    return strlen($compact) <= 6 ? $compact : substr($compact, -6);
};
$formatDateDe = static function (string $iso): string {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
        return $m[3] . '.' . $m[2] . '.' . $m[1];
    }
    return $iso;
};

$sumIn = array_reduce($filtered, static fn(float $c, array $r): float => $c + (float)($r['amount_in'] ?? 0), 0.0);
$sumOut = array_reduce($filtered, static fn(float $c, array $r): float => $c + (float)($r['amount_out'] ?? 0), 0.0);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buchungen | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .kontobuch-grid table td,
        .kontobuch-grid table th {
            padding: 0.3rem 0.4rem;
            line-height: 1.15;
        }
        .kontobuch-grid tbody tr:nth-child(even) { background: #fafafa; }
        .kontobuch-grid td.amount-out { background: #fdeaea; }
        .kontobuch-grid td.amount-in { background: #ebf9e8; }
        .kontobuch-grid td.saldo { background: #e9f2ff; }
        .status-badge {
            display: inline-block;
            min-width: 1.7rem;
            padding: 0.1rem 0.3rem;
            border-radius: 0.28rem;
            border: 1px solid #d1d5db;
            text-align: center;
            font-weight: 700;
        }
        .status-n { background: #f3f4f6; }
        .status-v { background: #fff7d6; }
        .status-x { background: #ffe3e3; }
    </style>
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
        <a class="active" href="transactions.php">Analyse</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
        <a href="cashflow.php">Cashflow</a>
    </nav>

    <header class="header">
        <div>
            <h1>Analyse</h1>
            <p>Ansicht im Kontobuch-Stil mit Filtern und Status.</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <form method="get" class="filters-inline card" id="analysis-filter-form">
        <label>Jahr
            <select name="year">
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?= htmlspecialchars((string)$year, ENT_QUOTES) ?>" <?= $year === $effectiveYear ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$year, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Kostenart
            <select name="category">
                <option value="">Alle</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category, ENT_QUOTES) ?>" <?= $category === $categoryFilter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Typ
            <select name="type">
                <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>Alle</option>
                <option value="in" <?= $typeFilter === 'in' ? 'selected' : '' ?>>Einnahmen</option>
                <option value="out" <?= $typeFilter === 'out' ? 'selected' : '' ?>>Ausgaben</option>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Alle</option>
                <option value="n" <?= $statusFilter === 'n' ? 'selected' : '' ?>>n — offen</option>
                <option value="v" <?= $statusFilter === 'v' ? 'selected' : '' ?>>v — verarbeitet</option>
                <option value="x" <?= $statusFilter === 'x' ? 'selected' : '' ?>>x — gesperrt</option>
            </select>
        </label>
        <label>Konto
            <select name="account">
                <option value="">Alle</option>
                <?php foreach ($accountValues as $acc): ?>
                    <option value="<?= htmlspecialchars((string)$acc, ENT_QUOTES) ?>" <?= $accountFilter === $acc ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$acc, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Suche
            <input type="text" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES) ?>" placeholder="ID, Empfänger, Notiz ...">
        </label>
        <button type="submit">Filter anwenden</button>
        <a class="button-link" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['format' => 'csv'])), ENT_QUOTES) ?>">CSV Export</a>
    </form>

    <section class="kpi-grid">
        <article class="card">
            <h2>Gefundene Buchungen</h2>
            <p class="value"><?= count($filtered) ?></p>
        </article>
        <article class="card">
            <h2>Einnahmen</h2>
            <p class="value positive"><?= htmlspecialchars($formatCurrency($sumIn), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Ausgaben</h2>
            <p class="value negative"><?= htmlspecialchars($formatCurrency($sumOut), ENT_QUOTES) ?></p>
        </article>
        <article class="card">
            <h2>Netto</h2>
            <p class="value <?= ($sumIn - $sumOut) >= 0 ? 'positive' : 'negative' ?>">
                <?= htmlspecialchars($formatCurrency($sumIn - $sumOut), ENT_QUOTES) ?>
            </p>
        </article>
    </section>

    <section class="card">
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Datum</th>
                    <th>Empfänger</th>
                    <th>Kostenart</th>
                    <th>Konto</th>
                    <th>Haben/Aus</th>
                    <th>Soll/Ein</th>
                    <th>Saldo</th>
                    <th>W</th>
                    <th>Status</th>
                    <th>Notiz</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filtered as $row): ?>
                    <?php
                    $account = (string)($row['account_display'] ?? '-');
                    $receipt = trim((string)($row['receipt_path'] ?? ''));
                    $status = (string)($row['booking_status_display'] ?? 'x');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDateDe((string)($row['booking_date'] ?? '')), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($row['counterparty'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$row['category_name'], ENT_QUOTES) ?></td>
                        <td title="<?= htmlspecialchars($account, ENT_QUOTES) ?>"><?= htmlspecialchars($shortAccountDisplay($account), ENT_QUOTES) ?></td>
                        <td class="amount-out"><?= htmlspecialchars($formatAmountCell((float)($row['amount_out'] ?? 0)), ENT_QUOTES) ?></td>
                        <td class="amount-in"><?= htmlspecialchars($formatAmountCell((float)($row['amount_in'] ?? 0)), ENT_QUOTES) ?></td>
                        <td class="saldo"><?= htmlspecialchars(number_format((float)($row['saldo'] ?? 0), 2, '.', "'"), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($row['currency'] ?? 'CHF'), ENT_QUOTES) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES) ?>">
                                <?= htmlspecialchars($status, ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars((string)($row['note'] ?? ''), ENT_QUOTES) ?>
                            <?php if ($receipt !== ''): ?>
                                | <a href="<?= htmlspecialchars($receipt, ENT_QUOTES) ?>" target="_blank" rel="noopener">Beleg</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
(function () {
    const form = document.getElementById('analysis-filter-form');
    if (!form) return;

    form.querySelectorAll('select').forEach((sel) => {
        sel.addEventListener('change', () => form.submit());
    });
})();
</script>
</body>
</html>

