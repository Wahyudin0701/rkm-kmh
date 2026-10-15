<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Buat akun user login otomatis untuk karyawan yang sudah ada di sistem
        $employees = Employee::whereNull('user_id')->get();
        foreach ($employees as $emp) {
            $cleanNik = strtolower(str_replace(['-', ' '], '', $emp->nik));
            $user = User::firstOrCreate(
                ['username' => $emp->nik],
                [
                    'name' => $emp->name,
                    'email' => $cleanNik . '@kmh.co.id',
                    'password' => Hash::make('password'),
                    'role' => 'employee',
                ]
            );

            $emp->update(['user_id' => $user->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
