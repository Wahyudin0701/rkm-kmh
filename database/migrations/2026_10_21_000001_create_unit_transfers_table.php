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
        Schema::create('unit_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 60)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->date('transfer_date');
            $table->string('from_location', 255);
            $table->string('from_site_status', 30)->default('inside_site');
            $table->string('to_location', 255);
            $table->string('to_site_status', 30)->default('inside_site');
            $table->text('description')->nullable();
            $table->string('reported_by', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Data Armada & Supir Pengangkut (Transporter)
            $table->foreignId('transporter_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('transporter_unit_name', 255)->nullable();
            $table->foreignId('transporter_driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('transporter_driver_name', 255)->nullable();
            $table->text('transporter_description')->nullable();

            // Dokumen Berita Acara Pemindahan (BAST)
            $table->string('document_number', 255);
            $table->string('document_title', 255);
            $table->string('document_file_path', 255)->nullable();
            $table->string('status', 30)->default('completed');

            $table->timestamps();

            // Index
            $table->index(['vehicle_id', 'transfer_date']);
            $table->index(['transfer_date', 'to_site_status']);
            $table->index('document_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_transfers');
    }
};
