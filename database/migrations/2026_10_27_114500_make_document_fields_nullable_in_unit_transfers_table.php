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
        Schema::table('unit_transfers', function (Blueprint $table) {
            $table->string('document_number', 255)->nullable()->change();
            $table->string('document_title', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_transfers', function (Blueprint $table) {
            $table->string('document_number', 255)->nullable(false)->change();
            $table->string('document_title', 255)->nullable(false)->change();
        });
    }
};
