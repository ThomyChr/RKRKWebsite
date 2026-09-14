<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/JsonStore.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$store = new JsonStore($dataDir);

function h_budget(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function now_iso_budget(): string
{
    return date('Y-m-d\TH:i:s');
}

function budget_cell_class(float $value): string
{
    if ($value > 0.00001) {
        return 'amount-in';
    }
    if ($value < -0.00001) {
        return 'amount-out';
    }
    return '';
}

function parse_budget_amount_input(string $raw): float
{
    $raw = trim($raw);
    if ($raw === '') {
        return 0.0;
    }
    $raw = str_replace(["'", ' ', ','], ['', '', '.'], $raw);
    return is_numeric($raw) ? (float)$raw : 0.0;
}

function parse_budget_period_input(string $raw, string $fallback): string
{
    $raw = trim($raw);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return $fallback;
    }
    return date('Y-m-d', $ts);
}

function redirect_budget(array $params = []): never
{
    header('Location: budget.php' . (count($params) ? '?' . http_build_query($params) : ''));
    exit;
}

$categories = $repo->getCategoryMapByCode();
ksort($categories, SORT_NATURAL);

$lines = $store->readList('budget_plan_lines.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        if ($action === 'save_budget_table') {
            $budgets = $_POST['budgets'] ?? [];
            $periodDates = $_POST['period_dates'] ?? [];
            $originalPeriodDates = $_POST['original_period_dates'] ?? [];
            if (!is_array($budgets)) {
                redirect_budget(['error' => 'Ungültige Budgetdaten.']);
            }
            if (!is_array($periodDates)) {
                $periodDates = [];
            }
            if (!is_array($originalPeriodDates)) {
                $originalPeriodDates = [];
            }

            $index = [];
            foreach ($lines as $i => $row) {
                $period = (string)($row['period_date'] ?? '');
                $code = trim((string)($row['cost_category_code'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $period) || $code === '') {
                    continue;
                }
                $year = (int)substr($period, 0, 4);
                $index[$year . '|' . $code] = $i;
            }

            // Remove old row-period values first, so a changed date does not leave stale rows.
            foreach ($budgets as $rowKey => $_perCat) {
                if (!is_array($_perCat)) {
                    continue;
                }
                $originalPeriod = trim((string)($originalPeriodDates[$rowKey] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $originalPeriod)) {
                    continue;
                }
                foreach ($categories as $code => $_name) {
                    $oldYear = (int)substr($originalPeriod, 0, 4);
                    $oldKey = $oldYear . '|' . $code;
                    if (isset($index[$oldKey])) {
                        unset($lines[$index[$oldKey]]);
                        unset($index[$oldKey]);
                    }
                }
            }
            $lines = array_values($lines);

            $index = [];
            foreach ($lines as $i => $row) {
                $period = (string)($row['period_date'] ?? '');
                $code = trim((string)($row['cost_category_code'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $period) || $code === '') {
                    continue;
                }
                $year = (int)substr($period, 0, 4);
                $index[$year . '|' . $code] = $i;
            }

            foreach ($budgets as $yearRaw => $perCat) {
                if (!is_array($perCat) || !ctype_digit((string)$yearRaw)) {
                    continue;
                }
                $year = (int)$yearRaw;
                $periodDate = parse_budget_period_input((string)($periodDates[$yearRaw] ?? ''), sprintf('%04d-01-01', $year));
                foreach ($categories as $code => $_name) {
                    $amount = round(parse_budget_amount_input((string)($perCat[$code] ?? '0')), 0);
                    $newYear = (int)substr($periodDate, 0, 4);
                    $key = $newYear . '|' . $code;

                    if (isset($index[$key])) {
                        $idx = $index[$key];
                        $lines[$idx]['amount'] = $amount;
                        $lines[$idx]['updated_at'] = now_iso_budget();
                        $lines[$idx]['period_date'] = $periodDate;
                        $lines[$idx]['cost_category_code'] = (string)$code;
                    } else {
                        $lines[] = [
                            'id' => bin2hex(random_bytes(16)),
                            'period_date' => $periodDate,
                            'cost_category_code' => (string)$code,
                            'amount' => $amount,
                            'currency' => 'CHF',
                            'source_sheet' => 'Budget (Web)',
                            'source_row_no' => null,
                            'created_at' => now_iso_budget(),
                            'updated_at' => now_iso_budget(),
                        ];
                        $index[$key] = array_key_last($lines);
                    }
                }
            }

            $store->writeList('budget_plan_lines.json', $lines);
            redirect_budget(['ok' => 'Budget gespeichert.']);
        }

        redirect_budget(['error' => 'Unbekannte Aktion.']);
    } catch (Throwable $e) {
        redirect_budget(['error' => 'Fehler: ' . $e->getMessage()]);
    }
}

