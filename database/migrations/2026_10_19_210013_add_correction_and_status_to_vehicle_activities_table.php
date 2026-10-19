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
        Schema::table('vehicle_activities', function (Blueprint $table) {
            $table->boolean('is_corrected')->default(false)->after('rejection_note');
            $table->decimal('original_current_km', 12, 2)->nullable()->after('is_corrected');
            $table->text('correction_reason')->nullable()->after('original_current_km');
            $table->foreignId('corrected_by_user_id')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('corrected_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_activities', function (Blueprint $table) {
            $table->dropForeign(['corrected_by_user_id']);
            $table->dropColumn([
                'is_corrected',
                'original_current_km',
                'correction_reason',
                'corrected_by_user_id',
                'corrected_at',
            ]);
        });
    }
};
