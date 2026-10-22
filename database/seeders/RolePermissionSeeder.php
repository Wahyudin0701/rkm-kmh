<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cache permission Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Baca seluruh daftar permission dari konfigurasi rbac_permissions
        $modules = config('rbac_permissions.modules', []);
        $allPermissions = [];

        foreach ($modules as $moduleKey => $moduleData) {
            foreach ($moduleData['permissions'] as $permKey => $permMeta) {
                $allPermissions[] = $permKey;
                Permission::firstOrCreate([
                    'name' => $permKey,
                    'guard_name' => 'web',
                ]);
            }
        }

        // 3. Pemetaan Default Permission per Role
        $rolesWithPermissions = [
            'super_admin' => $allPermissions,
            'hse_admin' => [
                'hse.employees.view',
                'hse.employees.create',
                'hse.employees.edit',
            ],
            'qma_admin' => [
                'qma.vehicles.view',
                'qma.vehicles.create',
                'qma.vehicles.edit',
                'qma.vehicles.delete',
                'qma.logs.view',
                'qma.logs.edit_odometer',
                'qma.summary.view',
                'qma.p2h.view',
                'qma.p2h.approve',
                'qma.p2h.templates',
            ],
            'pg_admin' => [
                'pg.guards.view',
                'pg.guards.create',
                'pg.guards.edit',
                'pg.guards.toggle',
                'pg.guards.delete',
                'pg.logs.view',
            ],
            'gate_officer' => [
                'gate.inspect',
                'gate.approve',
                'gate.reject',
            ],
            'employee' => [
                'employee.dashboard',
                'employee.p2h',
                'employee.timesheet',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        // 4. Buat Akun Default Super Admin (Mengambil dari config/auth.php & .env)
        $superAdminConfig = config('auth.superadmin', [
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@kmh.co.id',
            'password' => 'password',
        ]);

        $superAdmin = User::firstOrCreate(
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

        // 5. Sinkronisasi Akun Existing ke Spatie Roles
        $existingUsers = User::all();
        foreach ($existingUsers as $user) {
            if ($user->role && in_array($user->role, array_keys($rolesWithPermissions))) {
                if (!$user->hasRole($user->role)) {
                    $user->assignRole($user->role);
                }
            }
        }
    }
}
