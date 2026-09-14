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

function h_book(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function now_iso_book(): string
{
    return date('Y-m-d\TH:i:s');
}

/** Letzte 6 Zeichen ohne Leerzeichen (für Tabellenanzeige). */
function account_table_display(string $account): string
{
    $account = trim($account);
    if ($account === '' || $account === '-') {
        return $account;
    }
    $compact = preg_replace('/\s+/u', '', $account);
    if ($compact === '') {
        return $account;
    }
    if (strlen($compact) <= 6) {
        return $compact;
    }
    return substr($compact, -6);
}

function redirect_book(array $params = []): never
{
    $query = http_build_query($params);
    header('Location: kontobuch.php' . ($query !== '' ? ('?' . $query) : ''));
    exit;
}

/** @param array<string, scalar|null> $params */
function redirect_kontobuch(array $params): never
{
    $base = [
        'booking_top_account' => (string)(
            $_POST['redirect_booking_top_account']
            ?? $_POST['redirect_account']
            ?? $_GET['booking_top_account']
            ?? $_GET['account']
            ?? ''
        ),
        'filter_year' => (string)($_POST['redirect_filter_year'] ?? $_GET['filter_year'] ?? ''),
        'filter_period' => (string)($_POST['redirect_filter_period'] ?? $_GET['filter_period'] ?? ''),
        'filter_counterparty' => (string)($_POST['redirect_filter_counterparty'] ?? $_GET['filter_counterparty'] ?? ''),
        'filter_note' => (string)($_POST['redirect_filter_note'] ?? $_GET['filter_note'] ?? ''),
        'filter_status' => (string)($_POST['redirect_filter_status'] ?? $_GET['filter_status'] ?? ''),
    ];
    $merged = array_merge($base, $params);
    $clean = [];
    foreach ($merged as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $clean[$k] = $v;
    }
    redirect_book($clean);
}

function parse_booking_date_input(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $raw, $m)) {
        $d = (int)$m[1];
        $mo = (int)$m[2];
        $y = (int)$m[3];
        if (!checkdate($mo, $d, $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d', $ts);
}

function parse_ch_amount_input(string $raw): float
{
    $raw = trim(str_replace(["'", "'", "\xC2\xA0"], '', $raw));
    $raw = str_replace(',', '.', $raw);
    if ($raw === '' || $raw === '-') {
        return 0.0;
    }
    return (float)$raw;
}

function receipt_public_base_path(): string
{
    return '../../../rkrk/data/ar/belege/';
}

function store_receipt(array $file, string $dataDir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Beleg konnte nicht hochgeladen werden (Upload Error).');
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Ungültiger Upload.');
    }

    $originalName = (string)($file['name'] ?? 'beleg');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Belegformat nicht erlaubt (pdf/png/jpg/jpeg/webp).');
    }

    $targetDir = rtrim($dataDir, '/\\') . '/belege';
    if (!is_dir($targetDir) && !mkdir($concurrentDirectory = $targetDir, 0777, true) && !is_dir($concurrentDirectory)) {
        throw new RuntimeException('Belegverzeichnis konnte nicht erstellt werden.');
    }

    $targetName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $targetName;
    if (!move_uploaded_file($tmp, $targetPath)) {
        throw new RuntimeException('Beleg konnte nicht gespeichert werden.');
    }

    return receipt_public_base_path() . $targetName;
}

/** @return array<int, string> */
function extract_note_receipt_candidates(string $note): array
{
    $candidates = [];

    if (preg_match_all('/https?:\/\/[^\s\)\]]+/iu', $note, $m)) {
        foreach ($m[0] as $raw) {
            $v = trim((string)$raw);
            if ($v !== '') {
                $candidates[] = $v;
            }
        }
    }

    if (preg_match_all('/Belege[\\\\\/][^|]+?\.(?:pdf|png|jpe?g|webp)/iu', $note, $m2)) {
        foreach ($m2[0] as $raw) {
            $v = trim((string)$raw);
            if ($v !== '') {
                $candidates[] = $v;
            }
        }
    }

    $trimmed = trim($note);
    if (preg_match('/\.(pdf|png|jpe?g|webp)$/iu', $trimmed)) {
        $candidates[] = $trimmed;
    }

    $uniq = [];
    foreach ($candidates as $c) {
        if (!in_array($c, $uniq, true)) {
            $uniq[] = $c;
        }
    }
    return $uniq;
}

function sanitize_receipt_name(string $rawName): string
{
    $name = trim(str_replace('\\', '/', $rawName));
    $name = basename($name);
    $name = preg_replace('/[^\w\.\-\s]+/u', '_', $name) ?? 'beleg';
    $name = trim($name, " \t\n\r\0\x0B.");
    return $name !== '' ? $name : 'beleg';
}

/** @param string $targetDir absolute path */
function unique_receipt_path(string $targetDir, string $baseName): string
{
    $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
    $nameNoExt = $ext !== '' ? substr($baseName, 0, -(strlen($ext) + 1)) : $baseName;
    $nameNoExt = $nameNoExt !== '' ? $nameNoExt : 'beleg';
    $candidate = $targetDir . '/' . $nameNoExt . ($ext !== '' ? ('.' . $ext) : '');
    $i = 1;
    while (file_exists($candidate)) {
        $candidate = $targetDir . '/' . $nameNoExt . '_' . $i . ($ext !== '' ? ('.' . $ext) : '');
        $i++;
    }
    return $candidate;
}

/**
 * @return array{copied: bool, target_rel: string|null}
 */
function copy_receipt_candidate_to_data(string $candidate, string $projectRoot, string $dataDir): array
{
    $targetDir = rtrim($dataDir, '/\\') . '/belege';
    if (!is_dir($targetDir) && !mkdir($concurrentDirectory = $targetDir, 0777, true) && !is_dir($concurrentDirectory)) {
        return ['copied' => false, 'target_rel' => null];
    }

    $candidate = trim($candidate, " \t\n\r\0\x0B\"'");
    if ($candidate === '') {
        return ['copied' => false, 'target_rel' => null];
    }

    $isUrl = (bool)preg_match('/^https?:\/\//i', $candidate);
    if ($isUrl) {
        $urlPath = (string)parse_url($candidate, PHP_URL_PATH);
        $base = sanitize_receipt_name($urlPath !== '' ? $urlPath : 'beleg_from_url');
        if (!preg_match('/\.(pdf|png|jpe?g|webp)$/i', $base)) {
            $base .= '.pdf';
        }
        $targetAbs = unique_receipt_path($targetDir, $base);
        $ctx = stream_context_create([
            'http' => ['timeout' => 12],
            'https' => ['timeout' => 12],
        ]);
        $content = @file_get_contents($candidate, false, $ctx);
        if ($content === false || $content === '') {
            return ['copied' => false, 'target_rel' => null];
        }
        if (@file_put_contents($targetAbs, $content) === false) {
            return ['copied' => false, 'target_rel' => null];
        }
        return ['copied' => true, 'target_rel' => receipt_public_base_path() . basename($targetAbs)];
    }

    $normalized = str_replace('\\', '/', $candidate);
    $normalized = ltrim($normalized, '/');
    $base = sanitize_receipt_name($normalized);
    $targetAbs = unique_receipt_path($targetDir, $base);

    $sourceCandidates = [
        $projectRoot . '/' . $normalized,
        $projectRoot . '/public/' . $normalized,
        $projectRoot . '/public/uploads/belege/' . basename($normalized),
        $projectRoot . '/../../../rkrk/data/ar/' . $normalized,
        $projectRoot . '/../../../rkrk/data/ar/belege/' . basename($normalized),
    ];
    if (stripos($normalized, 'Belege/') === 0) {
        $rest = substr($normalized, strlen('Belege/'));
        $sourceCandidates[] = $projectRoot . '/Belege/' . $rest;
        $sourceCandidates[] = $projectRoot . '/../../../rkrk/data/ar/belege/' . $rest;
    }
    if (stripos($normalized, 'belege/') === 0) {
        $rest = substr($normalized, strlen('belege/'));
        $sourceCandidates[] = $projectRoot . '/../../../rkrk/data/ar/belege/' . $rest;
    }

    $sourceAbs = null;
    foreach ($sourceCandidates as $candAbs) {
        if (is_file($candAbs)) {
            $sourceAbs = $candAbs;
            break;
        }
    }
    if ($sourceAbs === null) {
        return ['copied' => false, 'target_rel' => null];
    }

    if (!@copy($sourceAbs, $targetAbs)) {
        return ['copied' => false, 'target_rel' => null];
    }
    return ['copied' => true, 'target_rel' => receipt_public_base_path() . basename($targetAbs)];
}

