<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $guardUsers = DB::table('users')->where('role', 'gate_officer')->get();
        foreach ($guardUsers as $user) {
            $emp = DB::table('employees')
                ->where('user_id', $user->id)
                ->orWhere('nik', $user->username)
                ->first();

            if ($emp) {
                DB::table('employees')->where('id', $emp->id)->update([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'unit_kerja' => $emp->unit_kerja ?: 'Private Guard',
                    'division' => ($emp->division && $emp->division !== '-') ? $emp->division : 'Private Guard',
                    'position' => ($emp->position && $emp->position !== '-') ? $emp->position : 'Anggota',
                    'employee_type' => $emp->employee_type ?: 'Karyawan',
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('employees')->insert([
                    'user_id' => $user->id,
                    'nik' => $user->username,
                    'name' => $user->name,
                    'photo_path' => $user->photo_path ?? null,
                    'employee_type' => 'Karyawan',
                    'unit_kerja' => 'Private Guard',
                    'division' => 'Private Guard',
                    'position' => 'Anggota',
                    'is_operator' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive rollback needed
    }
};
