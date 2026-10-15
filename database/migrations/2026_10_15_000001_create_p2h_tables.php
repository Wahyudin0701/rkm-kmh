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
        // 1. Tabel Master Template P2H (28 Kategori Unit Support)
        Schema::create('p2h_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // Checksheet Code, misal: EXD4RP
            $table->string('name', 100); // Kategori, misal: Excavator
            $table->string('file_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Butir Pertanyaan / Item Cek per Template
        Schema::create('p2h_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('p2h_templates')->cascadeOnDelete();
            $table->string('section', 100); // Start, Sebelum Mesin Hidup, Setelah Mesin Menyala, dll
            $table->string('title', 255); // Pertanyaan / Butir Pemeriksaan
            $table->string('question_type', 50); // choice, datetime, number, text
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'sequence']);
        });

        // 3. Tambahkan Foreign Key p2h_template_id pada tabel vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('p2h_template_id')->nullable()->after('category')->constrained('p2h_templates')->nullOnDelete();
        });

        // 4. Tabel Transaksi / Riwayat P2H
        Schema::create('p2h_records', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no', 60)->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('template_id')->constrained('p2h_templates')->restrictOnDelete();
            $table->string('shift', 30); // Shift 1, Shift 2
            $table->decimal('start_hm', 12, 2);
            $table->decimal('start_km', 12, 2)->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('stop_time')->nullable();
            $table->string('summary_condition', 30)->default('good'); // good, has_defect
            $table->integer('defect_count')->default(0);
            $table->text('general_notes')->nullable();
            $table->string('validation_status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
            $table->text('validation_notes')->nullable();
            $table->dateTime('inspected_at');
            $table->timestamps();

            $table->index(['vehicle_id', 'inspected_at']);
            $table->index(['employee_id', 'inspected_at']);
            $table->index(['validation_status']);
        });

        // 5. Tabel Hasil Pengecekan Butir Item P2H
        Schema::create('p2h_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2h_record_id')->constrained('p2h_records')->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('p2h_template_items')->nullOnDelete();
            $table->string('section', 100);
            $table->string('title', 255);
            $table->string('question_type', 50);
            $table->string('condition_status', 20)->nullable(); // 'baik', 'rusak'
            $table->text('answer_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['p2h_record_id', 'condition_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2h_record_items');
        Schema::dropIfExists('p2h_records');

        if (Schema::hasColumn('vehicles', 'p2h_template_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropForeign(['p2h_template_id']);
                $table->dropColumn('p2h_template_id');
            });
        }

        Schema::dropIfExists('p2h_template_items');
        Schema::dropIfExists('p2h_templates');
    }
};