$accounts = $repo->getAccounts();
$accountMapAll = account_map($accounts, false);
$accountMapActive = account_map($accounts, true);
$categories = $repo->getCategoryMapByCode();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'extract_note_links') {
        try {
            $rows = $store->readList('transactions.json');
            $projectRoot = dirname(__DIR__);
            $updated = 0;
            $copied = 0;
            foreach ($rows as &$row) {
                if (!is_array($row)) {
                    continue;
                }
                $note = trim((string)($row['note'] ?? ''));
                if ($note === '') {
                    continue;
                }
                $existingReceipt = trim((string)($row['receipt_path'] ?? ''));
                if ($existingReceipt !== '') {
                    continue;
                }
                $candidates = extract_note_receipt_candidates($note);
                if (empty($candidates)) {
                    continue;
                }
                foreach ($candidates as $cand) {
                    $result = copy_receipt_candidate_to_data($cand, $projectRoot, $dataDir);
                    if (!$result['copied'] || $result['target_rel'] === null) {
                        continue;
                    }
                    $row['receipt_path'] = $result['target_rel'];
                    $copied++;
                    $updated++;
                    $cleaned = trim(str_replace($cand, '', $note));
                    $cleaned = preg_replace('/\s{2,}/u', ' ', (string)$cleaned) ?? $cleaned;
                    $cleaned = trim($cleaned, " \t\n\r\0\x0B|,;");
                    $row['note'] = $cleaned === '' ? null : $cleaned;
                    $row['updated_at'] = now_iso_book();
                    break;
                }
            }
            unset($row);
            if ($updated > 0) {
                $store->writeList('transactions.json', $rows);
                redirect_kontobuch(['ok' => "Beleg-Links extrahiert: {$copied} Datei(en) nach data/ar/belege übernommen."]);
            }
            redirect_kontobuch(['ok' => 'Keine extrahierbaren Beleg-Links gefunden.']);
        } catch (Throwable $e) {
            redirect_kontobuch(['error' => 'Fehler bei Beleg-Link-Extraktion: ' . $e->getMessage()]);
        }
    }
    if ($action === 'add_booking') {
        try {
            if (empty($accountMapActive)) {
                redirect_kontobuch(['error' => 'Bitte zuerst ein aktives Konto unter "Konti" anlegen.']);
            }

            $rows = $store->readList('transactions.json');
            $date = trim((string)($_POST['booking_date'] ?? ''));
            $counterparty = trim((string)($_POST['counterparty'] ?? ''));
            $categoryCode = trim((string)($_POST['category_code'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));
            $statusRaw = trim((string)($_POST['booking_status'] ?? 'x'));
            $status = in_array($statusRaw, ['n', 'v', 'x'], true) ? $statusRaw : 'x';
            $bookingType = trim((string)($_POST['booking_type'] ?? 'standard'));
            $topAccount = trim((string)($_POST['booking_top_account'] ?? ''));
            $sourceAccount = $topAccount !== '' ? $topAccount : trim((string)($_POST['source_account'] ?? ''));
            $targetAccount = $bookingType === 'transfer'
                ? trim((string)($_POST['target_account'] ?? ''))
                : '';
            $amountSigned = parse_ch_amount_input((string)($_POST['amount'] ?? '0'));
            $targetAmount = (float)($_POST['target_amount'] ?? 0);

            if ($date === '' || $counterparty === '' || $sourceAccount === '') {
                redirect_kontobuch(['error' => 'Datum, Empfänger und Konto (oben oder Quellkonto) sind Pflichtfelder.']);
            }
            if (!isset($accountMapActive[$sourceAccount])) {
                redirect_kontobuch(['error' => 'Quellkonto nicht gefunden oder inaktiv.']);
            }

            $receiptPath = null;
            if (isset($_FILES['receipt'])) {
                $receiptPath = store_receipt($_FILES['receipt'], $dataDir);
            }

            $categoryName = $categories[$categoryCode] ?? null;
            $categoryRaw = $categoryName === null ? ($categoryCode !== '' ? $categoryCode : null) : null;

            if ($bookingType === 'transfer') {
                if ($targetAccount === '' || !isset($accountMapActive[$targetAccount])) {
                    redirect_kontobuch(['error' => 'Bitte ein gültiges aktives Zielkonto für Transfer wählen.']);
                }
                if ($targetAccount === $sourceAccount) {
                    redirect_kontobuch(['error' => 'Quell- und Zielkonto dürfen nicht identisch sein.']);
                }
                $sourceAmount = abs($amountSigned);
                if ($sourceAmount <= 0) {
                    redirect_kontobuch(['error' => 'Betrag (Quelle) muss > 0 sein.']);
                }

                $sourceCurrency = strtoupper((string)($accountMapActive[$sourceAccount]['currency'] ?? 'CHF'));
                $destCurrency = strtoupper((string)($accountMapActive[$targetAccount]['currency'] ?? 'CHF'));
                $effectiveTargetAmount = $targetAmount;
                if ($sourceCurrency === $destCurrency) {
                    if ($effectiveTargetAmount <= 0) {
                        $effectiveTargetAmount = $sourceAmount;
                    }
                } else {
                    if ($effectiveTargetAmount <= 0) {
                        redirect_kontobuch(['error' => 'Bei unterschiedlichen Währungen muss der Zielbetrag angegeben werden.']);
                    }
                }

                $transferId = 'TRF' . date('YmdHis') . random_int(100, 999);
                $base = [
                    'cost_category_code' => $categoryName === null ? null : $categoryCode,
                    'cost_category_name_raw' => $categoryRaw ?? 'Transfer',
                    'source_sheet' => 'Web',
                    'source_row_no' => null,
                    'booking_status' => $status,
                    'receipt_path' => $receiptPath,
                    'transfer_id' => $transferId,
                    'updated_at' => now_iso_book(),
                    'created_at' => now_iso_book(),
                ];

                $rows[] = array_merge($base, [
                    'id' => bin2hex(random_bytes(16)),
                    'external_id' => $transferId . '_OUT',
                    'booking_date' => $date,
                    'counterparty' => $counterparty,
                    'account' => $sourceAccount,
                    'target_account' => $targetAccount,
                    'amount_in' => 0.0,
                    'amount_out' => $sourceAmount,
                    'currency' => $sourceCurrency,
                    'note' => trim(($note !== '' ? $note . ' | ' : '') . 'Transfer zu ' . $targetAccount),
                    'running_balance_imported' => null,
                ]);

                $rows[] = array_merge($base, [
                    'id' => bin2hex(random_bytes(16)),
                    'external_id' => $transferId . '_IN',
                    'booking_date' => $date,
                    'counterparty' => $counterparty,
                    'account' => $targetAccount,
                    'target_account' => $sourceAccount,
                    'amount_in' => $effectiveTargetAmount,
                    'amount_out' => 0.0,
                    'currency' => $destCurrency,
                    'note' => trim(($note !== '' ? $note . ' | ' : '') . 'Transfer von ' . $sourceAccount),
                    'running_balance_imported' => null,
                ]);
            } else {
                $absAmount = abs($amountSigned);
                if ($absAmount <= 0) {
                    redirect_kontobuch(['error' => 'Betrag darf nicht 0 sein (positiv = Ein, negativ = Aus).']);
                }
                $in = $amountSigned > 0 ? $absAmount : 0.0;
                $out = $amountSigned < 0 ? $absAmount : 0.0;
                $rows[] = [
                    'id' => bin2hex(random_bytes(16)),
                    'external_id' => 'WEB' . date('YmdHis') . random_int(100, 999),
                    'booking_date' => $date,
                    'counterparty' => $counterparty,
                    'cost_category_code' => $categoryName === null ? null : $categoryCode,
                    'cost_category_name_raw' => $categoryRaw,
                    'account' => $sourceAccount,
                    'target_account' => $targetAccount === '' ? null : $targetAccount,
                    'amount_in' => $in,
                    'amount_out' => $out,
                    'currency' => strtoupper((string)($accountMapActive[$sourceAccount]['currency'] ?? 'CHF')),
                    'note' => $note === '' ? null : $note,
                    'running_balance_imported' => null,
                    'source_sheet' => 'Web',
                    'source_row_no' => null,
                    'booking_status' => $status,
                    'receipt_path' => $receiptPath,
                    'transfer_id' => null,
                    'updated_at' => now_iso_book(),
                    'created_at' => now_iso_book(),
                ];
            }

            $store->writeList('transactions.json', $rows);
            redirect_kontobuch(['ok' => 'Buchung gespeichert.']);
        } catch (Throwable $e) {
            redirect_kontobuch(['error' => 'Fehler beim Speichern: ' . $e->getMessage()]);
        }
    }
    if ($action === 'save_lines') {
        try {
            $lines = $_POST['lines'] ?? null;
            if (!is_array($lines)) {
                redirect_kontobuch(['error' => 'Keine Zeilen übermittelt.']);
            }

            $rows = $store->readList('transactions.json');
            $deleteSet = [];
            $deleteIdsRaw = $_POST['delete_ids'] ?? [];
            if (is_array($deleteIdsRaw)) {
                foreach ($deleteIdsRaw as $rawId) {
                    $rid = trim((string)$rawId);
                    if ($rid !== '') {
                        $deleteSet[$rid] = true;
                    }
                }
            }
            if (!empty($deleteSet)) {
                $deleteTransferSet = [];
                foreach ($rows as $r) {
                    $rid = trim((string)($r['id'] ?? ''));
                    if ($rid === '' || !isset($deleteSet[$rid])) {
                        continue;
                    }
                    $tid = trim((string)($r['transfer_id'] ?? ''));
                    if ($tid !== '') {
                        $deleteTransferSet[$tid] = true;
                    }
                }
                $rows = array_values(array_filter(
                    $rows,
                    static function (array $r) use ($deleteSet, $deleteTransferSet): bool {
                        $rid = trim((string)($r['id'] ?? ''));
                        if ($rid !== '' && isset($deleteSet[$rid])) {
                            return false;
                        }
                        $tid = trim((string)($r['transfer_id'] ?? ''));
                        if ($tid !== '' && isset($deleteTransferSet[$tid])) {
                            return false;
                        }
                        return true;
                    }
                ));
            }

            $byId = [];
            foreach ($rows as $i => $r) {
                $rid = trim((string)($r['id'] ?? ''));
                if ($rid !== '') {
                    $byId[$rid] = $i;
                }
            }

            foreach ($lines as $lineId => $line) {
                if (!is_array($line)) {
                    continue;
                }
                $id = trim((string)($line['id'] ?? ''));
                if ($id === '' || $id !== (string)$lineId || !isset($byId[$id])) {
                    continue;
                }
                $idx = $byId[$id];
                $orig = $rows[$idx];
                $transferId = trim((string)($orig['transfer_id'] ?? ''));
                $isTransfer = $transferId !== '';

                $status = trim((string)($line['booking_status'] ?? ''));
                if (!in_array($status, ['n', 'v', 'x'], true)) {
                    $status = trim((string)($orig['booking_status'] ?? 'x'));
                    if (!in_array($status, ['n', 'v', 'x'], true)) {
                        $status = 'x';
                    }
                }

                if ($isTransfer) {
                    $rows[$idx]['counterparty'] = trim((string)($line['counterparty'] ?? ''));
                    $rows[$idx]['note'] = ($t = trim((string)($line['note'] ?? ''))) === '' ? null : $t;
                    $rows[$idx]['booking_status'] = $status;
                    unset($rows[$idx]['scope_code']);
                    $catCode = trim((string)($line['cost_category_code'] ?? ''));
                    $catName = $categories[$catCode] ?? null;
                    if ($catName !== null) {
                        $rows[$idx]['cost_category_code'] = $catCode;
                        $rows[$idx]['cost_category_name_raw'] = null;
                    } elseif ($catCode !== '') {
                        $rows[$idx]['cost_category_code'] = null;
                        $rows[$idx]['cost_category_name_raw'] = $catCode;
                    }
                    $rows[$idx]['updated_at'] = now_iso_book();
                    continue;
                }

                $dateIso = parse_booking_date_input((string)($line['booking_date'] ?? ''));
                if ($dateIso === null) {
                    redirect_kontobuch(['error' => 'Ungültiges Datum bei Zeile ' . $id . '.']);
                }

                $accountCode = trim((string)($line['account'] ?? ''));
                if ($accountCode === '' || (!isset($accountMapAll[$accountCode]) && $accountCode !== '-')) {
                    redirect_kontobuch(['error' => 'Ungültiges Konto bei Zeile ' . $id . '.']);
                }
                if ($accountCode !== '-' && !isset($accountMapActive[$accountCode])) {
                    redirect_kontobuch(['error' => 'Konto ist inaktiv: ' . $accountCode]);
                }

                $amountIn = round(parse_ch_amount_input((string)($line['amount_in'] ?? '0')), 2);
                $amountOut = round(parse_ch_amount_input((string)($line['amount_out'] ?? '0')), 2);
                if ($amountIn > 0.0 && $amountOut > 0.0) {
                    redirect_kontobuch(['error' => 'Nur Haben oder Soll, nicht beides: Zeile ' . $id . '.']);
                }
                if ($amountIn <= 0.0 && $amountOut <= 0.0) {
                    redirect_kontobuch(['error' => 'Betrag fehlt bei Zeile ' . $id . '.']);
                }

                $catCode = trim((string)($line['cost_category_code'] ?? ''));
                $catName = $categories[$catCode] ?? null;
                if ($catName !== null) {
                    $rows[$idx]['cost_category_code'] = $catCode;
                    $rows[$idx]['cost_category_name_raw'] = null;
                } elseif ($catCode !== '') {
                    $rows[$idx]['cost_category_code'] = null;
                    $rows[$idx]['cost_category_name_raw'] = $catCode;
                } else {
                    $rows[$idx]['cost_category_code'] = null;
                    $rows[$idx]['cost_category_name_raw'] = null;
                }

                $rows[$idx]['booking_date'] = $dateIso;
                $rows[$idx]['counterparty'] = trim((string)($line['counterparty'] ?? ''));
                if ($rows[$idx]['counterparty'] === '') {
                    redirect_kontobuch(['error' => 'Empfänger fehlt bei Zeile ' . $id . '.']);
                }

                $rows[$idx]['account'] = $accountCode;
                $rows[$idx]['amount_in'] = $amountIn;
                $rows[$idx]['amount_out'] = $amountOut;
                if ($accountCode !== '-') {
                    $rows[$idx]['currency'] = strtoupper((string)($accountMapActive[$accountCode]['currency'] ?? 'CHF'));
                }
                $rows[$idx]['note'] = ($t = trim((string)($line['note'] ?? ''))) === '' ? null : $t;
                $rows[$idx]['booking_status'] = $status;
                unset($rows[$idx]['scope_code']);
                $rows[$idx]['updated_at'] = now_iso_book();
            }

            $store->writeList('transactions.json', $rows);
            redirect_kontobuch(['ok' => 'Kontobuch-Zeilen gespeichert.']);
        } catch (Throwable $e) {
            redirect_kontobuch(['error' => 'Fehler beim Speichern: ' . $e->getMessage()]);
        }
    }
}

