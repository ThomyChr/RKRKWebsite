<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src\JsonStore.php';

$dataDir = realpath(__DIR__ . '/../data') ?: (__DIR__ . '/../data');
$repo = new DataRepository($dataDir);
$store = new JsonStore($dataDir);

/**
 * @param array<string, string> $params
 */
function redirect_with(array $params = []): never
{
    $query = http_build_query($params);
    header('Location: manage.php' . ($query !== '' ? ('?' . $query) : ''));
    exit;
}

function now_iso(): string
{
    return date('Y-m-d\TH:i:s');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        if ($action === 'add_category') {
            $code = trim((string)($_POST['code'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            if ($code === '' || $name === '') {
                redirect_with(['error' => 'Kostenarten-Code und Name sind Pflichtfelder.']);
            }

            $rows = $store->readList('cost_categories.json');
            $updated = false;
            foreach ($rows as &$row) {
                if ((string)($row['code'] ?? '') === $code) {
                    $row['name'] = $name;
                    $row['is_active'] = true;
                    $row['updated_at'] = now_iso();
                    $updated = true;
                    break;
                }
            }
            unset($row);
            if (!$updated) {
                $rows[] = [
                    'id' => bin2hex(random_bytes(16)),
                    'code' => $code,
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now_iso(),
                    'updated_at' => now_iso(),
                ];
            }
            $store->writeList('cost_categories.json', $rows);
            redirect_with(['ok' => $updated ? 'Kostenart aktualisiert.' : 'Kostenart hinzugefügt.']);
        }

        if ($action === 'add_budget') {
            $period = trim((string)($_POST['period'] ?? ''));
            $categoryCode = trim((string)($_POST['category_code'] ?? ''));
            $amount = (float)($_POST['amount'] ?? 0);
            if ($period === '' || $categoryCode === '') {
                redirect_with(['error' => 'Periode und Kostenart sind Pflichtfelder.']);
            }

            $periodDate = $period . '-01';
            $rows = $store->readList('budget_plan_lines.json');
            $updated = false;
            foreach ($rows as &$row) {
                if ((string)($row['period_date'] ?? '') === $periodDate && (string)($row['cost_category_code'] ?? '') === $categoryCode) {
                    $row['amount'] = $amount;
                    $row['updated_at'] = now_iso();
                    $updated = true;
                    break;
                }
            }
            unset($row);
            if (!$updated) {
                $rows[] = [
                    'id' => bin2hex(random_bytes(16)),
                    'period_date' => $periodDate,
                    'cost_category_code' => $categoryCode,
                    'amount' => $amount,
                    'currency' => 'CHF',
                    'source_sheet' => 'Web',
                    'source_row_no' => null,
                    'created_at' => now_iso(),
                    'updated_at' => now_iso(),
                ];
            }
            $store->writeList('budget_plan_lines.json', $rows);
            redirect_with(['ok' => $updated ? 'Budgetzeile aktualisiert.' : 'Budgetzeile hinzugefügt.']);
        }

        if ($action === 'add_transaction') {
            $date = trim((string)($_POST['booking_date'] ?? ''));
            $counterparty = trim((string)($_POST['counterparty'] ?? ''));
            $categoryCode = trim((string)($_POST['category_code'] ?? ''));
            $type = trim((string)($_POST['type'] ?? 'in'));
            $amount = (float)($_POST['amount'] ?? 0);
            $account = trim((string)($_POST['account'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            $statusRaw = trim((string)($_POST['booking_status'] ?? 'x'));
            $bookingStatus = in_array($statusRaw, ['n', 'v', 'x'], true) ? $statusRaw : 'x';

            if ($date === '' || $counterparty === '' || $categoryCode === '' || $amount <= 0) {
                redirect_with(['error' => 'Datum, Empfänger, Kostenart und Betrag > 0 sind Pflichtfelder.']);
            }

            $amountIn = $type === 'out' ? 0.0 : $amount;
            $amountOut = $type === 'out' ? $amount : 0.0;

            $rows = $store->readList('transactions.json');
            $rows[] = [
                'id' => bin2hex(random_bytes(16)),
                'external_id' => 'WEB' . date('YmdHis') . random_int(100, 999),
                'booking_date' => $date,
                'counterparty' => $counterparty,
                'cost_category_code' => $categoryCode,
                'cost_category_name_raw' => null,
                'account' => $account === '' ? null : $account,
                'amount_in' => $amountIn,
                'amount_out' => $amountOut,
                'currency' => 'CHF',
                'note' => $note === '' ? null : $note,
                'running_balance_imported' => null,
                'source_sheet' => 'Web',
                'source_row_no' => null,
                'booking_status' => $bookingStatus,
                'created_at' => now_iso(),
                'updated_at' => now_iso(),
            ];
            $store->writeList('transactions.json', $rows);
            redirect_with(['ok' => 'Buchung hinzugefügt.']);
        }

        if ($action === 'add_inventory') {
            $inventoryNo = trim((string)($_POST['inventory_no'] ?? ''));
            $itemName = trim((string)($_POST['item_name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $quantity = (float)($_POST['quantity'] ?? 0);
            $purchasePriceUnit = (float)($_POST['purchase_price_unit'] ?? 0);
            $purchasePeriod = trim((string)($_POST['purchase_period'] ?? ''));
            $depreciation = (float)($_POST['depreciation_rate_pct'] ?? 0);
            $currentValue = (float)($_POST['current_value'] ?? 0);
            $disposalPeriod = trim((string)($_POST['disposal_period'] ?? ''));
            $disposalValue = (float)($_POST['disposal_value'] ?? 0);

            if ($inventoryNo === '' || $itemName === '' || $quantity <= 0) {
                redirect_with(['error' => 'Inventarnummer, Artikel und Anzahl > 0 sind Pflichtfelder.']);
            }

            $rows = $store->readList('inventory_items.json');
            $updated = false;
            foreach ($rows as &$row) {
                if ((string)($row['inventory_no'] ?? '') === $inventoryNo) {
                    $row['item_name'] = $itemName;
                    $row['description'] = $description === '' ? null : $description;
                    $row['quantity'] = $quantity;
                    $row['purchase_price_unit'] = $purchasePriceUnit;
                    $row['purchase_period'] = $purchasePeriod === '' ? null : $purchasePeriod;
                    $row['depreciation_rate_pct'] = $depreciation;
                    $row['current_value'] = $currentValue;
                    $row['disposal_period'] = $disposalPeriod === '' ? null : $disposalPeriod;
                    $row['disposal_value'] = $disposalValue;
                    $row['updated_at'] = now_iso();
                    $updated = true;
                    break;
                }
            }
            unset($row);

            if (!$updated) {
                $rows[] = [
                    'id' => bin2hex(random_bytes(16)),
                    'inventory_no' => $inventoryNo,
                    'quantity' => $quantity,
                    'item_name' => $itemName,
                    'description' => $description === '' ? null : $description,
                    'purchase_price_unit' => $purchasePriceUnit,
                    'purchase_period' => $purchasePeriod === '' ? null : $purchasePeriod,
                    'new_value' => $purchasePriceUnit * $quantity,
                    'depreciation_rate_pct' => $depreciation,
                    'current_value' => $currentValue,
                    'disposal_period' => $disposalPeriod === '' ? null : $disposalPeriod,
                    'disposal_value' => $disposalValue,
                    'source_sheet' => 'Web',
                    'source_row_no' => null,
                    'imported_at' => now_iso(),
                    'created_at' => now_iso(),
                    'updated_at' => now_iso(),
                ];
            }

            $store->writeList('inventory_items.json', $rows);
            redirect_with(['ok' => $updated ? 'Inventarposition aktualisiert.' : 'Inventarposition hinzugefügt.']);
        }

        redirect_with(['error' => 'Unbekannte Aktion.']);
    } catch (Throwable $e) {
        redirect_with(['error' => 'Fehler beim Speichern: ' . $e->getMessage()]);
    }
}

$categoriesByCode = $repo->getCategoryMapByCode();
$categoryRows = [];
foreach ($categoriesByCode as $code => $name) {
    $categoryRows[] = ['code' => $code, 'name' => $name];
}
usort($categoryRows, static fn(array $a, array $b): int => strcmp($a['code'], $b['code']));

$transactions = $repo->getTransactions();
usort(
    $transactions,
    static fn(array $a, array $b): int => strcmp((string)($b['booking_date'] ?? ''), (string)($a['booking_date'] ?? ''))
);
$recentTransactions = array_slice($transactions, 0, 12);

$budgetRows = $repo->getBudgetLines();
usort(
    $budgetRows,
    static fn(array $a, array $b): int => strcmp((string)($b['period_date'] ?? ''), (string)($a['period_date'] ?? ''))
);
$recentBudgetRows = array_slice($budgetRows, 0, 12);

$inventoryRows = $repo->getInventoryItems();
usort(
    $inventoryRows,
    static fn(array $a, array $b): int => strcmp((string)($b['updated_at'] ?? ''), (string)($a['updated_at'] ?? ''))
);
$recentInventoryRows = array_slice($inventoryRows, 0, 12);
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eingaben | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
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
        <a href="cashflow.php">Cashflow</a>
        <a class="active" href="manage.php">Legacy Eingaben</a>
    </nav>

    <header class="header">
        <div>
            <h1>Eingabeseiten</h1>
            <p>Direkte Pflege von Buchungen, Budget, Budgetkategorien und Inventur in JSON.</p>
        </div>
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>Neue Buchung erfassen</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_transaction">
            <label>Datum
                <input type="date" name="booking_date" required>
            </label>
            <label>Empfänger
                <input type="text" name="counterparty" required>
            </label>
            <label>Kostenart
                <select name="category_code" required>
                    <option value="">Bitte wählen</option>
                    <?php foreach ($categoryRows as $cat): ?>
                        <option value="<?= h($cat['code']) ?>"><?= h($cat['code'] . ' - ' . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Typ
                <select name="type">
                    <option value="in">Einnahme</option>
                    <option value="out">Ausgabe</option>
                </select>
            </label>
            <label>Betrag (CHF)
                <input type="number" name="amount" step="0.01" min="0.01" required>
            </label>
            <label>Konto
                <input type="text" name="account">
            </label>
            <label class="full-width">Notiz
                <input type="text" name="note">
            </label>
            <label>Status
                <select name="booking_status">
                    <option value="n">n — offen</option>
                    <option value="v">v — verarbeitet</option>
                    <option value="x" selected>x — gesperrt</option>
                </select>
            </label>
            <div class="full-width">
                <button type="submit">Buchung speichern</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Budget erfassen / aktualisieren</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_budget">
            <label>Periode
                <input type="month" name="period" required>
            </label>
            <label>Kostenart
                <select name="category_code" required>
                    <option value="">Bitte wählen</option>
                    <?php foreach ($categoryRows as $cat): ?>
                        <option value="<?= h($cat['code']) ?>"><?= h($cat['code'] . ' - ' . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Betrag (CHF)
                <input type="number" name="amount" step="0.01" required>
            </label>
            <div class="full-width">
                <button type="submit">Budget speichern</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Budgetkategorie (Kostenart) pflegen</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_category">
            <label>Code
                <input type="text" name="code" required>
            </label>
            <label>Name
                <input type="text" name="name" required>
            </label>
            <div class="full-width">
                <button type="submit">Kostenart speichern</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Inventur erfassen / aktualisieren</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_inventory">
            <label>Inventarnummer
                <input type="text" name="inventory_no" required>
            </label>
            <label>Artikel
                <input type="text" name="item_name" required>
            </label>
            <label>Anzahl
                <input type="number" name="quantity" min="0.01" step="0.01" required>
            </label>
            <label>Kaufpreis pro Einheit
                <input type="number" name="purchase_price_unit" step="0.01" min="0" value="0">
            </label>
            <label>Kaufperiode (YYYY.MM)
                <input type="text" name="purchase_period" placeholder="2026.04">
            </label>
            <label>Abschreiber [%/Jahr]
                <input type="number" name="depreciation_rate_pct" step="0.01" min="0" max="100" value="0">
            </label>
            <label>Zeitwert
                <input type="number" name="current_value" step="0.01" value="0">
            </label>
            <label>Liquidation (YYYY.MM)
                <input type="text" name="disposal_period" placeholder="2027.12">
            </label>
            <label>Verkaufswert
                <input type="number" name="disposal_value" step="0.01" value="0">
            </label>
            <label class="full-width">Beschreibung
                <input type="text" name="description">
            </label>
            <div class="full-width">
                <button type="submit">Inventar speichern</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Letzte Buchungen</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Datum</th>
                    <th>ID</th>
                    <th>Empfänger</th>
                    <th>Kostenart</th>
                    <th>Ein</th>
                    <th>Aus</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentTransactions as $tx): ?>
                    <?php $code = (string)($tx['cost_category_code'] ?? ''); ?>
                    <?php
                    $st = trim((string)($tx['booking_status'] ?? ''));
                    $st = in_array($st, ['n', 'v', 'x'], true) ? $st : 'x';
                    ?>
                    <tr>
                        <td><?= h((string)($tx['booking_date'] ?? '')) ?></td>
                        <td><?= h((string)($tx['external_id'] ?? '')) ?></td>
                        <td><?= h((string)($tx['counterparty'] ?? '')) ?></td>
                        <td><?= h($code . ' - ' . ($categoriesByCode[$code] ?? 'Unmapped')) ?></td>
                        <td><?= h(number_format((float)($tx['amount_in'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h(number_format((float)($tx['amount_out'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h($st) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Letzte Budgetwerte</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>Periode</th>
                    <th>Kostenart</th>
                    <th>Betrag</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentBudgetRows as $budget): ?>
                    <?php $code = (string)($budget['cost_category_code'] ?? ''); ?>
                    <tr>
                        <td><?= h((string)($budget['period_date'] ?? '')) ?></td>
                        <td><?= h($code . ' - ' . ($categoriesByCode[$code] ?? 'Unmapped')) ?></td>
                        <td><?= h(number_format((float)($budget['amount'] ?? 0), 2, '.', "'")) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Letzte Inventarwerte</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>INV#</th>
                    <th>Artikel</th>
                    <th>Anzahl</th>
                    <th>Kaufpreis</th>
                    <th>Zeitwert</th>
                    <th>Verkaufswert</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentInventoryRows as $inv): ?>
                    <tr>
                        <td><?= h((string)($inv['inventory_no'] ?? '')) ?></td>
                        <td><?= h((string)($inv['item_name'] ?? '')) ?></td>
                        <td><?= h(number_format((float)($inv['quantity'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h(number_format((float)($inv['purchase_price_unit'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h(number_format((float)($inv['current_value'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h(number_format((float)($inv['disposal_value'] ?? 0), 2, '.', "'")) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>

