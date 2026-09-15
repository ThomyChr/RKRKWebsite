<?php

declare(strict_types=1);

/**
 * Konto-Schlüssel: bevorzugt kuerzel, sonst legacy code.
 */
function account_kuerzel(array $acc): string
{
    $k = trim((string)($acc['kuerzel'] ?? ''));
    if ($k !== '') {
        return $k;
    }
    return trim((string)($acc['code'] ?? ''));
}

/**
 * Vergleich von Kontonummer/IBAN unabhängig von Leerzeichen und Gross/Klein.
 * Leer oder "-" wird als Platzhalter "-" normalisiert.
 */
function account_normalize_for_match(string $account): string
{
    $t = trim($account);
    if ($t === '' || $t === '-') {
        return '-';
    }

    return strtoupper((string)preg_replace('/\s+/u', '', $t));
}

/**
 * @param array<int, array<string, mixed>> $accounts
 * @return array<string, array<string, mixed>>
 */
function account_map(array $accounts, bool $activeOnly = false): array
{
    $map = [];
    foreach ($accounts as $acc) {
        $key = account_kuerzel($acc);
        if ($key === '') {
            continue;
        }
        if ($activeOnly && empty($acc['is_active'])) {
            continue;
        }
        $map[$key] = $acc;
    }
    return $map;
}

/**
 * Aktueller Saldo je registriertem Konto: alle Buchungen chronologisch, Zuordnung über
 * Kürzel/Code/IBAN (normalisiert) wie im Kontobuch.
 *
 * @param array<int, array<string, mixed>> $accounts
 * @param array<int, array<string, mixed>> $transactions
 * @return array{rows: array<int, array{kuerzel: string, name: string, balance: float}>, orphan_total: float}
 */
function finance_account_balances(array $accounts, array $transactions): array
{
    $tx = $transactions;
    usort(
        $tx,
        static function (array $a, array $b): int {
            $cmp = strcmp((string)($a['booking_date'] ?? ''), (string)($b['booking_date'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp2 = strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));
            if ($cmp2 !== 0) {
                return $cmp2;
            }
            return strcmp((string)($a['external_id'] ?? ''), (string)($b['external_id'] ?? ''));
        }
    );

    $normToKuerzel = [];
    foreach ($accounts as $acc) {
        if (!is_array($acc)) {
            continue;
        }
        $ku = account_kuerzel($acc);
        if ($ku === '') {
            continue;
        }
        foreach ([$ku, (string)($acc['code'] ?? ''), (string)($acc['iban'] ?? '')] as $cand) {
            $c = trim($cand);
            if ($c === '' || $c === '-') {
                continue;
            }
            $normToKuerzel[account_normalize_for_match($c)] = $ku;
        }
    }

    $byKuerzel = [];
    $orphanParts = [];
    foreach ($tx as $row) {
        $account = trim((string)($row['account'] ?? ''));
        if ($account === '') {
            $account = '-';
        }
        $n = account_normalize_for_match($account);
        $ku = $normToKuerzel[$n] ?? null;
        $delta = (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
        if ($ku !== null) {
            $byKuerzel[$ku] = ($byKuerzel[$ku] ?? 0.0) + $delta;
        } else {
            $orphanParts[$n] = ($orphanParts[$n] ?? 0.0) + $delta;
        }
    }

    $registered = [];
    foreach ($accounts as $acc) {
        if (!is_array($acc)) {
            continue;
        }
        if (empty($acc['is_active'])) {
            continue;
        }
        $ku = account_kuerzel($acc);
        if ($ku === '') {
            continue;
        }
        $registered[] = [
            'kuerzel' => $ku,
            'name' => trim((string)($acc['name'] ?? '')),
            'balance' => (float)($byKuerzel[$ku] ?? 0.0),
        ];
    }
    usort($registered, static fn(array $a, array $b): int => strcmp($a['kuerzel'], $b['kuerzel']));

    $orphanTotal = 0.0;
    foreach ($orphanParts as $v) {
        $orphanTotal += (float)$v;
    }

    return ['rows' => $registered, 'orphan_total' => $orphanTotal];
}
