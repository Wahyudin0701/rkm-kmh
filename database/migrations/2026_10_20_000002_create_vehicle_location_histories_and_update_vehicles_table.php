<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan kolom status posisi di dalam/luar site pada tabel vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('site_status')->default('inside_site')->after('status');
            $table->timestamp('location_updated_at')->nullable()->after('location');
            $table->string('location_updated_by')->nullable()->after('location_updated_at');
            $table->text('location_note')->nullable()->after('location_updated_by');
        });

        // 2. Buat tabel riwayat perubahan posisi unit (audit trail log)
        Schema::create('vehicle_location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('site_status')->default('inside_site'); // 'inside_site' atau 'outside_site'
            $table->string('location'); // Detail lokasi penempatan
            $table->text('note')->nullable(); // Catatan pergerakan / keperluan
            $table->string('source')->default('manual'); // 'manual' (input user QMA) atau 'gate_crossing' (otomatis pos gerbang)
            $table->foreignId('vehicle_activity_id')->nullable()->constrained('vehicle_activities')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable(); // Snapshot nama pengubah posisi
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_location_histories');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'site_status',
                'location_updated_at',
                'location_updated_by',
                'location_note',
            ]);
        });
    }
};
