<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Dapatkan path direktori penyimpanan backup.
     */
    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * Dapatkan daftar seluruh file backup yang tersedia, diurutkan dari yang terbaru.
     *
     * @return array<int, array{
     *     filename: string,
     *     path: string,
     *     type: string,
     *     type_label: string,
     *     size_raw: int,
     *     size_human: string,
     *     created_at: Carbon,
     *     created_at_human: string
     * }>
     */
    public function listBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['sql', 'zip', 'gz'])) {
                continue;
            }

            $filename = $file->getFilename();
            $size = $file->getSize();
            $createdAt = Carbon::createFromTimestamp($file->getMTime());

            $isFull = str_ends_with($filename, '.zip') || str_contains($filename, '_full_');
            $type = $isFull ? 'full' : 'database';
            $typeLabel = $isFull ? 'Full Backup (+ File Media)' : 'Database SQL';

            $backups[] = [
                'filename' => $filename,
                'path' => $file->getPathname(),
                'type' => $type,
                'type_label' => $typeLabel,
                'size_raw' => $size,
                'size_human' => $this->formatBytes($size),
                'created_at' => $createdAt,
                'created_at_human' => $createdAt->translatedFormat('d M Y, H:i') . ' WIB',
            ];
        }

        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Buat backup database (MySQL / SQLite) ke dalam file .sql murni.
     * Menggunakan PDO stream generator mandiri tanpa ketergantungan mysqldump eksternal.
     */
    public function createDatabaseBackup(?string $customFilename = null): string
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $driver = DB::connection()->getDriverName();
        $database = config('database.connections.' . config('database.default') . '.database', 'database');
        $cleanDbName = str_replace([':', '/', '\\'], '_', (string) $database);
        $timestamp = now()->format('Ymd_His');
        $filename = $customFilename ?: "backup_db_{$cleanDbName}_{$timestamp}.sql";

        // Amankan nama file dari traversal
        $filename = basename($filename);
        if (!str_ends_with($filename, '.sql')) {
            $filename .= '.sql';
        }

        $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        $handle = fopen($fullPath, 'w');

        if (!$handle) {
            throw new RuntimeException("Gagal membuka file backup untuk penulisan: {$fullPath}");
        }

        try {
            $pdo = DB::connection()->getPdo();
            $nowString = now()->format('Y-m-d H:i:s');

            // Header SQL Standar
            fwrite($handle, "-- ==============================================================\n");
            fwrite($handle, "-- RKM-KMH DATABASE BACKUP (PT Kerinci Merangin Hidro)\n");
            fwrite($handle, "-- Dibuat pada   : {$nowString} WIB\n");
            fwrite($handle, "-- Driver       : {$driver}\n");
            fwrite($handle, "-- Database     : {$database}\n");
            fwrite($handle, "-- ==============================================================\n\n");

            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
            } else {
                fwrite($handle, "SET NAMES utf8mb4;\n");
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
                fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
                fwrite($handle, "SET time_zone = '+00:00';\n\n");
            }

            // Ambil daftar seluruh tabel aktif
            $tables = $this->getAllTableNames();

            foreach ($tables as $tableName) {
                $this->dumpTableStructureAndData($handle, $pdo, $tableName, $driver);
            }

            // Footer SQL
            fwrite($handle, "\n-- ==============================================================\n");
            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = ON;\n");
            } else {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            }
            fwrite($handle, "-- END OF RKM-KMH DATABASE BACKUP\n");
            fwrite($handle, "-- ==============================================================\n");

            fclose($handle);
            return $filename;
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
            throw new RuntimeException("Gagal membuat backup database: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Buat full backup (.zip) yang mencakup file database .sql dan seluruh file upload media (storage/app/public).
     */
    public function createFullBackup(?string $customFilename = null): string
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive tidak tersedia di server ini.');
        }

        $database = config('database.connections.' . config('database.default') . '.database', 'database');
        $cleanDbName = str_replace([':', '/', '\\'], '_', (string) $database);
        $timestamp = now()->format('Ymd_His');
        $zipFilename = $customFilename ?: "backup_full_{$cleanDbName}_{$timestamp}.zip";
        $zipFilename = basename($zipFilename);
        if (!str_ends_with($zipFilename, '.zip')) {
            $zipFilename .= '.zip';
        }

        $zipFullPath = $this->backupDir . DIRECTORY_SEPARATOR . $zipFilename;

        // 1. Buat temporary SQL dump terlebih dahulu
        $tempSqlName = "temp_db_{$timestamp}.sql";
        $this->createDatabaseBackup($tempSqlName);
        $tempSqlFullPath = $this->backupDir . DIRECTORY_SEPARATOR . $tempSqlName;

        $zip = new ZipArchive();
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            if (File::exists($tempSqlFullPath)) {
                File::delete($tempSqlFullPath);
            }
            throw new RuntimeException("Gagal membuat file ZIP di: {$zipFullPath}");
        }

        try {
            // Masukkan database SQL ke dalam root arsip ZIP
            $zip->addFile($tempSqlFullPath, 'database.sql');

            // Masukkan seluruh file media yang ada di storage/app/public (misal foto profil, foto kendaraan, simper)
            $publicStorageDir = storage_path('app/public');
            if (File::isDirectory($publicStorageDir)) {
                $files = File::allFiles($publicStorageDir);
                foreach ($files as $file) {
                    $relativePath = 'storage/' . $file->getRelativePathname();
                    $zip->addFile($file->getRealPath(), str_replace('\\', '/', $relativePath));
                }
            }

            // Tambahkan metadata ringkas manifest.json
            $manifest = [
                'system' => 'RKM-KMH (PT Kerinci Merangin Hidro)',
                'created_at' => now()->toIso8601String(),
                'type' => 'full',
                'database' => $database,
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

            $zip->close();

            // Hapus file temporary SQL
            if (File::exists($tempSqlFullPath)) {
                File::delete($tempSqlFullPath);
            }

            return $zipFilename;
        } catch (\Throwable $e) {
            $zip->close();
            if (File::exists($tempSqlFullPath)) {
                File::delete($tempSqlFullPath);
            }
            if (File::exists($zipFullPath)) {
                File::delete($zipFullPath);
            }
            throw new RuntimeException("Gagal membuat full backup ZIP: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pulihkan (Restore) database & aset dari file backup (.sql atau .zip).
     */
    public function restore(string $backupFilenameOrPath): void
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $filePath = File::exists($backupFilenameOrPath)
            ? $backupFilenameOrPath
            : $this->backupDir . DIRECTORY_SEPARATOR . basename($backupFilenameOrPath);

        if (!File::exists($filePath)) {
            throw new RuntimeException("File backup tidak ditemukan: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            $this->restoreFromZip($filePath);
        } elseif ($extension === 'sql') {
            $this->restoreFromSql($filePath);
        } elseif ($extension === 'gz') {
            $this->restoreFromGzip($filePath);
        } else {
            throw new RuntimeException("Format file tidak didukung untuk pemulihan: .{$extension}");
        }
    }

    /**
     * Pulihkan dari file SQL langsung.
     */
    public function restoreFromSql(string $sqlFilePath): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();

        // Nonaktifkan pemeriksaan foreign key untuk mencegah deadlock/relasi urutan
        if ($driver === 'sqlite') {
            $pdo->exec("PRAGMA foreign_keys = OFF;");
        } else {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
        }

        $handle = fopen($sqlFilePath, 'r');
        if (!$handle) {
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
            throw new RuntimeException("Gagal membaca file SQL: {$sqlFilePath}");
        }

        $currentQuery = '';
        $inQuotes = false;
        $quoteChar = '';
        $escaped = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                // Lewati komentar baris tunggal jika tidak sedang di dalam quote
                if (!$inQuotes && (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#'))) {
                    // Cek jika komentar multi baris
                    if (str_starts_with($trimmed, '/*') && !str_ends_with($trimmed, '*/')) {
                        while (($line = fgets($handle)) !== false) {
                            if (str_contains($line, '*/')) break;
                        }
                    }
                    continue;
                }

                $len = strlen($line);
                for ($i = 0; $i < $len; $i++) {
                    $char = $line[$i];

                    if ($escaped) {
                        $currentQuery .= $char;
                        $escaped = false;
                        continue;
                    }

                    if ($char === '\\') {
                        $escaped = true;
                        $currentQuery .= $char;
                        continue;
                    }

                    if ($inQuotes) {
                        if ($char === $quoteChar) {
                            $inQuotes = false;
                        }
                        $currentQuery .= $char;
                    } else {
                        if ($char === "'" || $char === '"' || $char === '`') {
                            $inQuotes = true;
                            $quoteChar = $char;
                            $currentQuery .= $char;
                        } elseif ($char === ';') {
                            // Akhir dari sebuah statement SQL
                            $statement = trim($currentQuery);
                            if ($statement !== '') {
                                $pdo->exec($statement);
                            }
                            $currentQuery = '';
                        } else {
                            $currentQuery .= $char;
                        }
                    }
                }
            }

            // Jalankan statement terakhir jika ada yang tersisa tanpa titik koma
            $finalStatement = trim($currentQuery);
            if ($finalStatement !== '') {
                $pdo->exec($finalStatement);
            }

            fclose($handle);
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
            throw new RuntimeException("Gagal mengeksekusi restore SQL: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pulihkan dari file full backup .zip (restore database.sql dan sinkronkan file media).
     */
    public function restoreFromZip(string $zipFilePath): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive tidak tersedia di server ini.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Gagal membuka file arsip ZIP: {$zipFilePath}");
        }

        $tempExtractDir = storage_path('app/temp_restore_' . uniqid());
        File::makeDirectory($tempExtractDir, 0755, true);

        try {
            $zip->extractTo($tempExtractDir);
            $zip->close();

            // 1. Eksekusi file SQL database
            $sqlFile = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (!File::exists($sqlFile)) {
                $foundSql = File::glob($tempExtractDir . '/*.sql');
                if (!empty($foundSql)) {
                    $sqlFile = $foundSql[0];
                } else {
                    throw new RuntimeException('File database.sql tidak ditemukan di dalam paket ZIP.');
                }
            }

            $this->restoreFromSql($sqlFile);

            // 2. Pulihkan file aset media ke storage/app/public jika ada
            $extractedStorageDir = $tempExtractDir . DIRECTORY_SEPARATOR . 'storage';
            $targetStorageDir = storage_path('app/public');

            if (File::isDirectory($extractedStorageDir)) {
                if (!File::isDirectory($targetStorageDir)) {
                    File::makeDirectory($targetStorageDir, 0755, true);
                }
                File::copyDirectory($extractedStorageDir, $targetStorageDir);
            }

            // Bersihkan temporary folder
            File::deleteDirectory($tempExtractDir);
        } catch (\Throwable $e) {
            if (File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
            throw $e;
        }
    }

    /**
     * Pulihkan dari file SQL terkompresi Gzip (.sql.gz).
     */
    protected function restoreFromGzip(string $gzFilePath): void
    {
        if (!function_exists('gzopen')) {
            throw new RuntimeException('Ekstensi PHP zlib tidak tersedia untuk dekompresi gzip.');
        }

        $tempSqlFile = storage_path('app/temp_restore_' . uniqid() . '.sql');
        $gz = gzopen($gzFilePath, 'rb');
        $out = fopen($tempSqlFile, 'wb');

        if (!$gz || !$out) {
            throw new RuntimeException("Gagal mengekstrak file gzip: {$gzFilePath}");
        }

        while (!gzeof($gz)) {
            fwrite($out, gzread($gz, 65536));
        }

        gzclose($gz);
        fclose($out);

        try {
            $this->restoreFromSql($tempSqlFile);
            File::delete($tempSqlFile);
        } catch (\Throwable $e) {
            if (File::exists($tempSqlFile)) {
                File::delete($tempSqlFile);
            }
            throw $e;
        }
    }

    /**
     * Hapus file backup tertentu dari penyimpanan.
     */
    public function deleteBackup(string $filename): bool
    {
        $filename = basename($filename);
        $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }

    /**
     * Dapatkan informasi statistik penyimpanan backup dan database sistem.
     *
     * @return array{
     *     total_backups: int,
     *     total_size_raw: int,
     *     total_size_human: string,
     *     database_name: string,
     *     table_count: int,
     *     estimated_db_size_human: string,
     *     last_backup: ?array
     * }
     */
    public function getSystemStats(): array
    {
        $backups = $this->listBackups();
        $totalSize = array_sum(array_column($backups, 'size_raw'));

        $driver = DB::connection()->getDriverName();
        $tableNames = $this->getAllTableNames();
        $tableCount = count($tableNames);

        $dbSizeBytes = 0;
        $databaseName = config('database.connections.' . config('database.default') . '.database', 'database');

        if ($driver === 'mysql') {
            $sizeResult = DB::selectOne("
                SELECT SUM(data_length + index_length) AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$databaseName]);
            $dbSizeBytes = (int) ($sizeResult->size ?? 0);
        }

        return [
            'total_backups' => count($backups),
            'total_size_raw' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'database_name' => (string) $databaseName,
            'table_count' => $tableCount,
            'estimated_db_size_human' => $this->formatBytes($dbSizeBytes),
            'last_backup' => $backups[0] ?? null,
        ];
    }

    /**
     * Dapatkan daftar seluruh nama tabel aktif di database.
     *
     * @return array<int, string>
     */
    public function getAllTableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return array_values(array_map(fn ($r) => $r->name, $rows));
        }

        // MySQL
        $database = config('database.connections.' . config('database.default') . '.database');
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tableKey = "Tables_in_{$database}";

        $names = [];
        foreach ($tables as $tRow) {
            $names[] = $tRow->$tableKey ?? current((array) $tRow);
        }

        return $names;
    }

    /**
     * Dump struktur dan data satu tabel database.
     */
    protected function dumpTableStructureAndData($handle, PDO $pdo, string $tableName, string $driver): void
    {
        fwrite($handle, "\n-- -------------------------------------------------------------\n");
        fwrite($handle, "-- Struktur Tabel untuk `{$tableName}`\n");
        fwrite($handle, "-- -------------------------------------------------------------\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

        if ($driver === 'sqlite') {
            $createResult = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$tableName]);
            $createTableSql = $createResult->sql ?? '';
        } else {
            $createResult = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
            $createSqlKey = 'Create Table';
            $createTableSql = $createResult->$createSqlKey ?? current((array) $createResult);
        }

        if ($createTableSql) {
            fwrite($handle, $createTableSql . ";\n\n");
        }

        // Dump Data Baris (chunk 200 baris untuk efisiensi RAM)
        fwrite($handle, "-- Dumping Data untuk `{$tableName}`\n");

        $query = DB::table($tableName);
        $totalRows = $query->count();

        if ($totalRows === 0) {
            fwrite($handle, "-- (Tabel kosong)\n\n");
            return;
        }

        $query->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $pdo, $tableName) {
            if ($rows->isEmpty()) return;

            $columns = array_keys((array) $rows->first());
            $escapedColumns = array_map(fn ($c) => "`{$c}`", $columns);
            $columnsStr = implode(', ', $escapedColumns);

            fwrite($handle, "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES\n");

            $rowCount = count($rows);
            $i = 0;

            foreach ($rows as $row) {
                $i++;
                $values = [];

                foreach ((array) $row as $val) {
                    if (is_null($val)) {
                        $values[] = 'NULL';
                    } elseif (is_int($val) || is_float($val)) {
                        $values[] = (string) $val;
                    } elseif (is_bool($val)) {
                        $values[] = $val ? '1' : '0';
                    } else {
                        $values[] = $pdo->quote((string) $val);
                    }
                }

                $line = '  (' . implode(', ', $values) . ')';
                $line .= ($i === $rowCount) ? ";\n" : ",\n";
                fwrite($handle, $line);
            }
        });

        fwrite($handle, "\n");
    }

    /**
     * Format byte ke satuan yang mudah dibaca (KB, MB, GB).
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $size = $bytes / pow(1024, $power);

        return round($size, $precision) . ' ' . $units[$power];
    }
}
