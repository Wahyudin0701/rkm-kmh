<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $guardUsers = User::where('role', 'gate_officer')->get();
        foreach ($guardUsers as $user) {
            $emp = Employee::where('user_id', $user->id)->orWhere('nik', $user->username)->first();
            if ($emp) {
                $emp->update([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'unit_kerja' => $emp->unit_kerja ?: 'Private Guard',
                    'division' => ($emp->division && $emp->division !== '-') ? $emp->division : 'Private Guard',
                    'position' => ($emp->position && $emp->position !== '-') ? $emp->position : 'Anggota',
                    'employee_type' => $emp->employee_type ?: 'Karyawan',
                ]);
            } else {
                Employee::create([
                    'user_id' => $user->id,
                    'nik' => $user->username,
                    'name' => $user->name,
                    'photo_path' => $user->photo_path,
                    'employee_type' => 'Karyawan',
                    'unit_kerja' => 'Private Guard',
                    'division' => 'Private Guard',
                    'position' => 'Anggota',
                    'is_operator' => false,
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
