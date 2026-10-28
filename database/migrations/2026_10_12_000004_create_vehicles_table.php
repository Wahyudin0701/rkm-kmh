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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // LVCode / UnitCode
            $table->string('category', 30)->default('light_vehicle'); // 'light_vehicle', 'unit_support'
            $table->string('brand_type', 150); // MITSUBISHI - L300 - PICK UP
            $table->string('license_plate', 30)->nullable(); // Nopol
            $table->string('ownership', 150)->nullable(); // Teks bebas pemilik aset
            $table->string('division', 100)->nullable();
            $table->string('status', 50)->default('Operation'); // Operation, Standby, Breakdown
            $table->decimal('current_km', 12, 2)->default(0); // Odometer KM terstandarisasi
            $table->string('photo_path')->nullable();

            // Atribut spesifik Kendaraan Berat (Unit Support) - nullable
            $table->string('unit_category', 100)->nullable(); // Excavator, Crane, dll
            $table->string('unit_type', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->smallInteger('production_year')->nullable();
            $table->string('location', 150)->nullable();
            $table->decimal('fuel_consumption', 8, 2)->nullable();
            $table->string('silo_document', 150)->nullable();
            $table->string('stnk_document', 150)->nullable();
            $table->string('pajak_document', 150)->nullable();
            $table->string('kir_document', 150)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
