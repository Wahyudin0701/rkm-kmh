<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class CreateBackupCommand extends Command
{
    /**
     * Nama dan signature perintah CLI.
     */
    protected $signature = 'backup:create 
                            {--scope=semua : Cakupan backup: semua atau pilihan}
                            {--tables= : Daftar nama tabel dipisah koma (jika scope=pilihan)}
                            {--filename= : Nama kustom untuk file .kmh}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Buat backup terenkripsi (.kmh) untuk sistem RKM-KMH';

    public function handle(DatabaseBackupService $service): int
    {
        $scope = strtolower($this->option('scope') ?: 'semua');
        $customName = $this->option('filename');
        $tablesOption = $this->option('tables');

        $selectedTables = null;
        $includeMedia = false;

        if ($scope === 'semua') {
            $includeMedia = true;
            $this->info("Memulai pembuatan [Backup Semua] sistem RKM-KMH (.kmh)...");
        } else {
            if ($tablesOption) {
                $selectedTables = array_map('trim', explode(',', $tablesOption));
            } else {
                $selectedTables = $service->getAllTableNames();
            }
            $this->info("Memulai pembuatan [Pilih Backup] (" . count($selectedTables) . " tabel) (.kmh)...");
        }

        try {
            $startTime = microtime(true);
            $filename = $service->createKmhBackup($selectedTables, $includeMedia, $customName);
            $duration = round(microtime(true) - $startTime, 2);

            $fullPath = $service->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
            $size = file_exists($fullPath) ? filesize($fullPath) : 0;
            $sizeHuman = FileSizeHelper::format($size);

            $this->newLine();
            $this->info("✓ File backup .kmh berhasil dibuat dan terenkripsi!");
            $this->line("  File   : <comment>{$filename}</comment>");
            $this->line("  Lokasi : {$fullPath}");
            $this->line("  Ukuran : {$sizeHuman}");
            $this->line("  Waktu  : {$duration} detik");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal membuat backup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

class FileSizeHelper
{
    public static function format(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
