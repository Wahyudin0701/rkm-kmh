<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan halaman utama Super Admin: Manajemen Akun & Hak Akses Seluruh Modul.
     */
    public function index(Request $request): View
    {
        $roleFilter = $request->query('role');
        $search = trim((string) $request->query('search', ''));

        $query = User::with(['gate', 'securityGuard.gate', 'employee'])->latest();

        if ($roleFilter && $roleFilter !== 'all') {
            if ($roleFilter === 'admin_group') {
                $query->whereIn('role', ['super_admin', 'superadmin', 'admin', 'hse_admin', 'qma_admin', 'pg_admin']);
            } elseif ($roleFilter === 'super_admin') {
                $query->whereIn('role', ['super_admin', 'superadmin', 'admin']);
            } else {
                $query->where('role', $roleFilter);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        // Ringkasan Statistik Pengguna
        $stats = [
            'total' => User::count(),
            'super_admin' => User::whereIn('role', ['super_admin', 'superadmin', 'admin'])->count(),
            'hse_admin' => User::where('role', 'hse_admin')->count(),
            'qma_admin' => User::where('role', 'qma_admin')->count(),
            'pg_admin' => User::where('role', 'pg_admin')->count(),
            'gate_officer' => User::where('role', 'gate_officer')->count(),
            'employee' => User::where('role', 'employee')->count(),
        ];

        // Daftar Gerbang Aktif untuk Pilihan Pos Satpam
        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('superadmin.roles_index', compact('users', 'stats', 'gates', 'roleFilter', 'search'));
    }

    /**
     * Simpan akun admin / pengguna baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer', 'employee'])],
            'gate_id' => ['nullable', 'exists:gates,id'],
        ], [
            'username.unique' => 'Username tersebut sudah digunakan oleh akun lain.',
            'username.regex' => 'Username hanya boleh berupa huruf, angka, titik (.), tanda hubung (-) dan garis bawah (_).',
            'email.unique' => 'Email tersebut sudah terdaftar di sistem.',
            'password.min' => 'Password minimal terdiri dari 6 karakter.',
            'role.in' => 'Pilihan role tidak valid.',
        ]);

        // Jika bukan gate_officer, kosongkan gate_id
        if ($validated['role'] !== 'gate_officer') {
            $validated['gate_id'] = null;
        }

        // Default password ke 'password' jika kosong atau tidak diisi
        $rawPassword = !empty($validated['password']) ? $validated['password'] : 'password';
        $validated['password'] = Hash::make($rawPassword);

        $user = User::create($validated);

        // Jika role adalah 'employee', buat juga record master Employee (Unit Kerja, Divisi, Jabatan Wajib Diisi)
        if ($validated['role'] === 'employee') {
            $empData = $request->validate([
                'unit_kerja' => ['required', 'string', 'max:100'],
                'division' => ['required', 'string', 'max:100'],
                'position' => ['required', 'string', 'max:100'],
            ], [
                'unit_kerja.required' => 'Unit kerja wajib dipilih untuk karyawan.',
                'division.required' => 'Divisi wajib dipilih untuk karyawan.',
                'position.required' => 'Jabatan wajib diisi untuk karyawan.',
            ]);

            $nik = $validated['username'];

            \App\Models\Employee::create([
                'user_id' => $user->id,
                'nik' => $nik,
                'name' => $validated['name'],
                'unit_kerja' => $empData['unit_kerja'],
                'division' => $empData['division'],
                'position' => $empData['position'],
                'is_operator' => false, // Default bukan driver, akan diatur oleh Admin HSE
                'simper_expired_at' => null,
            ]);
        }

        return redirect()->route('superadmin.roles.index')
            ->with('success', "Akun '{$validated['name']}' dengan role '{$validated['role']}' berhasil dibuat.");
    }

    /**
     * Perbarui data akun pengguna.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::in(['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer', 'employee'])],
            'gate_id' => ['nullable', 'exists:gates,id'],
        ], [
            'username.unique' => 'Username tersebut sudah digunakan oleh akun lain.',
            'username.regex' => 'Username hanya boleh berupa huruf, angka, titik (.), tanda hubung (-) dan garis bawah (_).',
            'email.unique' => 'Email tersebut sudah terdaftar di sistem.',
            'password.min' => 'Password baru minimal terdiri dari 6 karakter.',
        ]);

        // Keamanan: Cegah super admin terakhir mengubah rolenya sendiri menjadi role biasa
        if ($user->id === Auth::id() && $validated['role'] !== 'super_admin') {
            $superAdminCount = User::whereIn('role', ['super_admin', 'superadmin', 'admin'])->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Gagal: Anda adalah satu-satunya Super Admin aktif di sistem.');
            }
        }

        if ($validated['role'] !== 'gate_officer') {
            $validated['gate_id'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Jika user ini adalah karyawan, sinkronkan nama, NIK (dari username), dan data kerjanya (wajib diisi)
        if ($user->role === 'employee' || $user->employee) {
            $unitKerja = $request->input('employee_unit_kerja') ?? $request->input('unit_kerja');
            $division = $request->input('employee_division') ?? $request->input('division');
            $position = $request->input('employee_position') ?? $request->input('position');

            if ($user->role === 'employee' && (empty($unitKerja) || empty($division) || empty($position))) {
                return back()->withErrors([
                    'employee_unit_kerja' => 'Unit kerja, divisi, dan jabatan wajib diisi untuk akun karyawan.',
                ])->withInput();
            }

            $empFields = [
                'name' => $user->name,
                'nik' => $user->username,
            ];
            if (!empty($unitKerja)) {
                $empFields['unit_kerja'] = $unitKerja;
            }
            if (!empty($division)) {
                $empFields['division'] = $division;
            }
            if (!empty($position)) {
                $empFields['position'] = $position;
            }

            if ($user->employee) {
                $user->employee->update($empFields);
            } else {
                $empFields['user_id'] = $user->id;
                $empFields['is_operator'] = false;
                $empFields['simper_expired_at'] = null;
                \App\Models\Employee::create($empFields);
            }
        }

        return redirect()->route('superadmin.roles.index')
            ->with('success', "Data akun '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Keamanan 1: Cegah Super Admin menghapus akunnya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Operasi ditolak: Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Keamanan 2: Cegah menghapus jika merupakan super admin terakhir
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::whereIn('role', ['super_admin', 'superadmin', 'admin'])->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Operasi ditolak: Akun Super Admin terakhir tidak boleh dihapus.');
            }
        }

        // Keamanan 3: Cegah penghapusan jika Karyawan memiliki riwayat aktivitas atau P2H
        if ($user->employee) {
            if ($user->employee->activities()->exists()) {
                return back()->with('error', "Operasi ditolak: Akun '{$user->name}' tidak dapat dihapus karena memiliki riwayat log pergerakan kendaraan di pos gerbang.");
            }
            if ($user->employee->p2hRecords()->exists()) {
                return back()->with('error', "Operasi ditolak: Akun '{$user->name}' tidak dapat dihapus karena memiliki riwayat checksheet inspeksi keselamatan P2H.");
            }
        }

        // Keamanan 4: Cegah penghapusan jika Petugas Satpam memiliki riwayat validasi pos gerbang
        if ($user->securityGuard) {
            if ($user->securityGuard->activities()->exists()) {
                return back()->with('error', "Operasi ditolak: Petugas Satpam '{$user->name}' tidak dapat dihapus karena telah memvalidasi aktivitas kendaraan di pos gerbang.");
            }
        }

        $userName = $user->name;
        DB::transaction(function () use ($user) {
            if ($user->employee) {
                $user->employee->delete();
            }
            if ($user->securityGuard) {
                $user->securityGuard->delete();
            }
            $user->delete();
        });

        return redirect()->route('superadmin.roles.index')
            ->with('success', "Akun '{$userName}' telah berhasil dihapus dari sistem.");
    }

    /**
     * Reset password akun pengguna menjadi default "password".
     */
    public function resetPassword(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make('password'),
        ]);

        return redirect()->route('superadmin.roles.index')
            ->with('success', "Password akun '{$user->name}' berhasil direset ke default ('password').");
    }
}
