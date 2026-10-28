<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel system_settings untuk menyimpan konfigurasi sistem (seperti retensi auto-delete sampah)
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // Nilai bawaan retensi otomatis tong sampah: 30 hari
            DB::table('system_settings')->insertOrIgnore([
                'key' => 'trash_retention_days',
                'value' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Tambahkan kolom deleted_at (SoftDeletes) ke seluruh tabel operasional dan master
        $tables = [
            'users',
            'employees',
            'employee_simpers',
            'simper_types',
            'vehicles',
            'vehicle_activities',
            'unit_transfers',
            'unit_support_timesheets',
            'p2h_templates',
            'p2h_template_items',
            'p2h_records',
            'p2h_record_items',
            'gates',
            'security_guards',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'employees',
            'employee_simpers',
            'simper_types',
            'vehicles',
            'vehicle_activities',
            'unit_transfers',
            'unit_support_timesheets',
            'p2h_templates',
            'p2h_template_items',
            'p2h_records',
            'p2h_record_items',
            'gates',
            'security_guards',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }

        Schema::dropIfExists('system_settings');
    }
};
