<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminConfig = config('auth.superadmin');
        if ($superAdminConfig && !empty($superAdminConfig['username']) && !empty($superAdminConfig['password'])) {
            $superAdmin = User::updateOrCreate(
                ['username' => $superAdminConfig['username']],
                [
                    'name' => $superAdminConfig['name'] ?? 'Super Administrator',
                    'email' => $superAdminConfig['email'] ?? 'superadmin@kmh.co.id',
                    'password' => Hash::make($superAdminConfig['password']),
                    'role' => 'super_admin',
                ]
            );

            if (!$superAdmin->hasRole('super_admin')) {
                $superAdmin->assignRole('super_admin');
            }
        }
    }
}
