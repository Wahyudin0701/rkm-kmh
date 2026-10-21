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
        Schema::table('unit_support_timesheets', function (Blueprint $table) {
            $table->decimal('end_meter', 12, 2)->nullable()->default(null)->change();
            $table->decimal('operating_hours', 10, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_support_timesheets', function (Blueprint $table) {
            $table->decimal('end_meter', 12, 2)->nullable(false)->change();
            $table->decimal('operating_hours', 10, 2)->nullable(false)->change();
        });
    }
};
