<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/DataRepository.php';
require_once __DIR__ . '/../src/YearlyStore.php';
require_once __DIR__ . '/../src/AccountHelpers.php';
require_once __DIR__ . '/rkrk_ar_shell.php';
rkrk_ar_boot();

$dataDir = realpath(__DIR__ . '/../../../rkrk/data/ar') ?: (__DIR__ . '/../../../rkrk/data/ar');
$repo = new DataRepository($dataDir);
$yearly = $repo->yearly();

function h_book(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function kb_ico(string $name): string
{
    if ($name === 'edit') {
        return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    }

    return '';
}

function now_iso_book(): string
{
    return date('Y-m-d\TH:i:s');
}

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
    $out = [];
    if (preg_match_all('/https?:\/\/[^\s\)\]]+/iu', $note, $m)) {
        foreach ($m[0] as $u) {
            $out[] = rtrim((string)$u, '.,;');
        }
    }
    if (preg_match_all('/Belege[\\\\\/][^|]+?\.(?:pdf|png|jpe?g|webp)/iu', $note, $m2)) {
        foreach ($m2[0] as $p) {
            $out[] = (string)$p;
        }
    }
    $trimmed = trim($note);
    if ($trimmed !== '' && preg_match('/\.(?:pdf|png|jpe?g|webp)$/iu', $trimmed) && !in_array($trimmed, $out, true)) {
        $out[] = $trimmed;
    }

    return array_values(array_unique($out));
}

function sanitize_receipt_name(string $rawName): string
{
    $base = basename(str_replace('\\', '/', $rawName));
    $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?? 'beleg';

    return $base !== '' ? $base : 'beleg';
}

function unique_receipt_path(string $targetDir, string $baseName): string
{
    $targetAbs = rtrim($targetDir, '/\\') . '/' . $baseName;
    if (!is_file($targetAbs)) {
        return $targetAbs;
    }
    $pi = pathinfo($baseName);
    $stem = (string)($pi['filename'] ?? 'beleg');
    $ext = isset($pi['extension']) ? '.' . $pi['extension'] : '';
    $n = 2;
    do {
        $targetAbs = rtrim($targetDir, '/\\') . '/' . $stem . '_' . $n . $ext;
        $n++;
    } while (is_file($targetAbs));

    return $targetAbs;
}

/** @return array{copied: bool, target_rel: ?string} */
function copy_receipt_candidate_to_data(string $candidate, string $projectRoot, string $dataDir): array
{
    $targetDir = rtrim($dataDir, '/\\') . '/belege';
    if (!is_dir($targetDir) && !mkdir($concurrentDirectory = $targetDir, 0777, true) && !is_dir($concurrentDirectory)) {
        return ['copied' => false, 'target_rel' => null];
    }
    $normalized = str_replace('\\', '/', trim($candidate));
    if (preg_match('#^https?://#iu', $normalized)) {
        $urlPath = (string)(parse_url($normalized, PHP_URL_PATH) ?? '');
        $base = sanitize_receipt_name($urlPath !== '' ? $urlPath : 'beleg_from_url');
        $ctx = stream_context_create(['http' => ['timeout' => 12], 'https' => ['timeout' => 12]]);
        $bin = @file_get_contents($normalized, false, $ctx);
        if ($bin === false || $bin === '') {
        return ['copied' => false, 'target_rel' => null];
        }
        $targetAbs = unique_receipt_path($targetDir, $base);
        if (@file_put_contents($targetAbs, $bin) === false) {
            return ['copied' => false, 'target_rel' => null];
        }

        return ['copied' => true, 'target_rel' => receipt_public_base_path() . basename($targetAbs)];
    }

    $base = sanitize_receipt_name($normalized);
    $targetAbs = unique_receipt_path($targetDir, $base);
    $sourceCandidates = [];
    if (stripos($normalized, 'Belege/') === 0) {
        $rest = substr($normalized, strlen('Belege/'));
        $sourceCandidates[] = $projectRoot . '/Belege/' . $rest;
    }
    $sourceCandidates[] = $normalized;
    $sourceCandidates[] = $projectRoot . '/' . ltrim($normalized, '/');
    $sourceAbs = null;
    foreach ($sourceCandidates as $cand) {
        if (is_file($cand)) {
            $sourceAbs = $cand;
            break;
        }
    }
    if ($sourceAbs === null || !@copy($sourceAbs, $targetAbs)) {
        return ['copied' => false, 'target_rel' => null];
    }

    return ['copied' => true, 'target_rel' => receipt_public_base_path() . basename($targetAbs)];
}

function kb_status(string $raw): string
{
    $s = strtolower(trim($raw));

    return in_array($s, ['n', 'v', 'x'], true) ? $s : 'x';
}

function kb_row_stamp(array $row): string
{
    return (string)($row['updated_at'] ?? $row['created_at'] ?? '');
}

function kb_post_confirmed(string $flag): bool
{
    return trim((string)($_POST[$flag] ?? '')) === '1';
}

function kb_sticky_cookie_name(): string
{
    return 'ar_kb_datum';
}

function kb_sticky_datum(): string
{
    $today = date('Y-m-d');
    $raw = (string)($_COOKIE[kb_sticky_cookie_name()] ?? '');
    if (preg_match('/^(\d{4}-\d{2}-\d{2})\|(\d{4}-\d{2}-\d{2})$/', $raw, $m) && $m[2] === $today) {
        return $m[1];
    }

    return $today;
}

function kb_set_sticky_datum(string $date): void
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return;
    }
    setcookie(kb_sticky_cookie_name(), $date . '|' . date('Y-m-d'), [
        'expires' => time() + 86400 * 14,
        'path' => '/',
        'samesite' => 'Lax',
    ]);
}

/** @return array<string, string> */
function kb_filters(): array
{
    $isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    $g = static function (string $key, string $alt = ''): string {
        $v = $_GET[$key] ?? ($alt !== '' ? ($_GET[$alt] ?? '') : '');
        if (is_array($v)) {
            $v = end($v);
        }

        return trim((string)$v);
    };
    $p = static function (string $key): string {
        $v = $_POST[$key] ?? '';
        if (is_array($v)) {
            $v = end($v);
        }

        return trim((string)$v);
    };

    $konto = $isPost ? $p('filter_konto') : $g('konto', 'booking_top_account');
    if ($konto === '') {
        $konto = $g('konto', 'booking_top_account');
    }
    $zeitraum = strtolower($isPost ? ($p('zeitraum') !== '' ? $p('zeitraum') : $g('zeitraum', 'filter_period')) : $g('zeitraum', 'filter_period'));
    if ($zeitraum === '') {
        $zeitraum = '3m';
    }
    if (!in_array($zeitraum, ['3m', '6m', '1y', 'all'], true)) {
        $zeitraum = '3m';
    }
    $status = strtolower($isPost ? ($p('filter_status') !== '' ? $p('filter_status') : $g('status', 'filter_status')) : $g('status', 'filter_status'));
    if (in_array($status, ['alle', 'all', '0'], true)) {
        $status = '';
    }
    if ($status === 'offen' || $status === 'n') {
        $status = 'offen';
    }

    return [
        'konto' => $konto,
        'zeitraum' => $zeitraum,
        'jahr' => $isPost ? ($p('jahr') !== '' ? $p('jahr') : $g('jahr', 'filter_year')) : $g('jahr', 'filter_year'),
        'empfaenger' => $isPost ? $p('filter_empfaenger') : $g('empfaenger', 'filter_counterparty'),
        'memo' => $isPost ? $p('filter_memo') : $g('memo', 'filter_note'),
        'status' => $status,
        'edit' => $g('edit'),
        'abstimmen' => ($isPost ? $p('abstimmen') : $g('abstimmen')) === '1' ? '1' : '',
    ];
}

/** @param array<string, scalar|null> $override */
function kb_qs(array $override = []): array
{
    $base = kb_filters();
    $merged = array_merge($base, $override);
    $clean = [];
    foreach ($merged as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $clean[$k] = (string)$v;
    }

    return $clean;
}

/** @param array<string, scalar|null> $params */
function kb_go(array $params = []): void
{
    $clean = kb_qs($params);
    $hash = '';
    if (isset($clean['_hash'])) {
        $hash = '#' . $clean['_hash'];
        unset($clean['_hash']);
    }
    $query = http_build_query($clean);
    header('Location: kontobuch.php' . ($query !== '' ? ('?' . $query) : '') . $hash);
    exit;
}

