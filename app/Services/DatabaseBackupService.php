<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    public const KMH_MAGIC_HEADER = "KMH_ENCRYPTED_BACKUP_V1\n";

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
     * Dapatkan kunci enkripsi 256-bit berbasis APP_KEY sistem.
     * Hanya sistem ini yang bisa mendekripsi dan membaca konten file .kmh.
     */
    protected function getEncryptionKey(): string
    {
        $appKey = config('app.key');
        if (str_starts_with((string)$appKey, 'base64:')) {
            $appKey = base64_decode(substr((string)$appKey, 7));
        }
        return hash('sha256', (string)$appKey . '_RKM_KMH_ENCRYPTION_SECRET', true);
    }

    /**
     * Enkripsi payload mentah ke dalam format kontainer aman .kmh dengan header magic bytes & HMAC.
     */
    public function encryptToKmh(string $rawContent): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($rawContent, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);

        return self::KMH_MAGIC_HEADER . $iv . $hmac . $ciphertext;
    }

    /**
     * Dekripsi file .kmh kembali ke konten arsip aslinya setelah verifikasi HMAC.
     */
    public function decryptFromKmh(string $kmhContent): string
    {
        $headerLen = strlen(self::KMH_MAGIC_HEADER);
        if (substr($kmhContent, 0, $headerLen) !== self::KMH_MAGIC_HEADER) {
            throw new RuntimeException("File ini bukan format arsip .kmh yang sah atau file rusak.");
        }

        $iv = substr($kmhContent, $headerLen, 16);
        $hmac = substr($kmhContent, $headerLen + 16, 32);
        $ciphertext = substr($kmhContent, $headerLen + 48);

        $key = $this->getEncryptionKey();
        $calculatedHmac = hash_hmac('sha256', $ciphertext, $key, true);

        if (!hash_equals($hmac, $calculatedHmac)) {
            throw new RuntimeException("Gagal membuka file .kmh: Kunci enkripsi tidak cocok atau file telah dimodifikasi oleh sistem luar.");
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new RuntimeException("Gagal mendekripsi payload arsip .kmh.");
        }

        return $decrypted;
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
     *     created_at_human: string,
     *     is_kmh: bool
     * }>
     */
    public function listBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['kmh', 'sql', 'zip', 'gz'])) {
                continue;
            }

            $filename = $file->getFilename();
            $size = $file->getSize();
            $createdAt = Carbon::createFromTimestamp($file->getMTime());
            $isKmh = ($extension === 'kmh');

            $isFull = str_contains($filename, 'semua') || str_contains($filename, '_full_') || $extension === 'zip';
            $type = $isFull ? 'semua' : 'pilihan';
            $typeLabel = $isFull ? 'Backup Semua' : 'Pilih Backup';

            $backups[] = [
                'filename' => $filename,
                'path' => $file->getPathname(),
                'type' => $type,
                'type_label' => $typeLabel,
                'size_raw' => $size,
                'size_human' => $this->formatBytes($size),
                'created_at' => $createdAt,
                'created_at_human' => $createdAt->translatedFormat('d M Y, H:i') . ' WIB',
                'is_kmh' => $isKmh,
            ];
        }

        usort($backups, fn ($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return $backups;
    }

    /**
     * Buat backup terenkripsi .kmh (baik "Pilih Backup" tabel tertentu atau "Backup Semua" + media).
     *
     * @param array<int, string>|null $selectedTables Tabel yang dipilih (null = semua tabel)
     * @param bool $includeMedia Sertakan file media upload di storage/app/public
     * @param string|null $customFilename Nama file kustom opsional
     */
    public function createKmhBackup(?array $selectedTables = null, bool $includeMedia = false, ?string $customFilename = null): string
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive diperlukan untuk memaketkan arsip.');
        }

        $allTables = $this->getAllTableNames();
        $targetTables = (!empty($selectedTables)) ? array_values(array_intersect($allTables, $selectedTables)) : $allTables;

        if (empty($targetTables)) {
            throw new RuntimeException('Silakan pilih minimal satu tabel yang ingin dicadangkan.');
        }

        $database = config('database.connections.' . config('database.default') . '.database', 'rkm_kmh');
        $cleanDbName = str_replace([':', '/', '\\'], '_', (string) $database);
        $timestamp = now()->format('Ymd_His');

        $isFull = ($includeMedia || count($targetTables) === count($allTables));
        $prefix = $includeMedia ? 'backup_semua' : 'backup_pilihan';

        $filename = $customFilename ?: "{$prefix}_{$cleanDbName}_{$timestamp}.kmh";
        $filename = basename($filename);
        if (!str_ends_with($filename, '.kmh')) {
            $filename .= '.kmh';
        }

        // 1. Buat temporary SQL dump untuk tabel-tabel yang dipilih
        $tempSqlFile = storage_path('app/temp_dump_' . uniqid() . '.sql');
        $this->dumpTablesToSqlFile($targetTables, $tempSqlFile);

        // 2. Buat temporary ZIP container
        $tempZipFile = storage_path('app/temp_pkg_' . uniqid() . '.zip');
        $zip = new ZipArchive();
        if ($zip->open($tempZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempSqlFile);
            throw new RuntimeException('Gagal membuat paket arsip sementara.');
        }

        try {
            $zip->addFile($tempSqlFile, 'database.sql');

            // Tambahkan file media jika diminta (Backup Semua)
            if ($includeMedia) {
                $publicStorageDir = storage_path('app/public');
                if (File::isDirectory($publicStorageDir)) {
                    $files = File::allFiles($publicStorageDir);
                    foreach ($files as $file) {
                        $relativePath = 'storage/' . $file->getRelativePathname();
                        $zip->addFile($file->getRealPath(), str_replace('\\', '/', $relativePath));
                    }
                }
            }

            // Manifest informasi arsip .kmh
            $manifest = [
                'system' => 'RKM-KMH (PT Kerinci Merangin Hidro)',
                'format' => 'KMH_ENCRYPTED_V1',
                'created_at' => now()->toIso8601String(),
                'type' => $includeMedia ? 'semua' : 'pilihan',
                'database' => $database,
                'table_count' => count($targetTables),
                'tables' => $targetTables,
                'has_media' => $includeMedia,
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $zip->close();

            @unlink($tempSqlFile);

            // 3. Enkripsi seluruh paket ZIP menjadi file .kmh terenkripsi
            $rawZipContent = file_get_contents($tempZipFile);
            @unlink($tempZipFile);

            $encryptedKmh = $this->encryptToKmh($rawZipContent);
            $finalPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($finalPath, $encryptedKmh);

            return $filename;
        } catch (\Throwable $e) {
            if (is_file($tempSqlFile)) @unlink($tempSqlFile);
            if (is_file($tempZipFile)) @unlink($tempZipFile);
            throw new RuntimeException("Gagal membuat arsip terenkripsi .kmh: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Inspeksi dan baca metadata isi file arsip .kmh tanpa melakukan restore atau manipulasi database.
     * Mengembalikan manifest, daftar tabel terarsip, metrik data, dan status integritas enkripsi.
     *
     * @return array<string, mixed>
     */
    public function inspectKmhFile(string $filePath): array
    {
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        if (!File::exists($filePath)) {
            throw new RuntimeException("Berkas file backup tidak ditemukan: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $headerLen = strlen(self::KMH_MAGIC_HEADER);

        if (substr($content, 0, $headerLen) !== self::KMH_MAGIC_HEADER) {
            throw new RuntimeException("Berkas bukan format arsip .kmh yang sah atau file rusak (Magic Header tidak cocok).");
        }

        // Dekripsi payload (verifikasi HMAC & kunci enkripsi)
        $decryptedZip = $this->decryptFromKmh($content);

        // Buka zip sementara untuk membaca manifest dan statistik file
        $tempZip = tempnam(sys_get_temp_dir(), 'kmh_inspect_');
        file_put_contents($tempZip, $decryptedZip);

        try {
            $zip = new ZipArchive();
            if ($zip->open($tempZip) !== true) {
                throw new RuntimeException("Gagal membuka struktur arsip ZIP internal di dalam berkas .kmh.");
            }

            $manifestJson = $zip->getFromName('manifest.json');
            $manifest = $manifestJson ? json_decode($manifestJson, true) : [];

            $filesCount = $zip->numFiles;
            $mediaCount = 0;
            $sqlSize = 0;
            $mediaSamples = [];

            for ($i = 0; $i < $filesCount; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'] ?? '';
                $size = $stat['size'] ?? 0;

                if (str_starts_with($name, 'storage/')) {
                    $mediaCount++;
                    if (count($mediaSamples) < 5) {
                        $mediaSamples[] = basename($name);
                    }
                } elseif (str_ends_with($name, '.sql') || $name === 'database.sql') {
                    $sqlSize = $size;
                }
            }

            $zip->close();

            $tables = $manifest['tables'] ?? [];
            $tableCount = count($tables);
            $hasMedia = (bool) ($manifest['has_media'] ?? ($mediaCount > 0));
            $createdAt = isset($manifest['created_at']) ? Carbon::parse($manifest['created_at']) : Carbon::createFromTimestamp(filemtime($filePath));

            return [
                'is_valid' => true,
                'filename' => basename($filePath),
                'file_size' => filesize($filePath),
                'file_size_human' => $this->formatBytes(filesize($filePath)),
                'format' => $manifest['format'] ?? 'KMH_ENCRYPTED_V1',
                'system' => $manifest['system'] ?? 'PT Kerinci Merangin Hidro (RKM)',
                'created_at' => $createdAt->toIso8601String(),
                'created_at_human' => $createdAt->translatedFormat('d F Y, H:i:s') . ' WIB',
                'created_at_diff' => $createdAt->diffForHumans(),
                'type' => $manifest['type'] ?? ($hasMedia ? 'semua' : 'pilihan'),
                'type_label' => ($manifest['type'] ?? '') === 'semua' || $hasMedia
                    ? 'Backup Semua (Database + Media Foto)'
                    : 'Pilih Backup (Tabel Tertentu)',
                'database' => $manifest['database'] ?? config('database.connections.mysql.database', 'rkm_kmh'),
                'table_count' => $tableCount,
                'tables' => $tables,
                'has_media' => $hasMedia,
                'media_file_count' => $mediaCount,
                'media_samples' => $mediaSamples,
                'sql_size' => $sqlSize,
                'sql_size_human' => $this->formatBytes($sqlSize),
                'laravel_version' => $manifest['laravel_version'] ?? app()->version(),
                'php_version' => $manifest['php_version'] ?? PHP_VERSION,
                'encryption_cipher' => 'AES-256-CBC',
                'hmac_verification' => 'SHA-256 Terverifikasi Sah',
            ];
        } finally {
            if (is_file($tempZip)) {
                @unlink($tempZip);
            }
        }
    }

    /**
     * Pulihkan (Restore) data dari file .kmh (atau .sql / .zip sebagai fallback).
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

        $content = file_get_contents($filePath);
        $headerLen = strlen(self::KMH_MAGIC_HEADER);

        // Jika file berformat .kmh terenkripsi
        if (substr($content, 0, $headerLen) === self::KMH_MAGIC_HEADER) {
            $this->restoreFromKmhContent($content);
            return;
        }

        // Fallback untuk file .sql atau .zip warisan
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'zip') {
            $this->restoreFromZip($filePath);
        } elseif ($extension === 'sql') {
            $this->restoreFromSql($filePath);
        } else {
            throw new RuntimeException("Format file tidak didukung untuk pemulihan: .{$extension}");
        }
    }

    /**
     * Pulihkan dari konten terenkripsi .kmh.
     */
    protected function restoreFromKmhContent(string $kmhContent): void
    {
        $decryptedZipContent = $this->decryptFromKmh($kmhContent);

        $tempZipFile = storage_path('app/temp_restore_' . uniqid() . '.zip');
        file_put_contents($tempZipFile, $decryptedZipContent);

        try {
            $this->restoreFromZip($tempZipFile);
            @unlink($tempZipFile);
        } catch (\Throwable $e) {
            if (is_file($tempZipFile)) @unlink($tempZipFile);
            throw $e;
        }
    }

    /**
     * Pulihkan dari file arsip ZIP.
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

        $tempExtractDir = storage_path('app/temp_extract_' . uniqid());
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
                    throw new RuntimeException('File database.sql tidak ditemukan di dalam paket arsip.');
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

            File::deleteDirectory($tempExtractDir);
        } catch (\Throwable $e) {
            if (File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
            throw $e;
        }
    }

    /**
     * Eksekusi pemulihan dari file skrip SQL.
     */
    public function restoreFromSql(string $sqlFilePath): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();

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

                if (!$inQuotes && (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#'))) {
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
     * Kelompokkan tabel database ke dalam modul-modul fungsional untuk memudahkan pilihan backup.
     *
     * @return array<string, array{name: string, description: string, tables: array<int, string>}>
     */
    public function getTableGroups(): array
    {
        $allTables = $this->getAllTableNames();

        $groups = [
            'hse' => [
                'name' => 'K3 / HSE (Karyawan & SIMPER)',
                'description' => 'Data personil karyawan, master jenis SIMPER, dan izin mengemudi.',
                'tables' => array_values(array_intersect($allTables, [
                    'employees', 'simper_types', 'employee_simpers'
                ])),
            ],
            'qma' => [
                'name' => 'QMA (Kendaraan, Alat Berat & P2H)',
                'description' => 'Master unit LV/HE, checklist P2H, transfer unit, dan log aktivitas.',
                'tables' => array_values(array_intersect($allTables, [
                    'vehicles', 'vehicle_activities', 'vehicle_location_histories',
                    'unit_transfers', 'unit_support_timesheets', 'p2h_templates',
                    'p2h_template_items', 'p2h_records', 'p2h_record_items'
                ])),
            ],
            'pg' => [
                'name' => 'Private Guard (Satpam & Pos Gerbang)',
                'description' => 'Data personil satpam lapangan dan pos pemeriksaan gerbang.',
                'tables' => array_values(array_intersect($allTables, [
                    'gates', 'security_guards'
                ])),
            ],
            'rbac' => [
                'name' => 'Pengguna & Hak Akses (RBAC)',
                'description' => 'Akun pengguna, jabatan role, direct permissions, dan log aktivitas admin.',
                'tables' => array_values(array_intersect($allTables, [
                    'users', 'roles', 'permissions', 'model_has_roles', 
                    'model_has_permissions', 'role_has_permissions', 'admin_activity_logs'
                ])),
            ],
            'system' => [
                'name' => 'Sistem & Internal Database',
                'description' => 'Migrasi, sesi, cache, pengaturan retensi, dan antrean background jobs.',
                'tables' => array_values(array_intersect($allTables, [
                    'system_settings', 'migrations', 'sessions', 'cache', 'cache_locks', 
                    'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'
                ])),
            ],
        ];

        // Kelompokkan tabel lain jika ada tabel kustom tambahan di database
        $categorized = [];
        foreach ($groups as $g) {
            $categorized = array_merge($categorized, $g['tables']);
        }
        $otherTables = array_values(array_diff($allTables, $categorized));
        if (!empty($otherTables)) {
            $groups['other'] = [
                'name' => 'Tabel Lainnya',
                'description' => 'Tabel data tambahan sistem.',
                'tables' => $otherTables,
            ];
        }

        return $groups;
    }

    /**
     * Dump struktur dan data dari daftar tabel tertentu ke file SQL.
     *
     * @param array<int, string> $tables
     */
    protected function dumpTablesToSqlFile(array $tables, string $targetSqlPath): void
    {
        $handle = fopen($targetSqlPath, 'w');
        if (!$handle) {
            throw new RuntimeException("Gagal membuka file SQL sementara untuk penulisan: {$targetSqlPath}");
        }

        try {
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $database = config('database.connections.' . config('database.default') . '.database', 'database');
            $nowString = now()->format('Y-m-d H:i:s');

            fwrite($handle, "-- ==============================================================\n");
            fwrite($handle, "-- RKM-KMH SECURE DATABASE DUMP (PT Kerinci Merangin Hidro)\n");
            fwrite($handle, "-- Dibuat pada : {$nowString} WIB\n");
            fwrite($handle, "-- Database   : {$database}\n");
            fwrite($handle, "-- ==============================================================\n\n");

            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
            } else {
                fwrite($handle, "SET NAMES utf8mb4;\n");
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
                fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
                fwrite($handle, "SET time_zone = '+00:00';\n\n");
            }

            foreach ($tables as $tableName) {
                $this->dumpTableStructureAndData($handle, $pdo, $tableName, $driver);
            }

            fwrite($handle, "\n-- ==============================================================\n");
            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = ON;\n");
            } else {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            }
            fwrite($handle, "-- END OF DUMP\n");

            fclose($handle);
        } catch (\Throwable $e) {
            if (is_resource($handle)) fclose($handle);
            throw $e;
        }
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

        fwrite($handle, "-- Dumping Data untuk `{$tableName}`\n");

        $query = DB::table($tableName);

        // Pastikan data yang berada di tong sampah (soft-deleted) tidak bocor ke dalam data backup
        if (Schema::hasColumn($tableName, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

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
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $size = $bytes / pow(1024, $power);

        return round($size, $precision) . ' ' . $units[$power];
    }
}
