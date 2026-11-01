<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Services\SimpleXlsxImporter;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncExcelVehicleDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qma:sync-excel-locations {--file=Berkas/DATA LOKASI TERBARU RKM KMH.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbarui lokasi, status site, tipe, dan spesifikasi unit alat berat (Heavy Equipment) dari berkas Excel DATA LOKASI TERBARU RKM KMH';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = base_path($this->option('file'));
        if (!file_exists($filePath)) {
            $this->error("Berkas Excel tidak ditemukan di: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Membaca berkas: {$filePath} ...");
        $rows = SimpleXlsxImporter::parse($filePath);
        $totalRows = count($rows);
        $this->info("Total baris terbaca: {$totalRows}");

        if ($totalRows < 2) {
            $this->warn('Berkas Excel kosong atau tidak memiliki baris data.');
            return self::SUCCESS;
        }

        $updatedCount = 0;
        $createdCount = 0;

        DB::beginTransaction();
        try {
            for ($i = 1; $i < $totalRows; $i++) {
                $r = $rows[$i];
                $code1 = trim($r[4] ?? '');
                if (!$code1) {
                    continue;
                }

                $cat = trim($r[2] ?? '') ?: 'SUPPORT';
                $type = trim($r[3] ?? '') ?: 'HEAVY EQUIPMENT';
                $statusRaw = strtoupper(trim($r[6] ?? ''));
                $jenis = strtoupper(trim($r[7] ?? ''));
                $lokasiSite = trim($r[8] ?? '');
                $plate = trim($r[9] ?? '');
                $assetNo = trim($r[10] ?? '');
                $brand = trim($r[11] ?? '');
                $model = trim($r[12] ?? '');
                $serial = trim($r[13] ?? '');
                $prodYear = trim($r[16] ?? '');
                $lokasiCurrent = trim($r[18] ?? '');
                $owner = trim($r[20] ?? '');

                // 1. Status Operasional
                $status = match ($statusRaw) {
                    'OPERASI', 'OPERATION' => 'Operation',
                    'BREAKDOWN' => 'Breakdown',
                    'STANDBY' => 'Standby',
                    'MAINTENANCE' => 'Maintenance',
                    default => 'Standby',
                };

                // 2. Lokasi & Status Area (Inside / Outside Site)
                if (strtoupper($lokasiCurrent) === 'SITE' || empty($lokasiCurrent)) {
                    $finalLocation = $lokasiSite ?: 'Site Kerinci';
                    $siteStatus = 'inside_site';
                } else {
                    $finalLocation = $lokasiCurrent;
                    $siteStatus = 'outside_site';
                }

                // 3. Timesheet Mobile Flag
                $isMobile = (str_contains($jenis, 'MOBILE') || $type === 'DUMP TRUCK' || $type === 'LIGHT TRUCK');

                // 4. Cari unit di database (cocokkan kode asli, kode tanpa spasi, atau nomor seri)
                $cleanCode = preg_replace('/[^a-zA-Z0-9]/', '', $code1);
                $vehicle = Vehicle::where('code', $code1)
                    ->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '-', '') = ?", [$cleanCode])
                    ->first();

                if (!$vehicle && !empty($serial) && $serial !== '?') {
                    $vehicle = Vehicle::where('serial_number', $serial)->first();
                }

                $cleanBrand = ($brand !== '?' && !empty($brand)) ? $brand : null;
                $cleanModel = ($model !== '?' && !empty($model)) ? $model : null;
                $cleanSerial = ($serial !== '?' && !empty($serial)) ? $serial : null;
                $yearInt = (is_numeric($prodYear) && (int) $prodYear > 1950 && (int) $prodYear < 2100) ? (int) $prodYear : null;
                $brandTypeCombined = trim(($cleanBrand ?? '') . ' ' . ($cleanModel ?? ''));

                if ($vehicle) {
                    // Update data unit yang sudah ada
                    $updatePayload = [
                        'category' => 'unit_support',
                        'unit_category' => $cat,
                        'unit_type' => $type,
                        'location' => $finalLocation,
                        'site_status' => $siteStatus,
                        'status' => $status,
                        'location_updated_at' => Carbon::now(),
                        'location_updated_by' => 'Import Berkas Excel Lokasi',
                        'location_note' => "Pembaruan dari berkas DATA LOKASI TERBARU RKM KMH ({$finalLocation})",
                    ];

                    if ($cleanBrand) {
                        $updatePayload['brand'] = $cleanBrand;
                    }
                    if ($cleanModel) {
                        $updatePayload['model_number'] = $cleanModel;
                    }
                    if (empty($vehicle->brand_type) && !empty($brandTypeCombined)) {
                        $updatePayload['brand_type'] = $brandTypeCombined;
                    }
                    if ($cleanSerial && empty($vehicle->serial_number)) {
                        $updatePayload['serial_number'] = $cleanSerial;
                    }
                    if ($yearInt && empty($vehicle->production_year)) {
                        $updatePayload['production_year'] = $yearInt;
                    }
                    if (!empty($plate) && empty($vehicle->license_plate)) {
                        $updatePayload['license_plate'] = $plate;
                    }
                    if (!empty($owner)) {
                        $updatePayload['ownership'] = $owner;
                    }
                    if ($isMobile) {
                        $updatePayload['is_timesheet_mobile'] = true;
                    }

                    $vehicle->update($updatePayload);
                    $updatedCount++;
                } else {
                    // Buat unit baru jika belum terdaftar
                    $vehicle = Vehicle::create([
                        'code' => $code1,
                        'category' => 'unit_support',
                        'unit_category' => $cat,
                        'unit_type' => $type,
                        'brand' => $cleanBrand,
                        'model_number' => $cleanModel,
                        'brand_type' => !empty($brandTypeCombined) ? $brandTypeCombined : $type,
                        'serial_number' => $cleanSerial,
                        'license_plate' => $plate ?: null,
                        'production_year' => $yearInt,
                        'location' => $finalLocation,
                        'site_status' => $siteStatus,
                        'status' => $status,
                        'ownership' => $owner ?: 'PT Kerinci Merangin Hidro',
                        'is_timesheet_mobile' => $isMobile,
                        'location_updated_at' => Carbon::now(),
                        'location_updated_by' => 'Import Berkas Excel Lokasi',
                        'location_note' => "Pendaftaran baru dari berkas DATA LOKASI TERBARU RKM KMH",
                    ]);
                    $createdCount++;
                }

                // Catat riwayat lokasi unit
                VehicleLocationHistory::create([
                    'vehicle_id' => $vehicle->id,
                    'site_status' => $siteStatus,
                    'location' => $finalLocation,
                    'note' => "Sinkronisasi Berkas Excel: Status {$status} di {$finalLocation}",
                    'source' => 'import',
                    'user_id' => null,
                    'user_name' => 'Import Berkas Excel Lokasi',
                    'recorded_at' => Carbon::now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal melakukan sinkronisasi: " . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Sinkronisasi Selesai!");
        $this->info("- Unit diperbarui: {$updatedCount}");
        $this->info("- Unit baru dibuat: {$createdCount}");

        return self::SUCCESS;
    }
}
