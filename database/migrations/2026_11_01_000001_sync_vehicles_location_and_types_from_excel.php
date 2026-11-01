<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $filePath = base_path('Berkas/DATA LOKASI TERBARU RKM KMH.xlsx');
        if (file_exists($filePath)) {
            Artisan::call('qma:sync-excel-locations');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
