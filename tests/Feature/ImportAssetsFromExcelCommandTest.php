<?php

namespace Tests\Feature;

use App\Models\P2hTemplate;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAssetsFromExcelCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pastikan perintah console units:import-assets terdaftar di artisan.
     */
    public function test_import_assets_command_is_registered(): void
    {
        $this->artisan('units:import-assets --help')
            ->assertExitCode(0)
            ->expectsOutputToContain('Import aset Light Vehicle (LV) dan Heavy Equipment (HE)');
    }

    /**
     * Perintah gagal dengan pesan informatif jika file Excel tidak ditemukan.
     */
    public function test_import_assets_command_fails_gracefully_when_file_not_found(): void
    {
        $this->artisan('units:import-assets', [
            '--excel' => 'invalid/path/nonexistent.xlsx',
            '--photos' => 'invalid/path/photos',
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('File Excel tidak ditemukan');
    }

    /**
     * Mode dry-run berjalan sukses tanpa menulis perubahan ke database.
     */
    public function test_import_assets_command_dry_run_does_not_modify_database(): void
    {
        $excelPath = 'E:/rkm-kmh/Berkas/ASEET KMH.xlsx';
        $photosDir = 'E:/rkm-kmh/Berkas/FOTO ALAT';

        if (!file_exists($excelPath) || !is_dir($photosDir)) {
            $this->markTestSkipped('Berkas fisik Excel atau Foto tidak tersedia di environment ini.');
        }

        $initialCount = Vehicle::count();

        $this->artisan('units:import-assets', [
            '--excel' => $excelPath,
            '--photos' => $photosDir,
            '--dry-run' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('[DRY RUN MODE AKTIF]')
            ->expectsOutputToContain('Import selesai!');

        $this->assertEquals($initialCount, Vehicle::count());
    }
}
