<?php

namespace App\Console\Commands;

use App\Models\SimperType;
use App\Models\Vehicle;
use App\Services\SimpleXlsxImporter;
use Illuminate\Console\Command;

class SyncTimesheetMobileUnitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'units:sync-timesheet-mobile
                            {--file= : Path ke file Excel DATA LOKASI TERBARU RKM KMH.xlsx}
                            {--simper=SIMPER-DT : Kode SIMPER yang diwajibkan untuk unit ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi 140 unit heavy equipment mobile ke timesheet mobilisasi karyawan berdasarkan file Excel data lokasi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('Berkas/DATA LOKASI TERBARU RKM KMH.xlsx');

        if (!file_exists($filePath)) {
            $this->error("File Excel tidak ditemukan pada path: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Membaca data dari: {$filePath}");
        $rows = SimpleXlsxImporter::parse($filePath);

        if (empty($rows)) {
            $this->error("Gagal membaca atau file Excel kosong.");
            return Command::FAILURE;
        }

        $simperCode = $this->option('simper') ?: 'SIMPER-DT';
        $simperType = SimperType::where('code', $simperCode)->first();

        if (!$simperType) {
            $this->warn("SIMPER type dengan kode '{$simperCode}' belum ada. Menggunakan SIMPER pertama atau default.");
            $simperType = SimperType::first();
        }

        $simperTypeId = $simperType ? $simperType->id : null;
        $this->info("SIMPER yang diasosiasikan: " . ($simperType ? "{$simperType->name} ({$simperType->code})" : 'None'));

        $matchedCount = 0;
        $unmatchedCount = 0;
        $updatedList = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // Skip header
            }

            $jenis = isset($row[7]) ? trim((string) $row[7]) : '';
            if (strtoupper($jenis) !== 'TIMESHEET MOBILE') {
                continue;
            }

            $code1 = isset($row[4]) ? trim((string) $row[4]) : '';
            $code2 = isset($row[5]) ? trim((string) $row[5]) : '';
            $desc = isset($row[6]) ? trim((string) $row[6]) : '';
            $lokasi = isset($row[8]) ? trim((string) $row[8]) : '';

            $vehicle = Vehicle::where('code', $code1)
                ->orWhere('code', $code2)
                ->orWhere('license_plate', $code1)
                ->orWhere('license_plate', $code2)
                ->first();

            if ($vehicle) {
                $vehicle->is_timesheet_mobile = true;
                if ($simperTypeId) {
                    $vehicle->required_simper_type_id = $simperTypeId;
                }
                if ($lokasi && empty($vehicle->location)) {
                    $vehicle->location = $lokasi;
                }
                $vehicle->save();

                $matchedCount++;
                if (count($updatedList) < 15) {
                    $updatedList[] = [
                        'code' => $vehicle->code,
                        'category' => $vehicle->category,
                        'unit_type' => $vehicle->unit_type ?: $desc,
                        'required_simper' => $simperType ? $simperType->code : '-',
                    ];
                }
            } else {
                $unmatchedCount++;
                $this->warn("Tidak ditemukan unit di database: Code1='{$code1}', Code2='{$code2}'");
            }
        }

        $this->newLine();
        $this->table(['Unit Code', 'Kategori', 'Tipe Unit', 'Wajib SIMPER'], $updatedList);

        $this->info("Berhasil sinkronisasi {$matchedCount} unit heavy equipment mobile ke Timesheet!");
        if ($unmatchedCount > 0) {
            $this->warn("Terdapat {$unmatchedCount} unit yang tidak cocok di database.");
        }

        return Command::SUCCESS;
    }
}
