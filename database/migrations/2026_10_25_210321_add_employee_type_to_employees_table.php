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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_type', 50)->default('Karyawan')->after('name')->index();
        });

        DB::table('employees')->whereNull('employee_type')->update(['employee_type' => 'Karyawan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employee_type']);
            $table->dropColumn('employee_type');
        });
    }
};
