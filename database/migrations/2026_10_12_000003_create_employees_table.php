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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->unique();
            $table->string('name', 100);
            $table->string('unit_kerja', 100)->nullable(); // Pembangkit, Bendungan, Teknik, Umum
            $table->string('division', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->boolean('is_operator')->default(false);
            $table->date('simper_expired_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
