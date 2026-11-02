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
            $table->string('transporter_type', 30)->default('internal')->after('user_id');
            $table->string('transporter_origin_location', 255)->nullable()->after('transporter_vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_transfers', function (Blueprint $table) {
            $table->dropColumn(['transporter_type', 'transporter_origin_location']);
        });
    }
};
