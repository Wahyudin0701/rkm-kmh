<?php

namespace App\Http\Controllers\Hse;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Tampilkan halaman dashboard Admin HSE (Format referensi enterprise IT KMH).
     */
    public function dashboard(): View
    {
        $totalEmployees = Employee::count();
        $totalDrivers = Employee::where('is_operator', true)->count();
        $expiredSimperCount = Employee::where('is_operator', true)
            ->where(function ($q) {
                $q->whereNull('simper_expired_at')
                  ->orWhere('simper_expired_at', '<', now()->toDateString());
            })
            ->count();
        $nonDriversCount = Employee::where('is_operator', false)->count();

        // 1. Log Driver Terkini (Driver Aktif)
        $recentDrivers = Employee::where('is_operator', true)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        // 2. Monitoring Status & Masa Berlaku SIMPER (Prioritas Kedaluwarsa & Segera Habis)
        $today = now()->toDateString();
        $simperLogs = Employee::where('is_operator', true)
            ->orderByRaw("CASE 
                WHEN simper_expired_at IS NULL THEN 1 
                WHEN simper_expired_at < '{$today}' THEN 0 
                ELSE 2 
            END ASC, simper_expired_at ASC")
            ->take(5)
            ->get();

        // 3. Log Karyawan Non-Driver (Staf Administrasi & Teknis Lainnya)
        $nonDrivers = Employee::where('is_operator', false)
            ->orderByDesc('updated_at')
            ->take(4)
            ->get();

        return view('hse.dashboard', compact(
            'totalEmployees',
            'totalDrivers',
            'expiredSimperCount',
            'nonDriversCount',
            'recentDrivers',
            'simperLogs',
            'nonDrivers'
        ));
    }

    /**
     * Tampilkan daftar master karyawan HSE & status SIMPER.
     */
    public function index(Request $request): View
    {
        $query = Employee::query();

        // 1. Pencarian Kata Kunci (NIK, Nama, Jabatan, Unit, Divisi)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nik', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('unit_kerja', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status Driver
        if ($request->filled('driver_status')) {
            $driverStatus = $request->input('driver_status');
            if ($driverStatus === 'driver') {
                $query->where('is_operator', true);
            } elseif ($driverStatus === 'non_driver') {
                $query->where('is_operator', false);
            }
        }

        // 3. Filter Masa Berlaku SIMPER
        if ($request->filled('simper_status')) {
            $simperStatus = $request->input('simper_status');
            $today = now()->toDateString();
            $expiringSoon = now()->addDays(30)->toDateString();

            if ($simperStatus === 'valid') {
                $query->where('is_operator', true)
                      ->whereNotNull('simper_expired_at')
                      ->where('simper_expired_at', '>=', $today);
            } elseif ($simperStatus === 'expired') {
                $query->where('is_operator', true)
                      ->where(function ($q) use ($today) {
                          $q->whereNull('simper_expired_at')
                            ->orWhere('simper_expired_at', '<', $today);
                      });
            } elseif ($simperStatus === 'expiring_soon') {
                $query->where('is_operator', true)
                      ->whereNotNull('simper_expired_at')
                      ->where('simper_expired_at', '>=', $today)
                      ->where('simper_expired_at', '<=', $expiringSoon);
            }
        }

        $employees = $query->orderBy('name')->get();

        return view('hse.employees_index', compact('employees'));
    }

    /**
     * Simpan data karyawan baru beserta izin SIMPER (Form modal gambar 3).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:50', 'unique:employees,nik'],
            'name' => ['required', 'string', 'max:100'],
            'unit_kerja' => ['required', 'string'],
            'division' => ['required', 'string'],
            'position' => ['required', 'string'],
            'is_operator' => ['required', 'in:1,0'],
            'simper_expired_at' => ['nullable', 'date'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.unique' => 'NIK sudah terdaftar dalam sistem.',
            'name.required' => 'Nama karyawan wajib diisi.',
            'unit_kerja.required' => 'Unit kerja wajib dipilih.',
            'division.required' => 'Divisi wajib dipilih.',
            'position.required' => 'Posisi / Jabatan wajib diisi.',
            'is_operator.required' => 'Pilih apakah karyawan berstatus Operator / Driver.',
        ]);

        $isOperator = (bool) $validated['is_operator'];
        $simperExpiredAt = $isOperator ? ($validated['simper_expired_at'] ?? null) : null;

        Employee::create([
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'unit_kerja' => $validated['unit_kerja'],
            'division' => $validated['division'],
            'position' => $validated['position'],
            'is_operator' => $isOperator,
            'simper_expired_at' => $simperExpiredAt,
        ]);

        return back()->with('success', "Data karyawan {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Perpanjang atau perbarui data karyawan & SIMPER.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'nik' => ['sometimes', 'required', 'string', 'max:50', 'unique:employees,nik,' . $employee->id],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'unit_kerja' => ['sometimes', 'required', 'string'],
            'division' => ['sometimes', 'required', 'string'],
            'position' => ['sometimes', 'required', 'string'],
            'is_operator' => ['required', 'in:1,0'],
            'simper_expired_at' => ['nullable', 'date'],
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'is_operator.required' => 'Status driver wajib dipilih.',
        ]);

        $isOperator = (bool) $validated['is_operator'];
        $simperExpiredAt = $isOperator ? ($validated['simper_expired_at'] ?? $employee->simper_expired_at) : null;

        $employee->update([
            'nik' => $validated['nik'] ?? $employee->nik,
            'name' => $validated['name'] ?? $employee->name,
            'unit_kerja' => $validated['unit_kerja'] ?? $employee->unit_kerja,
            'division' => $validated['division'] ?? $employee->division,
            'position' => $validated['position'] ?? $employee->position,
            'is_operator' => $isOperator,
            'simper_expired_at' => $simperExpiredAt,
        ]);

        return back()->with('success', "Data SIMPER untuk {$employee->name} berhasil diperbarui.");
    }
}
