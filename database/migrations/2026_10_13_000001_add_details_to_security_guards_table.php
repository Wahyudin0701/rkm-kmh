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
        Schema::table('security_guards', function (Blueprint $table) {
            $table->string('badge_id', 50)->nullable()->unique()->after('id');
            $table->unsignedTinyInteger('team')->default(1)->after('name'); // Regu 1, 2, 3
            $table->foreignId('gate_id')->nullable()->constrained('gates')->nullOnDelete()->after('team');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('gate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_guards', function (Blueprint $table) {
            $table->dropForeign(['gate_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['badge_id', 'team', 'gate_id', 'user_id']);
        });
    }
};