$years = [];
foreach ($lines as $row) {
    $d = (string)($row['period_date'] ?? '');
    if (preg_match('/^\d{4}/', $d)) {
        $years[(int)substr($d, 0, 4)] = true;
    }
}
foreach ($repo->getTransactions() as $row) {
    $d = (string)($row['booking_date'] ?? '');
    if (preg_match('/^\d{4}/', $d)) {
        $years[(int)substr($d, 0, 4)] = true;
    }
}
if ($years === []) {
    $years[(int)date('Y')] = true;
}
$yearList = array_keys($years);
sort($yearList);
$yearDateMap = [];
foreach ($yearList as $y) {
    $yearDateMap[$y] = sprintf('%04d-01-01', $y);
}
foreach ($lines as $row) {
    $d = (string)($row['period_date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        continue;
    }
    $y = (int)substr($d, 0, 4);
    if (isset($yearDateMap[$y])) {
        $yearDateMap[$y] = $d;
    }
}

$matrix = [];
foreach ($yearList as $y) {
    $matrix[$y] = [];
    foreach ($categories as $code => $_name) {
        $matrix[$y][$code] = 0.0;
    }
}
foreach ($lines as $row) {
    $d = (string)($row['period_date'] ?? '');
    $code = trim((string)($row['cost_category_code'] ?? ''));
    if (!preg_match('/^\d{4}/', $d) || !isset($categories[$code])) {
        continue;
    }
    $y = (int)substr($d, 0, 4);
    if (!isset($matrix[$y])) {
        $matrix[$y] = [];
        foreach ($categories as $c => $_name) {
            $matrix[$y][$c] = 0.0;
        }
    }
    $matrix[$y][$code] += (float)($row['amount'] ?? 0);
}

$formatInputCell = static fn(float $v): string => abs($v) < 0.00001 ? '' : (string)(int)round($v, 0);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Budget | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .budget-grid table {
            min-width: 980px;
        }
        .budget-grid .kb-th {
            position: relative;
            white-space: nowrap;
        }
        .budget-grid .kb-th-inner {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .budget-grid td:first-child,
        .budget-grid th:first-child {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
        }
        .budget-grid th:first-child {
            background: #f3f4f6;
            z-index: 2;
        }
        .budget-grid td.amount-in,
        .budget-grid td.amount-out {
            font-weight: 600;
        }
        .budget-input {
            width: 100%;
            min-width: 84px;
            border: 1px solid #d1d5db;
            border-radius: 0.35rem;
            padding: 0.28rem 0.35rem;
            text-align: right;
            background: transparent;
        }
    </style>
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a class="active" href="budget.php">Budget</a>
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
            <h1>Budget definieren</h1>
            <p>Budgetmatrix pro Jahr und Kostenart (wie im Excel-Ansatz).</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_budget((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_budget((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>Budgettabelle</h2>
        <form method="post">
            <input type="hidden" name="action" value="save_budget_table">
            <div class="table-wrapper kontobuch-grid budget-grid">
                <table id="budget-table">
                    <colgroup>
                        <col style="width: 9%">
                        <?php foreach ($categories as $_code => $_name): ?>
                            <col style="width: 8%">
                        <?php endforeach; ?>
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="kb-th"><div class="kb-th-inner"><span class="kb-th-label">Datum</span></div></th>
                        <?php foreach ($categories as $code => $name): ?>
                            <th class="kb-th" title="<?= h_budget($name) ?>">
                                <div class="kb-th-inner">
                                    <span class="kb-th-label"><?= h_budget(str_pad((string)$code, 2, '0', STR_PAD_LEFT) . ' ' . $name) ?></span>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($yearList as $year): ?>
                        <tr>
                            <td>
                                <input
                                    type="date"
                                    name="period_dates[<?= h_budget((string)$year) ?>]"
                                    value="<?= h_budget((string)($yearDateMap[$year] ?? sprintf('%04d-01-01', $year))) ?>"
                                    class="budget-input"
                                >
                                <input
                                    type="hidden"
                                    name="original_period_dates[<?= h_budget((string)$year) ?>]"
                                    value="<?= h_budget((string)($yearDateMap[$year] ?? sprintf('%04d-01-01', $year))) ?>"
                                >
                            </td>
                            <?php foreach ($categories as $code => $_name): ?>
                                <?php $cellValue = (float)($matrix[$year][$code] ?? 0.0); ?>
                                <td class="<?= h_budget(budget_cell_class($cellValue)) ?>">
                                    <input
                                        type="number"
                                        step="1"
                                        name="budgets[<?= h_budget((string)$year) ?>][<?= h_budget((string)$code) ?>]"
                                        value="<?= h_budget($formatInputCell($cellValue)) ?>"
                                        class="budget-input"
                                    >
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions" style="margin-top:0.75rem;">
                <button type="submit">Budget speichern</button>
                <a class="button-link secondary" href="budget.php">Zurücksetzen</a>
            </div>
        </form>
    </section>
</div>
</body>
</html>
