<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    /**
     * Tampilkan matriks Role & Permission RBAC.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('id')->get();
        $modules = config('rbac_permissions.modules', []);

        return view('admin.roles.index', compact('roles', 'modules'));
    }

    /**
     * Buat Role baru di sistem.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Role dengan nama tersebut sudah ada.',
        ]);

        $role = Role::create([
            'name' => strtolower(trim(str_replace(' ', '_', $request->input('name')))),
            'guard_name' => 'web',
        ]);

        // Rekam ke Log Riwayat Aktivitas Super Admin
        $roleNameClean = ucwords(str_replace('_', ' ', $role->name));
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Role & Hak Akses',
            description: "Menambahkan peran hak akses baru: '{$roleNameClean}' ({$role->name}) ke dalam sistem.",
            targetType: 'Role',
            targetId: $role->id,
            targetName: $role->name,
            newValues: ['name' => $role->name]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$role->name}] berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui hak akses (permissions) untuk suatu role dari matriks.
     */
    public function updatePermissions(Request $request, int $roleId): RedirectResponse
    {
        $role = Role::findOrFail($roleId);

        // Jangan izinkan modifikasi permission super_admin karena otomatis memiliki seluruh akses
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Role Super Administrator selalu memiliki hak akses penuh (bypassed) dan tidak dapat dikurangi.');
        }

        $selectedPermissions = $request->input('permissions', []);
        $role->syncPermissions($selectedPermissions);

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Rekam ke Log Riwayat Aktivitas Super Admin
        $roleNameClean = ucwords(str_replace('_', ' ', $role->name));
        $count = count($selectedPermissions);
        AdminActivityLog::record(
            actionType: 'permission',
            module: 'Role & Hak Akses',
            description: "Memperbarui izin akses untuk peran '{$roleNameClean}' ({$count} izin akses aktif).",
            targetType: 'Role',
            targetId: $role->id,
            targetName: $role->name,
            newValues: ['permissions' => $selectedPermissions]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Matriks permission untuk role [{$role->name}] berhasil diperbarui.");
    }

    /**
     * Hapus role kustom dari sistem.
     */
    public function destroyRole(int $roleId): RedirectResponse
    {
        $role = Role::findOrFail($roleId);

        // Proteksi role inti sistem
        $coreRoles = ['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer'];
        if (in_array($role->name, $coreRoles)) {
            return redirect()->route('admin.roles.index')
                ->with('error', "Aksi Ditolak: Role inti sistem [{$role->name}] tidak dapat dihapus.");
        }

        $name = $role->name;
        $role->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Rekam ke Log Riwayat Aktivitas Super Admin
        $roleNameClean = ucwords(str_replace('_', ' ', $name));
        AdminActivityLog::record(
            actionType: 'delete',
            module: 'Role & Hak Akses',
            description: "Menghapus peran hak akses: '{$roleNameClean}' ({$name}) dari sistem.",
            targetType: 'Role',
            targetId: $roleId,
            targetName: $name
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$name}] berhasil dihapus dari sistem.");
    }
}
