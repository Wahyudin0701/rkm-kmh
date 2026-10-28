<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\User;
use App\Services\ImageCompressionService;
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
        $query = User::with(['roles', 'gate', 'employee']);

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

        // Pengurutan data pengguna
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->latest('created_at'),
        };

        $users = $query->with(['permissions', 'roles', 'employee'])->paginate(15)->withQueryString();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $rolePermissionsMap = $roles->mapWithKeys(function ($r) {
            return [$r->name => $r->permissions->pluck('name')->toArray()];
        });
        $gates = Gate::where('is_active', true)->orderBy('name')->get();
        $modules = config('rbac_permissions.modules', []);

        $distinctUnits = Employee::query()->whereNotNull('unit_kerja')->where('unit_kerja', '!=', '')->distinct()->pluck('unit_kerja')->toArray();
        $defaultUnits = ['Pembangkit', 'Bendungan', 'Teknik', 'Umum', 'Logistik', 'HSE', 'QMA'];
        $unitKerjaList = array_values(array_unique(array_filter(array_merge($defaultUnits, $distinctUnits))));

        return view('admin.users.index', compact('users', 'roles', 'gates', 'modules', 'rolePermissionsMap', 'unitKerjaList'));
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
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'employee_type' => ['nullable', 'string', 'in:karyawan,sukon,Karyawan,Sukon'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'is_operator' => ['nullable', 'in:0,1'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'NIK wajib diisi.',
            'username.unique' => 'NIK tersebut sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.exists' => 'Role yang dipilih tidak valid.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], // Simpan ke kolom legacy untuk backwards-compat
            'gate_id' => $validated['gate_id'] ?? null,
            'photo_path' => $photoPath,
        ]);

        // Assign Spatie Role
        $user->assignRole($validated['role']);

        // Sync Direct Permissions (jika ada izin khusus tambahan di luar bawaan role)
        if ($request->has('direct_permissions')) {
            $role = Role::findByName($validated['role']);
            $rolePermNames = $role ? $role->permissions->pluck('name')->toArray() : [];
            $directPerms = array_diff($request->input('direct_permissions', []), $rolePermNames);
            $user->syncPermissions(array_values($directPerms));
        }

        // Reset permission cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Sinkronisasi profil master Employee jika role karyawan, gate officer, pg admin, atau ada isian data karyawan
        $isEmployeeRole = $user->isEmployee() || $validated['role'] === 'employee';
        $isGateOfficer = $user->isGateOfficer() || $validated['role'] === 'gate_officer';
        $isPgAdmin = $user->isPgAdmin() || $validated['role'] === 'pg_admin';
        $hasEmployeeFields = !empty($validated['unit_kerja']) || !empty($validated['division']) || !empty($validated['position']) || (isset($validated['is_operator']) && $validated['is_operator'] !== '');

        if ($isEmployeeRole || $isGateOfficer || $isPgAdmin || $hasEmployeeFields) {
            $isOperator = (isset($validated['is_operator']) && $validated['is_operator'] !== '')
                ? (bool) $validated['is_operator']
                : ($isEmployeeRole ? true : false);

            $defaultUnit = ($isGateOfficer || $isPgAdmin) ? 'Private Guard' : 'Umum';
            $defaultDivision = ($isGateOfficer || $isPgAdmin) ? 'Private Guard' : '-';
            $defaultPosition = $isGateOfficer ? 'Anggota' : ($isPgAdmin ? 'Admin PG' : ($isEmployeeRole ? 'Driver Operasional' : '-'));

            $emp = Employee::where('user_id', $user->id)->orWhere('nik', $user->username)->first();
            $empData = [
                'user_id' => $user->id,
                'nik' => $user->username,
                'name' => $user->name,
                'employee_type' => !empty($validated['employee_type']) ? ucfirst(strtolower($validated['employee_type'])) : ($emp?->employee_type ?? 'Karyawan'),
                'unit_kerja' => !empty($validated['unit_kerja']) ? $validated['unit_kerja'] : ($emp?->unit_kerja ?: $defaultUnit),
                'division' => !empty($validated['division']) ? $validated['division'] : ($emp?->division ?: $defaultDivision),
                'position' => !empty($validated['position']) ? $validated['position'] : ($emp?->position ?: $defaultPosition),
                'is_operator' => $isOperator,
                'photo_path' => $photoPath ?: $emp?->photo_path,
            ];
            if ($emp) {
                $emp->update($empData);
            } else {
                Employee::create($empData);
            }
        }

        // Rekam ke Log Riwayat Aktivitas Super Admin
        $roleTitle = $this->getFriendlyRoleName($validated['role']);
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Manajemen Pengguna',
            description: "Menambahkan akun pengguna baru: {$user->name} (NIK: {$user->username}) dengan hak akses '{$roleTitle}'.",
            targetType: 'User',
            targetId: $user->id,
            targetName: $user->name,
            newValues: [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
                'gate_id' => $user->gate_id,
            ]
        );

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
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'employee_type' => ['nullable', 'string', 'in:karyawan,sukon,Karyawan,Sukon'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'is_operator' => ['nullable', 'in:0,1'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'NIK wajib diisi.',
            'username.unique' => 'NIK tersebut sudah digunakan oleh akun lain.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
            'password.min' => 'Password minimal 6 karakter jika ingin diubah.',
            'role.required' => 'Role wajib dipilih.',
        ]);

        $oldValues = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'gate_id' => $user->gate_id,
        ];

        $oldRole = $user->role;
        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->gate_id = array_key_exists('gate_id', $validated) ? $validated['gate_id'] : $user->gate_id;

        $passwordUpdated = false;
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $passwordUpdated = true;
        }

        if ($request->hasFile('photo')) {
            ImageCompressionService::deleteIfExists($user->photo_path);
            $user->photo_path = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
        }

        $user->save();

        // Sync Spatie Role
        $user->syncRoles([$validated['role']]);

        // Sync Direct Permissions (hanya simpan izin khusus yang bukan bawaan role)
        $role = Role::findByName($validated['role']);
        $rolePermNames = $role ? $role->permissions->pluck('name')->toArray() : [];
        $directPerms = array_diff($request->input('direct_permissions', []), $rolePermNames);
        $user->syncPermissions(array_values($directPerms));

        // Reset permission cache Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Sinkronkan data master Employee
        $emp = $user->employee ?: Employee::where('nik', $user->username)->first();
        $isEmployeeRole = $user->isEmployee() || $validated['role'] === 'employee';
        $isGateOfficer = $user->isGateOfficer() || $validated['role'] === 'gate_officer';
        $isPgAdmin = $user->isPgAdmin() || $validated['role'] === 'pg_admin';
        $hasEmployeeFields = !empty($validated['unit_kerja']) || !empty($validated['division']) || !empty($validated['position']) || (isset($validated['is_operator']) && $validated['is_operator'] !== '');

        if ($emp || $isEmployeeRole || $isGateOfficer || $isPgAdmin || $hasEmployeeFields) {
            $defaultUnit = ($isGateOfficer || $isPgAdmin) ? 'Private Guard' : 'Umum';
            $defaultDivision = ($isGateOfficer || $isPgAdmin) ? 'Private Guard' : '-';
            $defaultPosition = $isGateOfficer ? 'Anggota' : ($isPgAdmin ? 'Admin PG' : ($isEmployeeRole ? 'Driver Operasional' : '-'));

            $empData = [
                'name' => $user->name,
                'nik' => $user->username,
                'photo_path' => $user->photo_path ?: $emp?->photo_path,
            ];
            if (array_key_exists('employee_type', $validated) && $validated['employee_type'] !== null) {
                $empData['employee_type'] = ucfirst(strtolower($validated['employee_type']));
            }
            if (!empty($validated['unit_kerja'])) {
                $empData['unit_kerja'] = $validated['unit_kerja'];
            } elseif ($isGateOfficer || $isPgAdmin) {
                $empData['unit_kerja'] = ($emp && !empty($emp->unit_kerja)) ? $emp->unit_kerja : $defaultUnit;
            }

            if (!empty($validated['division'])) {
                $empData['division'] = $validated['division'];
            } elseif ($isGateOfficer || $isPgAdmin) {
                $empData['division'] = ($emp && !empty($emp->division) && $emp->division !== '-') ? $emp->division : $defaultDivision;
            }

            if (!empty($validated['position'])) {
                $empData['position'] = $validated['position'];
            } elseif ($isGateOfficer || $isPgAdmin) {
                $empData['position'] = ($emp && !empty($emp->position) && $emp->position !== '-') ? $emp->position : $defaultPosition;
            }

            if (array_key_exists('is_operator', $validated) && $validated['is_operator'] !== null && $validated['is_operator'] !== '') {
                $empData['is_operator'] = (bool) $validated['is_operator'];
            }

            if ($emp) {
                $emp->update($empData);
                if (!$emp->user_id) {
                    $emp->update(['user_id' => $user->id]);
                }
            } else {
                $empData['user_id'] = $user->id;
                $empData['employee_type'] = $empData['employee_type'] ?? 'Karyawan';
                $empData['unit_kerja'] = $empData['unit_kerja'] ?? $defaultUnit;
                $empData['division'] = $empData['division'] ?? $defaultDivision;
                $empData['position'] = $empData['position'] ?? $defaultPosition;
                $empData['is_operator'] = $empData['is_operator'] ?? ($isEmployeeRole ? true : false);
                Employee::create($empData);
            }
        }

        // Susun deskripsi perubahan yang mudah dipahami non-IT
        $roleChanged = $oldRole !== $validated['role'];
        $changes = [];

        if (($oldValues['name'] ?? '') !== $user->name) {
            $changes[] = "nama dari '{$oldValues['name']}' menjadi '{$user->name}'";
        }
        if (($oldValues['username'] ?? '') !== $user->username) {
            $changes[] = "NIK dari '{$oldValues['username']}' menjadi '{$user->username}'";
        }
        if (($oldValues['email'] ?? '') !== $user->email) {
            $changes[] = "email menjadi '{$user->email}'";
        }
        if ($roleChanged) {
            $oldRoleTitle = $this->getFriendlyRoleName($oldRole);
            $newRoleTitle = $this->getFriendlyRoleName($validated['role']);
            $changes[] = "hak akses dari '{$oldRoleTitle}' menjadi '{$newRoleTitle}'";
        }
        if ($passwordUpdated) {
            $changes[] = "kata sandi (password) diperbarui";
        }

        $desc = "Memperbarui akun pengguna: {$user->name} ({$user->username})";
        if (!empty($changes)) {
            $desc .= " — " . implode(', ', $changes) . ".";
        } else {
            $desc .= " — Informasi profil disimpan.";
        }

        // Rekam ke Log Riwayat Aktivitas Super Admin
        AdminActivityLog::record(
            actionType: 'update',
            module: 'Manajemen Pengguna',
            description: $desc,
            targetType: 'User',
            targetId: $user->id,
            targetName: $user->name,
            oldValues: $oldValues,
            newValues: [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
                'gate_id' => $user->gate_id,
            ]
        );

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

        $deletedSnapshot = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'gate_id' => $user->gate_id,
        ];

        $name = $user->name;
        $username = $user->username;
        $userRole = $user->role ?? '';
        $userId = $user->id;

        $user->syncRoles([]);
        $user->delete();

        // Rekam ke Log Riwayat Aktivitas Super Admin
        $roleTitle = $this->getFriendlyRoleName($userRole);
        AdminActivityLog::record(
            actionType: 'delete',
            module: 'Manajemen Pengguna',
            description: "Menghapus akun pengguna: {$name} (Username: {$username}) dengan hak akses '{$roleTitle}'.",
            targetType: 'User',
            targetId: $userId,
            targetName: $name,
            oldValues: $deletedSnapshot
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Akun pengguna [{$name}] berhasil dihapus dari sistem.");
    }

    /**
     * Konversi kode role teknis menjadi nama peran yang mudah dipahami dalam Bahasa Indonesia.
     */
    private function getFriendlyRoleName(string $role): string
    {
        return match($role) {
            'super_admin' => 'Super Administrator (Akses Penuh)',
            'hse_admin' => 'Admin HSE (Keselamatan & Karyawan)',
            'qma_admin' => 'Admin QMA (Armada Kendaraan)',
            'pg_admin' => 'Admin PG (Keamanan & Satpam)',
            'gate_officer' => 'Petugas Pos Gerbang',
            default => ucwords(str_replace('_', ' ', $role))
        };
    }
}
