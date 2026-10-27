<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPerms = [
            'hse.employees.delete',
            'hse.simper_types.view',
            'hse.simper_types.create',
            'hse.simper_types.edit',
            'hse.simper_types.delete',
        ];

        foreach ($newPerms as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Daftarkan ke super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($newPerms);
        }

        // Daftarkan ke hse_admin
        $hseAdmin = Role::where('name', 'hse_admin')->first();
        if ($hseAdmin) {
            $hseAdmin->givePermissionTo($newPerms);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perms = [
            'hse.employees.delete',
            'hse.simper_types.view',
            'hse.simper_types.create',
            'hse.simper_types.edit',
            'hse.simper_types.delete',
        ];

        Permission::whereIn('name', $perms)->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
