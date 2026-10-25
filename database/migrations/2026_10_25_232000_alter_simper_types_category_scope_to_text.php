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
        Schema::table('simper_types', function (Blueprint $table) {
            try {
                $table->dropIndex('simper_types_category_scope_index');
            } catch (\Throwable $e) {
                // Ignore jika indeks tidak ada
            }
            $table->text('category_scope')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simper_types', function (Blueprint $table) {
            $table->string('category_scope', 50)->change();
            $table->index('category_scope');
        });
    }
};
