<?php

declare(strict_types=1);

final class YearlyStore
{
    private const SUBDIR = 'transactions';
    private const INDEX_FILE = 'transactions/_index.json';
    private const TOTALS_FILE = 'transactions/_totals.json';
    private const IDMAP_FILE = 'transactions/_idmap.json';
    private const LEGACY_FILE = 'transactions.json';

    private string $dataDir;
    private JsonStore $store;
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $yearMemo = [];
    /** @var array<string, string>|null */
    private ?array $idMapMemo = null;
    /** @var array<string, mixed>|null */
    private ?array $indexMemo = null;
    /** @var array<string, mixed>|null */
    private ?array $totalsMemo = null;
    private bool $migrated = false;

    public function __construct(string $dataDir, JsonStore $store)
    {
        $this->dataDir = rtrim($dataDir, DIRECTORY_SEPARATOR);
        $this->store = $store;
    }

    public static function yearKeyFromDate(string $date): string
    {
        $date = trim($date);
        if (preg_match('/^(\d{4})/', $date, $m) !== 1) {
            return '_undated';
        }
        $y = (int)$m[1];
        if ($y < 1990 || $y > 2100) {
            return '_undated';
        }

        return (string)$y;
    }

    public function ensureMigrated(): void
    {
        if ($this->migrated) {
            return;
        }
        $this->migrated = true;
        $indexPath = $this->dataDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::INDEX_FILE);
        if (is_file($indexPath)) {
            return;
        }
        $existingYears = $this->scanYearFiles();
        if ($existingYears !== []) {
            $this->rebuildMetaFromDisk($existingYears);

            return;
        }
        $legacy = $this->store->readList(self::LEGACY_FILE);
        if ($legacy === []) {
            $this->writeIndex(['updated_at' => date('c'), 'count' => 0, 'years' => []]);
            $this->writeTotals(['updated_at' => date('c'), 'by_year' => new \stdClass()]);
            $this->writeIdMap([]);

            return;
        }
        $buckets = [];
        foreach ($legacy as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = self::yearKeyFromDate((string)($row['booking_date'] ?? ''));
            $buckets[$key][] = $row;
        }
        foreach ($buckets as $key => $rows) {
            $this->writeYear((string)$key, $rows);
        }
        $legacyPath = $this->store->absolutePath(self::LEGACY_FILE);
        if (is_file($legacyPath)) {
            $this->store->deleteFile(self::LEGACY_FILE);
        }
    }

    /**
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $this->ensureMigrated();
        $years = [];
        foreach ($this->yearKeys() as $key) {
            if (preg_match('/^\d{4}$/', $key) === 1) {
                $years[] = (int)$key;
            }
        }
        rsort($years, SORT_NUMERIC);

        return $years;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param string|int $key
     * @return array<int, array<string, mixed>>
     */
    public function readYear($key): array
    {
        $key = (string)$key;
        $this->ensureMigrated();
        $file = $this->relativeFile($key);
        if ($file === '') {
            return [];
        }
        if (array_key_exists($key, $this->yearMemo)) {
            return $this->yearMemo[$key];
        }
        $rows = $this->store->readList($file);
        $this->yearMemo[$key] = $rows;

        return $rows;
    }

    /**
     * @param string|int $key
     * @param array<int, array<string, mixed>> $rows
     */
    public function writeYear($key, array $rows): void
    {
        $key = (string)$key;
        $file = $this->relativeFile($key);
        if ($file === '') {
            throw new RuntimeException('Invalid year key: ' . $key);
        }
        $oldIds = [];
        foreach ($this->readYear($key) as $row) {
            $id = trim((string)($row['id'] ?? ''));
            if ($id !== '') {
                $oldIds[$id] = true;
            }
        }
        $rows = array_values($rows);
        if ($rows === []) {
            $this->store->deleteFile($file);
            $this->yearMemo[$key] = [];
        } else {
            $this->store->writeList($file, $rows);
            $this->yearMemo[$key] = $rows;
        }
        $this->updateIndexForYear($key, $rows);
        $this->updateIdMapForYear($key, $rows, array_keys($oldIds));
        $this->updateTotalsForYear($key, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readRange(?string $from, ?string $to): array
    {
        $this->ensureMigrated();
        $filter = ($from !== null && $from !== '') || ($to !== null && $to !== '');
        $rows = [];
        foreach ($this->yearKeysInRange($from, $to) as $key) {
            foreach ($this->readYear($key) as $row) {
                if ($filter) {
                    $d = (string)($row['booking_date'] ?? '');
                    if ($from !== null && $from !== '' && $d !== '' && $d < $from) {
                        continue;
                    }
                    if ($to !== null && $to !== '' && $d !== '' && $d > $to) {
                        continue;
                    }
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readAll(): array
    {
        $this->ensureMigrated();
        $rows = [];
        foreach ($this->sortedYearKeys() as $key) {
            foreach ($this->readYear($key) as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }
        $this->ensureMigrated();
        $year = $this->idMap()[$id] ?? null;
        $keys = $year !== null ? [$year] : $this->sortedYearKeys();
        foreach ($keys as $key) {
            foreach ($this->readYear($key) as $row) {
                if (trim((string)($row['id'] ?? '')) === $id) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    public function findPair(array $row): ?array
    {
        $tid = trim((string)($row['transfer_id'] ?? ''));
        $self = trim((string)($row['id'] ?? ''));
        if ($tid === '') {
            return null;
        }
        $year = self::yearKeyFromDate((string)($row['booking_date'] ?? ''));
        foreach (array_merge([$year], $this->sortedYearKeys()) as $key) {
            foreach ($this->readYear($key) as $cand) {
                if (trim((string)($cand['id'] ?? '')) === $self) {
                    continue;
                }
                if (trim((string)($cand['transfer_id'] ?? '')) === $tid) {
                    return $cand;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function upsert(array $row): void
    {
        $this->ensureMigrated();
        $id = trim((string)($row['id'] ?? ''));
        $newYear = self::yearKeyFromDate((string)($row['booking_date'] ?? ''));
        $oldYear = $id !== '' ? ($this->idMap()[$id] ?? null) : null;
        if ($oldYear !== null && $oldYear !== $newYear) {
            $kept = [];
            foreach ($this->readYear($oldYear) as $r) {
                if (trim((string)($r['id'] ?? '')) !== $id) {
                    $kept[] = $r;
                }
            }
            $this->writeYear($oldYear, $kept);
        }
        $yearRows = $this->readYear($newYear);
        $found = false;
        foreach ($yearRows as $i => $r) {
            if ($id !== '' && trim((string)($r['id'] ?? '')) === $id) {
                $yearRows[$i] = $row;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $yearRows[] = $row;
        }
        $this->writeYear($newYear, $yearRows);
    }

    /**
     * @param array<int, string> $ids
     */
    public function deleteByIds(array $ids): void
    {
        $this->ensureMigrated();
        $want = [];
        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id !== '') {
                $want[$id] = true;
            }
        }
        if ($want === []) {
            return;
        }
        $byYear = [];
        foreach ($want as $id => $_) {
            $y = $this->idMap()[$id] ?? null;
            if ($y !== null) {
                $byYear[$y][$id] = true;
            }
        }
        if ($byYear === []) {
            foreach ($this->sortedYearKeys() as $key) {
                $byYear[$key] = $want;
            }
        }
        foreach ($byYear as $key => $idSet) {
            $kept = [];
            $changed = false;
            foreach ($this->readYear($key) as $row) {
                $rid = trim((string)($row['id'] ?? ''));
                if (isset($idSet[$rid]) || isset($want[$rid])) {
                    $changed = true;
                    continue;
                }
                $kept[] = $row;
            }
            if ($changed) {
                $this->writeYear($key, $kept);
            }
        }
    }

    /**
     * @param callable(array<string, mixed>): (?array<string, mixed>) $fn
     */
    public function mapAll(callable $fn): int
    {
        $this->ensureMigrated();
        $n = 0;
        foreach ($this->sortedYearKeys() as $key) {
            $rows = $this->readYear($key);
            $changed = false;
            foreach ($rows as $i => $row) {
                $new = $fn($row);
                if (is_array($new)) {
                    $rows[$i] = $new;
                    $changed = true;
                    $n++;
                }
            }
            if ($changed) {
                $this->writeYear($key, $rows);
            }
        }

        return $n;
    }

    /**
     * Kontosaldi (amount_in - amount_out) aller Buchungen mit Datum < $fromDate.
     *
     * @return array<string, float>
     */
    public function accountDeltasBefore(string $fromDate): array
    {
        $this->ensureMigrated();
        $fromDate = trim($fromDate);
        if ($fromDate === '') {
            return [];
        }
        $fromYear = self::yearKeyFromDate($fromDate);
        $out = [];
        $byYear = $this->totalsData()['by_year'] ?? [];
        if (!is_array($byYear)) {
            $byYear = [];
        }
        foreach ($this->sortedYearKeys() as $key) {
            if ($key === '_undated' || ($fromYear !== '_undated' && $key < $fromYear)) {
                $yearMap = is_array($byYear[$key] ?? null) ? $byYear[$key] : [];
                foreach ($yearMap as $acc => $sums) {
                    $all = is_array($sums) ? (float)($sums['all'] ?? 0) : (float)$sums;
                    $out[(string)$acc] = ($out[(string)$acc] ?? 0.0) + $all;
                }
                continue;
            }
            if ($key === $fromYear) {
                foreach ($this->readYear($key) as $row) {
                    $d = (string)($row['booking_date'] ?? '');
                    if ($d !== '' && $d >= $fromDate) {
                        continue;
                    }
                    $acc = $this->accountKey($row);
                    $out[$acc] = ($out[$acc] ?? 0.0) + $this->delta($row);
                }
            }
        }

        return $out;
    }

    /**
     * @return array<string, array{all: float, n: float, v: float, x: float}>
     */
    public function accountStatusSums(): array
    {
        $this->ensureMigrated();
        $out = [];
        $byYear = $this->totalsData()['by_year'] ?? [];
        if (!is_array($byYear)) {
            return [];
        }
        foreach ($byYear as $yearMap) {
            if (!is_array($yearMap)) {
                continue;
            }
            foreach ($yearMap as $acc => $sums) {
                if (!is_array($sums)) {
                    continue;
                }
                $key = (string)$acc;
                if (!isset($out[$key])) {
                    $out[$key] = ['all' => 0.0, 'n' => 0.0, 'v' => 0.0, 'x' => 0.0];
                }
                foreach (['all', 'n', 'v', 'x'] as $st) {
                    $out[$key][$st] += (float)($sums[$st] ?? 0);
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    public function vIdsForAccount(string $accountNorm): array
    {
        $this->ensureMigrated();
        $ids = [];
        foreach ($this->sortedYearKeys() as $key) {
            foreach ($this->readYear($key) as $row) {
                if ($this->accountKey($row) !== $accountNorm) {
                    continue;
                }
                $st = strtolower(trim((string)($row['booking_status'] ?? 'x')));
                if ($st !== 'v') {
                    continue;
                }
                $id = trim((string)($row['id'] ?? ''));
                if ($id !== '') {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * @param string|int $key
     */
    private function relativeFile($key): string
    {
        $key = (string)$key;
        if ($key !== '_undated' && preg_match('/^\d{4}$/', $key) !== 1) {
            return '';
        }

        return self::SUBDIR . '/' . $key . '.json';
    }

    /**
     * @return array<int, string>
     */
    private function yearKeys(): array
    {
        $index = $this->indexData();
        $keys = [];
        foreach (($index['years'] ?? []) as $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $k = trim((string)($meta['key'] ?? ''));
            if ($k !== '') {
                $keys[] = $k;
            }
        }
        if ($keys === []) {
            $keys = $this->scanYearFiles();
        }

        return $keys;
    }

    /**
     * @return array<int, string>
     */
    private function sortedYearKeys(): array
    {
        $undated = [];
        $years = [];
        foreach ($this->yearKeys() as $k) {
            if ($k === '_undated') {
                $undated[] = $k;
            } else {
                $years[] = $k;
            }
        }
        sort($years, SORT_STRING);

        return array_merge($undated, $years);
    }

    /**
     * @return array<int, string>
     */
    private function yearKeysInRange(?string $from, ?string $to): array
    {
        $fromKey = ($from !== null && $from !== '') ? self::yearKeyFromDate($from) : null;
        $toKey = ($to !== null && $to !== '') ? self::yearKeyFromDate($to) : null;
        $out = [];
        foreach ($this->sortedYearKeys() as $key) {
            if ($key === '_undated') {
                if ($fromKey === null) {
                    $out[] = $key;
                }
                continue;
            }
            if ($fromKey !== null && $fromKey !== '_undated' && $key < $fromKey) {
                continue;
            }
            if ($toKey !== null && $toKey !== '_undated' && $key > $toKey) {
                continue;
            }
            $out[] = $key;
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function scanYearFiles(): array
    {
        $dir = $this->dataDir . DIRECTORY_SEPARATOR . self::SUBDIR;
        if (!is_dir($dir)) {
            return [];
        }
        $keys = [];
        $entries = @scandir($dir);
        if ($entries === false) {
            return [];
        }
        foreach ($entries as $entry) {
            if (!preg_match('/^(\d{4}|_undated)\.json$/', $entry, $m)) {
                continue;
            }
            $keys[] = $m[1];
        }

        return $keys;
    }

    /**
     * @param array<int, string> $keys
     */
    private function rebuildMetaFromDisk(array $keys): void
    {
        foreach ($keys as $key) {
            $this->writeYear($key, $this->store->readList($this->relativeFile($key)));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function indexData(): array
    {
        if ($this->indexMemo !== null) {
            return $this->indexMemo;
        }
        $raw = $this->store->readJson(self::INDEX_FILE);
        $this->indexMemo = is_array($raw) ? $raw : ['years' => []];

        return $this->indexMemo;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeIndex(array $data): void
    {
        $this->store->writeJson(self::INDEX_FILE, $data);
        $this->indexMemo = $data;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    /**
     * @param string|int $key
     * @param array<int, array<string, mixed>> $rows
     */
    private function updateIndexForYear($key, array $rows): void
    {
        $key = (string)$key;
        $index = $this->indexData();
        $years = [];
        foreach (($index['years'] ?? []) as $meta) {
            if (!is_array($meta)) {
                continue;
            }
            if (trim((string)($meta['key'] ?? '')) === $key) {
                continue;
            }
            $years[] = $meta;
        }
        if ($rows !== []) {
            $min = null;
            $max = null;
            foreach ($rows as $row) {
                $d = trim((string)($row['booking_date'] ?? ''));
                if ($d === '') {
                    continue;
                }
                if ($min === null || $d < $min) {
                    $min = $d;
                }
                if ($max === null || $d > $max) {
                    $max = $d;
                }
            }
            $years[] = ['key' => $key, 'count' => count($rows), 'min' => $min, 'max' => $max];
        }
        usort($years, static function (array $a, array $b): int {
            return strcmp((string)($a['key'] ?? ''), (string)($b['key'] ?? ''));
        });
        $count = 0;
        foreach ($years as $meta) {
            $count += (int)($meta['count'] ?? 0);
        }
        $this->writeIndex(['updated_at' => date('c'), 'count' => $count, 'years' => $years]);
    }

    /**
     * @return array<string, string>
     */
    private function idMap(): array
    {
        if ($this->idMapMemo !== null) {
            return $this->idMapMemo;
        }
        $raw = $this->store->readJson(self::IDMAP_FILE);
        $ids = is_array($raw) ? ($raw['ids'] ?? $raw) : [];
        $this->idMapMemo = [];
        if (is_array($ids)) {
            foreach ($ids as $id => $year) {
                $this->idMapMemo[(string)$id] = (string)$year;
            }
        }

        return $this->idMapMemo;
    }

    /**
     * @param array<string, string> $map
     */
    private function writeIdMap(array $map): void
    {
        $this->store->writeJson(self::IDMAP_FILE, ['ids' => $map === [] ? new \stdClass() : $map]);
        $this->idMapMemo = $map;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $oldIds
     */
    /**
     * @param string|int $key
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $oldIds
     */
    private function updateIdMapForYear($key, array $rows, array $oldIds): void
    {
        $key = (string)$key;
        $map = $this->idMap();
        foreach ($oldIds as $id) {
            if (($map[$id] ?? '') === $key) {
                unset($map[$id]);
            }
        }
        foreach ($rows as $row) {
            $id = trim((string)($row['id'] ?? ''));
            if ($id !== '') {
                $map[$id] = $key;
            }
        }
        $this->writeIdMap($map);
    }

    /**
     * @return array<string, mixed>
     */
    private function totalsData(): array
    {
        if ($this->totalsMemo !== null) {
            return $this->totalsMemo;
        }
        $raw = $this->store->readJson(self::TOTALS_FILE);
        $this->totalsMemo = is_array($raw) ? $raw : ['by_year' => []];

        return $this->totalsMemo;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeTotals(array $data): void
    {
        if (isset($data['by_year']) && $data['by_year'] === []) {
            $data['by_year'] = new \stdClass();
        }
        $this->store->writeJson(self::TOTALS_FILE, $data);
        $this->totalsMemo = $data;
        if ($this->totalsMemo['by_year'] instanceof \stdClass) {
            $this->totalsMemo['by_year'] = [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    /**
     * @param string|int $key
     * @param array<int, array<string, mixed>> $rows
     */
    private function updateTotalsForYear($key, array $rows): void
    {
        $key = (string)$key;
        $totals = $this->totalsData();
        $byYear = $totals['by_year'] ?? [];
        if (!is_array($byYear)) {
            $byYear = [];
        }
        $yearMap = [];
        foreach ($rows as $row) {
            $acc = $this->accountKey($row);
            if (!isset($yearMap[$acc])) {
                $yearMap[$acc] = ['all' => 0.0, 'n' => 0.0, 'v' => 0.0, 'x' => 0.0];
            }
            $delta = $this->delta($row);
            $yearMap[$acc]['all'] += $delta;
            $st = strtolower(trim((string)($row['booking_status'] ?? 'x')));
            if (!in_array($st, ['n', 'v', 'x'], true)) {
                $st = 'x';
            }
            $yearMap[$acc][$st] += $delta;
        }
        if ($yearMap === []) {
            unset($byYear[$key]);
        } else {
            $byYear[$key] = $yearMap;
        }
        $this->writeTotals(['updated_at' => date('c'), 'by_year' => $byYear]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function accountKey(array $row): string
    {
        $t = trim((string)($row['account'] ?? ''));
        if ($t === '' || $t === '-') {
            return '-';
        }

        return strtoupper((string)preg_replace('/\s+/u', '', $t));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function delta(array $row): float
    {
        return (float)($row['amount_in'] ?? 0) - (float)($row['amount_out'] ?? 0);
    }
}
