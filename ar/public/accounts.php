<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/JsonStore.php';
require_once __DIR__ . '/../src/AccountHelpers.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();
rkrk_ar_emit_chrome('styles.css');

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$store = new JsonStore($dataDir);

function h_acc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function now_iso_acc(): string
{
    return date('Y-m-d\TH:i:s');
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array{0: array<int, array<string, mixed>>, 1: bool}
 */
function migrate_accounts(array $rows): array
{
    $changed = false;
    foreach ($rows as &$row) {
        if (!is_array($row)) {
            continue;
        }
        if (empty($row['id'])) {
            $row['id'] = bin2hex(random_bytes(16));
            $changed = true;
        }
        if (trim((string)($row['kuerzel'] ?? '')) === '' && trim((string)($row['code'] ?? '')) !== '') {
            $row['kuerzel'] = trim((string)$row['code']);
            $changed = true;
        }
    }
    unset($row);
    return [$rows, $changed];
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function find_account_by_id(array $rows, string $id): ?array
{
    foreach ($rows as $row) {
        if ((string)($row['id'] ?? '') === $id) {
            return $row;
        }
    }
    return null;
}

$rows = $store->readList('accounts.json');
[$rows, $migrated] = migrate_accounts($rows);
if ($migrated) {
    $store->writeList('accounts.json', $rows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    try {
        $rows = $store->readList('accounts.json');
        [$rows] = migrate_accounts($rows);

        if ($action === 'save_account') {
            $id = trim((string)($_POST['id'] ?? ''));
            $kuerzel = trim((string)($_POST['kuerzel'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $iban = trim((string)($_POST['iban'] ?? ''));
            $bank = trim((string)($_POST['bank'] ?? ''));
            $vorname = trim((string)($_POST['vorname'] ?? ''));
            $nachname = trim((string)($_POST['nachname'] ?? ''));
            $strasse = trim((string)($_POST['strasse'] ?? ''));
            $plz = trim((string)($_POST['plz'] ?? ''));
            $ort = trim((string)($_POST['ort'] ?? ''));
            $bank_name = trim((string)($_POST['bank_name'] ?? ''));
            $bank_strasse = trim((string)($_POST['bank_strasse'] ?? ''));
            $bank_plz = trim((string)($_POST['bank_plz'] ?? ''));
            $bank_ort = trim((string)($_POST['bank_ort'] ?? ''));
            $bic = trim((string)($_POST['bic'] ?? ''));
            $currency = strtoupper(trim((string)($_POST['currency'] ?? 'CHF')));
            $kontotyp = trim((string)($_POST['kontotyp'] ?? 'Giro'));
            $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';

            if ($kuerzel === '' || $name === '' || $currency === '') {
                header('Location: accounts.php?error=' . urlencode('Kürzel, Kontoname und Währung sind Pflichtfelder.'));
                exit;
            }

            foreach ($rows as $other) {
                if ((string)($other['id'] ?? '') === $id) {
                    continue;
                }
                if (strcasecmp(account_kuerzel($other), $kuerzel) === 0) {
                    header('Location: accounts.php?error=' . urlencode('Dieses Kürzel ist bereits vergeben.'));
                    exit;
                }
            }

            $payload = [
                'name' => $name,
                'kuerzel' => $kuerzel,
                'code' => $kuerzel,
                'iban' => $iban === '' ? null : $iban,
                'bank' => $bank === '' ? null : $bank,
                'vorname' => $vorname === '' ? null : $vorname,
                'nachname' => $nachname === '' ? null : $nachname,
                'strasse' => $strasse === '' ? null : $strasse,
                'plz' => $plz === '' ? null : $plz,
                'ort' => $ort === '' ? null : $ort,
                'bank_name' => $bank_name === '' ? null : $bank_name,
                'bank_strasse' => $bank_strasse === '' ? null : $bank_strasse,
                'bank_plz' => $bank_plz === '' ? null : $bank_plz,
                'bank_ort' => $bank_ort === '' ? null : $bank_ort,
                'bic' => $bic === '' ? null : $bic,
                'currency' => $currency,
                'kontotyp' => $kontotyp,
                'is_active' => $isActive,
                'updated_at' => now_iso_acc(),
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
                    header('Location: accounts.php?error=' . urlencode('Konto nicht gefunden.'));
                    exit;
                }
                $store->writeList('accounts.json', $rows);
                header('Location: accounts.php?ok=' . urlencode('Konto gespeichert.'));
                exit;
            }

            $payload['id'] = bin2hex(random_bytes(16));
            $payload['created_at'] = now_iso_acc();
            $rows[] = $payload;
            $store->writeList('accounts.json', $rows);
            header('Location: accounts.php?ok=' . urlencode('Konto angelegt.'));
            exit;
        }

        if ($action === 'delete_account') {
            $id = trim((string)($_POST['id'] ?? ''));
            if ($id === '') {
                header('Location: accounts.php?error=' . urlencode('Ungültige Anfrage.'));
                exit;
            }
            $newRows = array_values(array_filter($rows, static fn(array $r): bool => (string)($r['id'] ?? '') !== $id));
            if (count($newRows) === count($rows)) {
                header('Location: accounts.php?error=' . urlencode('Konto nicht gefunden.'));
                exit;
            }
            $store->writeList('accounts.json', $newRows);
            header('Location: accounts.php?ok=' . urlencode('Konto gelöscht.'));
            exit;
        }

        if ($action === 'activate_account' || $action === 'deactivate_account') {
            $id = trim((string)($_POST['id'] ?? ''));
            if ($id === '') {
                header('Location: accounts.php?error=' . urlencode('Ungültige Anfrage.'));
                exit;
            }
            $active = $action === 'activate_account';
            foreach ($rows as &$row) {
                if ((string)($row['id'] ?? '') === $id) {
                    $row['is_active'] = $active;
                    $row['updated_at'] = now_iso_acc();
                    break;
                }
            }
            unset($row);
            $store->writeList('accounts.json', $rows);
            header('Location: accounts.php?ok=' . urlencode($active ? 'Konto aktiviert.' : 'Konto deaktiviert.'));
            exit;
        }

        header('Location: accounts.php?error=' . urlencode('Unbekannte Aktion.'));
        exit;
    } catch (Throwable $e) {
        header('Location: accounts.php?error=' . urlencode('Fehler: ' . $e->getMessage()));
        exit;
    }
}

$accounts = $repo->getAccounts();
[$accounts] = migrate_accounts($accounts);
usort($accounts, static fn(array $a, array $b): int => strcmp(account_kuerzel($a), account_kuerzel($b)));

$editId = trim((string)($_GET['edit'] ?? ''));
$editRow = $editId !== '' ? find_account_by_id($accounts, $editId) : null;

$kontotypen = ['Giro', 'Sparkonto', 'Festgeld', 'Kreditkarte', 'Depot', 'Krypto', 'Sonstiges'];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konti | Rehkitzrettung Finanzen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a href="inventur.php">Inventur</a>
        <a class="active" href="accounts.php">Konti</a>
        <a href="cashflow.php">Cashflow</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
    </nav>

    <header class="header">
        <div>
            <h1>Konti verwalten</h1>
            <p>Konten anlegen, bearbeiten, aktivieren/deaktivieren oder löschen.</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_acc((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_acc((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2><?= $editRow ? 'Konto bearbeiten' : 'Neues Konto' ?></h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="save_account">
            <input type="hidden" name="id" value="<?= h_acc((string)($editRow['id'] ?? '')) ?>">
            <label>Kürzel *
                <input type="text" name="kuerzel" required maxlength="32"
                       value="<?= h_acc((string)($editRow ? account_kuerzel($editRow) : '')) ?>"
                    <?= $editRow ? 'readonly class="readonly-field"' : '' ?>
                       placeholder="z. B. CHF-HAUPT">
            </label>
            <label>Kontoname *
                <input type="text" name="name" required
                       value="<?= h_acc((string)($editRow['name'] ?? '')) ?>"
                       placeholder="Anzeigename">
            </label>
            <label>Währung *
                <input type="text" name="currency" required maxlength="5" value="<?= h_acc((string)($editRow['currency'] ?? 'CHF')) ?>">
            </label>
            <label>IBAN
                <input type="text" name="iban" value="<?= h_acc((string)($editRow['iban'] ?? '')) ?>">
            </label>
            <label>Bank (Kurzbezeichnung)
                <input type="text" name="bank" value="<?= h_acc((string)($editRow['bank'] ?? '')) ?>" placeholder="z. B. UBS">
            </label>
            <label>Kontotyp
                <select name="kontotyp">
                    <?php foreach ($kontotypen as $kt): ?>
                        <option value="<?= h_acc($kt) ?>" <?= (($editRow['kontotyp'] ?? 'Giro') === $kt) ? 'selected' : '' ?>><?= h_acc($kt) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="full-width"><strong>Inhaber</strong></label>
            <label>Vorname
                <input type="text" name="vorname" value="<?= h_acc((string)($editRow['vorname'] ?? '')) ?>">
            </label>
            <label>Name
                <input type="text" name="nachname" value="<?= h_acc((string)($editRow['nachname'] ?? '')) ?>">
            </label>
            <label class="full-width"><strong>Adresse (Inhaber)</strong></label>
            <label>Strasse
                <input type="text" name="strasse" value="<?= h_acc((string)($editRow['strasse'] ?? '')) ?>">
            </label>
            <label>PLZ
                <input type="text" name="plz" value="<?= h_acc((string)($editRow['plz'] ?? '')) ?>">
            </label>
            <label>Ort
                <input type="text" name="ort" value="<?= h_acc((string)($editRow['ort'] ?? '')) ?>">
            </label>
            <label class="full-width"><strong>Bank (Details)</strong></label>
            <label>Bankname
                <input type="text" name="bank_name" value="<?= h_acc((string)($editRow['bank_name'] ?? '')) ?>">
            </label>
            <label>BIC / SWIFT
                <input type="text" name="bic" value="<?= h_acc((string)($editRow['bic'] ?? '')) ?>">
            </label>
            <label>Bank Strasse
                <input type="text" name="bank_strasse" value="<?= h_acc((string)($editRow['bank_strasse'] ?? '')) ?>">
            </label>
            <label>Bank PLZ
                <input type="text" name="bank_plz" value="<?= h_acc((string)($editRow['bank_plz'] ?? '')) ?>">
            </label>
            <label>Bank Ort
                <input type="text" name="bank_ort" value="<?= h_acc((string)($editRow['bank_ort'] ?? '')) ?>">
            </label>
            <label class="full-width">
                <input type="checkbox" name="is_active" value="1" <?= ($editRow === null || !empty($editRow['is_active'])) ? 'checked' : '' ?>>
                Konto ist aktiv (erscheint im Kontobuch zur Auswahl)
            </label>
            <div class="full-width form-actions">
                <button type="submit"><?= $editRow ? 'Speichern' : 'Konto anlegen' ?></button>
                <?php if ($editRow): ?>
                    <a class="button-link secondary" href="accounts.php">Abbrechen / Neues Konto</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Registrierte Konti</h2>
        <div class="table-wrapper kontobuch-grid">
            <table class="accounts-table">
                <thead>
                <tr>
                    <th>Kürzel</th>
                    <th>Kontoname</th>
                    <th>IBAN</th>
                    <th>Bank</th>
                    <th>Typ</th>
                    <th>Währung</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $acc): ?>
                    <?php
                    $kid = (string)($acc['id'] ?? '');
                    $ku = account_kuerzel($acc);
                    ?>
                    <tr class="<?= empty($acc['is_active']) ? 'row-inactive' : '' ?>">
                        <td><?= h_acc($ku) ?></td>
                        <td><?= h_acc((string)($acc['name'] ?? '')) ?></td>
                        <td><?= h_acc((string)($acc['iban'] ?? '–')) ?></td>
                        <td><?= h_acc((string)($acc['bank_name'] ?? $acc['bank'] ?? '–')) ?></td>
                        <td><?= h_acc((string)($acc['kontotyp'] ?? '–')) ?></td>
                        <td><?= h_acc((string)($acc['currency'] ?? '')) ?></td>
                        <td><?= !empty($acc['is_active']) ? 'aktiv' : 'inaktiv' ?></td>
                        <td class="cell-actions">
                            <a class="icon-link" href="accounts.php?edit=<?= h_acc($kid) ?>" title="Bearbeiten" aria-label="Bearbeiten">✎</a>
                            <?php if (!empty($acc['is_active'])): ?>
                                <form method="post" class="inline-form" onsubmit="return confirm('Konto deaktivieren?');">
                                    <input type="hidden" name="action" value="deactivate_account">
                                    <input type="hidden" name="id" value="<?= h_acc($kid) ?>">
                                    <button type="submit" class="icon-btn" title="Deaktivieren" aria-label="Deaktivieren">⏸</button>
                                </form>
                            <?php else: ?>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="activate_account">
                                    <input type="hidden" name="id" value="<?= h_acc($kid) ?>">
                                    <button type="submit" class="icon-btn" title="Aktivieren" aria-label="Aktivieren">▶</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" class="inline-form" onsubmit="return confirm('Konto endgültig löschen? Buchungen bleiben mit altem Kürzel erhalten.');">
                                <input type="hidden" name="action" value="delete_account">
                                <input type="hidden" name="id" value="<?= h_acc($kid) ?>">
                                <button type="submit" class="icon-btn icon-danger" title="Löschen" aria-label="Löschen">🗑</button>
                            </form>
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
