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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('filename');
            $table->string('scope')->default('semua'); // 'semua' atau 'pilihan'
            $table->string('scope_label');
            $table->integer('table_count')->default(0);
            $table->json('tables_included')->nullable();
            $table->boolean('include_media')->default(false);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_size_human')->nullable();
            $table->string('status')->default('Berhasil Diunduh');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
