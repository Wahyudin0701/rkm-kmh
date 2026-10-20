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
        Schema::create('unit_support_timesheets', function (Blueprint $table) {
            $table->id();
            $table->string('timesheet_no', 60)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('operator_id')->constrained('employees')->restrictOnDelete();
            $table->date('date');
            $table->string('shift', 30)->default('Shift 1 (Siang)'); // Shift 1 (Siang), Shift 2 (Malam), Non-Shift
            $table->string('meter_type', 10)->default('HM'); // 'HM' (Hour Meter) atau 'KM' (Odometer)
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('start_meter', 12, 2);
            $table->decimal('end_meter', 12, 2);
            $table->decimal('operating_hours', 10, 2); // end_meter - start_meter
            $table->decimal('standby_hours', 10, 2)->default(0);
            $table->decimal('breakdown_hours', 10, 2)->default(0);
            $table->string('location', 255);
            $table->string('activity_type', 100); // Galian, Timbunan, Loading, Hauling, Lifting, dll.
            $table->text('task_description')->nullable();
            $table->decimal('fuel_liters', 10, 2)->nullable();
            $table->string('status', 30)->default('Operation'); // Operation, Standby, Breakdown, Maintenance
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Index performa query & pelaporan
            $table->index(['date', 'shift']);
            $table->index(['vehicle_id', 'date']);
            $table->index(['operator_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_support_timesheets');
    }
};