$accountFilter = trim((string)($_GET['booking_top_account'] ?? $_GET['account'] ?? ''));
$accountFilterResolved = $accountFilter;
if ($accountFilter !== '' && $accountFilter !== '-') {
    $needle = account_normalize_for_match($accountFilter);
    foreach ($accountMapAll as $code => $acc) {
        if (!is_array($acc)) {
            continue;
        }
        $candidates = [(string)$code];
        foreach (['iban', 'code', 'kuerzel'] as $field) {
            $v = trim((string)($acc[$field] ?? ''));
            if ($v !== '') {
                $candidates[] = $v;
            }
        }
        foreach ($candidates as $cand) {
            if (account_normalize_for_match($cand) === $needle) {
                $accountFilterResolved = (string)$code;
                break 2;
            }
        }
    }
}

$transactions = $repo->getTransactions();

usort(
    $transactions,
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

$balances = [];
$prepared = [];
foreach ($transactions as $row) {
    $account = trim((string)($row['account'] ?? ''));
    if ($account === '') {
        $account = '-';
    }
    $balanceKey = account_normalize_for_match($account);
    if (!isset($balances[$balanceKey])) {
        $balances[$balanceKey] = 0.0;
    }
    $delta = (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
    $balances[$balanceKey] += $delta;
    $row['_konto'] = $account;
    $row['_saldo'] = $balances[$balanceKey];

    if ($accountFilter !== '' && $accountFilter !== '-') {
        if (account_normalize_for_match($account) !== account_normalize_for_match($accountFilterResolved)) {
            continue;
        }
    }

    $prepared[] = $row;
}

usort(
    $prepared,
    static function (array $a, array $b): int {
        $cmp = strcmp((string)($b['booking_date'] ?? ''), (string)($a['booking_date'] ?? ''));
        if ($cmp !== 0) {
            return $cmp;
        }
        $cmp2 = strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        if ($cmp2 !== 0) {
            return $cmp2;
        }
        return strcmp((string)($b['external_id'] ?? ''), (string)($a['external_id'] ?? ''));
    }
);

$formatCurrency = static fn(float $amount): string => number_format($amount, 2, '.', "'");
$formatAmountCell = static function (float $amount) use ($formatCurrency): string {
    return abs($amount) < 0.00001 ? '' : $formatCurrency($amount);
};
$formatDateDe = static function (string $iso): string {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) {
        return $m[3] . '.' . $m[2] . '.' . $m[1];
    }
    return $iso;
};

$bookingTopDefault = ($accountFilterResolved !== '' && $accountFilterResolved !== '-' && isset($accountMapActive[$accountFilterResolved]))
    ? $accountFilterResolved
    : '';
$counterpartyHints = [];
foreach ($prepared as $row) {
    $cp = trim((string)($row['counterparty'] ?? ''));
    if ($cp === '') {
        continue;
    }
    $key = function_exists('mb_strtolower') ? mb_strtolower($cp) : strtolower($cp);
    if (isset($counterpartyHints[$key])) {
        continue;
    }
    $catCode = trim((string)($row['cost_category_code'] ?? ''));
    $isTransfer = trim((string)($row['transfer_id'] ?? '')) !== '' || trim((string)($row['target_account'] ?? '')) !== '';
    $counterpartyHints[$key] = [
        'booking_type' => $isTransfer ? 'transfer' : 'standard',
        'category_code' => $catCode,
    ];
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Klassisches Kontobuch</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .kontobuch-grid table td,
        .kontobuch-grid table th {
            padding: 0.3rem 0.4rem;
            line-height: 1.15;
        }
        .kontobuch-grid tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .kontobuch-grid td.amount-out { background: #fdeaea; }
        .kontobuch-grid td.amount-in { background: #ebf9e8; }
        .kontobuch-grid td.saldo { background: #e9f2ff; }
        .kontobuch-grid td.status-cell {
            text-align: center;
            font-weight: 700;
            cursor: pointer;
            user-select: none;
            overflow: visible;
        }
        .status-badge {
            display: inline-block;
            min-width: 1.7rem;
            padding: 0.1rem 0.3rem;
            border-radius: 0.28rem;
            border: 1px solid #d1d5db;
        }
        .status-n { background: #f3f4f6; }
        .status-v { background: #fff7d6; }
        .status-x { background: #ffe3e3; }
        .row-locked { opacity: 0.7; }
        .row-editing td { outline: 1px solid #c7d2fe; outline-offset: -1px; }
        .row-selected td {
            background: #e8f1ff !important;
            box-shadow: inset 0 0 0 1px #7aa2ff;
        }
        .editable-cell { cursor: text; }
        .cell-display.empty-amount { color: transparent; }
        .edit-actions { display: flex; justify-content: flex-end; margin-top: 0.5rem; }
        .booking-conditional-hidden { display: none !important; }
        .kontobuch-grid #kontobuch-table {
            table-layout: fixed;
            width: 100%;
        }
        .kontobuch-grid #kontobuch-table col {
            min-width: 42px;
        }
        .kontobuch-grid .kb-th {
            position: relative;
            vertical-align: bottom;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kontobuch-grid .kb-th-inner {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 0;
        }
        .kontobuch-grid .kb-th-label {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .kontobuch-grid .kb-th-label:hover {
            text-decoration: underline;
        }
        .kontobuch-grid .kb-th-sort {
            flex: 0 0 auto;
            font-size: 0.65rem;
            opacity: 0.55;
            width: 0.75rem;
        }
        .kontobuch-grid .kb-th.sort-asc .kb-th-sort::after { content: '▲'; }
        .kontobuch-grid .kb-th.sort-desc .kb-th-sort::after { content: '▼'; }
        .kontobuch-grid .kb-resize-handle {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 8px;
            cursor: col-resize;
            user-select: none;
            z-index: 2;
        }
        .kontobuch-grid .kb-resize-handle:hover { background: rgba(59, 130, 246, 0.12); }
    </style>
</head>
<body>
<div class="container">
    <nav class="top-nav">
        <a href="index.php">Dashboard</a>
        <a class="active" href="kontobuch.php">Kontobuch</a>
        <a href="kostenarten.php">Kostenarten</a>
        <a href="budget.php">Budget</a>
        <a href="inventur.php">Inventur</a>
        <a href="accounts.php">Konti</a>
        <a href="cashflow.php">Cashflow</a>
        <a href="reports.php">Reports</a>
        <a href="jahresabschluss.php">Jahresabschluss</a>
    </nav>

    <header class="header">
        <div>
            <h1>Klassisches Kontobuch</h1>
            <p>Buchungen, Transfers zwischen Konti, Status n/v/x (ohne Status in den Daten = x, gesperrt/nicht editierbar) und Beleg-Upload.</p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_book((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_book((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2>Neue Buchung</h2>
        <form method="post" enctype="multipart/form-data" class="form-grid" id="new_booking_form">
            <input type="hidden" name="action" value="add_booking">
            <input type="hidden" name="redirect_booking_top_account" id="redirect_account_new" value="<?= h_book($accountFilterResolved) ?>">
            <label class="full-width">Konto
                <select name="booking_top_account" id="booking_top_account" aria-describedby="booking_top_account_hint">
                    <option value="">Alle Konten in der Liste</option>
                    <?php foreach ($accountMapActive as $code => $acc): ?>
                        <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                        <option value="<?= h_book((string)$code) ?>" data-currency="<?= h_book($cur) ?>" <?= $bookingTopDefault === $code ? 'selected' : '' ?>>
                            <?= h_book((string)$code . ' — ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="booking_top_account_hint" class="muted" style="display:block;margin-top:0.35rem;">
                    Mit gewähltem Konto: nur Buchungen dieses Kontos in der Tabelle unten. Ohne Auswahl: alle Konten. Seite lädt bei Konto-Wechsel neu.
                </small>
            </label>
            <label>Datum
                <input type="date" name="booking_date" id="booking_date_new" required value="<?= h_book(date('Y-m-d')) ?>">
            </label>
            <label>Empfänger
                <input type="text" name="counterparty" id="counterparty_new" required autocomplete="off">
            </label>
            <label>Kostenart
                <select name="category_code" id="category_code_new">
                    <option value="">(frei / keine)</option>
                    <?php foreach ($categories as $code => $name): ?>
                        <option value="<?= h_book((string)$code) ?>"><?= h_book((string)$code . ' - ' . (string)$name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div id="source_account_wrap" class="<?= $bookingTopDefault !== '' ? 'booking-conditional-hidden' : '' ?>">
                <label>Quellkonto
                    <select name="source_account" id="source_account_new" <?= $bookingTopDefault === '' ? 'required' : '' ?>>
                        <option value="">Bitte wählen</option>
                        <?php foreach ($accountMapActive as $code => $acc): ?>
                            <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                            <option value="<?= h_book((string)$code) ?>" data-currency="<?= h_book($cur) ?>" <?= $bookingTopDefault === $code ? 'selected' : '' ?>>
                                <?= h_book((string)$code . ' - ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Buchungstyp
                <select name="booking_type" id="booking_type">
                    <option value="standard">–</option>
                    <option value="transfer">Kontotransfer</option>
                </select>
            </label>
            <label id="target_account_wrap" class="booking-conditional-hidden">Zielkonto
                <select name="target_account" id="target_account_new">
                    <option value="">Bitte wählen</option>
                    <?php foreach ($accountMapActive as $code => $acc): ?>
                        <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                        <option value="<?= h_book((string)$code) ?>" data-currency="<?= h_book($cur) ?>">
                            <?= h_book((string)$code . ' - ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label id="amount_label_wrap"><span id="amount_label_text">Betrag</span>
                <input type="number" name="amount" id="booking_amount" step="any" inputmode="decimal" placeholder="z. B. 100 (Ein) oder -49.5 (Aus)">
            </label>
            <label id="target_amount_wrap" class="booking-conditional-hidden">Zielbetrag (Fremdwährung)
                <input type="number" name="target_amount" id="target_amount_input" step="any" min="0" value="">
            </label>
            <label class="full-width">Bemerkung
                <input type="text" name="note" id="note_new">
            </label>
            <label>Status
                <select name="booking_status" id="booking_status_new">
                    <option value="n">n — offen</option>
                    <option value="v">v — verarbeitet</option>
                    <option value="x" selected>x — gesperrt</option>
                </select>
            </label>
            <label class="full-width">Beleg (Datei)
                <input type="file" name="receipt" accept=".pdf,.png,.jpg,.jpeg,.webp">
            </label>
            <div class="full-width">
                <button type="submit">💾 Buchung speichern</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Kontobuch</h2>
        <form method="post" id="lines-form">
            <input type="hidden" name="action" value="save_lines">
            <input type="hidden" name="redirect_booking_top_account" id="redirect_account_lines" value="<?= h_book($accountFilterResolved) ?>">
            <div class="edit-actions" style="margin-bottom:0.5rem;">
                <span class="muted" style="margin-right:auto;">Bearbeitete Zeilen werden beim Sichern auf Status <b>x</b> (gesperrt) gesetzt. Markierte Zeilen werden gelöscht.</span>
                <button type="submit" title="Sichern (bearbeitete Zeilen → Status x)">💾 Sichern</button>
            </div>
            <div class="table-wrapper kontobuch-grid">
                <table id="kontobuch-table">
                    <colgroup id="kontobuch-colgroup">
                        <col data-col-idx="0" style="width:9%">
                        <col data-col-idx="1" style="width:15%">
                        <col data-col-idx="2" style="width:12%">
                        <col data-col-idx="3" style="width:8%">
                        <col data-col-idx="4" style="width:8%">
                        <col data-col-idx="5" style="width:8%">
                        <col data-col-idx="6" style="width:10%">
                        <col data-col-idx="7" style="width:4%">
                        <col data-col-idx="8" style="width:7%">
                        <col data-col-idx="9" style="width:20%">
                        <col data-col-idx="10" style="width:5%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="kb-th" data-sort-key="date" scope="col">
                            <div class="kb-th-inner">
                                <span class="kb-th-label">Datum</span>
                                <span class="kb-th-sort" aria-hidden="true"></span>
                            </div>
                            <span class="kb-resize-handle" data-col-idx="0" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="counterparty" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Empfänger</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="1" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="category" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Kostenart</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="2" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="account" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Konto</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="3" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="out" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Haben/Aus</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="4" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="in" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Soll/Ein</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="5" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="saldo" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Saldo</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="6" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="ccy" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">W</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="7" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="status" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Status</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="8" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" data-sort-key="note" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Bemerkung / Beleg</span><span class="kb-th-sort" aria-hidden="true"></span></div>
                            <span class="kb-resize-handle" data-col-idx="9" title="Breite ziehen" role="separator"></span>
                        </th>
                        <th class="kb-th" scope="col">
                            <div class="kb-th-inner"><span class="kb-th-label">Löschen</span></div>
                            <span class="kb-resize-handle" data-col-idx="10" title="Breite ziehen" role="separator"></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prepared as $row): ?>
                        <?php
                        $id = (string)($row['id'] ?? '');
                        $bookingDate = (string)($row['booking_date'] ?? '');
                        $createdAt = (string)($row['created_at'] ?? '');
                        $externalId = (string)($row['external_id'] ?? '');
                        $counterparty = (string)($row['counterparty'] ?? '');
                        $code = trim((string)($row['cost_category_code'] ?? ''));
                        $catName = $categories[$code] ?? (string)($row['cost_category_name_raw'] ?? '');
                        $categoryEditable = $code !== '' ? $code : $catName;
                        $account = (string)($row['_konto'] ?? '-');
                        $accountDisplay = account_table_display($account);
                        $amountOut = (float)($row['amount_out'] ?? 0);
                        $amountIn = (float)($row['amount_in'] ?? 0);
                        $balance = (float)($row['_saldo'] ?? 0);
                        $currency = (string)($row['currency'] ?? 'CHF');
                        $note = (string)($row['note'] ?? '');
                        $receipt = trim((string)($row['receipt_path'] ?? ''));
                        $status = trim((string)($row['booking_status'] ?? ''));
                        if (!in_array($status, ['n', 'v', 'x'], true)) {
                            $status = 'x';
                        }
                        ?>
                        <tr class="<?= $status === 'x' ? 'row-locked' : '' ?>"
                            data-row-id="<?= h_book($id) ?>"
                            data-transfer-id="<?= h_book((string)($row['transfer_id'] ?? '')) ?>"
                            data-target-account="<?= h_book((string)($row['target_account'] ?? '')) ?>"
                            data-status="<?= h_book($status) ?>"
                            data-sort-date="<?= h_book($bookingDate) ?>"
                            data-sort-created="<?= h_book($createdAt) ?>"
                            data-sort-external="<?= h_book($externalId) ?>"
                            data-sort-counterparty="<?= h_book($counterparty) ?>"
                            data-sort-category="<?= h_book($catName) ?>"
                            data-sort-account="<?= h_book($account) ?>"
                            data-sort-out="<?= h_book((string)$amountOut) ?>"
                            data-sort-in="<?= h_book((string)$amountIn) ?>"
                            data-sort-saldo="<?= h_book((string)$balance) ?>"
                            data-sort-ccy="<?= h_book($currency) ?>"
                            data-sort-note="<?= h_book($note) ?>"
                            data-sort-status="<?= h_book($status) ?>"
                        >
                            <td class="editable-cell" data-field="booking_date">
                                <span class="cell-display"><?= h_book($formatDateDe($bookingDate)) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][id]" value="<?= h_book($id) ?>">
                                <input type="hidden" name="lines[<?= h_book($id) ?>][booking_date]" value="<?= h_book($bookingDate) ?>">
                            </td>
                            <td class="editable-cell" data-field="counterparty">
                                <span class="cell-display"><?= h_book($counterparty) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][counterparty]" value="<?= h_book($counterparty) ?>">
                            </td>
                            <td class="editable-cell" data-field="cost_category_code">
                                <span class="cell-display"><?= h_book($catName) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][cost_category_code]" value="<?= h_book($categoryEditable) ?>">
                            </td>
                            <td class="editable-cell" data-field="account">
                                <span class="cell-display" title="<?= h_book($account) ?>"><?= h_book($accountDisplay) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][account]" value="<?= h_book($account) ?>">
                            </td>
                            <td class="editable-cell amount-out" data-field="amount_out">
                                <?php $outDisplay = $formatAmountCell($amountOut); ?>
                                <span class="cell-display <?= $outDisplay === '' ? 'empty-amount' : '' ?>"><?= h_book($outDisplay) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][amount_out]" value="<?= h_book((string)$amountOut) ?>">
                            </td>
                            <td class="editable-cell amount-in" data-field="amount_in">
                                <?php $inDisplay = $formatAmountCell($amountIn); ?>
                                <span class="cell-display <?= $inDisplay === '' ? 'empty-amount' : '' ?>"><?= h_book($inDisplay) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][amount_in]" value="<?= h_book((string)$amountIn) ?>">
                            </td>
                            <td class="saldo">
                                <span class="cell-display"><?= h_book($formatCurrency($balance)) ?></span>
                            </td>
                            <td><span class="cell-display"><?= h_book($currency) ?></span></td>
                            <td class="status-cell" data-field="booking_status">
                                <span class="status-badge status-<?= h_book($status) ?>"><?= h_book($status) ?></span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][booking_status]" value="<?= h_book($status) ?>">
                            </td>
                            <td class="editable-cell" data-field="note">
                                <span class="cell-display">
                                    <?= h_book($note) ?>
                                    <?php if ($receipt !== ''): ?>
                                        | <a href="<?= h_book($receipt) ?>" target="_blank" rel="noopener">Beleg</a>
                                    <?php endif; ?>
                                </span>
                                <input type="hidden" name="lines[<?= h_book($id) ?>][note]" value="<?= h_book($note) ?>">
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="delete_ids[]" value="<?= h_book($id) ?>" title="Zeile löschen">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="edit-actions">
                <span class="muted" style="margin-right:auto;">Bearbeitete Zeilen werden beim Sichern auf Status <b>x</b> (gesperrt) gesetzt. Markierte Zeilen werden gelöscht.</span>
                <button type="submit" title="Sichern (bearbeitete Zeilen → Status x)">💾 Sichern</button>
            </div>
        </form>
        <form method="post" style="margin-top:0.5rem;">
            <input type="hidden" name="action" value="extract_note_links">
            <button type="submit" class="btn-secondary">Beleg-Links aus Bemerkungen extrahieren</button>
        </form>
    </section>
</div>
<?php
$categoryOptions = [];
foreach ($categories as $cCode => $cName) {
    $categoryOptions[] = [
        'value' => (string)$cCode,
        'label' => (string)$cCode . ' - ' . (string)$cName,
    ];
}
$accountOptions = [
    ['value' => '-', 'label' => '-'],
];
foreach ($accountMapActive as $aCode => $aAcc) {
    $aCur = strtoupper((string)($aAcc['currency'] ?? 'CHF'));
    $accountOptions[] = [
        'value' => (string)$aCode,
        'label' => (string)$aCode . ' - ' . (string)($aAcc['name'] ?? '') . ' (' . $aCur . ')',
    ];
}
$counterpartySuggestions = [];
$noteSuggestions = [];
foreach ($prepared as $r) {
    $cp = trim((string)($r['counterparty'] ?? ''));
    if ($cp !== '' && !in_array($cp, $counterpartySuggestions, true)) {
        $counterpartySuggestions[] = $cp;
    }
    $nt = trim((string)($r['note'] ?? ''));
    if ($nt !== '' && !in_array($nt, $noteSuggestions, true)) {
        $noteSuggestions[] = $nt;
    }
}
?>
<script>
(() => {
    const table = document.getElementById('kontobuch-table');
    if (!table) return;

    const fieldsEditable = ['booking_date', 'counterparty', 'cost_category_code', 'account', 'amount_out', 'amount_in', 'note'];
    const statusSequence = ['n', 'v', 'x'];
    const categoryOptions = <?= json_encode($categoryOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const accountOptions = <?= json_encode($accountOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const counterpartySuggestions = <?= json_encode($counterpartySuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const noteSuggestions = <?= json_encode($noteSuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const toDisplayDate = (iso) => {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
        return m ? `${m[3]}.${m[2]}.${m[1]}` : iso;
    };
    const toIsoDate = (value) => {
        const m = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/.exec(value.trim());
        if (!m) return value.trim();
        const d = m[1].padStart(2, '0');
        const mo = m[2].padStart(2, '0');
        return `${m[3]}-${mo}-${d}`;
    };
    const formatAmount = (value) => {
        const n = Number(String(value).replace(/'/g, '').replace(',', '.'));
        if (!Number.isFinite(n) || Math.abs(n) < 0.00001) return '';
        return n.toLocaleString('de-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    const shortAccountDisplay = (full) => {
        const s = String(full || '').trim();
        if (!s || s === '-') return s;
        const c = s.replace(/\s+/g, '');
        if (c.length <= 6) return c;
        return c.slice(-6);
    };
    const parseNumeric = (value) => {
        const n = Number(String(value ?? '').replace(/'/g, '').replace(',', '.'));
        return Number.isFinite(n) ? n : 0;
    };
    const normalizeAccount = (value) => {
        const s = String(value || '').trim();
        if (!s || s === '-') return '-';
        return s.replace(/\s+/g, '').toUpperCase();
    };
    const formatSaldo = (value) => Number(value || 0).toLocaleString('de-CH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const recomputeDisplayedBalances = () => {
        const rows = Array.from(table.querySelectorAll('tr[data-row-id]'));
        rows.sort((a, b) => {
            const d = String(a.dataset.sortDate || '').localeCompare(String(b.dataset.sortDate || ''));
            if (d !== 0) return d;
            const c = String(a.dataset.sortCreated || '').localeCompare(String(b.dataset.sortCreated || ''));
            if (c !== 0) return c;
            return String(a.dataset.sortExternal || '').localeCompare(String(b.dataset.sortExternal || ''));
        });

        const balances = new Map();
        rows.forEach((row) => {
            const accInput = row.querySelector('input[name$="[account]"]');
            const inInput = row.querySelector('input[name$="[amount_in]"]');
            const outInput = row.querySelector('input[name$="[amount_out]"]');
            const key = normalizeAccount(accInput?.value || '-');
            const delta = parseNumeric(inInput?.value || 0) - parseNumeric(outInput?.value || 0);
            const next = (balances.get(key) || 0) + delta;
            balances.set(key, next);
            row.dataset.sortSaldo = String(next);
            const saldoDisplay = row.querySelector('td.saldo .cell-display');
            if (saldoDisplay) {
                saldoDisplay.textContent = formatSaldo(next);
            }
        });
    };
    const ensureDatalist = (id, values) => {
        let list = document.getElementById(id);
        if (!list) {
            list = document.createElement('datalist');
            list.id = id;
            document.body.appendChild(list);
        }
        list.innerHTML = '';
        values.forEach((v) => {
            const text = String(v || '').trim();
            if (!text) return;
            const opt = document.createElement('option');
            opt.value = text;
            list.appendChild(opt);
        });
        return list;
    };
    ensureDatalist('kb-counterparty-suggestions', counterpartySuggestions);
    ensureDatalist('kb-note-suggestions', noteSuggestions);

    const getRowStatus = (row) => row.dataset.status || 'x';
    const setRowStatus = (row, status) => {
        row.dataset.status = status;
        row.classList.toggle('row-locked', status === 'x');
        const badge = row.querySelector('.status-badge');
        const input = row.querySelector('input[name$="[booking_status]"]');
        if (badge) {
            badge.textContent = status;
            badge.classList.remove('status-n', 'status-v', 'status-x');
            badge.classList.add(`status-${status}`);
        }
        if (input) input.value = status;
        if (status === 'x') row.classList.remove('row-editing');
        row.dataset.sortStatus = status;
    };

    table.querySelectorAll('tr[data-row-id]').forEach((row) => {
        row.addEventListener('dblclick', (ev) => {
            const cell = ev.target.closest('td');
            if (!cell) return;

            if (cell.dataset.field === 'booking_status') {
                const current = getRowStatus(row);
                const next = statusSequence[(statusSequence.indexOf(current) + 1) % statusSequence.length];
                setRowStatus(row, next);
                return;
            }

            if (getRowStatus(row) === 'x') return;
            if (!fieldsEditable.includes(cell.dataset.field || '')) return;

            row.classList.add('row-editing');
            const display = cell.querySelector('.cell-display');
            const input = cell.querySelector(`input[name$="[${cell.dataset.field}]"]`);
            if (!display || !input) return;

            const useSelect = cell.dataset.field === 'cost_category_code' || cell.dataset.field === 'account';
            const editor = document.createElement(useSelect ? 'select' : 'input');
            if (useSelect) {
                const baseOptions = cell.dataset.field === 'cost_category_code' ? categoryOptions : accountOptions;
                const current = String(input.value || '').trim();
                const opts = [...baseOptions];
                if (current !== '' && !opts.some((o) => o.value === current)) {
                    opts.unshift({ value: current, label: current });
                }
                opts.forEach((o) => {
                    const opt = document.createElement('option');
                    opt.value = String(o.value ?? '');
                    opt.textContent = String(o.label ?? o.value ?? '');
                    editor.appendChild(opt);
                });
                editor.value = current;
            } else {
                editor.type = 'text';
                editor.value = cell.dataset.field === 'account' ? (input.value || '') : (input.value || display.textContent.trim());
                if (cell.dataset.field === 'counterparty') {
                    editor.setAttribute('list', 'kb-counterparty-suggestions');
                } else if (cell.dataset.field === 'note') {
                    editor.setAttribute('list', 'kb-note-suggestions');
                }
            }
            editor.style.width = '100%';
            editor.style.fontSize = '0.88rem';
            editor.style.padding = '0.1rem 0.2rem';
            display.style.display = 'none';
            cell.appendChild(editor);
            editor.focus();
            if (!useSelect && typeof editor.select === 'function') {
                editor.select();
            }

            const saveEditor = () => {
                let raw = String(editor.value || '').trim();
                const tr = cell.closest('tr');
                if (cell.dataset.field === 'booking_date') {
                    raw = toIsoDate(raw);
                    display.textContent = toDisplayDate(raw);
                    if (tr) tr.dataset.sortDate = raw;
                } else if (cell.dataset.field === 'amount_out' || cell.dataset.field === 'amount_in') {
                    input.value = raw === '' ? '0' : raw;
                    const amountText = formatAmount(input.value);
                    display.textContent = amountText;
                    display.classList.toggle('empty-amount', amountText === '');
                    if (tr) {
                        const n = parseFloat(input.value);
                        if (cell.dataset.field === 'amount_out') tr.dataset.sortOut = String(Number.isFinite(n) ? n : 0);
                        if (cell.dataset.field === 'amount_in') tr.dataset.sortIn = String(Number.isFinite(n) ? n : 0);
                    }
                    recomputeDisplayedBalances();
                    editor.remove();
                    display.style.display = '';
                    return;
                } else if (cell.dataset.field === 'cost_category_code') {
                    const selText = editor.selectedOptions?.[0]?.textContent || raw;
                    display.textContent = selText;
                    if (tr) tr.dataset.sortCategory = raw;
                } else if (cell.dataset.field === 'account') {
                    display.textContent = shortAccountDisplay(raw);
                    display.title = raw;
                    if (tr) tr.dataset.sortAccount = raw;
                } else {
                    display.textContent = raw;
                    if (tr && cell.dataset.field === 'counterparty') tr.dataset.sortCounterparty = raw;
                    if (tr && cell.dataset.field === 'note') tr.dataset.sortNote = raw;
                }
                input.value = raw;
                if (['booking_date', 'account'].includes(cell.dataset.field || '')) {
                    recomputeDisplayedBalances();
                }
                editor.remove();
                display.style.display = '';
            };

            editor.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEditor();
                }
                if (e.key === 'Escape') {
                    editor.remove();
                    display.style.display = '';
                }
            });
            if (useSelect) {
                editor.addEventListener('change', saveEditor);
            }
            editor.addEventListener('blur', saveEditor, { once: true });
        });
    });
})();
</script>
<script>
(() => {
    const LS_DATE = 'kontobuch_default_booking_date';
    const dateInput = document.getElementById('booking_date_new');
    if (dateInput) {
        const today = new Date().toISOString().slice(0, 10);
        const saved = localStorage.getItem(LS_DATE);
        if (saved && /^\d{4}-\d{2}-\d{2}$/.test(saved)) {
            dateInput.value = saved;
        } else {
            dateInput.value = today;
            localStorage.setItem(LS_DATE, today);
        }
        dateInput.addEventListener('change', () => {
            localStorage.setItem(LS_DATE, dateInput.value);
        });
    }

    const bookingType = document.getElementById('booking_type');
    const categorySel = document.getElementById('category_code_new');
    const topSel = document.getElementById('booking_top_account');
    const sourceWrap = document.getElementById('source_account_wrap');
    const sourceSel = document.getElementById('source_account_new');
    const targetSel = document.getElementById('target_account_new');
    const noteInput = document.getElementById('note_new');
    const statusSel = document.getElementById('booking_status_new');
    const redirectAccountNew = document.getElementById('redirect_account_new');
    const redirectAccountLines = document.getElementById('redirect_account_lines');
    const linesForm = document.getElementById('lines-form');
    const targetAccountWrap = document.getElementById('target_account_wrap');
    const amountLabel = document.getElementById('amount_label_text');
    const targetAmountWrap = document.getElementById('target_amount_wrap');
    const targetAmountInput = document.getElementById('target_amount_input');
    const amountInput = document.getElementById('booking_amount');

    function getCurrency(sel) {
        const opt = sel && sel.selectedOptions && sel.selectedOptions[0];
        if (!opt) {
            return 'CHF';
        }
        const c = (opt.getAttribute('data-currency') || 'CHF').trim();
        return c.toUpperCase();
    }

    function getSourceCurrency() {
        if (topSel && topSel.value) {
            const opt = topSel.selectedOptions[0];
            if (opt) {
                return (opt.getAttribute('data-currency') || 'CHF').trim().toUpperCase();
            }
        }
        return getCurrency(sourceSel);
    }

    function syncSourceFromTop() {
        if (!topSel || !sourceSel) {
            return;
        }
        const top = topSel.value;
        if (!top) {
            return;
        }
        const ok = Array.from(sourceSel.options).some((o) => o.value === top);
        if (ok) {
            sourceSel.value = top;
        }
    }

    const LS_LAST_BOOKING_TYPE = 'kontobuch_last_booking_type';
    const LS_LAST_TARGET_ACCOUNT = 'kontobuch_last_target_account';
    const counterpartyHints = <?= json_encode($counterpartyHints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function persistBookingPrefs() {
        if (bookingType) {
            localStorage.setItem(LS_LAST_BOOKING_TYPE, bookingType.value);
        }
        if (targetSel) {
            localStorage.setItem(LS_LAST_TARGET_ACCOUNT, targetSel.value || '');
        }
    }

    function updateTopSourceUi() {
        const hasTop = topSel && topSel.value !== '';
        if (sourceWrap) {
            sourceWrap.classList.toggle('booking-conditional-hidden', !!hasTop);
        }
        if (sourceSel) {
            sourceSel.required = !hasTop;
        }
        if (hasTop) {
            syncSourceFromTop();
        }
        const qs = new URLSearchParams(window.location.search);
        const urlAcc = qs.get('booking_top_account') || qs.get('account') || '';
        const acc = (topSel && topSel.value) ? topSel.value : urlAcc;
        if (redirectAccountNew) {
            redirectAccountNew.value = acc;
        }
        if (redirectAccountLines) {
            redirectAccountLines.value = acc;
        }
    }

    function applyFilterAccountFromTopSelection() {
        if (!topSel) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('account');
        const selected = topSel.value || '';
        if (selected === '') {
            url.searchParams.delete('booking_top_account');
        } else {
            url.searchParams.set('booking_top_account', selected);
        }
        window.location.href = url.toString();
    }

    function updateNewBookingForm() {
        updateTopSourceUi();
        const isTransfer = bookingType && bookingType.value === 'transfer';
        if (targetAccountWrap) {
            targetAccountWrap.classList.toggle('booking-conditional-hidden', !isTransfer);
        }
        if (targetSel) {
            targetSel.required = !!isTransfer;
            if (!isTransfer) {
                targetSel.value = '';
            }
        }
        if (amountLabel) {
            amountLabel.textContent = isTransfer ? 'Quellbetrag' : 'Betrag (+ Ein / − Aus)';
        }

        const srcCur = getSourceCurrency();
        const tgtCode = targetSel && targetSel.value ? targetSel.value : '';
        const tgtCur = tgtCode ? getCurrency(targetSel) : srcCur;
        const showFx = !!isTransfer && tgtCode !== '' && srcCur !== tgtCur;
        if (targetAmountWrap) {
            targetAmountWrap.classList.toggle('booking-conditional-hidden', !showFx);
        }
        if (targetAmountInput && !showFx) {
            targetAmountInput.value = '';
        }
    }

    if (bookingType) {
        bookingType.addEventListener('change', persistBookingPrefs);
    }
    if (targetSel) {
        targetSel.addEventListener('change', persistBookingPrefs);
    }
    [bookingType, sourceSel, targetSel, topSel].forEach((el) => {
        if (el) {
            el.addEventListener('change', updateNewBookingForm);
        }
    });
    topSel?.addEventListener('change', applyFilterAccountFromTopSelection);

    const lockEditedLinesForSave = () => {
        const kbTable = document.getElementById('kontobuch-table');
        kbTable?.querySelectorAll('tr.row-editing[data-row-id]').forEach((row) => {
            row.dataset.status = 'x';
            row.dataset.sortStatus = 'x';
            row.classList.add('row-locked');
            const input = row.querySelector('input[name$="[booking_status]"]');
            const badge = row.querySelector('.status-badge');
            if (input) {
                input.value = 'x';
            }
            if (badge) {
                badge.textContent = 'x';
                badge.classList.remove('status-n', 'status-v', 'status-x');
                badge.classList.add('status-x');
            }
        });
    };

    linesForm?.addEventListener('submit', () => {
        lockEditedLinesForSave();
        updateTopSourceUi();
    });

    const counterpartyInput = document.getElementById('counterparty_new');
    const table = document.getElementById('kontobuch-table');
    table?.querySelectorAll('tr[data-row-id]').forEach((row) => {
        row.addEventListener('click', () => {
            table.querySelectorAll('tr.row-selected').forEach((r) => r.classList.remove('row-selected'));
            row.classList.add('row-selected');

            const dateVal = row.querySelector('input[name$="[booking_date]"]')?.value || '';
            const cpVal = row.querySelector('input[name$="[counterparty]"]')?.value || '';
            const catVal = row.querySelector('input[name$="[cost_category_code]"]')?.value || '';
            const accVal = row.querySelector('input[name$="[account]"]')?.value || '';
            const outVal = Number(row.querySelector('input[name$="[amount_out]"]')?.value || '0');
            const inVal = Number(row.querySelector('input[name$="[amount_in]"]')?.value || '0');
            const noteVal = row.querySelector('input[name$="[note]"]')?.value || '';
            const rowStatus = row.querySelector('input[name$="[booking_status]"]')?.value || 'n';
            const transferId = (row.dataset.transferId || '').trim();
            const targetAccount = (row.dataset.targetAccount || '').trim();

            if (dateInput && dateVal) dateInput.value = dateVal;
            if (counterpartyInput) counterpartyInput.value = cpVal;
            if (categorySel) {
                if (Array.from(categorySel.options).some((o) => o.value === catVal)) {
                    categorySel.value = catVal;
                } else if (catVal === '') {
                    categorySel.value = '';
                }
            }
            if (noteInput) noteInput.value = noteVal;
            if (statusSel) statusSel.value = ['n', 'v', 'x'].includes(rowStatus) ? rowStatus : 'x';

            const isTransfer = transferId !== '';
            if (bookingType) {
                bookingType.value = isTransfer ? 'transfer' : 'standard';
            }
            if (topSel && Array.from(topSel.options).some((o) => o.value === accVal)) {
                topSel.value = accVal;
            }
            if (sourceSel && Array.from(sourceSel.options).some((o) => o.value === accVal)) {
                sourceSel.value = accVal;
            }
            if (targetSel) {
                if (isTransfer && targetAccount !== '' && Array.from(targetSel.options).some((o) => o.value === targetAccount)) {
                    targetSel.value = targetAccount;
                } else if (!isTransfer) {
                    targetSel.value = '';
                }
            }
            if (amountInput) {
                const signed = inVal > 0 ? inVal : (outVal > 0 ? -outVal : 0);
                amountInput.value = signed === 0 ? '' : String(signed);
            }
            if (targetAmountInput && !isTransfer) {
                targetAmountInput.value = '';
            }

            updateNewBookingForm();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    let appliedLastBookingOnCounterparty = false;
    let lastAppliedHintKey = '';
    function applyCounterpartyHint() {
        if (!counterpartyInput) {
            return false;
        }
        const key = counterpartyInput.value.trim().toLocaleLowerCase('de-CH');
        if (!key) {
            lastAppliedHintKey = '';
            return false;
        }
        const hint = counterpartyHints[key];
        if (!hint || lastAppliedHintKey === key) {
            return false;
        }
        if (hint.booking_type && bookingType && Array.from(bookingType.options).some((o) => o.value === hint.booking_type)) {
            bookingType.value = hint.booking_type;
        }
        if (hint.category_code && categorySel && Array.from(categorySel.options).some((o) => o.value === hint.category_code)) {
            categorySel.value = hint.category_code;
        }
        updateNewBookingForm();
        lastAppliedHintKey = key;
        return true;
    }
    function applyLastBookingFromStorage() {
        const last = localStorage.getItem(LS_LAST_BOOKING_TYPE);
        if (last !== 'transfer' && last !== 'standard') {
            return;
        }
        if (bookingType) {
            bookingType.value = last;
        }
        if (last === 'transfer' && targetSel) {
            const t = localStorage.getItem(LS_LAST_TARGET_ACCOUNT) || '';
            if (t && Array.from(targetSel.options).some((o) => o.value === t)) {
                targetSel.value = t;
            }
        }
        updateNewBookingForm();
    }
    counterpartyInput?.addEventListener('input', () => {
        if (applyCounterpartyHint()) {
            appliedLastBookingOnCounterparty = true;
            return;
        }
        if (appliedLastBookingOnCounterparty) {
            return;
        }
        if (!counterpartyInput.value.trim()) {
            return;
        }
        appliedLastBookingOnCounterparty = true;
        applyLastBookingFromStorage();
    });

    const newBookingForm = document.getElementById('new_booking_form');
    newBookingForm?.addEventListener('submit', () => {
        persistBookingPrefs();
        updateTopSourceUi();
    });

    updateNewBookingForm();
})();
</script>
</body>
</html>

