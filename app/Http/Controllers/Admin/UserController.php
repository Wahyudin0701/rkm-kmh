<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna sistem dengan filter & pencarian.
     */
    public function index(Request $request): View
    {
        $query = User::with(['roles', 'gate'])->latest();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleFilter = $request->input('role');
            $query->where(function ($q) use ($roleFilter) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', $roleFilter))
                    ->orWhere('role', $roleFilter);
            });
        }

        $users = $query->with('permissions')->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();
        $modules = config('rbac_permissions.modules', []);

        return view('admin.users.index', compact('users', 'roles', 'gates', 'modules'));
    }

    /**
     * Simpan pengguna baru ke sistem beserta penugasan Spatie Role.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'gate_id' => ['nullable', 'exists:gates,id'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username tersebut sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.exists' => 'Role yang dipilih tidak valid.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], // Simpan ke kolom legacy untuk backwards-compat
            'gate_id' => $validated['gate_id'] ?? null,
        ]);

        // Assign Spatie Role
        $user->assignRole($validated['role']);

        // Sync Direct Permissions (jika ada izin khusus tambahan)
        if ($request->has('direct_permissions')) {
            $user->syncPermissions($request->input('direct_permissions', []));
        }

        // Reset permission cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun pengguna [{$user->name}] berhasil dibuat dengan role [{$validated['role']}].");
    }

    /**
     * Perbarui data pengguna & perbarui Role Spatie serta Direct Permissions.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'gate_id' => ['nullable', 'exists:gates,id'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username tersebut sudah digunakan oleh akun lain.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal 6 karakter jika ingin diubah.',
            'role.required' => 'Role wajib dipilih.',
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->gate_id = $validated['gate_id'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync Spatie Role
        $user->syncRoles([$validated['role']]);

        // Sync Direct Permissions
        $user->syncPermissions($request->input('direct_permissions', []));

        // Reset permission cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.users.index')
            ->with('success', "Data pengguna [{$user->name}] berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna dari sistem.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Proteksi: jangan hapus akun sendiri yang sedang login
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Aksi Ditolak: Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
        }

        // Proteksi: akun superadmin utama tidak boleh dihapus
        if ($user->username === 'superadmin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Aksi Ditolak: Akun Super Administrator utama sistem tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun pengguna [{$name}] berhasil dihapus dari sistem.");
    }
}
