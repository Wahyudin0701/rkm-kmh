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
        Schema::create('vehicle_activities', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 60)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('gate_id')->constrained('gates')->restrictOnDelete();
            $table->string('direction', 10); // 'in', 'out'
            $table->decimal('previous_km', 12, 2);
            $table->decimal('current_km', 12, 2);
            $table->decimal('distance', 12, 2); // current_km - previous_km
            $table->text('purpose');
            $table->timestamp('recorded_at');
            $table->foreignId('security_guard_id')->nullable()->constrained('security_guards')->nullOnDelete();
            $table->string('validation_status', 20)->default('pending'); // 'pending', 'approved', 'rejected'
            $table->text('rejection_note')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes untuk percepatan performa filter log & antrean gerbang
            $table->index(['gate_id', 'validation_status']);
            $table->index(['vehicle_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_activities');
    }
};
