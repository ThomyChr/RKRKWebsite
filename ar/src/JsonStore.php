<?php

declare(strict_types=1);

final class JsonStore
{
    private string $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readList(string $fileName): array
    {
        $path = $this->dataDir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn($row): bool => is_array($row)));
    }

    /**
     * Kopiert eine bestehende Datendatei nach {@see self::ARCHIVE_SUBDIR} (relativ zum Datenverzeichnis),
     * bevor sie überschrieben wird. Dateien in diesem Verzeichnis, die älter als {@see self::ARCHIVE_RETENTION_DAYS}
     * sind (mtime), werden beim Speichern entfernt.
     */
    private const ARCHIVE_SUBDIR = 'Archiv';

    private const ARCHIVE_RETENTION_DAYS = 30;

    private function pruneOldArchiveFiles(): void
    {
        $archivDir = $this->dataDir . DIRECTORY_SEPARATOR . self::ARCHIVE_SUBDIR;
        if (!is_dir($archivDir)) {
            return;
        }
        $cutoff = time() - (self::ARCHIVE_RETENTION_DAYS * 86400);
        $entries = @scandir($archivDir);
        if ($entries === false) {
            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $archivDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($full)) {
                continue;
            }
            $mtime = @filemtime($full);
            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($full);
            }
        }
    }

    /**
     * Zeitstempel für Archivdateinamen: {@code YYYYMMDDhhmm}, Minuten (Uhr) auf 10-Minuten-Takte gerundet.
     */
    private function archiveFilenameTimestamp(): string
    {
        $dt = new DateTimeImmutable();
        $minuteOfDay = ((int)$dt->format('H')) * 60 + (int)$dt->format('i') + ((int)$dt->format('s')) / 60.0;
        $rounded = (int)round($minuteOfDay / 10.0) * 10;
        if ($rounded >= 1440) {
            $dt = $dt->modify('+1 day')->setTime(0, 0, 0);

            return $dt->format('Ymd') . '0000';
        }
        $h = intdiv($rounded, 60);
        $m = $rounded % 60;

        return $dt->format('Ymd') . sprintf('%02d%02d', $h, $m);
    }

    private function archiveExistingFileBeforeWrite(string $path, string $fileName): void
    {
        if (!is_file($path)) {
            return;
        }

        $archivDir = $this->dataDir . DIRECTORY_SEPARATOR . self::ARCHIVE_SUBDIR;
        if (!is_dir($archivDir) && !mkdir($archivDir, 0777, true) && !is_dir($archivDir)) {
            throw new RuntimeException('Could not create archive directory: ' . $archivDir);
        }

        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? $ext : 'json';
        $stamp = $this->archiveFilenameTimestamp();
        $archPath = $archivDir . DIRECTORY_SEPARATOR . $base . '_' . $stamp . '.' . $ext;
        while (is_file($archPath)) {
            $archPath = $archivDir . DIRECTORY_SEPARATOR . $base . '_' . $stamp . '_' . bin2hex(random_bytes(2)) . '.' . $ext;
        }
        if (!@copy($path, $archPath)) {
            throw new RuntimeException('Could not archive file before write: ' . $fileName);
        }
        @touch($archPath);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function writeList(string $fileName, array $rows): void
    {
        if (!is_dir($this->dataDir) && !mkdir($concurrentDirectory = $this->dataDir, 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException('Could not create data directory: ' . $this->dataDir);
        }

        $path = $this->dataDir . DIRECTORY_SEPARATOR . $fileName;
        $this->archiveExistingFileBeforeWrite($path, $fileName);
        $json = json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Could not encode JSON for file: ' . $fileName);
        }

        $bytes = file_put_contents($path, $json . PHP_EOL, LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException('Could not write JSON file: ' . $fileName);
        }
        $this->pruneOldArchiveFiles();
    }
}

