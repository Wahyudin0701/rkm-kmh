<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InspectKmhBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'kmh:inspect 
                            {file? : Nama file .kmh di folder backup atau path absolut ke file .kmh}';

    /**
     * The console command description.
     */
    protected $description = 'Inspeksi dan baca isi metadata file backup .kmh terenkripsi tanpa mengubah database';

    public function handle(DatabaseBackupService $service): int
    {
        $input = $this->argument('file');
        $backupDir = $service->getBackupDirectory();

        if (!$input) {
            $files = collect(File::glob($backupDir . '/*.kmh'))
                ->map(fn($f) => basename($f))
                ->values()
                ->all();

            if (empty($files)) {
                $this->warn("Tidak ada file .kmh di direktori backup ({$backupDir}).");
                $this->line("Gunakan: php artisan kmh:inspect /path/ke/file.kmh");
                return Command::FAILURE;
            }

            $input = $this->choice('Pilih file .kmh yang ingin diinspeksi:', $files);
        }

        // Tentukan path lengkap
        $targetPath = File::exists($input)
            ? $input
            : $backupDir . DIRECTORY_SEPARATOR . basename($input);

        if (!File::exists($targetPath)) {
            $this->error("Berkas .kmh tidak ditemukan pada: {$targetPath}");
            return Command::FAILURE;
        }

        $this->info("Sedang memeriksa & membaca arsip: " . basename($targetPath) . " ...");

        try {
            $data = $service->inspectKmhFile($targetPath);

            $this->newLine();
            $this->line("=====================================================================");
            $this->info(" HASIL INSPEKSI ARSIP KMH (RKM BACKUP READER)");
            $this->line("=====================================================================");

            $this->table(
                ['Parameter', 'Keterangan'],
                [
                    ['Nama File', $data['filename']],
                    ['Ukuran Berkas', $data['file_size_human'] . " ({$data['file_size']} bytes)"],
                    ['Format Arsip', $data['format']],
                    ['Sistem Asal', $data['system']],
                    ['Waktu Pembuatan', $data['created_at_human'] . " ({$data['created_at_diff']})"],
                    ['Tipe Backup', $data['type_label']],
                    ['Database Asal', $data['database']],
                    ['Enkripsi & Keamanan', $data['encryption_cipher'] . ' (' . $data['hmac_verification'] . ')'],
                    ['Ukuran SQL Database', $data['sql_size_human']],
                    ['File Media (storage/)', $data['has_media'] ? "{$data['media_file_count']} berkas media" : 'Tidak disertakan'],
                    ['Versi Environment', "Laravel {$data['laravel_version']} / PHP {$data['php_version']}"],
                ]
            );

            if (!empty($data['tables'])) {
                $this->newLine();
                $this->comment(" Daftar Tabel & Jumlah Baris Data (" . count($data['tables']) . " tabel):");
                $formattedTables = array_map(function ($t) use ($data) {
                    $rows = $data['table_details'][$t]['rows_count'] ?? 0;
                    return "{$t} ({$rows} baris)";
                }, $data['tables']);

                $chunks = array_chunk($formattedTables, 3);
                $rows = array_map(function ($chunk) {
                    return array_pad($chunk, 3, '');
                }, $chunks);
                $this->table(['Tabel & Baris', 'Tabel & Baris', 'Tabel & Baris'], $rows);
            }

            if (!empty($data['media_samples'])) {
                $this->newLine();
                $this->comment(" Sampel Berkas Media:");
                foreach ($data['media_samples'] as $sample) {
                    $this->line("   - {$sample}");
                }
            }

            $this->newLine();
            $this->info("✓ Berkas sah, terenkripsi valid, dan siap dipulihkan jika diperlukan.");
            $this->line("=====================================================================");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal membaca arsip .kmh: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
