<?php

namespace App\Http\Controllers\Hse;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Services\ImageCompressionService;
use App\Services\SimperPdfExporter;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
     * Unduh format template Excel untuk impor data karyawan.
     */
    public function downloadImportTemplate(): Response
    {
        $metaLines = [
            'PETUNJUK PENGISIAN:',
            '1. Kolom NIK dan Nama Lengkap WAJIB diisi.',
            '2. Kolom Operator Driver diisi YA (jika pengemudi/operator alat) atau TIDAK.',
            '3. Kolom Masa Berlaku SIMPER format YYYY-MM-DD (contoh: 2026-12-31). Kosongkan jika bukan driver.',
        ];

        $headers = [
            'No' => 6,
            'NIK' => 18,
            'Nama Lengkap' => 28,
            'Unit Kerja' => 20,
            'Divisi' => 20,
            'Jabatan' => 24,
            'Operator Driver' => 18,
            'Masa Berlaku SIMPER' => 22,
        ];

        $rows = [
            [1, 'KMH-2026-001', 'Ahmad Rizky Pratama', 'Pembangkit', 'Operasi', 'Operator Turbin', 'YA', '2026-12-31'],
            [2, 'KMH-2026-002', 'Dewi Lestari', 'Umum', 'Keuangan', 'Staff Finance', 'TIDAK', ''],
            [3, 'KMH-2026-003', 'Hendra Saputra', 'Bendungan', 'Civil Maintenance', 'Driver Operasional', 'YA', '2027-06-30'],
        ];

        $alignments = [
            'center', // No
            'center', // NIK
            'left',   // Nama
            'left',   // Unit Kerja
            'left',   // Divisi
            'left',   // Jabatan
            'center', // Operator Driver
            'center', // SIMPER Expired
        ];

        $binary = SimpleXlsxExporter::create(
            'TEMPLATE IMPOR DATA KARYAWAN & SIMPER - PT KERINCI MERANGIN HIDRO',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        $fileName = 'Template_Impor_Karyawan_KMH.xlsx';

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Proses impor data karyawan secara massal dari file Excel / CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // Max 10MB
        ], [
            'file.required' => 'Silakan pilih file Excel (.xlsx) atau CSV yang akan diimpor.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());

        if (!in_array($extension, ['xlsx', 'csv'])) {
            return back()->with('error', 'Format file harus berupa Excel (.xlsx) atau file teks CSV (.csv).');
        }

        try {
            $rows = SimpleXlsxImporter::parse($file->getRealPath(), $extension);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca isi file: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->with('error', 'File yang diunggah kosong atau tidak memiliki data yang dapat dibaca.');
        }

        // Cari baris header (mencari baris yang mengandung 'nik' atau 'nama')
        $headerRowIndex = null;
        $colMap = [
            'nik' => null,
            'name' => null,
            'unit_kerja' => null,
            'division' => null,
            'position' => null,
            'is_operator' => null,
            'simper_expired_at' => null,
        ];

        foreach ($rows as $rIdx => $row) {
            $normalizedRow = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            $hasNik = false;
            $hasName = false;

            foreach ($normalizedRow as $cIdx => $cellText) {
                if (str_contains($cellText, 'nik')) {
                    $colMap['nik'] = $cIdx;
                    $hasNik = true;
                } elseif (str_contains($cellText, 'nama')) {
                    $colMap['name'] = $cIdx;
                    $hasName = true;
                } elseif (str_contains($cellText, 'unit')) {
                    $colMap['unit_kerja'] = $cIdx;
                } elseif (str_contains($cellText, 'divisi') || str_contains($cellText, 'division')) {
                    $colMap['division'] = $cIdx;
                } elseif (str_contains($cellText, 'jabatan') || str_contains($cellText, 'posisi') || str_contains($cellText, 'position')) {
                    $colMap['position'] = $cIdx;
                } elseif (str_contains($cellText, 'operator') || str_contains($cellText, 'driver')) {
                    $colMap['is_operator'] = $cIdx;
                } elseif (str_contains($cellText, 'simper') || str_contains($cellText, 'berlaku') || str_contains($cellText, 'expired')) {
                    $colMap['simper_expired_at'] = $cIdx;
                }
            }

            if ($hasNik && $hasName) {
                $headerRowIndex = $rIdx;
                break;
            }
        }

        // Fallback jika tidak ditemukan header eksplisit (asumsi urutan standar)
        if ($headerRowIndex === null) {
            $headerRowIndex = 0;
            $colMap = [
                'nik' => 1,
                'name' => 2,
                'unit_kerja' => 3,
                'division' => 4,
                'position' => 5,
                'is_operator' => 6,
                'simper_expired_at' => 7,
            ];
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $nik = trim((string) ($colMap['nik'] !== null ? ($row[$colMap['nik']] ?? '') : ''));
                $name = trim((string) ($colMap['name'] !== null ? ($row[$colMap['name']] ?? '') : ''));

                // Lewati jika NIK atau Nama kosong
                if ($nik === '' || $name === '') {
                    $skippedCount++;
                    continue;
                }

                $unitKerja = trim((string) ($colMap['unit_kerja'] !== null ? ($row[$colMap['unit_kerja']] ?? 'Umum') : 'Umum'));
                $division = trim((string) ($colMap['division'] !== null ? ($row[$colMap['division']] ?? '-') : '-'));
                $position = trim((string) ($colMap['position'] !== null ? ($row[$colMap['position']] ?? '-') : '-'));

                // Deteksi status driver (jika kolom ada dan berisi ya/driver)
                $operatorRaw = $colMap['is_operator'] !== null ? strtolower(trim((string) ($row[$colMap['is_operator']] ?? ''))) : '';
                $isOperator = in_array($operatorRaw, ['ya', 'yes', '1', 'true', 'driver', 'operator', 'y']);

                // Deteksi tanggal SIMPER
                $simperRaw = $colMap['simper_expired_at'] !== null ? trim((string) ($row[$colMap['simper_expired_at']] ?? '')) : '';
                $simperExpiredAt = $isOperator ? SimpleXlsxImporter::normalizeDate($simperRaw) : null;

                // 1. Sinkronisasi Akun User untuk login karyawan
                $cleanEmail = strtolower(str_replace(['-', ' '], '', $nik)) . '@kmh.co.id';
                $user = User::firstOrCreate(
                    ['username' => $nik],
                    [
                        'name' => $name,
                        'email' => $cleanEmail,
                        'password' => Hash::make('123456'),
                        'role' => 'employee',
                    ]
                );

                if (!$user->hasRole('employee')) {
                    $user->assignRole('employee');
                }

                // 2. Simpan atau perbarui data master Employee
                $isExisting = Employee::where('nik', $nik)->first();
                if ($isExisting) {
                    $isExisting->update([
                        'name' => $name,
                        'unit_kerja' => $unitKerja ?: 'Umum',
                        'division' => $division ?: '-',
                        'position' => $position ?: '-',
                        'is_operator' => $isOperator,
                        'simper_expired_at' => $simperExpiredAt,
                        'user_id' => $user->id,
                    ]);
                    $updatedCount++;
                } else {
                    Employee::create([
                        'nik' => $nik,
                        'name' => $name,
                        'unit_kerja' => $unitKerja ?: 'Umum',
                        'division' => $division ?: '-',
                        'position' => $position ?: '-',
                        'is_operator' => $isOperator,
                        'simper_expired_at' => $simperExpiredAt,
                        'user_id' => $user->id,
                    ]);
                    $insertedCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses data: ' . $e->getMessage());
        }

        $totalProcessed = $insertedCount + $updatedCount;
        if ($totalProcessed === 0) {
            return back()->with('error', 'Tidak ada data valid yang dapat diimpor dari file tersebut. Pastikan kolom NIK dan Nama terisi.');
        }

        $message = "Berhasil mengimpor {$totalProcessed} data karyawan ({$insertedCount} data baru, {$updatedCount} data diperbarui).";
        if ($skippedCount > 0) {
            $message .= " Sebanyak {$skippedCount} baris kosong dilewati.";
        }

        return back()->with('success', $message);
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
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
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

        $user = User::firstOrCreate(
            ['username' => $validated['nik']],
            [
                'name' => $validated['name'],
                'email' => strtolower(str_replace(['-', ' '], '', $validated['nik'])) . '@kmh.co.id',
                'password' => Hash::make('123456'),
                'role' => 'employee',
            ]
        );

        if (!$user->hasRole('employee')) {
            $user->assignRole('employee');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
            $user->update(['photo_path' => $photoPath]);
        }

        Employee::create([
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'unit_kerja' => $validated['unit_kerja'],
            'division' => $validated['division'],
            'position' => $validated['position'],
            'is_operator' => $isOperator,
            'simper_expired_at' => $simperExpiredAt,
            'photo_path' => $photoPath,
            'user_id' => $user->id,
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
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'is_operator.required' => 'Status driver wajib dipilih.',
        ]);

        $isOperator = (bool) $validated['is_operator'];
        $simperExpiredAt = $isOperator ? ($validated['simper_expired_at'] ?? $employee->simper_expired_at) : null;

        $photoPath = $employee->photo_path;
        if ($request->hasFile('photo')) {
            ImageCompressionService::deleteIfExists($employee->photo_path);
            $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
        }

        $employee->update([
            'nik' => $validated['nik'] ?? $employee->nik,
            'name' => $validated['name'] ?? $employee->name,
            'unit_kerja' => $validated['unit_kerja'] ?? $employee->unit_kerja,
            'division' => $validated['division'] ?? $employee->division,
            'position' => $validated['position'] ?? $employee->position,
            'is_operator' => $isOperator,
            'simper_expired_at' => $simperExpiredAt,
            'photo_path' => $photoPath,
        ]);

        // Sinkronkan akun login User terkait
        $linkedUser = $employee->user ?: User::where('username', $employee->nik)->first();
        if ($linkedUser) {
            if (!$employee->user_id) {
                $employee->update(['user_id' => $linkedUser->id]);
            }
            $linkedUser->update([
                'name' => $employee->name,
                'photo_path' => $photoPath,
            ]);
        }

        return back()->with('success', "Data SIMPER untuk {$employee->name} berhasil diperbarui.");
    }
}
