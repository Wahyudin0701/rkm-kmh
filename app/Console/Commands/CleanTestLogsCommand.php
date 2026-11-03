<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Models\UnitSupportTimesheet;
use App\Models\UnitTransfer;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use App\Models\VehicleLocationHistory;
use App\Services\TrashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanTestLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kmh:clean-test-logs 
                            {--force : Jalankan pembersihan tanpa konfirmasi interaktif}
                            {--include-he : Sertakan juga pembersihan timesheet alat berat & transfer unit}
                            {--empty-trash : Kosongkan seluruh item di Tong Sampah}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan seluruh riwayat transaksi uji coba (Timesheet LV, Riwayat Pos Gerbang, Log Aktivitas Admin) menjelang launching.';

    /**
     * Execute the console command.
     */
    public function handle(TrashService $trashService): int
    {
        $this->info('===============================================================');
        $this->info(' PEMBERSIHAN DATA UJI COBA (PRE-LAUNCH CLEAN SLATE) - RKM-KMH ');
        $this->info('===============================================================');
        $this->warn('Perintah ini akan membersihkan data riwayat pengujian:');
        $this->line(' - Riwayat Perjalanan LV / Perlintasan Gerbang (vehicle_activities)');
        $this->line(' - Riwayat Mutasi Lokasi Kendaraan (vehicle_location_histories)');
        $this->line(' - Log Riwayat Aktivitas Admin (admin_activity_logs)');

        if ($this->option('include-he')) {
            $this->line(' - Timesheet Kendaraan Berat / HE (unit_support_timesheets)');
            $this->line(' - Riwayat Mobilisasi / Transfer Unit (unit_transfers)');
        }

        if ($this->option('empty-trash')) {
            $this->line(' - Seluruh data di Tong Sampah (Recycle Bin)');
        }

        $this->info("\nMaster Kendaraan, Data Karyawan, SIMPER, Pos Gerbang & Akun Pengguna TETAP 100% UTUH.");

        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan pembersihan riwayat uji coba ini?', false)) {
                $this->warn('Operasi pembersihan dibatalkan.');
                return Command::SUCCESS;
            }
        }

        $this->output->write('Sedang membersihkan log riwayat uji coba... ');

        Schema::disableForeignKeyConstraints();

        try {
            // 1. Bersihkan Catatan Perjalanan LV & Perlintasan Gerbang
            $activityCount = DB::table('vehicle_activities')->count();
            DB::table('vehicle_activities')->truncate();

            // 2. Bersihkan Riwayat Lokasi Kendaraan
            $locationHistoryCount = DB::table('vehicle_location_histories')->count();
            DB::table('vehicle_location_histories')->truncate();

            // 3. Bersihkan Log Aktivitas Admin
            $adminLogCount = DB::table('admin_activity_logs')->count();
            DB::table('admin_activity_logs')->truncate();

            // 4. Bersihkan Timesheet HE & Transfer jika dipilih
            $heCount = 0;
            $transferCount = 0;
            if ($this->option('include-he')) {
                $heCount = DB::table('unit_support_timesheets')->count();
                DB::table('unit_support_timesheets')->truncate();

                $transferCount = DB::table('unit_transfers')->count();
                DB::table('unit_transfers')->truncate();
            }

            // 5. Reset status site kendaraan kembali ke default ('inside_site')
            DB::table('vehicles')->update([
                'site_status' => 'inside_site',
                'location_updated_at' => null,
                'location_updated_by' => null,
                'location_note' => null,
            ]);

            // 6. Kosongkan Tong Sampah jika diminta
            $trashCount = 0;
            if ($this->option('empty-trash')) {
                $trashCount = $trashService->emptyTrash();
            }

            Schema::enableForeignKeyConstraints();

            $this->info("SELESAI!\n");
            $this->table(['Kategori Log Dibersihkan', 'Jumlah Data Terhapus'], [
                ['Riwayat Perjalanan LV (vehicle_activities)', number_format($activityCount)],
                ['Riwayat Mutasi Lokasi (vehicle_location_histories)', number_format($locationHistoryCount)],
                ['Log Riwayat Aktivitas Admin (admin_activity_logs)', number_format($adminLogCount)],
                ['Timesheet Alat Berat / HE', $this->option('include-he') ? number_format($heCount) : 'Dilewati'],
                ['Riwayat Transfer Unit', $this->option('include-he') ? number_format($transferCount) : 'Dilewati'],
                ['Tong Sampah (Recycle Bin)', $this->option('empty-trash') ? number_format($trashCount) : 'Dilewati'],
            ]);

            $this->info("\nSistem sekarang bersih dan siap digunakan untuk peluncuran resmi (Launching)! 🎉");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->error("\nTerjadi kesalahan: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
