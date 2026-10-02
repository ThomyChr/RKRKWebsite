<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/JsonStore.php';
require_once __DIR__ . '/../src/InventoryDepreciation.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_require_login();

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$store = new JsonStore($dataDir);

function h_inv(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function now_inv(): string
{
    return date('Y-m-d\TH:i:s');
}

function redirect_inv(array $params = []): never
{
    header('Location: inventur.php' . (count($params) ? '?' . http_build_query($params) : ''));
    exit;
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function find_inv_by_id(array $rows, string $id): ?array
{
    foreach ($rows as $row) {
        if ((string)($row['id'] ?? '') === $id) {
            return $row;
        }
    }
    return null;
}

function inv_is_sold(array $row): bool
{
    $period = trim((string)($row['disposal_period'] ?? ''));
    $value = (float)($row['disposal_value'] ?? 0);
    return $period !== '' || abs($value) > 0.00001;
}

function inv_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

$rows = $store->readList('inventory_items.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        if ($action === 'save_item') {
            $id = trim((string)($_POST['id'] ?? ''));
            $inventoryNo = trim((string)($_POST['inventory_no'] ?? ''));
            $quantity = (float)($_POST['quantity'] ?? 0);
            $itemName = trim((string)($_POST['item_name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $purchasePriceUnit = (float)($_POST['purchase_price_unit'] ?? 0);
            $purchasePeriod = trim((string)($_POST['purchase_period'] ?? ''));
            $newValueManual = (float)($_POST['new_value'] ?? 0);
            $depreciation = (float)($_POST['depreciation_rate_pct'] ?? 0);
            $currentValue = (float)($_POST['current_value'] ?? 0);
            $disposalPeriod = trim((string)($_POST['disposal_period'] ?? ''));
            $disposalValue = (float)($_POST['disposal_value'] ?? 0);
            $useAuto = isset($_POST['use_auto_zeitwert']) && $_POST['use_auto_zeitwert'] === '1';
            $stichtagRaw = trim((string)($_POST['stichtag'] ?? ''));

            if ($inventoryNo === '' || $itemName === '' || $quantity <= 0) {
                $err = ['error' => 'INV#, Artikel und Anzahl > 0 sind Pflichtfelder.'];
                if ($id !== '') {
                    $err['edit'] = $id;
                }
                redirect_inv($err);
            }

            $stichtag = $stichtagRaw !== ''
                ? DateTimeImmutable::createFromFormat('Y-m-d', $stichtagRaw) ?: new DateTimeImmutable('today')
                : new DateTimeImmutable('today');

            $neuwert = $newValueManual > 0 ? $newValueManual : round($quantity * $purchasePriceUnit, 2);

            if ($useAuto) {
                $preview = inventur_compute_preview(
                    $quantity,
                    $purchasePriceUnit,
                    $neuwert,
                    $depreciation,
                    $purchasePeriod,
                    $stichtag,
                    $disposalPeriod
                );
                $currentValue = $preview['zeitwert'];
            }

            $payload = [
                'inventory_no' => $inventoryNo,
                'quantity' => $quantity,
                'item_name' => $itemName,
                'description' => $description === '' ? null : $description,
                'purchase_price_unit' => $purchasePriceUnit,
                'purchase_period' => $purchasePeriod === '' ? null : $purchasePeriod,
                'new_value' => $neuwert,
                'depreciation_rate_pct' => $depreciation,
                'current_value' => $currentValue,
                'disposal_period' => $disposalPeriod === '' ? null : $disposalPeriod,
                'disposal_value' => $disposalValue,
                'source_sheet' => 'Web',
                'source_row_no' => null,
                'updated_at' => now_inv(),
            ];

            if ($id !== '') {
                $found = false;
                foreach ($rows as &$row) {
                    if ((string)($row['id'] ?? '') === $id) {
                        $row = array_merge($row, $payload);
                        $found = true;
                        break;
                    }
                }
                unset($row);
                if (!$found) {
                    redirect_inv(['error' => 'Position nicht gefunden.']);
                }
                $store->writeList('inventory_items.json', $rows);
                redirect_inv(['ok' => 'Inventar gespeichert.']);
            }

            $payload['id'] = bin2hex(random_bytes(16));
            $payload['created_at'] = now_inv();
            $payload['imported_at'] = now_inv();
            $maxSort = 0;
            foreach ($rows as $existing) {
                $sortOrder = (int)($existing['sort_order'] ?? 0);
                if ($sortOrder > $maxSort) {
                    $maxSort = $sortOrder;
                }
            }
            $payload['sort_order'] = $maxSort + 10;
            $rows[] = $payload;
            $store->writeList('inventory_items.json', $rows);
            redirect_inv(['ok' => 'Inventar angelegt.']);
        }

        if ($action === 'reorder_items') {
            $orderedIds = $_POST['ordered_ids'] ?? [];
            if (!is_array($orderedIds)) {
                redirect_inv(['error' => 'Ungültige Sortierung.']);
            }
            $orderMap = [];
            $rank = 1;
            foreach ($orderedIds as $rawId) {
                $id = trim((string)$rawId);
                if ($id === '' || isset($orderMap[$id])) {
                    continue;
                }
                $orderMap[$id] = $rank++;
            }
            if ($orderMap === []) {
                redirect_inv(['error' => 'Keine Sortierung übergeben.']);
            }
            foreach ($rows as &$row) {
                $id = (string)($row['id'] ?? '');
                if ($id !== '' && isset($orderMap[$id])) {
                    $row['sort_order'] = $orderMap[$id] * 10;
                    $row['updated_at'] = now_inv();
                }
            }
            unset($row);
            $store->writeList('inventory_items.json', $rows);
            redirect_inv(['ok' => 'Reihenfolge gespeichert.']);
        }

        if ($action === 'delete_item') {
            $id = trim((string)($_POST['id'] ?? ''));
            if ($id === '') {
                redirect_inv(['error' => 'Ungültige Anfrage.']);
            }
            $newRows = array_values(array_filter($rows, static fn(array $r): bool => (string)($r['id'] ?? '') !== $id));
            if (count($newRows) === count($rows)) {
                redirect_inv(['error' => 'Eintrag nicht gefunden.']);
            }
            $store->writeList('inventory_items.json', $newRows);
            redirect_inv(['ok' => 'Eintrag gelöscht.']);
        }

        redirect_inv(['error' => 'Unbekannte Aktion.']);
    } catch (Throwable $e) {
        redirect_inv(['error' => 'Fehler: ' . $e->getMessage()]);
    }
}

rkrk_ar_emit_chrome('styles.css');

$items = $repo->getInventoryItems();
usort($items, static function (array $a, array $b): int {
    $sa = (int)($a['sort_order'] ?? 0);
    $sb = (int)($b['sort_order'] ?? 0);
    if ($sa > 0 && $sb > 0 && $sa !== $sb) {
        return $sa <=> $sb;
    }
    if ($sa > 0 && $sb <= 0) {
        return -1;
    }
    if ($sb > 0 && $sa <= 0) {
        return 1;
    }
    return strcmp((string)($a['inventory_no'] ?? ''), (string)($b['inventory_no'] ?? ''));
});

$filterShow = trim((string)($_GET['show'] ?? 'active'));
$filterShow = in_array($filterShow, ['active', 'sold', 'all'], true) ? $filterShow : 'active';
$filterPurchaseYear = trim((string)($_GET['purchase_year'] ?? ''));
$filterQ = trim((string)($_GET['q'] ?? ''));

$visibleItems = [];
foreach ($items as $it) {
    $isSold = inv_is_sold($it);
    if ($filterShow === 'active' && $isSold) {
        continue;
    }
    if ($filterShow === 'sold' && !$isSold) {
        continue;
    }
    if ($filterPurchaseYear !== '') {
        $period = (string)($it['purchase_period'] ?? '');
        if (substr($period, 0, 4) !== $filterPurchaseYear) {
            continue;
        }
    }
    if ($filterQ !== '') {
        $hay = inv_lower(implode(' ', [
            (string)($it['inventory_no'] ?? ''),
            (string)($it['item_name'] ?? ''),
            (string)($it['description'] ?? ''),
        ]));
        if (!str_contains($hay, inv_lower($filterQ))) {
            continue;
        }
    }
    $visibleItems[] = $it;
}

$editId = trim((string)($_GET['edit'] ?? ''));
$editRow = $editId !== '' ? find_inv_by_id($items, $editId) : null;

$stichtagDefault = (new DateTimeImmutable('today'))->format('Y-m-d');
$previewData = null;
if ($editRow) {
    $previewData = inventur_compute_preview(
        (float)($editRow['quantity'] ?? 0),
        (float)($editRow['purchase_price_unit'] ?? 0),
        (float)($editRow['new_value'] ?? 0),
        (float)($editRow['depreciation_rate_pct'] ?? 0),
        (string)($editRow['purchase_period'] ?? ''),
        new DateTimeImmutable($stichtagDefault),
        (string)($editRow['disposal_period'] ?? '')
    );
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventur | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a class="active" href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="transactions.php">Analyse</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
        <a href="cashflow.php">Cashflow</a>
    </nav>

    <header class="header">
        <div>
            <h1>Inventur</h1>
            <p>Erfassen und Bearbeiten von Inventarpositionen.</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_inv((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_inv((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2><?= $editRow ? 'Position bearbeiten' : 'Neue Position' ?></h2>
        <form method="post" class="form-grid" id="inv-form">
            <input type="hidden" name="action" value="save_item">
            <input type="hidden" name="id" value="<?= h_inv((string)($editRow['id'] ?? '')) ?>">

            <label>INV# *
                <input type="text" name="inventory_no" required value="<?= h_inv((string)($editRow['inventory_no'] ?? '')) ?>" <?= $editRow ? 'readonly class="readonly-field"' : '' ?>>
            </label>
            <label>#Anzahl *
                <input type="number" name="quantity" id="inv_qty" step="0.01" min="0.01" required value="<?= h_inv((string)($editRow['quantity'] ?? '1')) ?>">
            </label>
            <label>Artikel *
                <input type="text" name="item_name" required value="<?= h_inv((string)($editRow['item_name'] ?? '')) ?>">
            </label>
            <label class="full-width">Beschreibung
                <input type="text" name="description" value="<?= h_inv((string)($editRow['description'] ?? '')) ?>">
            </label>
            <label>Kaufpreis pro Einheit
                <input type="number" name="purchase_price_unit" id="inv_ppu" step="0.01" min="0" value="<?= h_inv((string)($editRow['purchase_price_unit'] ?? '0')) ?>">
            </label>
            <label>Kaufdatum (YYYY.MM)
                <input type="text" name="purchase_period" id="inv_period" placeholder="2021.03" value="<?= h_inv((string)($editRow['purchase_period'] ?? '')) ?>">
            </label>
            <label>Neuwert (leer = Anzahl × Kaufpreis)
                <input type="number" name="new_value" id="inv_newval" step="0.01" min="0" value="<?= h_inv((string)($editRow['new_value'] ?? '')) ?>">
            </label>
            <label>Abschreiber [%/Jahr]
                <input type="number" name="depreciation_rate_pct" id="inv_rate" step="0.01" min="0" max="100" value="<?= h_inv((string)($editRow['depreciation_rate_pct'] ?? '0')) ?>">
            </label>
            <label>Stichtag Berechnung
                <input type="date" name="stichtag" id="inv_stichtag" value="<?= h_inv($stichtagDefault) ?>">
            </label>

            <div class="card full-width inv-preview">
                <strong>Abschreibungsberechnung (Vorschau)</strong>
                <p class="muted">Neuwert × (1 − p/100)<sup>t</sup> mit t = Jahren seit Kaufdatum (Monatsgenau).</p>
                <ul class="preview-list">
                    <li>Neuwert (Basis): <span id="pv_neuwert">–</span></li>
                    <li>Jahre seit Kauf: <span id="pv_jahre">–</span></li>
                    <li>Berechneter Zeitwert: <strong id="pv_zeitwert">–</strong></li>
                </ul>
                <label class="inline-check">
                    <input type="checkbox" name="use_auto_zeitwert" value="1" id="inv_auto" checked>
                    Beim Speichern berechneten Zeitwert übernehmen
                </label>
            </div>

            <label>Zeitwert (manuell)
                <input type="number" name="current_value" id="inv_curr" step="0.01" value="<?= h_inv((string)($editRow['current_value'] ?? '0')) ?>">
            </label>
            <label>Verkauf (YYYY.MM)
                <input type="text" name="disposal_period" value="<?= h_inv((string)($editRow['disposal_period'] ?? '')) ?>">
            </label>
            <label>Verkaufswert
                <input type="number" name="disposal_value" step="0.01" value="<?= h_inv((string)($editRow['disposal_value'] ?? '0')) ?>">
            </label>
            <div class="full-width form-actions">
                <button type="submit"><?= $editRow ? 'Speichern' : 'Anlegen' ?></button>
                <?php if ($editRow): ?>
                    <a class="button-link secondary" href="inventur.php">Zurück</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if ($previewData && $editRow): ?>
        <p class="muted">Server-Vorschau (Stichtag <?= h_inv($previewData['stichtag']) ?>, berechnet bis <?= h_inv((string)($previewData['berechnet_bis'] ?? $previewData['stichtag'])) ?>):
            Zeitwert <?= h_inv((string)$previewData['zeitwert']) ?> CHF bei <?= h_inv((string)$previewData['jahre']) ?> Jahren.</p>
    <?php endif; ?>

    <section class="card">
        <h2>Alle Positionen</h2>
        <form method="get" class="filters-inline" id="inv-filter-form" style="margin-bottom:0.75rem;">
            <label>Zeigen
                <select name="show">
                    <option value="active" <?= $filterShow === 'active' ? 'selected' : '' ?>>Nur aktiv</option>
                    <option value="sold" <?= $filterShow === 'sold' ? 'selected' : '' ?>>Nur verkauft</option>
                    <option value="all" <?= $filterShow === 'all' ? 'selected' : '' ?>>Alle</option>
                </select>
            </label>
            <label>Kaufjahr
                <input type="text" name="purchase_year" value="<?= h_inv($filterPurchaseYear) ?>" placeholder="z.B. 2024">
            </label>
            <label>Suche
                <input type="text" name="q" value="<?= h_inv($filterQ) ?>" placeholder="INV#, Artikel, Beschreibung">
            </label>
            <button type="submit">Filtern</button>
            <a class="button-link secondary" href="inventur.php">Zurücksetzen</a>
        </form>
        <div class="table-wrapper kontobuch-grid">
            <table id="inventur-table">
                <thead>
                <tr>
                    <th style="width:1%">↕</th>
                    <th>INV#</th>
                    <th>Anz.</th>
                    <th>Artikel</th>
                    <th>Kaufdatum</th>
                    <th>Neuwert</th>
                    <th>%/J.</th>
                    <th>Zeitwert</th>
                    <th>Verkaufsdatum</th>
                    <th>Verkaufswert</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($visibleItems as $it): ?>
                    <?php $isSold = inv_is_sold($it); ?>
                    <tr data-inv-id="<?= h_inv((string)($it['id'] ?? '')) ?>" draggable="true" class="<?= $isSold ? 'row-sold' : '' ?>">
                        <td class="drag-handle" title="Ziehen zum Sortieren">⋮⋮</td>
                        <td><?= h_inv((string)($it['inventory_no'] ?? '')) ?></td>
                        <td><?= h_inv((string)($it['quantity'] ?? '')) ?></td>
                        <td><?= h_inv((string)($it['item_name'] ?? '')) ?></td>
                        <td><?= h_inv((string)($it['purchase_period'] ?? '–')) ?></td>
                        <td><?= h_inv(number_format((float)($it['new_value'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h_inv((string)($it['depreciation_rate_pct'] ?? '')) ?></td>
                        <td><?= h_inv(number_format((float)($it['current_value'] ?? 0), 2, '.', "'")) ?></td>
                        <td><?= h_inv((string)($it['disposal_period'] ?? '–')) ?></td>
                        <td><?= h_inv((float)($it['disposal_value'] ?? 0) > 0 ? number_format((float)($it['disposal_value'] ?? 0), 2, '.', "'") : '') ?></td>
                        <td class="cell-actions">
                            <a class="icon-link" href="inventur.php?edit=<?= h_inv((string)($it['id'] ?? '')) ?>" title="Bearbeiten" aria-label="Bearbeiten">✎</a>
                            <form method="post" class="inline-form" onsubmit="return confirm('Position löschen?');">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="id" value="<?= h_inv((string)($it['id'] ?? '')) ?>">
                                <button type="submit" class="icon-btn icon-danger" title="Löschen" aria-label="Löschen">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($visibleItems === []): ?>
                    <tr><td colspan="11" class="muted">Keine Positionen für den aktuellen Filter gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <form method="post" id="inv-reorder-form" class="form-actions" style="margin-top:0.75rem;">
            <input type="hidden" name="action" value="reorder_items">
            <div id="inv-order-fields"></div>
            <button type="submit">Reihenfolge speichern</button>
        </form>
    </section>
</div>
<script>
(function () {
  function parsePeriod(s) {
    s = String(s || '').trim();
    var m = s.match(/^(\d{4})\.(\d{1,2})$/);
    if (!m) return null;
    var y = parseInt(m[1], 10), mo = Math.min(12, Math.max(1, parseInt(m[2], 10)));
    return new Date(y, mo - 1, 1);
  }
  function yearsBetween(from, to) {
    if (!from || !to || to < from) return 0;
    var ms = to - from;
    return ms / (365.25 * 24 * 3600 * 1000);
  }
  function declining(neuwert, ratePct, years) {
    if (neuwert <= 0) return 0;
    if (years <= 0) return Math.round(neuwert * 2) / 2;
    var r = Math.min(100, Math.max(0, ratePct));
    var f = Math.pow(1 - r / 100, years);
    return Math.max(0, Math.round((neuwert * f) * 2) / 2);
  }
  function recalc() {
    var qty = parseFloat(document.getElementById('inv_qty').value) || 0;
    var ppu = parseFloat(document.getElementById('inv_ppu').value) || 0;
    var nvIn = parseFloat(document.getElementById('inv_newval').value);
    var neuwert = nvIn > 0 ? nvIn : Math.round(qty * ppu * 100) / 100;
    var rate = parseFloat(document.getElementById('inv_rate').value) || 0;
    var period = document.getElementById('inv_period').value;
    var st = document.getElementById('inv_stichtag').value;
    var stDate = st ? new Date(st + 'T12:00:00') : new Date();
    var pDate = parsePeriod(period);
    var jahre = pDate ? yearsBetween(pDate, stDate) : 0;
    var zw = declining(neuwert, rate, jahre);
    document.getElementById('pv_neuwert').textContent = neuwert.toFixed(2) + ' CHF';
    document.getElementById('pv_jahre').textContent = jahre.toFixed(3);
    document.getElementById('pv_zeitwert').textContent = zw.toFixed(2) + ' CHF';
    var auto = document.getElementById('inv_auto').checked;
    document.getElementById('inv_curr').readOnly = auto;
    if (auto) {
      document.getElementById('inv_curr').value = zw.toFixed(2);
    }
  }
  ['inv_qty','inv_ppu','inv_newval','inv_rate','inv_period','inv_stichtag','inv_auto'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', recalc);
      el.addEventListener('change', recalc);
    }
  });

  var filterForm = document.getElementById('inv-filter-form');
  if (filterForm) {
    filterForm.querySelectorAll('select').forEach(function (sel) {
      sel.addEventListener('change', function () { filterForm.submit(); });
    });
  }

  var table = document.getElementById('inventur-table');
  var reorderForm = document.getElementById('inv-reorder-form');
  var orderFields = document.getElementById('inv-order-fields');
  var draggingRow = null;
  if (table && reorderForm && orderFields) {
    table.addEventListener('dragstart', function (ev) {
      var row = ev.target && ev.target.closest('tr[data-inv-id]');
      if (!row) return;
      draggingRow = row;
      row.classList.add('dragging');
      if (ev.dataTransfer) {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', row.getAttribute('data-inv-id') || '');
      }
    });
    table.addEventListener('dragend', function () {
      if (draggingRow) draggingRow.classList.remove('dragging');
      draggingRow = null;
    });
    table.addEventListener('dragover', function (ev) {
      if (!draggingRow) return;
      var row = ev.target && ev.target.closest('tr[data-inv-id]');
      if (!row || row === draggingRow) return;
      ev.preventDefault();
      var rect = row.getBoundingClientRect();
      var before = ev.clientY < rect.top + rect.height / 2;
      row.parentNode.insertBefore(draggingRow, before ? row : row.nextSibling);
    });
    reorderForm.addEventListener('submit', function () {
      orderFields.innerHTML = '';
      table.querySelectorAll('tbody tr[data-inv-id]').forEach(function (row) {
        var id = row.getAttribute('data-inv-id');
        if (!id) return;
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'ordered_ids[]';
        hidden.value = id;
        orderFields.appendChild(hidden);
      });
    });
  }

  recalc();
})();
</script>
<style>
.muted { color: #6b7280; font-size: 0.9rem; }
.inv-preview { background: #f9fafb; margin-top: 0.5rem; }
.preview-list { margin: 0.5rem 0; padding-left: 1.2rem; }
.inline-check { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; }
.row-sold { color: #6b7280; background: #f3f4f6 !important; }
.drag-handle { cursor: grab; user-select: none; text-align: center; color: #6b7280; }
tr.dragging { opacity: 0.55; }
</style>
</body>
</html>