function kb_can_write(): bool
{
    global $LoggedOn, $SessNme_RegisteredUser;
    if (!empty($LoggedOn)) {
        return true;
    }
    if (isset($SessNme_RegisteredUser) && trim((string)($_SESSION[$SessNme_RegisteredUser] ?? '')) !== '') {
        return true;
    }

    return trim((string)($_SESSION['Benutzername'] ?? '')) !== '';
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function kb_find_index(array $rows, string $id): ?int
{
    if ($id === '') {
        return null;
    }
    foreach ($rows as $i => $row) {
        if (trim((string)($row['id'] ?? '')) === $id) {
            return $i;
        }
    }

    return null;
}

/**
 * @param array<int, array<string, mixed>> $rows
 */
function kb_pair_index(array $rows, array $row, int $selfIdx): ?int
{
    $tid = trim((string)($row['transfer_id'] ?? ''));
    if ($tid === '') {
        return null;
    }
    foreach ($rows as $i => $r) {
        if ($i === $selfIdx) {
            continue;
        }
        if (trim((string)($r['transfer_id'] ?? '')) === $tid) {
            return $i;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $row
 * @param array<string, string> $categories
 * @return array<string, mixed>
 */
function kb_apply_category(array $row, string $categoryCode, array $categories): array
{
    $catName = $categories[$categoryCode] ?? null;
    if ($catName !== null) {
        $row['cost_category_code'] = $categoryCode;
        $row['cost_category_name_raw'] = null;
    } elseif ($categoryCode !== '') {
        $row['cost_category_code'] = null;
        $row['cost_category_name_raw'] = $categoryCode;
    } else {
        $row['cost_category_code'] = null;
        $row['cost_category_name_raw'] = null;
    }

    return $row;
}

$accounts = $repo->getAccounts();
$accountMapAll = account_map($accounts, false);
$accountMapActive = account_map($accounts, true);
$categories = $repo->getCategoryMapByCode();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kb_can_write()) {
        kb_go(['error' => 'Bitte einloggen, um Buchungen zu speichern.']);
    }
    $action = trim((string)($_POST['action'] ?? ''));
        try {
        if ($action === 'extract_note_links') {
            $projectRoot = dirname(__DIR__);
            $copied = 0;
            $updated = $yearly->mapAll(static function (array $row) use ($projectRoot, $dataDir, &$copied): ?array {
                $note = trim((string)($row['note'] ?? ''));
                if ($note === '') {
                    return null;
                }
                $existingReceipt = trim((string)($row['receipt_path'] ?? ''));
                if ($existingReceipt !== '') {
                    return null;
                }
                $candidates = extract_note_receipt_candidates($note);
                if ($candidates === []) {
                    return null;
                }
                foreach ($candidates as $cand) {
                    $result = copy_receipt_candidate_to_data($cand, $projectRoot, $dataDir);
                    if (!$result['copied'] || $result['target_rel'] === null) {
                        continue;
                    }
                    $row['receipt_path'] = $result['target_rel'];
                    $copied++;
                    $cleaned = trim(str_replace($cand, '', $note));
                    $cleaned = preg_replace('/\s{2,}/u', ' ', (string)$cleaned) ?? $cleaned;
                    $cleaned = trim($cleaned, " \t\n\r\0\x0B|,;");
                    $row['note'] = $cleaned === '' ? null : $cleaned;
                    $row['updated_at'] = now_iso_book();

                    return $row;
                }

                return null;
            });
            if ($updated > 0) {
                kb_go(['ok' => "Beleg-Links extrahiert: {$copied} Datei(en) nach data/ar/belege übernommen.", 'edit' => '']);
            }
            kb_go(['ok' => 'Keine extrahierbaren Beleg-Links gefunden.', 'edit' => '']);
        }

        if ($action === 'toggle_status') {
            $id = trim((string)($_POST['id'] ?? ''));
            $row = $yearly->findById($id);
            if ($row === null) {
                kb_go(['error' => 'Buchung nicht gefunden.', 'edit' => '']);
            }
            $current = kb_status((string)($row['booking_status'] ?? 'x'));
            $cycle = ['n' => 'v', 'v' => 'x', 'x' => 'n'];
            $forced = strtolower(trim((string)($_POST['set_status'] ?? '')));
            $inAbst = trim((string)($_POST['abstimmen'] ?? '')) === '1';
            if (in_array($forced, ['n', 'v', 'x'], true)) {
                $next = $forced;
            } elseif ($inAbst && $current === 'n') {
                $next = 'v';
            } elseif ($inAbst && $current === 'v') {
                $next = 'n';
            } else {
                $next = $cycle[$current] ?? 'n';
            }
            if ($current === 'x' && !kb_post_confirmed('confirm_x')) {
                kb_go(['error' => 'Gesperrte Buchung (x) nicht ohne Bestätigung geändert.', 'edit' => '']);
            }
            $expect = (string)($_POST['expect_modified'] ?? '');
            if ($expect !== '' && kb_row_stamp($row) !== $expect) {
                kb_go(['error' => 'Buchung wurde zwischenzeitlich geändert. Bitte neu laden.', 'edit' => '']);
            }
            $now = now_iso_book();
            $row['booking_status'] = $next;
            $row['updated_at'] = $now;
            $yearly->upsert($row);
            $pair = $yearly->findPair($row);
            if ($pair !== null) {
                $pair['booking_status'] = $next;
                $pair['updated_at'] = $now;
                $yearly->upsert($pair);
            }
            kb_go(['ok' => 'Status ' . $next . '.', 'edit' => '', '_hash' => 'r' . $id]);
        }

        if ($action === 'abschliessen') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $now = now_iso_book();
            $n = 0;
            $seen = [];
            foreach ($ids as $rawId) {
                $id = trim((string)$rawId);
                if ($id === '' || isset($seen[$id])) {
                    continue;
                }
                $row = $yearly->findById($id);
                if ($row === null || kb_status((string)($row['booking_status'] ?? '')) !== 'v') {
                    continue;
                }
                $row['booking_status'] = 'x';
                $row['updated_at'] = $now;
                $yearly->upsert($row);
                $seen[$id] = true;
                $n++;
                $pair = $yearly->findPair($row);
                if ($pair !== null) {
                    $pid = trim((string)($pair['id'] ?? ''));
                    $pair['booking_status'] = 'x';
                    $pair['updated_at'] = $now;
                    $yearly->upsert($pair);
                    if ($pid !== '') {
                        $seen[$pid] = true;
                    }
                }
            }
            kb_go(['ok' => $n . ' gemerkte Buchungen (v) auf x gesetzt.', 'abstimmen' => '1', 'edit' => '']);
        }

        if ($action === 'abstimmen_all') {
            $kontoAll = trim((string)($_POST['filter_konto'] ?? $_POST['konto'] ?? ''));
            if ($kontoAll === '' || !isset($accountMapActive[$kontoAll])) {
                kb_go(['error' => 'Bitte zuerst ein Konto wählen.']);
            }
            $needleAll = account_normalize_for_match($kontoAll);
            $now = now_iso_book();
            $n = $yearly->mapAll(static function (array $row) use ($needleAll, $now): ?array {
                $acc = trim((string)($row['account'] ?? ''));
                if ($acc === '') {
                    $acc = '-';
                }
                if (account_normalize_for_match($acc) !== $needleAll) {
                    return null;
                }
                if (kb_status((string)($row['booking_status'] ?? 'x')) === 'x') {
                    return null;
                }
                $row['booking_status'] = 'x';
                $row['updated_at'] = $now;

                return $row;
            });
            kb_go(['ok' => $n . ' Buchungen auf x (abgestimmt) gesetzt.', 'konto' => $kontoAll, 'edit' => '']);
        }

        if ($action === 'delete') {
            $id = trim((string)($_POST['id'] ?? ''));
            $row = $yearly->findById($id);
            if ($row === null) {
                kb_go(['error' => 'Buchung nicht gefunden.', 'edit' => '']);
            }
            $st = kb_status((string)($row['booking_status'] ?? 'x'));
            $tid = trim((string)($row['transfer_id'] ?? ''));
            if ($st === 'x' && !kb_post_confirmed('confirm_x')) {
                kb_go(['error' => 'Gesperrte Buchung (x) nicht ohne Bestätigung gelöscht.', 'edit' => $id]);
            }
            if ($tid !== '' && !kb_post_confirmed('confirm_transfer')) {
                kb_go(['error' => 'Transfer nicht ohne Bestätigung gelöscht — Gegenkonto würde mitlaufen.', 'edit' => $id]);
            }
            $expect = (string)($_POST['expect_modified'] ?? '');
            if ($expect !== '' && kb_row_stamp($row) !== $expect) {
                kb_go(['error' => 'Buchung wurde zwischenzeitlich geändert. Bitte neu laden.', 'edit' => '']);
            }
            $deleteIds = [$id];
            if ($tid !== '') {
                $pair = $yearly->findPair($row);
                if ($pair !== null) {
                    $pid = trim((string)($pair['id'] ?? ''));
                    if ($pid !== '') {
                        $deleteIds[] = $pid;
                    }
                }
            }
            $yearly->deleteByIds($deleteIds);
            kb_go(['ok' => $tid !== '' ? 'Transfer gelöscht.' : 'Buchung gelöscht.', 'edit' => '']);
        }

        if ($action === 'add_booking' || $action === 'save_booking') {
            $date = parse_booking_date_input((string)($_POST['datum'] ?? $_POST['booking_date'] ?? ''));
            $konto = trim((string)($_POST['konto'] ?? ''));
            $type = trim((string)($_POST['booking_type'] ?? 'standard'));
            $betrag = parse_ch_amount_input((string)($_POST['betrag'] ?? $_POST['amount'] ?? '0'));
            $emp = trim((string)($_POST['empfaenger'] ?? $_POST['counterparty'] ?? ''));
            $memo = trim((string)($_POST['memo'] ?? $_POST['note'] ?? ''));
            $categoryCode = trim((string)($_POST['category_code'] ?? ''));
            $ziel = trim((string)($_POST['ziel_konto'] ?? $_POST['target_account'] ?? ''));
            $targetAmount = parse_ch_amount_input((string)($_POST['target_amount'] ?? '0'));
            $postedId = trim((string)($_POST['id'] ?? ''));
            $existing = $action === 'save_booking' ? $yearly->findById($postedId) : null;
            $status = $existing !== null
                ? kb_status((string)($existing['booking_status'] ?? 'n'))
                : 'n';
            $postedStatus = strtolower(trim((string)($_POST['booking_status'] ?? '')));
            if (in_array($postedStatus, ['n', 'v', 'x'], true)) {
                $status = $postedStatus;
            }

            if ($action === 'save_booking' && $existing === null) {
                kb_go(['error' => 'Buchung nicht gefunden — nichts geändert.', 'edit' => '']);
            }
            if ($date === null || $konto === '' || ($existing === null && abs($betrag) < 0.00001)) {
                kb_go(['error' => 'Datum, Konto und Betrag sind Pflicht.', 'konto' => $konto, 'edit' => $postedId]);
            }
            if (!isset($accountMapActive[$konto])) {
                kb_go(['error' => 'Konto nicht gefunden oder inaktiv.', 'konto' => $konto, 'edit' => $postedId]);
            }
            if ($existing !== null && abs($betrag) < 0.00001) {
                kb_go(['error' => 'Betrag darf nicht 0 sein.', 'konto' => $konto, 'edit' => $postedId]);
            }
            if ($existing !== null && kb_status((string)($existing['booking_status'] ?? 'x')) === 'x' && !kb_post_confirmed('confirm_x')) {
                kb_go(['error' => 'Gesperrte Buchung (x) nicht ohne Bestätigung geändert.', 'konto' => $konto, 'edit' => $postedId]);
            }
            $isExistingTransfer = $existing !== null && trim((string)($existing['transfer_id'] ?? '')) !== '';
            $willTransfer = $type === 'transfer' || $isExistingTransfer;
            if ($willTransfer && !kb_post_confirmed('confirm_transfer')) {
                kb_go(['error' => 'Transfer nicht ohne Bestätigung gespeichert — Gegenkonto-Saldo würde mitlaufen.', 'konto' => $konto, 'edit' => $postedId]);
            }
            if ($existing !== null) {
                $expect = (string)($_POST['expect_modified'] ?? '');
                if ($expect !== '' && kb_row_stamp($existing) !== $expect) {
                    kb_go(['error' => 'Buchung wurde zwischenzeitlich geändert. Bitte neu laden.', 'konto' => $konto, 'edit' => $postedId]);
                }
            }

            $receiptPath = isset($_FILES['beleg']) ? store_receipt($_FILES['beleg'], $dataDir) : null;
            $now = now_iso_book();
            $sourceCurrency = strtoupper((string)($accountMapActive[$konto]['currency'] ?? 'CHF'));

            if ($existing !== null) {
                if ($isExistingTransfer) {
                    $pair = $yearly->findPair($existing);
                    if ($ziel === '' && $pair !== null) {
                        $ziel = trim((string)($pair['account'] ?? ''));
                    }
                    if ($ziel === '' || $ziel === $konto || !isset($accountMapActive[$ziel])) {
                        kb_go(['error' => 'Bitte ein anderes gültiges Zielkonto wählen.', 'konto' => $konto, 'edit' => $postedId]);
                    }
                    $destCurrency = strtoupper((string)($accountMapActive[$ziel]['currency'] ?? 'CHF'));
                    $sourceAmount = abs($betrag);
                    $effectiveTarget = $targetAmount;
                    if ($sourceCurrency === $destCurrency) {
                        if ($effectiveTarget <= 0) {
                            $effectiveTarget = $sourceAmount;
                        }
                    } elseif ($effectiveTarget <= 0) {
                        kb_go(['error' => 'Bei unterschiedlichen Währungen muss der Zielbetrag angegeben werden.', 'konto' => $konto, 'edit' => $postedId]);
                    }
                    $tid = trim((string)($existing['transfer_id'] ?? ''));
                    $srcRow = $existing;
                    $dstRow = $pair;
                    $srcIsOut = (float)($existing['amount_out'] ?? 0) > 0 || (float)($existing['amount_in'] ?? 0) <= 0;
                    if (!$srcIsOut && $pair !== null) {
                        $srcRow = $pair;
                        $dstRow = $existing;
                    }
                    $apply = static function (array $row) use ($date, $emp, $memo, $status, $categoryCode, $categories, $receiptPath, $now, $tid): array {
                        $row['booking_date'] = $date;
                        $row['counterparty'] = $emp !== '' ? $emp : 'Umbuchung';
                        $row['note'] = $memo === '' ? null : $memo;
                        $row['booking_status'] = $status;
                        $row['transfer_id'] = $tid;
                        $row['updated_at'] = $now;
                        if ($receiptPath !== null) {
                            $row['receipt_path'] = $receiptPath;
                        }
                        $row = kb_apply_category($row, $categoryCode, $categories);

                        return $row;
                    };
                    $srcRow = $apply($srcRow);
                    $srcRow['account'] = $konto;
                    $srcRow['target_account'] = $ziel;
                    $srcRow['amount_out'] = $sourceAmount;
                    $srcRow['amount_in'] = 0.0;
                    $srcRow['currency'] = $sourceCurrency;
                    $srcRow['note'] = trim(($memo !== '' ? $memo . ' | ' : '') . 'Transfer zu ' . $ziel);
                    if ($dstRow === null) {
                        $dstRow = [
                            'id' => bin2hex(random_bytes(16)),
                            'external_id' => $tid . '_IN',
                            'created_at' => $now,
                            'source_sheet' => 'Web',
                            'source_row_no' => null,
                            'running_balance_imported' => null,
                        ];
                    }
                    $dstRow = $apply($dstRow);
                    $dstRow['account'] = $ziel;
                    $dstRow['target_account'] = $konto;
                    $dstRow['amount_in'] = $effectiveTarget;
                    $dstRow['amount_out'] = 0.0;
                    $dstRow['currency'] = $destCurrency;
                    $dstRow['note'] = trim(($memo !== '' ? $memo . ' | ' : '') . 'Transfer von ' . $konto);
                    $yearly->upsert($srcRow);
                    $yearly->upsert($dstRow);
                    kb_set_sticky_datum($date);
                    kb_go(['ok' => 'Transfer geändert.', 'konto' => $konto, 'edit' => '', '_hash' => 'r' . $postedId]);
                }

                $abs = abs($betrag);
                $row = $existing;
                $row['booking_date'] = $date;
                $row['account'] = $konto;
                $row['counterparty'] = $emp;
                $row['note'] = $memo === '' ? null : $memo;
                $row['booking_status'] = $status;
                $row['amount_in'] = $betrag > 0 ? $abs : 0.0;
                $row['amount_out'] = $betrag < 0 ? $abs : 0.0;
                $row['currency'] = $sourceCurrency;
                $row['updated_at'] = $now;
                if ($receiptPath !== null) {
                    $row['receipt_path'] = $receiptPath;
                }
                $row = kb_apply_category($row, $categoryCode, $categories);
                if ($emp === '') {
                    kb_go(['error' => 'Empfänger ist Pflicht.', 'konto' => $konto, 'edit' => $postedId]);
                }
                $yearly->upsert($row);
                kb_set_sticky_datum($date);
                kb_go(['ok' => 'Buchung geändert.', 'konto' => $konto, 'edit' => '', '_hash' => 'r' . $postedId]);
            }

            if ($type === 'transfer') {
                if ($ziel === '' || $ziel === $konto || !isset($accountMapActive[$ziel])) {
                    kb_go(['error' => 'Bitte ein gültiges aktives Zielkonto wählen.', 'konto' => $konto]);
                }
                $sourceAmount = abs($betrag);
                if ($sourceAmount <= 0) {
                    kb_go(['error' => 'Betrag muss ungleich 0 sein.', 'konto' => $konto]);
                }
                $destCurrency = strtoupper((string)($accountMapActive[$ziel]['currency'] ?? 'CHF'));
                $effectiveTarget = $targetAmount;
                if ($sourceCurrency === $destCurrency) {
                    if ($effectiveTarget <= 0) {
                        $effectiveTarget = $sourceAmount;
                    }
                } elseif ($effectiveTarget <= 0) {
                    kb_go(['error' => 'Bei unterschiedlichen Währungen muss der Zielbetrag angegeben werden.', 'konto' => $konto]);
                }
                $transferId = 'TRF' . date('YmdHis') . random_int(100, 999);
                $base = [
                    'cost_category_code' => ($categories[$categoryCode] ?? null) !== null ? $categoryCode : null,
                    'cost_category_name_raw' => ($categories[$categoryCode] ?? null) !== null ? null : ($categoryCode !== '' ? $categoryCode : 'Transfer'),
                    'source_sheet' => 'Web',
                    'source_row_no' => null,
                    'booking_status' => $status,
                    'receipt_path' => $receiptPath,
                    'transfer_id' => $transferId,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'running_balance_imported' => null,
                ];
                $yearly->upsert(array_merge($base, [
                    'id' => bin2hex(random_bytes(16)),
                    'external_id' => $transferId . '_OUT',
                    'booking_date' => $date,
                    'counterparty' => $emp !== '' ? $emp : 'Umbuchung',
                    'account' => $konto,
                    'target_account' => $ziel,
                    'amount_in' => 0.0,
                    'amount_out' => $sourceAmount,
                    'currency' => $sourceCurrency,
                    'note' => trim(($memo !== '' ? $memo . ' | ' : '') . 'Transfer zu ' . $ziel),
                ]));
                $yearly->upsert(array_merge($base, [
                    'id' => bin2hex(random_bytes(16)),
                    'external_id' => $transferId . '_IN',
                    'booking_date' => $date,
                    'counterparty' => $emp !== '' ? $emp : 'Umbuchung',
                    'account' => $ziel,
                    'target_account' => $konto,
                    'amount_in' => $effectiveTarget,
                    'amount_out' => 0.0,
                    'currency' => $destCurrency,
                    'note' => trim(($memo !== '' ? $memo . ' | ' : '') . 'Transfer von ' . $konto),
                ]));
                kb_set_sticky_datum($date);
                kb_go(['ok' => 'Transfer gespeichert.', 'konto' => $konto, 'edit' => '']);
            }

            $abs = abs($betrag);
            if ($abs <= 0) {
                kb_go(['error' => 'Betrag darf nicht 0 sein (positiv = Ein, negativ = Aus).', 'konto' => $konto]);
            }
            if ($emp === '') {
                kb_go(['error' => 'Empfänger ist Pflicht.', 'konto' => $konto]);
            }
            $newId = bin2hex(random_bytes(16));
            $row = [
                'id' => $newId,
                'external_id' => 'WEB' . date('YmdHis') . random_int(100, 999),
                'booking_date' => $date,
                'counterparty' => $emp,
                'account' => $konto,
                'target_account' => null,
                'amount_in' => $betrag > 0 ? $abs : 0.0,
                'amount_out' => $betrag < 0 ? $abs : 0.0,
                'currency' => $sourceCurrency,
                'note' => $memo === '' ? null : $memo,
                'running_balance_imported' => null,
                'source_sheet' => 'Web',
                'source_row_no' => null,
                'booking_status' => $status,
                'receipt_path' => $receiptPath,
                'transfer_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $row = kb_apply_category($row, $categoryCode, $categories);
            $yearly->upsert($row);
            kb_set_sticky_datum($date);
            kb_go(['ok' => 'Buchung gespeichert.', 'konto' => $konto, 'edit' => '', '_hash' => 'r' . $newId]);
        }

        kb_go(['error' => 'Unbekannte Aktion.']);
        } catch (Throwable $e) {
        kb_go(['error' => 'Fehler: ' . $e->getMessage()]);
    }
}

$filters = kb_filters();
$accountFilter = $filters['konto'];
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

$periodFilter = $filters['zeitraum'];
$jahrFilter = (int)$filters['jahr'];
if ($jahrFilter < 1990 || $jahrFilter > 2100) {
    $jahrFilter = 0;
}
$from = null;
$to = null;
if ($periodFilter === '3m') {
    $from = date('Y-m-d', strtotime('-3 months'));
} elseif ($periodFilter === '6m') {
    $from = date('Y-m-d', strtotime('-6 months'));
} elseif ($periodFilter === '1y') {
    $from = date('Y-m-d', strtotime('-1 year'));
}

$loadFrom = $from;
$loadTo = $to;
if ($jahrFilter > 0) {
    $yearStart = sprintf('%04d-01-01', $jahrFilter);
    $yearEnd = sprintf('%04d-12-31', $jahrFilter);
    $loadFrom = ($loadFrom === null || $loadFrom < $yearStart) ? $yearStart : $loadFrom;
    $loadTo = ($loadTo === null || $loadTo > $yearEnd) ? $yearEnd : $loadTo;
}

$balances = ($loadFrom !== null) ? $yearly->accountDeltasBefore($loadFrom) : [];
$transactions = ($loadFrom !== null && $loadTo !== null && $loadFrom > $loadTo)
    ? []
    : $yearly->readRange($loadFrom, $loadTo);
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

$allPrepared = [];
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
    $row['_delta'] = $delta;
    $allPrepared[] = $row;
}

$accountNeedle = ($accountFilter !== '' && $accountFilter !== '-')
    ? account_normalize_for_match($accountFilterResolved)
    : '';

$prepared = [];
$nOffen = 0;
$sumOffen = 0.0;
$nV = 0;
foreach ($allPrepared as $row) {
    $account = (string)($row['_konto'] ?? '');
    if ($accountNeedle !== '' && account_normalize_for_match($account) !== $accountNeedle) {
            continue;
        }
    $d = (string)($row['booking_date'] ?? '');
    if ($jahrFilter > 0 && substr($d, 0, 4) !== (string)$jahrFilter) {
        continue;
    }
    if ($from !== null && $d < $from) {
        continue;
    }
    if ($to !== null && $periodFilter !== 'all' && $d > $to) {
        continue;
    }
    $emp = (string)($row['counterparty'] ?? '');
    if ($filters['empfaenger'] !== '' && stripos($emp, $filters['empfaenger']) === false) {
        continue;
    }
    $note = (string)($row['note'] ?? '');
    if ($filters['memo'] !== '' && stripos($note, $filters['memo']) === false) {
        continue;
    }
    $st = kb_status((string)($row['booking_status'] ?? 'x'));
    if ($filters['status'] === 'offen' && $st === 'x') {
        continue;
    }
    if ($st !== 'x') {
        $nOffen++;
        $sumOffen += (float)($row['_delta'] ?? 0);
        if ($st === 'v') {
            $nV++;
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

$openingSaldo = null;
$closingSaldo = null;
$lifeSums = ($accountNeedle !== '') ? ($yearly->accountStatusSums()[$accountNeedle] ?? null) : null;
if ($accountNeedle !== '') {
    $closingSaldo = $lifeSums !== null
        ? (float)($lifeSums['all'] ?? 0)
        : (float)($balances[$accountNeedle] ?? 0.0);
    if ($prepared !== []) {
        $oldest = $prepared[count($prepared) - 1];
        $openingSaldo = (float)($oldest['_saldo'] ?? 0) - (float)($oldest['_delta'] ?? 0);
    } else {
        $openingSaldo = $closingSaldo;
    }
}

$abstMode = $filters['abstimmen'] === '1';
$ausgangSaldo = null;
$progressSaldo = null;
$endSaldo = null;
$vIds = [];
if ($accountNeedle !== '') {
    if ($lifeSums !== null) {
        $ausgangSaldo = (float)($lifeSums['x'] ?? 0);
        $progressSaldo = $ausgangSaldo + (float)($lifeSums['v'] ?? 0);
        $endSaldo = (float)($lifeSums['all'] ?? 0);
    } else {
        $ausgangSaldo = 0.0;
        $progressSaldo = 0.0;
        $endSaldo = $closingSaldo;
    }
    if ($abstMode) {
        $vIds = $yearly->vIdsForAccount($accountNeedle);
    }
} else {
    foreach ($prepared as $row) {
        if (kb_status((string)($row['booking_status'] ?? '')) !== 'v') {
            continue;
        }
        $vid = trim((string)($row['id'] ?? ''));
        if ($vid !== '') {
            $vIds[] = $vid;
        }
    }
}

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
$showKontoCol = ($bookingTopDefault === '');

$editId = $filters['edit'];
$editRow = null;
$editIdx = null;
if ($editId !== '') {
    foreach ($allPrepared as $i => $r) {
        if (trim((string)($r['id'] ?? '')) === $editId) {
            $editRow = $r;
            $editIdx = $i;
            break;
        }
    }
    if ($editRow === null) {
        $found = $yearly->findById($editId);
        if ($found !== null) {
            $acc = trim((string)($found['account'] ?? ''));
            $found['_konto'] = $acc === '' ? '-' : $acc;
            $found['_delta'] = (float)($found['amount_in'] ?? 0) - (float)($found['amount_out'] ?? 0);
            $editRow = $found;
        } else {
            $editId = '';
        }
    }
}

$editType = 'standard';
$editZiel = '';
$fDatum = kb_sticky_datum();
$fKonto = $bookingTopDefault;
$fEmp = '';
$fMemo = '';
$fBetrag = '';
$fPlan = '';
$fTargetAmt = '';
$fStatus = 'n';
if ($editRow !== null) {
    $editType = trim((string)($editRow['transfer_id'] ?? '')) !== '' ? 'transfer' : 'standard';
    $fDatum = (string)($editRow['booking_date'] ?? $fDatum);
    $fKonto = (string)($editRow['_konto'] ?? $editRow['account'] ?? $fKonto);
    $fEmp = (string)($editRow['counterparty'] ?? '');
    $fMemo = (string)($editRow['note'] ?? '');
    if ($editType === 'transfer') {
        $fMemo = trim((string)preg_replace('/\s*\|\s*Transfer (zu|von) .+$/u', '', $fMemo));
    }
    $signed = (float)($editRow['amount_in'] ?? 0) - (float)($editRow['amount_out'] ?? 0);
    $fBetrag = abs($signed) < 0.00001 ? '' : (string)$signed;
    $fPlan = trim((string)($editRow['cost_category_code'] ?? ''));
    $fStatus = kb_status((string)($editRow['booking_status'] ?? 'n'));
    $editZiel = trim((string)($editRow['target_account'] ?? ''));
    if ($editZiel === '') {
        $pair = $yearly->findPair($editRow);
        if ($pair !== null) {
            $editZiel = trim((string)($pair['account'] ?? ''));
            $fTargetAmt = (string)((float)($pair['amount_in'] ?? 0) + (float)($pair['amount_out'] ?? 0));
        } elseif ($editIdx !== null) {
            $pidx = kb_pair_index($allPrepared, $editRow, $editIdx);
            if ($pidx !== null) {
                $editZiel = trim((string)($allPrepared[$pidx]['account'] ?? ''));
                $fTargetAmt = (string)((float)($allPrepared[$pidx]['amount_in'] ?? 0) + (float)($allPrepared[$pidx]['amount_out'] ?? 0));
            }
        }
    }
}

$counterpartyHints = [];
$empNames = [];
foreach ($allPrepared as $row) {
    $cp = trim((string)($row['counterparty'] ?? ''));
    if ($cp === '') {
        continue;
    }
    if (!in_array($cp, $empNames, true)) {
        $empNames[] = $cp;
    }
    $key = function_exists('mb_strtolower') ? mb_strtolower($cp) : strtolower($cp);
    $catCode = trim((string)($row['cost_category_code'] ?? ''));
    $isTransfer = trim((string)($row['transfer_id'] ?? '')) !== '';
    $counterpartyHints[$key] = [
        'booking_type' => $isTransfer ? 'transfer' : 'standard',
        'category_code' => $catCode,
    ];
}

$availableYears = $yearly->availableYears();

$kbForce = [];
if ($filters['empfaenger'] !== '') {
    $kbForce[] = 'f-emp';
}
if ($filters['memo'] !== '') {
    $kbForce[] = 'f-memo';
}
if ($editRow !== null) {
    if (trim((string)($editRow['cost_category_code'] ?? '')) !== ''
        || trim((string)($editRow['cost_category_name_raw'] ?? '')) !== '') {
        $kbForce[] = 'n-kat';
    }
    if (trim((string)($editRow['note'] ?? '')) !== '') {
        $kbForce[] = 'n-memo';
    }
    if (trim((string)($editRow['receipt_path'] ?? '')) !== '') {
        $kbForce[] = 'n-beleg';
    }
    if ($editType === 'transfer') {
        $kbForce[] = 'n-typ';
    }
}
$kbAnzeigeUser = trim((string)($_SESSION['Benutzername'] ?? 'anon'));
if ($kbAnzeigeUser === '') {
    $kbAnzeigeUser = 'anon';
}

rkrk_ar_emit_chrome('styles.css');
?>
    <style>
    .kb-hidden { display: none !important; }
    .kb-filter, .kb-filter-main {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem 0.8rem;
        align-items: end;
        margin-bottom: 1rem;
    }
    .kb-book {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
        gap: 0.65rem 0.8rem;
        align-items: end;
    }
    .kb-filter label, .kb-book label {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        min-width: 8.5rem;
        font-size: 0.9rem;
    }
    .kb-filter input, .kb-filter select, .kb-book input, .kb-book select {
        width: 100%;
        min-width: 0;
        border: 1px solid #d1d5db;
        border-radius: 0.4rem;
        padding: 0.4rem 0.55rem;
        font-size: 0.9rem;
    }
    .icon-link .ico, .icon-btn .ico { width: 0.95rem; height: 0.95rem; display: block; }
    .filter-pills { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; align-self: end; }
    .filter-pill {
        display: inline-flex;
        align-items: center;
        background: #fff;
        color: #475569;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        padding: 0.28rem 0.78rem;
        font-size: 0.82rem;
            font-weight: 700;
        text-decoration: none;
            cursor: pointer;
    }
    .filter-pill.is-active { background: #e8f0e4; border-color: #4d6b3e; color: #3f5a32; }
    .kb-list-head { display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap; }
    .kb-editing { outline: 2px solid #c7d2fe; }
    .ka-row-open { font-weight: 600; }
    .receipt-cell { max-width: 22rem; }
    .receipt-cell a { margin-left: 0.25rem; }
    button.kb-abst {
        min-width: 1.8rem;
        font-weight: 700;
            border-radius: 0.28rem;
            border: 1px solid #d1d5db;
        cursor: pointer;
        padding: 0.1rem 0.35rem;
    }
    button.kb-abst.is-n { background: #f3f4f6; }
    button.kb-abst.is-v { background: #fff7d6; }
    button.kb-abst.is-x { background: #ffe3e3; }
    .muted { color: #6b7280; }
    .nowrap { white-space: nowrap; }
    #kontobuch-table th.num,
    #kontobuch-table td.amount-out,
    #kontobuch-table td.amount-in,
    #kontobuch-table td.saldo {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .kb-abstimmen { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .kb-abstimmen form { display: inline; margin: 0; }
    .kb-abstimmen-link,
    .kb-abstimmen button {
        background: #d97706;
        color: #fff;
        border: 0;
        border-radius: 0.4rem;
        padding: 0.4rem 0.85rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-size: 0.88rem;
    }
    .kb-abstimmen button:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .kb-abstimmen-all { background: #b45309; }
    .ka-abst-hint { margin: 0.35rem 0 1rem; }
    .kb-abst-kpis .card:nth-child(1) {
        background: #dbeafe;
        box-shadow: inset 4px 0 0 #3b82f6;
    }
    .kb-abst-kpis .card:nth-child(2) {
        background: #ffedd5;
        box-shadow: inset 4px 0 0 #f97316;
    }
    .kb-abst-kpis .card:nth-child(3) {
        background: #d1fae5;
        box-shadow: inset 4px 0 0 #10b981;
    }
    .kb-anzeige-off { display: none !important; }
    .kb-anzeige { position: relative; min-width: auto; align-self: end; }
    .kb-anzeige > summary {
        list-style: none;
            cursor: pointer;
        background: #fff;
        border: 1px solid #d1d5db;
        color: #475569;
        border-radius: 999px;
        padding: 0.28rem 0.85rem;
        font-weight: 700;
        font-size: 0.82rem;
    }
    .kb-anzeige > summary::-webkit-details-marker { display: none; }
    .kb-anzeige-list {
            position: absolute;
            right: 0;
        z-index: 30;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.75rem 0.85rem 0.9rem;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        min-width: 18rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin-top: 0.35rem;
    }
    .kb-anzeige-list p {
        margin: 0.45rem 0 0.1rem;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #94a3b8;
        font-weight: 700;
    }
    .kb-anzeige-list p:first-child { margin-top: 0; }
    .kb-anzeige-list .filter-pills { align-self: stretch; padding-bottom: 0; }
    .kb-anzeige-list label.filter-pill {
        display: inline-flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 0;
        min-width: 0;
        margin: 0;
        background: #fff;
        color: #475569;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        padding: 0.28rem 0.78rem;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
            user-select: none;
    }
    .kb-anzeige-list label.filter-pill input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .kb-anzeige-list label.filter-pill.is-on {
        background: #e8f0e4;
        border-color: #4d6b3e;
        color: #3f5a32;
    }
    </style>
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
            <h1>Kontobuch</h1>
            <p>
                Buchungen und Transfers.
                <?php if ($bookingTopDefault !== '' && isset($accountMapActive[$bookingTopDefault])): ?>
                    <?= h_book((string)$bookingTopDefault . ' — ' . (string)($accountMapActive[$bookingTopDefault]['name'] ?? '')) ?>
                    <?php if ($closingSaldo !== null): ?>
                        · Saldo <?= h_book($formatCurrency($closingSaldo)) ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($periodFilter === 'all'): ?>
                    · alle Buchungen
                <?php elseif ($from !== null && $to !== null): ?>
                    · <?= h_book($formatDateDe($from)) ?>–<?= h_book($formatDateDe($to)) ?>
                <?php elseif ($from !== null): ?>
                    · ab <?= h_book($formatDateDe($from)) ?> (inkl. zukünftig)
                <?php endif; ?>
            </p>
        </div>
        <img class="header-logo" src="<?= htmlspecialchars(rtrim((string)($RkrkLocation ?? ''), "/\\") . '/data/rt/images/logo.png', ENT_QUOTES) ?>" alt="Logo" onerror="this.style.display='none';">
    </header>

    <?php if (isset($_GET['ok'])): ?>
        <div class="notice ok"><?= h_book((string)$_GET['ok']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="notice error"><?= h_book((string)$_GET['error']) ?></div>
    <?php endif; ?>

    <form method="get" class="kb-filter" id="kb_filter">
        <div class="kb-filter-main">
            <label>Konto
                <select name="konto" id="kb_konto" onchange="this.form.submit()">
                    <option value="">— alle —</option>
                    <?php foreach ($accountMapActive as $code => $acc): ?>
                        <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                        <option value="<?= h_book((string)$code) ?>" <?= $bookingTopDefault === (string)$code ? 'selected' : '' ?>>
                            <?= h_book((string)$code . ' — ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <input type="hidden" name="zeitraum" value="<?= h_book($periodFilter) ?>">
            <div class="filter-pills" role="group" aria-label="Zeitraum">
                <?php foreach (['3m' => '3 Monate', '6m' => '6 Monate', '1y' => '1 Jahr', 'all' => 'Alle'] as $pk => $plab): ?>
                    <button type="submit" name="zeitraum" value="<?= h_book($pk) ?>" class="filter-pill<?= $periodFilter === $pk ? ' is-active' : '' ?>"><?= h_book($plab) ?></button>
                <?php endforeach; ?>
            </div>
            <?php if ($availableYears !== []): ?>
                <label>Jahr
                    <select name="jahr" onchange="this.form.submit()">
                        <option value="">alle Jahre</option>
                        <?php foreach ($availableYears as $y): ?>
                            <option value="<?= (int)$y ?>" <?= $jahrFilter === (int)$y ? 'selected' : '' ?>><?= (int)$y ?></option>
                        <?php endforeach; ?>
                    </select>
            </label>
            <?php endif; ?>
            <div class="filter-pills">
                <button type="submit" name="status" value="<?= $filters['status'] === 'offen' ? 'alle' : 'offen' ?>" class="filter-pill<?= $filters['status'] === 'offen' ? ' is-active' : '' ?>">
                    <?= $abstMode ? 'Nur nicht abgestimmte' : 'Nur offen' ?><?= $nOffen > 0 ? ' ' . (int)$nOffen : '' ?>
                </button>
            </div>
            <label data-kb-show="f-emp">Empfänger
                <input type="text" name="empfaenger" list="emp_list" value="<?= h_book($filters['empfaenger']) ?>" placeholder="enthält…">
            </label>
            <label data-kb-show="f-memo">Bemerkung
                <input type="text" name="memo" value="<?= h_book($filters['memo']) ?>" placeholder="enthält…">
            </label>
            <button type="submit" class="filter-pill">Filtern</button>
            <?php if ($abstMode): ?>
                <input type="hidden" name="abstimmen" value="1">
            <?php endif; ?>
            <details class="kb-anzeige" id="kb_anzeige">
                <summary>Anzeige</summary>
                <div class="kb-anzeige-list">
                    <p>Filter</p>
                    <div class="filter-pills">
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="f-emp"> Empfänger</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="f-memo"> Bemerkung</label>
                    </div>
                    <p>Neue Buchung</p>
                    <div class="filter-pills">
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="n-kat"> Kostenart</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="n-typ"> Typ</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="n-memo"> Bemerkung</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="n-status"> Status</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="n-beleg"> Beleg</label>
                    </div>
                    <p>Liste</p>
                    <div class="filter-pills">
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="t-emp" checked> Empfänger</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="t-kat" checked> Kostenart</label>
                        <label class="filter-pill"><input type="checkbox" data-kb-toggle="t-memo" checked> Bemerkung</label>
                    </div>
                </div>
            </details>
        </div>
    </form>
    <datalist id="emp_list">
        <?php foreach ($empNames as $name): ?>
            <option value="<?= h_book($name) ?>">
                    <?php endforeach; ?>
    </datalist>

    <section class="card<?= $editRow !== null ? ' kb-editing' : '' ?>" id="kb_new">
        <h2><?= $editRow !== null ? 'Buchung bearbeiten' : 'Neue Buchung' ?></h2>
        <form method="post" enctype="multipart/form-data" class="kb-book" id="kb_new_form" autocomplete="off"
              data-status-x="<?= $editRow !== null && kb_status((string)($editRow['booking_status'] ?? '')) === 'x' ? '1' : '0' ?>"
              data-transfer="<?= $editType === 'transfer' ? '1' : '0' ?>">
            <input type="hidden" name="action" value="<?= $editRow !== null ? 'save_booking' : 'add_booking' ?>">
            <input type="hidden" name="confirm_x" id="kb_confirm_x" value="0">
            <input type="hidden" name="confirm_transfer" id="kb_confirm_transfer" value="0">
            <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
            <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
            <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
            <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
            <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
            <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
            <input type="hidden" name="abstimmen" value="<?= h_book($filters['abstimmen']) ?>">
            <?php if ($editRow !== null): ?>
                <input type="hidden" name="id" value="<?= h_book((string)($editRow['id'] ?? '')) ?>">
                <input type="hidden" name="expect_modified" value="<?= h_book(kb_row_stamp($editRow)) ?>">
            <?php endif; ?>
            <label>Datum
                <input type="date" name="datum" id="kb_datum" required value="<?= h_book($fDatum) ?>">
            </label>
            <?php if ($bookingTopDefault === ''): ?>
                <label>Konto
                    <select name="konto" id="kb_konto_new" required>
                        <option value="">Bitte wählen</option>
                        <?php foreach ($accountMapActive as $code => $acc): ?>
                            <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                            <option value="<?= h_book((string)$code) ?>" data-currency="<?= h_book($cur) ?>" <?= $fKonto === (string)$code ? 'selected' : '' ?>>
                                <?= h_book((string)$code . ' — ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php else: ?>
                <?php $curDef = strtoupper((string)($accountMapActive[$bookingTopDefault]['currency'] ?? 'CHF')); ?>
                <input type="hidden" name="konto" id="kb_konto_new" value="<?= h_book($bookingTopDefault) ?>" data-currency="<?= h_book($curDef) ?>">
            <?php endif; ?>
            <label>Empfänger
                <input type="text" name="empfaenger" id="kb_emp" list="emp_list" maxlength="80" autocomplete="off" value="<?= h_book($fEmp) ?>" <?= $editType === 'transfer' ? '' : 'required' ?>>
            </label>
            <label data-kb-show="n-kat">Kostenart
                <select name="category_code" id="kb_category">
                    <option value="">(frei / keine)</option>
                    <?php foreach ($categories as $code => $name): ?>
                        <option value="<?= h_book((string)$code) ?>" <?= $fPlan === (string)$code ? 'selected' : '' ?>><?= h_book((string)$code . ' - ' . (string)$name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Betrag
                <input type="text" name="betrag" id="kb_betrag" inputmode="decimal" placeholder="100 (Ein) oder -49.5 (Aus)" value="<?= h_book($fBetrag) ?>">
            </label>
            <label data-kb-show="n-typ">Typ
                <?php if ($editRow !== null): ?>
                    <input type="hidden" name="booking_type" id="booking_type" value="<?= h_book($editType) ?>">
                    <select disabled>
                        <option value="standard" <?= $editType === 'standard' ? 'selected' : '' ?>>Buchung</option>
                        <option value="transfer" <?= $editType === 'transfer' ? 'selected' : '' ?>>Kontotransfer</option>
                    </select>
                <?php else: ?>
                <select name="booking_type" id="booking_type">
                        <option value="standard">Buchung</option>
                    <option value="transfer">Kontotransfer</option>
                </select>
                <?php endif; ?>
            </label>
            <label id="kb_ziel_wrap" class="<?= $editType === 'transfer' ? '' : 'kb-hidden' ?>">Zielkonto
                <select name="ziel_konto" id="kb_ziel">
                    <option value="">Bitte wählen</option>
                    <?php foreach ($accountMapActive as $code => $acc): ?>
                        <?php $cur = strtoupper((string)($acc['currency'] ?? 'CHF')); ?>
                        <option value="<?= h_book((string)$code) ?>" data-currency="<?= h_book($cur) ?>" <?= $editZiel === (string)$code ? 'selected' : '' ?>>
                            <?= h_book((string)$code . ' — ' . (string)($acc['name'] ?? '') . ' (' . $cur . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label id="kb_target_amt_wrap" class="kb-hidden">Zielbetrag (Fremdwährung)
                <input type="text" name="target_amount" id="kb_target_amt" inputmode="decimal" value="<?= h_book($fTargetAmt) ?>">
            </label>
            <label class="full-width" data-kb-show="n-memo">Bemerkung
                <input type="text" name="note" id="kb_memo" maxlength="200" value="<?= h_book($fMemo) ?>">
            </label>
            <label data-kb-show="n-status">Status
                <select name="booking_status" id="kb_status">
                    <option value="n" <?= $fStatus === 'n' ? 'selected' : '' ?>>n — offen</option>
                    <option value="v" <?= $fStatus === 'v' ? 'selected' : '' ?>>v — gemerkt</option>
                    <option value="x" <?= $fStatus === 'x' ? 'selected' : '' ?>>x — abgestimmt</option>
                </select>
            </label>
            <label class="full-width" data-kb-show="n-beleg">Beleg (Datei)
                <input type="file" name="beleg" accept=".pdf,.png,.jpg,.jpeg,.webp">
                <?php if ($editRow !== null && trim((string)($editRow['receipt_path'] ?? '')) !== ''): ?>
                    <small><a href="<?= h_book((string)$editRow['receipt_path']) ?>" target="_blank" rel="noopener">aktueller Beleg</a></small>
                <?php endif; ?>
            </label>
            <div class="form-actions full-width">
                <button type="submit"><?= $editRow !== null ? 'Speichern' : 'Buchen' ?></button>
                <?php if ($editRow !== null): ?>
                    <a class="button-link secondary" href="kontobuch.php?<?= h_book(http_build_query(kb_qs(['edit' => '']))) ?>">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if ($abstMode): ?>
    <section class="kpi-grid kb-abst-kpis">
        <article class="card">
            <h2>Ausgangssaldo</h2>
            <p class="muted">nur abgestimmte (x)</p>
            <p class="value"><?= $ausgangSaldo !== null ? h_book($formatCurrency($ausgangSaldo)) : 'Konto wählen' ?></p>
        </article>
        <article class="card">
            <h2>Progresssaldo</h2>
            <p class="muted">x + gemerkte v<?= $vIds !== [] ? ' (' . count($vIds) . ')' : '' ?></p>
            <p class="value"><?= $progressSaldo !== null ? h_book($formatCurrency($progressSaldo)) : '—' ?></p>
        </article>
        <article class="card">
            <h2>Endsaldo</h2>
            <p class="muted">wenn jetzt alles auf x</p>
            <p class="value"><?= $endSaldo !== null ? h_book($formatCurrency($endSaldo)) : '—' ?></p>
        </article>
    </section>
    <p class="muted ka-abst-hint">Klick auf n setzt v (gemerkt, unabhängig von der Bank-Reihenfolge). Abschliessen setzt alle v auf x.</p>
    <?php endif; ?>

    <section class="card kontobuch-grid">
        <h2 class="kb-list-head">
            <span>
                <?= count($prepared) ?> Buchungen
                <?php if ($nOffen > 0): ?>
                    <span class="muted"> · <?= (int)$nOffen ?> offen <?= h_book($formatCurrency($sumOffen)) ?></span>
                <?php endif; ?>
                <?php if ($openingSaldo !== null && $bookingTopDefault !== ''): ?>
                    <span class="muted"> · Saldovortrag <?= h_book($formatCurrency($openingSaldo)) ?></span>
                <?php endif; ?>
            </span>
            <div class="kb-abstimmen">
            <?php if ($abstMode): ?>
                <?php if (kb_can_write()): ?>
                <form method="post" class="kb-abstimmen">
                    <input type="hidden" name="action" value="abschliessen">
                    <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
                    <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
                    <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
                    <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
                    <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
                    <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
                    <input type="hidden" name="abstimmen" value="1">
                    <?php foreach ($vIds as $vid): ?>
                        <input type="hidden" name="ids[]" value="<?= h_book($vid) ?>">
                    <?php endforeach; ?>
                    <button type="submit"<?= $vIds === [] ? ' disabled' : '' ?>
                        title="Alle gemerkten v auf x setzen"
                        <?php if ($vIds !== []): ?>onclick="return confirm('<?= (int)count($vIds) ?> gemerkte Buchungen (v) auf x setzen?');"<?php endif; ?>>
                        Abschliessen<?= $vIds !== [] ? ' (' . count($vIds) . ')' : '' ?>
                    </button>
                </form>
                <?php endif; ?>
                <a class="button-link" href="kontobuch.php?<?= h_book(http_build_query(kb_qs(['abstimmen' => '']))) ?>">Beenden</a>
            <?php else: ?>
                <a class="kb-abstimmen-link" href="kontobuch.php?<?= h_book(http_build_query(kb_qs(['abstimmen' => '1', 'edit' => '']))) ?>" title="Abstimmen: Filter bleiben, temporäre Saldi">Abstimmen</a>
                <?php if ($bookingTopDefault !== '' && kb_can_write()): ?>
                <form method="post" class="kb-abstimmen">
                    <input type="hidden" name="action" value="abstimmen_all">
                    <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
                    <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
                    <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
                    <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
                    <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
                    <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
                    <button type="submit" class="kb-abstimmen-all" onclick="return confirm('Alle Buchungen auf diesem Konto auf x (abgestimmt) setzen?');">Alle Abstimmen</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </h2>
        <div class="table-wrapper" id="kb_table_wrap">
                <table id="kontobuch-table">
                    <thead>
                    <tr>
                    <th>Datum</th>
                    <th data-kb-show="t-emp">Empfänger</th>
                    <th data-kb-show="t-kat">Kostenart</th>
                    <?php if ($showKontoCol): ?><th>Konto</th><?php endif; ?>
                    <th class="num">Haben/Aus</th>
                    <th class="num">Soll/Ein</th>
                    <th class="num">Saldo</th>
                    <th>Status</th>
                    <th data-kb-show="t-memo">Bemerkung / Beleg</th>
                    <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prepared as $row): ?>
                        <?php
                        $id = (string)($row['id'] ?? '');
                        $bookingDate = (string)($row['booking_date'] ?? '');
                        $counterparty = (string)($row['counterparty'] ?? '');
                        $code = trim((string)($row['cost_category_code'] ?? ''));
                        $catName = $categories[$code] ?? (string)($row['cost_category_name_raw'] ?? '');
                        $account = (string)($row['_konto'] ?? '-');
                        $accountDisplay = account_table_display($account);
                    $zielAcc = trim((string)($row['target_account'] ?? ''));
                        $amountOut = (float)($row['amount_out'] ?? 0);
                        $amountIn = (float)($row['amount_in'] ?? 0);
                        $balance = (float)($row['_saldo'] ?? 0);
                        $note = (string)($row['note'] ?? '');
                        $receipt = trim((string)($row['receipt_path'] ?? ''));
                    $status = kb_status((string)($row['booking_status'] ?? 'x'));
                    $isTransfer = trim((string)($row['transfer_id'] ?? '')) !== '';
                    $trClass = [];
                    if ($status === 'x') {
                        $trClass[] = 'row-locked';
                    }
                    if ($status !== 'x') {
                        $trClass[] = 'ka-row-open';
                    }
                    if ($editId !== '' && $id === $editId) {
                        $trClass[] = 'kb-editing';
                    }
                    $kontoCell = $accountDisplay;
                    if ($zielAcc !== '') {
                        $kontoCell .= ' → ' . account_table_display($zielAcc);
                    }
                    ?>
                    <tr id="r<?= h_book($id) ?>" class="<?= h_book(implode(' ', $trClass)) ?>">
                        <td class="nowrap"><?= h_book($formatDateDe($bookingDate)) ?></td>
                        <td data-kb-show="t-emp"><?= h_book($counterparty) ?></td>
                        <td data-kb-show="t-kat"><?= h_book($code !== '' ? ($code . ($catName !== '' ? ' - ' . $catName : '')) : $catName) ?></td>
                        <?php if ($showKontoCol): ?>
                            <td title="<?= h_book($account) ?>"><?= h_book($kontoCell) ?></td>
                        <?php endif; ?>
                        <td class="amount-out"><?= h_book($formatAmountCell($amountOut)) ?></td>
                        <td class="amount-in"><?= h_book($formatAmountCell($amountIn)) ?></td>
                        <td class="saldo"><?= h_book($formatCurrency($balance)) ?></td>
                        <td>
                            <form method="post" class="inline-form" data-status-x="<?= $status === 'x' ? '1' : '0' ?>" onsubmit="return kbConfirmStatus(this)">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= h_book($id) ?>">
                                <input type="hidden" name="confirm_x" value="0">
                                <input type="hidden" name="expect_modified" value="<?= h_book(kb_row_stamp($row)) ?>">
                                <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
                                <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
                                <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
                                <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
                                <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
                                <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
                                <input type="hidden" name="abstimmen" value="<?= h_book($filters['abstimmen']) ?>">
                                <button class="kb-abst is-<?= h_book($status) ?>" type="submit" title="<?= $abstMode ? 'Klick: n → v (gemerkt). Abschliessen setzt v auf x.' : 'Klick: n → v → x → n' ?>"><?= h_book($status) ?></button>
                            </form>
                            </td>
                        <td class="receipt-cell" data-kb-show="t-memo">
                                    <?= h_book($note) ?>
                                    <?php if ($receipt !== ''): ?>
                                <?= $note !== '' ? ' | ' : '' ?><a href="<?= h_book($receipt) ?>" target="_blank" rel="noopener">Beleg</a>
                                    <?php endif; ?>
                            </td>
                        <td class="cell-actions">
                            <a class="icon-link" href="kontobuch.php?<?= h_book(http_build_query(kb_qs(['edit' => $id]))) ?>#kb_new" title="Bearbeiten" aria-label="Bearbeiten"><?= kb_ico('edit') ?></a>
                            <form method="post" class="inline-form" data-status-x="<?= $status === 'x' ? '1' : '0' ?>" data-transfer="<?= $isTransfer ? '1' : '0' ?>" onsubmit="return kbConfirmDelete(this)">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h_book($id) ?>">
                                <input type="hidden" name="confirm_x" value="0">
                                <input type="hidden" name="confirm_transfer" value="0">
                                <input type="hidden" name="expect_modified" value="<?= h_book(kb_row_stamp($row)) ?>">
                                <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
                                <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
                                <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
                                <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
                                <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
                                <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
                                <input type="hidden" name="abstimmen" value="<?= h_book($filters['abstimmen']) ?>">
                                <button class="icon-btn icon-danger" type="submit" title="Löschen" aria-label="Löschen">&times;</button>
                            </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php if ($bookingTopDefault !== '' && $openingSaldo !== null): ?>
                    <tr>
                        <td class="nowrap">—</td>
                        <td class="muted" data-kb-show="t-emp">Saldovortrag</td>
                        <td data-kb-show="t-kat"></td>
                        <?php if ($showKontoCol): ?><td></td><?php endif; ?>
                        <td></td>
                        <td></td>
                        <td class="saldo"><?= h_book($formatCurrency($openingSaldo)) ?></td>
                        <td></td>
                        <td data-kb-show="t-memo"></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <form method="post" style="margin-top:0.75rem;">
            <input type="hidden" name="filter_konto" value="<?= h_book($filters['konto']) ?>">
            <input type="hidden" name="zeitraum" value="<?= h_book($filters['zeitraum']) ?>">
            <input type="hidden" name="jahr" value="<?= h_book($filters['jahr']) ?>">
            <input type="hidden" name="filter_empfaenger" value="<?= h_book($filters['empfaenger']) ?>">
            <input type="hidden" name="filter_memo" value="<?= h_book($filters['memo']) ?>">
            <input type="hidden" name="filter_status" value="<?= h_book($filters['status']) ?>">
            <input type="hidden" name="abstimmen" value="<?= h_book($filters['abstimmen']) ?>">
            <input type="hidden" name="action" value="extract_note_links">
            <button type="submit" class="btn-secondary">Beleg-Links aus Bemerkungen extrahieren</button>
        </form>
    </section>
</div>
<script>
(() => {
  const type = document.getElementById('booking_type');
  const zielWrap = document.getElementById('kb_ziel_wrap');
  const amtWrap = document.getElementById('kb_target_amt_wrap');
  const konto = document.getElementById('kb_konto_new');
  const ziel = document.getElementById('kb_ziel');
  const bookForm = document.getElementById('kb_new_form');
  const empInput = document.getElementById('kb_emp');
  const catSel = document.getElementById('kb_category');
  const hints = <?= json_encode($counterpartyHints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || {};

  function ccyOf(sel) {
    if (!sel) return 'CHF';
    if (sel.tagName === 'SELECT') {
      const opt = sel.options[sel.selectedIndex];
      return (opt && opt.getAttribute('data-currency')) || 'CHF';
    }
    return sel.getAttribute('data-currency') || 'CHF';
  }
  function syncType() {
    const isTransfer = (type ? type.value : (bookForm && bookForm.dataset.transfer === '1' ? 'transfer' : 'standard')) === 'transfer';
    if (zielWrap) zielWrap.classList.toggle('kb-hidden', !isTransfer);
    if (empInput) empInput.required = !isTransfer;
    syncFx();
  }
  function syncFx() {
    const isTransfer = zielWrap && !zielWrap.classList.contains('kb-hidden');
    if (!isTransfer || !amtWrap) {
      if (amtWrap) amtWrap.classList.add('kb-hidden');
                return;
            }
    amtWrap.classList.toggle('kb-hidden', ccyOf(konto) === ccyOf(ziel));
  }
  if (type) type.addEventListener('change', syncType);
  if (konto) konto.addEventListener('change', syncFx);
  if (ziel) ziel.addEventListener('change', syncFx);
  syncType();

  if (empInput && catSel) {
    empInput.addEventListener('change', () => {
      const key = (empInput.value || '').trim().toLowerCase();
      const hit = hints[key];
      if (hit && hit.category_code && !catSel.value) catSel.value = hit.category_code;
    });
  }

  function confirmFlags(form) {
    if (form.dataset.statusX === '1') {
      if (!confirm('Gesperrte Buchung (x) wirklich ändern?')) return false;
      const cx = form.querySelector('[name="confirm_x"]');
      if (cx) cx.value = '1';
    }
    if (form.dataset.transfer === '1') {
      if (!confirm('Transfer betrifft das Gegenkonto. Fortfahren?')) return false;
      const ct = form.querySelector('[name="confirm_transfer"]');
      if (ct) ct.value = '1';
    }
    return true;
  }
  window.kbConfirmStatus = (form) => confirmFlags(form);
  window.kbConfirmDelete = (form) => {
    if (!confirmFlags(form)) return false;
    return confirm('Buchung löschen?');
  };
  if (bookForm) {
    bookForm.addEventListener('submit', (ev) => {
      const t = type ? type.value : bookForm.dataset.transfer;
      if (t === 'transfer') bookForm.dataset.transfer = '1';
      const st = bookForm.querySelector('[name="booking_status"]');
      if (st && !st.disabled && st.value === 'x') bookForm.dataset.statusX = '1';
      if (!confirmFlags(bookForm)) ev.preventDefault();
    });
  }

  const USER = <?= json_encode($kbAnzeigeUser, JSON_UNESCAPED_UNICODE) ?>;
  const KEY = 'ar_kb_anzeige.' + USER;
  const defaults = {
    'f-emp': true,
    'f-memo': true,
    'n-kat': true,
    'n-typ': true,
    'n-memo': true,
    'n-status': false,
    'n-beleg': true,
    't-emp': true,
    't-kat': true,
    't-memo': true
  };
  const force = <?= json_encode($kbForce, JSON_UNESCAPED_UNICODE) ?> || [];

  function loadAnzeige() {
    const state = {};
    Object.keys(defaults).forEach((k) => { state[k] = defaults[k]; });
    try {
      const raw = localStorage.getItem(KEY) || localStorage.getItem('ar_kb_anzeige');
      if (raw) {
        const parsed = JSON.parse(raw);
        Object.keys(defaults).forEach((k) => {
          if (typeof parsed[k] === 'boolean') state[k] = parsed[k];
        });
      }
    } catch (e) {}
    force.forEach((k) => { state[k] = true; });
    return state;
  }

  function applyAnzeige(state) {
    document.querySelectorAll('[data-kb-show]').forEach((el) => {
      const key = el.getAttribute('data-kb-show');
      el.classList.toggle('kb-anzeige-off', !state[key]);
    });
    document.querySelectorAll('[data-kb-toggle]').forEach((cb) => {
      const key = cb.getAttribute('data-kb-toggle');
      const on = !!state[key];
      cb.checked = on;
      const lab = cb.closest('label');
      if (lab) lab.classList.toggle('is-on', on);
    });
    const statusSel = document.getElementById('kb_status');
    if (statusSel) statusSel.disabled = !state['n-status'];
  }

  function saveAnzeige(state) {
    try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
  }

  let anzeige = loadAnzeige();
  applyAnzeige(anzeige);
  syncType();
  document.querySelectorAll('[data-kb-toggle]').forEach((cb) => {
    cb.addEventListener('change', () => {
      const key = cb.getAttribute('data-kb-toggle');
      anzeige[key] = cb.checked;
      saveAnzeige(anzeige);
      applyAnzeige(anzeige);
      syncType();
        });
    });

  const hashId = (location.hash || '').replace(/^#/, '');
  if (hashId) {
    const row = document.getElementById(hashId);
    if (row) {
      try { row.scrollIntoView({ block: 'center' }); } catch (e) {}
    }
  }
})();
</script>
