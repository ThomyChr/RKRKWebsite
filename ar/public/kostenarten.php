<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/JsonStore.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$store = new JsonStore($dataDir);

function h_ka(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function now_ka(): string
{
    return date('Y-m-d\TH:i:s');
}

function redirect_ka(array $params = []): never
{
    header('Location: kostenarten.php' . (count($params) ? '?' . http_build_query($params) : ''));
    exit;
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array{0: array<int, array<string, mixed>>, 1: bool}
 */
function migrate_cost_categories(array $rows): array
{
    $changed = false;
    foreach ($rows as $idx => &$row) {
        if (!isset($row['sort_order'])) {
            $row['sort_order'] = ($idx + 1) * 10;
            $changed = true;
        }
        if (!array_key_exists('is_active', $row)) {
            $row['is_active'] = true;
            $changed = true;
        }
    }
    unset($row);
    return [$rows, $changed];
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function find_cat_by_id(array $rows, string $id): ?array
{
    foreach ($rows as $row) {
        if ((string)($row['id'] ?? '') === $id) {
            return $row;
        }
    }
    return null;
}

$rows = $store->readList('cost_categories.json');
[$rows, $migrated] = migrate_cost_categories($rows);
if ($migrated) {
    $store->writeList('cost_categories.json', $rows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        $rows = $store->readList('cost_categories.json');
        [$rows] = migrate_cost_categories($rows);

        if ($action === 'save_category') {
            $id = trim((string)($_POST['id'] ?? ''));
            $code = trim((string)($_POST['code'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';

            if ($code === '' || $name === '') {
                redirect_ka(['error' => 'Code und Name sind Pflichtfelder.']);
            }
            if ($code === 'KA#') {
                redirect_ka(['error' => 'Code KA# ist reserviert.']);
            }

            foreach ($rows as $other) {
                if ((string)($other['id'] ?? '') === $id) {
                    continue;
                }
                if ((string)($other['code'] ?? '') === $code) {
                    redirect_ka(['error' => 'Dieser Code ist bereits vergeben.']);
                }
            }

            if ($sortOrder <= 0) {
                $maxSo = 0;
                foreach ($rows as $r) {
                    $maxSo = max($maxSo, (int)($r['sort_order'] ?? 0));
                }
                $sortOrder = $maxSo + 10;
            }

            $payload = [
                'code' => $code,
                'name' => $name,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'updated_at' => now_ka(),
            ];

            if ($id !== '') {
                $found = false;
                foreach ($rows as &$row) {
                    if ((string)($row['id'] ?? '') === $id) {
                        if ((string)($row['code'] ?? '') === 'KA#') {
                            redirect_ka(['error' => 'Diese Zeile kann nicht geändert werden.']);
                        }
                        $row = array_merge($row, $payload);
                        $found = true;
                        break;
                    }
                }
                unset($row);
                if (!$found) {
                    redirect_ka(['error' => 'Eintrag nicht gefunden.']);
                }
                $store->writeList('cost_categories.json', $rows);
                redirect_ka(['ok' => 'Kostenart gespeichert.', 'edit' => $id]);
            }

            $payload['id'] = bin2hex(random_bytes(16));
            $payload['created_at'] = now_ka();
            $rows[] = $payload;
            usort($rows, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
            $store->writeList('cost_categories.json', $rows);
            redirect_ka(['ok' => 'Kostenart angelegt.', 'edit' => $payload['id']]);
        }

        if ($action === 'delete_category') {
            $id = trim((string)($_POST['id'] ?? ''));
            $row = find_cat_by_id($rows, $id);
            if ($row === null) {
                redirect_ka(['error' => 'Nicht gefunden.']);
            }
            if ((string)($row['code'] ?? '') === 'KA#') {
                redirect_ka(['error' => 'Kopfzeile KA# kann nicht gelöscht werden.']);
            }
            $newRows = array_values(array_filter($rows, static fn(array $r): bool => (string)($r['id'] ?? '') !== $id));
            $store->writeList('cost_categories.json', $newRows);
            redirect_ka(['ok' => 'Kostenart gelöscht.']);
        }

        if ($action === 'activate_category' || $action === 'deactivate_category') {
            $id = trim((string)($_POST['id'] ?? ''));
            $active = $action === 'activate_category';
            foreach ($rows as &$row) {
                if ((string)($row['id'] ?? '') === $id) {
                    if ((string)($row['code'] ?? '') === 'KA#') {
                        redirect_ka(['error' => 'Kopfzeile KA# nicht änderbar.']);
                    }
                    $row['is_active'] = $active;
                    $row['updated_at'] = now_ka();
                    break;
                }
            }
            unset($row);
            $store->writeList('cost_categories.json', $rows);
            redirect_ka(['ok' => $active ? 'Aktiviert.' : 'Deaktiviert.']);
        }

        if ($action === 'sort_move') {
            $id = trim((string)($_POST['id'] ?? ''));
            $dir = trim((string)($_POST['direction'] ?? ''));
            usort($rows, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
            $idx = -1;
            foreach ($rows as $i => $r) {
                if ((string)($r['id'] ?? '') === $id) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx < 0) {
                redirect_ka(['error' => 'Nicht gefunden.']);
            }
            $swapIdx = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($swapIdx < 0 || $swapIdx >= count($rows)) {
                redirect_ka(['ok' => 'Reihenfolge unverändert.']);
            }
            $soA = (int)($rows[$idx]['sort_order'] ?? 0);
            $soB = (int)($rows[$swapIdx]['sort_order'] ?? 0);
            $rows[$idx]['sort_order'] = $soB;
            $rows[$swapIdx]['sort_order'] = $soA;
            $rows[$idx]['updated_at'] = now_ka();
            $rows[$swapIdx]['updated_at'] = now_ka();
            usort($rows, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
            $store->writeList('cost_categories.json', $rows);
            redirect_ka(['ok' => 'Sortierung angepasst.']);
        }

        redirect_ka(['error' => 'Unbekannte Aktion.']);
    } catch (Throwable $e) {
        redirect_ka(['error' => $e->getMessage()]);
    }
}

$rows = $store->readList('cost_categories.json');
[$rows] = migrate_cost_categories($rows);
usort($rows, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));

$editId = trim((string)($_GET['edit'] ?? ''));
$editRow = $editId !== '' ? find_cat_by_id($rows, $editId) : null;
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kostenarten | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a class="active" href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
    </nav>

    <header class="header">
        <div>
            <h1>Kostenarten</h1>
            <p>Verwalten wie im Excel: Code, Bezeichnung, Reihenfolge, aktiv/inaktiv.</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_ka((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_ka((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2><?= $editRow ? 'Kostenart bearbeiten' : 'Neue Kostenart' ?></h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= h_ka((string)($editRow['id'] ?? '')) ?>">
            <label>Code (KA#) *
                <input type="text" name="code" required value="<?= h_ka((string)($editRow['code'] ?? '')) ?>"
                    <?= ($editRow && ($editRow['code'] ?? '') === 'KA#') ? 'readonly class="readonly-field"' : '' ?>>
            </label>
            <label>Bezeichnung *
                <input type="text" name="name" required value="<?= h_ka((string)($editRow['name'] ?? '')) ?>">
            </label>
            <label>Sortierung (klein = oben)
                <input type="number" name="sort_order" min="0" step="1"
                       value="<?= h_ka((string)($editRow['sort_order'] ?? '')) ?>" placeholder="auto">
            </label>
            <label class="full-width">
                <input type="checkbox" name="is_active" value="1" <?= ($editRow === null || !empty($editRow['is_active'])) ? 'checked' : '' ?>>
                Aktiv (in Auswahllisten)
            </label>
            <div class="full-width form-actions">
                <button type="submit"><?= $editRow ? 'Speichern' : 'Anlegen' ?></button>
                <?php if ($editRow): ?>
                    <a class="button-link secondary" href="kostenarten.php">Neu</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Alle Kostenarten</h2>
        <div class="table-wrapper kontobuch-grid">
            <table>
                <thead>
                <tr>
                    <th>Sort</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $rid = (string)($row['id'] ?? '');
                    $isHeader = ($row['code'] ?? '') === 'KA#';
                    ?>
                    <tr class="<?= empty($row['is_active']) ? 'row-inactive' : '' ?>">
                        <td><?= h_ka((string)($row['sort_order'] ?? '')) ?></td>
                        <td><?= h_ka((string)($row['code'] ?? '')) ?></td>
                        <td><?= h_ka((string)($row['name'] ?? '')) ?></td>
                        <td><?= !empty($row['is_active']) ? 'aktiv' : 'inaktiv' ?></td>
                        <td class="cell-actions">
                            <a class="icon-link" href="kostenarten.php?edit=<?= h_ka($rid) ?>" title="Bearbeiten" aria-label="Bearbeiten">✎</a>
                            <?php if (!$isHeader): ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="sort_move">
                                    <input type="hidden" name="id" value="<?= h_ka($rid) ?>">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="icon-btn" title="Nach oben" aria-label="Nach oben">↑</button>
                                </form>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="sort_move">
                                    <input type="hidden" name="id" value="<?= h_ka($rid) ?>">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="icon-btn" title="Nach unten" aria-label="Nach unten">↓</button>
                                </form>
                                <?php if (!empty($row['is_active'])): ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="deactivate_category">
                                        <input type="hidden" name="id" value="<?= h_ka($rid) ?>">
                                        <button type="submit" class="icon-btn" title="Deaktivieren" aria-label="Deaktivieren">⏸</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="activate_category">
                                        <input type="hidden" name="id" value="<?= h_ka($rid) ?>">
                                        <button type="submit" class="icon-btn" title="Aktivieren" aria-label="Aktivieren">▶</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="inline-form" onsubmit="return confirm('Kostenart löschen?');">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="id" value="<?= h_ka($rid) ?>">
                                    <button type="submit" class="icon-btn icon-danger" title="Löschen" aria-label="Löschen">🗑</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">(Kopfzeile)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
</body>
</html>
