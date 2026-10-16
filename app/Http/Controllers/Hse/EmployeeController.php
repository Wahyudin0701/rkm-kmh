<?php

namespace App\Http\Controllers\Hse;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Services\SimperPdfExporter;
use App\Services\SimpleXlsxExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
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
                WHEN simper_expired_at < ? THEN 0 
                ELSE 2 
            END ASC, simper_expired_at ASC", [$today])
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
     * Helper query builder untuk memfilter master data karyawan & status SIMPER.
     */
    private function getFilteredEmployees(Request $request): Builder
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

        return $query->orderBy('name');
    }

    /**
     * Tampilkan daftar master karyawan HSE & status SIMPER.
     */
    public function index(Request $request): View
    {
        $employees = $this->getFilteredEmployees($request)->paginate(20)->withQueryString();

        return view('hse.employees_index', compact('employees'));
    }

    /**
     * Ekspor data master SIMPER karyawan ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $employees = $this->getFilteredEmployees($request)->get();

        $today = now()->translatedFormat('d F Y, H:i');
        $driverFilter = match ($request->input('driver_status')) {
            'driver' => 'Driver',
            'non_driver' => 'Bukan Driver',
            default => 'Semua Status Driver'
        };
        $simperFilter = match ($request->input('simper_status')) {
            'valid' => 'Masih Berlaku (Aktif)',
            'expiring_soon' => 'Segera Habis (≤ 30 Hari)',
            'expired' => 'Kedaluwarsa (Expired)',
            default => 'Semua Masa Berlaku'
        };

        $metaLines = [
            'Filter: ' . $driverFilter . ' | ' . $simperFilter . ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
            'Total Data: ' . $employees->count() . ' Karyawan | Tanggal Dicetak: ' . $today . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'NIK' => 16,
            'Nama Lengkap' => 30,
            'Jabatan' => 24,
            'Unit Kerja' => 20,
            'Divisi' => 20,
            'Status Driver' => 16,
            'Masa Berlaku SIMPER' => 22,
            'Status SIMPER' => 24,
        ];

        $rows = [];
        $now = now();
        $soon = now()->addDays(30);

        foreach ($employees as $idx => $emp) {
            $isDriver = $emp->is_operator;
            $expDate = $emp->simper_expired_at ? $emp->simper_expired_at->format('d/m/Y') : '-';

            if (! $isDriver) {
                $statusText = 'Bukan Driver';
            } elseif (! $emp->simper_expired_at) {
                $statusText = 'Belum Ada SIMPER';
            } elseif ($emp->simper_expired_at->isPast() && ! $emp->simper_expired_at->isToday()) {
                $statusText = 'Kedaluwarsa (Expired)';
            } elseif ($emp->simper_expired_at->lte($soon)) {
                $statusText = 'Segera Habis (≤ 30 Hari)';
            } else {
                $statusText = 'Masih Berlaku (Aktif)';
            }

            $rows[] = [
                $idx + 1,
                $emp->nik,
                $emp->name,
                $emp->position ?: '-',
                $emp->unit_kerja ?: '-',
                $emp->division ?: '-',
                $isDriver ? 'Driver' : 'Bukan Driver',
                $expDate,
                $statusText,
            ];
        }

        $alignments = [
            'center', // No
            'center', // NIK
            'left',   // Nama
            'left',   // Jabatan
            'left',   // Unit Kerja
            'left',   // Divisi
            'center', // Status Driver
            'center', // Masa Berlaku
            'center', // Status SIMPER
        ];

        $fileName = 'Laporan_Data_Simper_' . now()->format('Ymd_Hi') . '.xlsx';
        $binary = SimpleXlsxExporter::create(
            'LAPORAN DATA SIMPER & STATUS DRIVER KARYAWAN - PT KERINCI MERANGIN HIDRO',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Tampilkan laporan resmi SIMPER ke format PDF (Pratinjau PDF Asli dengan Logo PT KMH).
     */
    public function exportPdf(Request $request): Response
    {
        $employees = $this->getFilteredEmployees($request)->get();

        $driverFilter = match ($request->input('driver_status')) {
            'driver' => 'Driver',
            'non_driver' => 'Bukan Driver',
            default => 'Semua Status Driver'
        };
        $simperFilter = match ($request->input('simper_status')) {
            'valid' => 'Masih Berlaku (Aktif)',
            'expiring_soon' => 'Segera Habis (≤ 30 Hari)',
            'expired' => 'Kedaluwarsa (Expired)',
            default => 'Semua Masa Berlaku'
        };

        $totalActive = $employees->filter(fn ($e) => $e->is_operator && $e->hasValidSimper())->count();
        $totalExpired = $employees->filter(fn ($e) => $e->is_operator && ! $e->hasValidSimper())->count();
        $totalNonDriver = $employees->filter(fn ($e) => ! $e->is_operator)->count();

        $metaLeft = [
            'Kategori' => $driverFilter,
            'Tanggal Cetak' => now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $statusSummary = "{$totalActive} Aktif, {$totalExpired} Expired";
        if ($totalNonDriver > 0) {
            $statusSummary .= ", {$totalNonDriver} Non-Driver";
        }

        $metaRight = [
            'Total Karyawan' => $employees->count() . ' Orang',
            'Status SIMPER' => $statusSummary,
        ];

        $subtitle = 'Data Karyawan & Driver - ' . now()->translatedFormat('d F Y');

        $pdfBinary = SimperPdfExporter::generate($employees, $metaLeft, $metaRight, $subtitle);
        $fileName = 'Laporan_Daftar_Simper_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Simpan data karyawan baru beserta izin SIMPER (Form modal tambah karyawan).
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
