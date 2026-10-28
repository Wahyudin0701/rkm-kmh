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
                            {--type=database : Jenis backup: database atau full}
                            {--filename= : Nama kustom untuk file backup}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Buat backup database MySQL atau Full Backup (+ media upload) sistem RKM-KMH';

    public function handle(DatabaseBackupService $service): int
    {
        $type = strtolower($this->option('type') ?: 'database');
        $customName = $this->option('filename');

        if (!in_array($type, ['database', 'full'])) {
            $this->error("Pilihan --type tidak valid. Gunakan 'database' atau 'full'.");
            return Command::FAILURE;
        }

        $this->info("Memulai pembuatan backup sistem RKM-KMH [Tipe: " . strtoupper($type) . "]...");

        try {
            $startTime = microtime(true);

            if ($type === 'full') {
                $filename = $service->createFullBackup($customName);
            } else {
                $filename = $service->createDatabaseBackup($customName);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $fullPath = $service->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
            $sizeHuman = FileSizeHelper::format(file_exists($fullPath) ? filesize($fullPath) : 0);

            $this->newLine();
            $this->info("✓ Backup berhasil dibuat!");
            $this->line("  File : <comment>{$filename}</comment>");
            $this->line("  Lokasi : {$fullPath}");
            $this->line("  Ukuran : {$sizeHuman}");
            $this->line("  Waktu : {$duration} detik");

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
