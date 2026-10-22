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
        $superAdminConfig = config('auth.superadmin', [
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@kmh.co.id',
            'password' => 'password',
        ]);

        $superAdmin = User::updateOrCreate(
            ['username' => $superAdminConfig['username']],
            [
                'name' => $superAdminConfig['name'],
                'email' => $superAdminConfig['email'],
                'password' => Hash::make($superAdminConfig['password']),
                'role' => 'super_admin',
            ]
        );

        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }
    }
}
