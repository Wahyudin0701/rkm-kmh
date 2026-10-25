<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Master Jenis SIMPER
        Schema::create('simper_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('category_scope', 50)->default('all')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 2. Tabel Relasi Multi-SIMPER Personil (Karyawan & Sukon)
        Schema::create('employee_simpers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('simper_type_id')->constrained('simper_types')->cascadeOnDelete();
            $table->string('simper_number', 100)->nullable();
            $table->date('expired_at')->index();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'simper_type_id']);
        });

        // 3. Tambahkan kolom pendukung di tabel vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('is_timesheet_mobile')->default(false)->after('site_status')->index();
            $table->foreignId('required_simper_type_id')->nullable()->after('is_timesheet_mobile')->constrained('simper_types')->nullOnDelete();
        });

        // 4. Inisialisasi Data Master Default SIMPER
        $now = now();
        $defaultTypes = [
            [
                'code' => 'SIMPER-LV',
                'name' => 'SIMPER Light Vehicle (LV)',
                'category_scope' => 'light_vehicle',
                'description' => 'Izin mengemudi armada ringan proyek (Hilux, Triton, Avanza, dsb.)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SIMPER-DT',
                'name' => 'SIMPER Dump Truck / Heavy Mobile',
                'category_scope' => 'unit_support_mobile',
                'description' => 'Izin mengemudi armada berat bergerak (Dump Truck, Mixer Truck, Water Truck, Fuel Truck, Manhaul, dsb.)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SIMPER-HEAVY',
                'name' => 'SIMPER Alat Berat Operasional',
                'category_scope' => 'unit_support_heavy',
                'description' => 'Izin operasi alat berat konstruksi & tambang (Excavator, Dozer, Wheel Loader, Crane, Drilling Rig, dsb.)',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('simper_types')->insert($defaultTypes);

        // Ambil ID SIMPER default
        $lvTypeId = DB::table('simper_types')->where('code', 'SIMPER-LV')->value('id');
        $dtTypeId = DB::table('simper_types')->where('code', 'SIMPER-DT')->value('id');
        $heavyTypeId = DB::table('simper_types')->where('code', 'SIMPER-HEAVY')->value('id');

        // 5. Migrasi Data SIMPER Karyawan Lama agar 100% Tidak Ada yang Terganggu
        $existingEmployees = DB::table('employees')
            ->where('is_operator', 1)
            ->whereNotNull('simper_expired_at')
            ->get();

        foreach ($existingEmployees as $emp) {
            $pos = strtolower($emp->position ?? '');
            $empType = $emp->employee_type ?? 'Karyawan';

            // Karyawan LV selalu dapat SIMPER-LV
            if ($lvTypeId) {
                DB::table('employee_simpers')->insertOrIgnore([
                    'employee_id' => $emp->id,
                    'simper_type_id' => $lvTypeId,
                    'simper_number' => 'SMP-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                    'expired_at' => $emp->simper_expired_at,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Jika posisi terkait alat berat/truk/sukon, berikan juga SIMPER-DT & SIMPER-HEAVY
            if (
                $empType === 'Sukon' ||
                str_contains($pos, 'operator') ||
                str_contains($pos, 'driver') ||
                str_contains($pos, 'dump') ||
                str_contains($pos, 'truck') ||
                str_contains($pos, 'heavy')
            ) {
                if ($dtTypeId) {
                    DB::table('employee_simpers')->insertOrIgnore([
                        'employee_id' => $emp->id,
                        'simper_type_id' => $dtTypeId,
                        'simper_number' => 'SMP-DT-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                        'expired_at' => $emp->simper_expired_at,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if ($heavyTypeId) {
                    DB::table('employee_simpers')->insertOrIgnore([
                        'employee_id' => $emp->id,
                        'simper_type_id' => $heavyTypeId,
                        'simper_number' => 'SMP-HV-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                        'expired_at' => $emp->simper_expired_at,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['required_simper_type_id']);
            $table->dropColumn(['is_timesheet_mobile', 'required_simper_type_id']);
        });

        Schema::dropIfExists('employee_simpers');
        Schema::dropIfExists('simper_types');
    }
};
