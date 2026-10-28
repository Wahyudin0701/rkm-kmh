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
        // Pastikan seluruh user dengan role employee memiliki profil data master Employee
        $users = User::withoutGlobalScopes()->where('role', 'employee')
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'employee'))
            ->get();

        foreach ($users as $user) {
            $user->getOrCreateEmployee();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu menghapus data employee karena aman untuk backwards compatibility
    }
};
