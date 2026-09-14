<?php
declare(strict_types=1);

/**
 * Retention for saved map KMLs and drawings ("Verläufe"):
 *   - after 30 days → move into <data>/archive/
 *   - after 90 days → delete permanently
 *
 * Live share_*.json files are excluded (own short-lived cleanup).
 */

require_once __DIR__ . '/map_env.php';

if (!function_exists('map_retention_archive_seconds')) {
    function map_retention_archive_seconds(): int
    {
        return 30 * 24 * 60 * 60;
    }
}

if (!function_exists('map_retention_delete_seconds')) {
    function map_retention_delete_seconds(): int
    {
        return 90 * 24 * 60 * 60;
    }
}

if (!function_exists('map_retention_is_share_file')) {
    function map_retention_is_share_file(string $basename): bool
    {
        return (bool)preg_match('/^share_[a-z0-9]+\.json$/i', $basename);
    }
}

if (!function_exists('map_retention_archive_dir')) {
    function map_retention_archive_dir(string $baseDir): ?string
    {
        $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir), DIRECTORY_SEPARATOR);
        if ($baseDir === '' || !is_dir($baseDir)) {
            return null;
        }
        return map_ensure_dir($baseDir . DIRECTORY_SEPARATOR . 'archive');
    }
}

if (!function_exists('map_retention_should_process_file')) {
    /**
     * @param list<string> $extensions lowercase without dot, e.g. ['kml','json']
     */
    function map_retention_should_process_file(string $basename, array $extensions): bool
    {
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return false;
        }
        if (map_retention_is_share_file($basename)) {
            return false;
        }
        $ext = strtolower((string)pathinfo($basename, PATHINFO_EXTENSION));
        if ($extensions !== [] && !in_array($ext, $extensions, true)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('map_retention_move_to_archive')) {
    function map_retention_move_to_archive(string $filePath, string $archiveDir): bool
    {
        if (!is_file($filePath) || !is_dir($archiveDir)) {
            return false;
        }
        $base = basename($filePath);
        $dest = $archiveDir . DIRECTORY_SEPARATOR . $base;
        if (is_file($dest)) {
            $pi = pathinfo($base);
            $stem = (string)($pi['filename'] ?? 'file');
            $ext = isset($pi['extension']) ? ('.' . $pi['extension']) : '';
            $dest = $archiveDir . DIRECTORY_SEPARATOR . $stem . '__archived_' . gmdate('Ymd_His') . $ext;
        }
        return @rename($filePath, $dest);
    }
}

if (!function_exists('map_retention_run')) {
    /**
     * Apply archive/delete policy inside one data directory.
     *
     * @param array{extensions?:list<string>} $opts
     * @return array{archived:int,deleted:int,errors:int}
     */
    function map_retention_run(string $baseDir, array $opts = []): array
    {
        $stats = ['archived' => 0, 'deleted' => 0, 'errors' => 0];
        $baseDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir), DIRECTORY_SEPARATOR);
        if ($baseDir === '' || !is_dir($baseDir)) {
            return $stats;
        }

        $extensions = $opts['extensions'] ?? ['kml', 'json'];
        if (!is_array($extensions)) {
            $extensions = ['kml', 'json'];
        }
        $extensions = array_values(array_map(static function ($e) {
            return strtolower(ltrim((string)$e, '.'));
        }, $extensions));

        $archiveSec = map_retention_archive_seconds();
        $deleteSec = map_retention_delete_seconds();
        $now = time();
        $archiveDir = map_retention_archive_dir($baseDir);
        if ($archiveDir === null) {
            $stats['errors'] += 1;
            return $stats;
        }

        $archiveReal = realpath($archiveDir) ?: $archiveDir;

        // 1) Active files: delete if >90d, else archive if >30d
        $activeFiles = @scandir($baseDir);
        if (is_array($activeFiles)) {
            foreach ($activeFiles as $name) {
                if ($name === '.' || $name === '..' || $name === 'archive') {
                    continue;
                }
                $path = $baseDir . DIRECTORY_SEPARATOR . $name;
                if (!is_file($path)) {
                    continue;
                }
                if (!map_retention_should_process_file($name, $extensions)) {
                    continue;
                }
                $mtime = @filemtime($path);
                if ($mtime === false) {
                    continue;
                }
                $age = $now - (int)$mtime;
                if ($age > $deleteSec) {
                    if (@unlink($path)) {
                        $stats['deleted'] += 1;
                    } else {
                        $stats['errors'] += 1;
                    }
                    continue;
                }
                if ($age > $archiveSec) {
                    if (map_retention_move_to_archive($path, $archiveDir)) {
                        $stats['archived'] += 1;
                    } else {
                        $stats['errors'] += 1;
                    }
                }
            }
        }

        // 2) Archive files: delete if >90d (from original mtime)
        $archFiles = @scandir($archiveReal);
        if (is_array($archFiles)) {
            foreach ($archFiles as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $path = $archiveReal . DIRECTORY_SEPARATOR . $name;
                if (!is_file($path)) {
                    continue;
                }
                if (!map_retention_should_process_file($name, $extensions)) {
                    continue;
                }
                $mtime = @filemtime($path);
                if ($mtime === false) {
                    continue;
                }
                $age = $now - (int)$mtime;
                if ($age > $deleteSec) {
                    if (@unlink($path)) {
                        $stats['deleted'] += 1;
                    } else {
                        $stats['errors'] += 1;
                    }
                }
            }
        }

        return $stats;
    }
}

if (!function_exists('map_retention_run_all')) {
    /**
     * Run retention on all known map data dirs + local kml-exports fallback.
     *
     * @return array{archived:int,deleted:int,errors:int,dirs:int}
     */
    function map_retention_run_all(): array
    {
        $total = ['archived' => 0, 'deleted' => 0, 'errors' => 0, 'dirs' => 0];
        $dirs = map_data_dir_candidates(false);
        $fallback = __DIR__ . DIRECTORY_SEPARATOR . 'kml-exports';
        if (is_dir($fallback)) {
            $real = realpath($fallback);
            if ($real !== false && !in_array($real, $dirs, true)) {
                $dirs[] = $real;
            }
        }
        foreach ($dirs as $dir) {
            $stats = map_retention_run($dir, ['extensions' => ['kml', 'json']]);
            $total['archived'] += $stats['archived'];
            $total['deleted'] += $stats['deleted'];
            $total['errors'] += $stats['errors'];
            $total['dirs'] += 1;
        }
        return $total;
    }
}

if (!function_exists('map_retention_find_file')) {
    /**
     * Find a file in active data dirs, then in archive/ subfolders.
     *
     * @param list<string> $dirs
     */
    function map_retention_find_file(array $dirs, string $basename): ?string
    {
        $basename = basename(str_replace(['/', '\\'], '', $basename));
        if ($basename === '') {
            return null;
        }
        foreach ($dirs as $dir) {
            $try = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $basename;
            if (is_file($try)) {
                return $try;
            }
        }
        foreach ($dirs as $dir) {
            $try = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . $basename;
            if (is_file($try)) {
                return $try;
            }
        }
        return null;
    }
}
