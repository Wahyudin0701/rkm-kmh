<?php

namespace App\Http\Controllers\Hse;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\EmployeeSimper;
use App\Models\SimperType;
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
use Illuminate\Validation\Rule;
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

        // 2. Monitoring Status & Masa Berlaku SIMPER (Prioritas Kedaluwarsa & Segera Habis dengan Dukungan Multi-SIMPER)
        $today = now()->toDateString();
        $simperLogs = Employee::where('is_operator', true)
            ->with(['simpers.simperType'])
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
        $query = Employee::with(['simpers.simperType']);

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

        // 4. Filter Jenis Personil / Karyawan (Karyawan, Sukon, dll)
        if ($request->filled('employee_type')) {
            $empType = trim($request->input('employee_type'));
            if ($empType === 'Karyawan') {
                $query->where(function ($q) {
                    $q->where('employee_type', 'Karyawan')->orWhereNull('employee_type');
                });
            } else {
                $query->where('employee_type', $empType);
            }
        }

        return $query->orderBy('name');
    }

    /**
     * Tampilkan daftar master karyawan HSE & status SIMPER.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        $employees = $this->getFilteredEmployees($request)->paginate($perPage)->withQueryString();

        $employeeTypes = Employee::whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->distinct()
            ->pluck('employee_type')
            ->all();

        if (!in_array('Karyawan', $employeeTypes)) {
            array_unshift($employeeTypes, 'Karyawan');
        }
        if (!in_array('Sukon', $employeeTypes)) {
            $employeeTypes[] = 'Sukon';
        }

        $availableSimperTypes = SimperType::active()->orderBy('name')->get();

        $unitKerjaList = Employee::whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->distinct()
            ->orderBy('unit_kerja')
            ->pluck('unit_kerja')
            ->all();

        return view('hse.employees_index', compact('employees', 'employeeTypes', 'availableSimperTypes', 'unitKerjaList'));
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
            'Jenis' => 16,
            'Jabatan' => 24,
            'Unit Kerja' => 20,
            'Divisi' => 20,
            'Status Driver' => 16,
            'Jenis Izin SIMPER' => 24,
            'Masa Berlaku SIMPER' => 22,
            'Status SIMPER' => 24,
        ];

        $rows = [];
        $now = now();
        $soon = now()->addDays(30);

        foreach ($employees as $idx => $emp) {
            $isDriver = $emp->is_operator;
            $expDate = $emp->simper_expired_at ? $emp->simper_expired_at->format('d/m/Y') : '-';

            // Ambil rincian seluruh kode SIMPER yang dipegang
            $simperList = $emp->simpers->map(function ($s) {
                $code = $s->simperType?->code ?: 'SIMPER';
                return str_replace('SIMPER-', '', $code);
            })->filter()->unique()->values();
            $simperTypesText = $simperList->isNotEmpty() ? $simperList->join(', ') : ($isDriver ? 'LV (Default)' : '-');

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
                $emp->employee_type ?: 'Karyawan',
                $emp->position ?: '-',
                $emp->unit_kerja ?: '-',
                $emp->division ?: '-',
                $isDriver ? 'Driver' : 'Bukan Driver',
                $simperTypesText,
                $expDate,
                $statusText,
            ];
        }

        $alignments = [
            'center', // No
            'center', // NIK
            'left',   // Nama
            'center', // Jenis
            'left',   // Jabatan
            'left',   // Unit Kerja
            'left',   // Divisi
            'center', // Status Driver
            'center', // Jenis Izin SIMPER
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
            'employee_type' => null,
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
                } elseif (str_contains($cellText, 'jenis') || str_contains($cellText, 'tipe') || str_contains($cellText, 'sukon')) {
                    $colMap['employee_type'] = $cIdx;
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

                $rawUnitKerja = $colMap['unit_kerja'] !== null ? trim((string) ($row[$colMap['unit_kerja']] ?? '')) : '';
                $rawDivision = $colMap['division'] !== null ? trim((string) ($row[$colMap['division']] ?? '')) : '';
                $rawPosition = $colMap['position'] !== null ? trim((string) ($row[$colMap['position']] ?? '')) : '';
                $rawEmployeeType = $colMap['employee_type'] !== null ? trim((string) ($row[$colMap['employee_type']] ?? '')) : '';
                $rawOperator = $colMap['is_operator'] !== null ? trim((string) ($row[$colMap['is_operator']] ?? '')) : '';
                $rawSimper = $colMap['simper_expired_at'] !== null ? trim((string) ($row[$colMap['simper_expired_at']] ?? '')) : '';

                $unitKerja = $rawUnitKerja ?: 'Umum';
                $division = $rawDivision ?: '-';
                $position = $rawPosition ?: '-';
                $employeeType = $rawEmployeeType ?: 'Karyawan';

                // Deteksi status driver (jika kolom ada dan berisi ya/driver)
                $operatorRaw = strtolower($rawOperator);
                $isOperator = in_array($operatorRaw, ['ya', 'yes', '1', 'true', 'driver', 'operator', 'y']);

                // Deteksi tanggal SIMPER
                $simperExpiredAt = $isOperator ? SimpleXlsxImporter::normalizeDate($rawSimper) : null;

                // 1. Sinkronisasi Akun User untuk login karyawan (gunakan withTrashed agar tidak bentrok dengan data di Tong Sampah)
                $cleanEmail = strtolower(str_replace(['-', ' '], '', $nik)) . '@kmh.co.id';
                $user = User::withTrashed()->where('username', $nik)->first();
                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->update([
                        'name' => $name,
                        'role' => $user->role ?: 'employee',
                    ]);
                } else {
                    if (User::withTrashed()->where('email', $cleanEmail)->exists()) {
                        $cleanEmail = strtolower(str_replace(['-', ' '], '', $nik)) . '.' . uniqid() . '@kmh.co.id';
                    }

                    $user = User::create([
                        'username' => $nik,
                        'name' => $name,
                        'email' => $cleanEmail,
                        'password' => Hash::make(config('auth.default_employee_password', '123456')),
                        'role' => 'employee',
                    ]);
                }

                if (!$user->hasRole('employee')) {
                    $user->assignRole('employee');
                }

                // 2. Simpan atau perbarui data master Employee (gunakan withTrashed agar tidak duplikat dengan NIK di Tong Sampah)
                $isExisting = Employee::withTrashed()->where('nik', $nik)->first();
                if ($isExisting) {
                    if ($isExisting->trashed()) {
                        $isExisting->restore();
                    }

                    $updateData = [
                        'name' => $name,
                        'user_id' => $user->id,
                    ];

                    // Perbarui kolom secara selektif jika pada berkas Excel terisi data baru
                    if ($rawEmployeeType !== '') {
                        $updateData['employee_type'] = $employeeType;
                    }
                    if ($rawUnitKerja !== '') {
                        $updateData['unit_kerja'] = $unitKerja;
                    } elseif (empty($isExisting->unit_kerja) || $isExisting->unit_kerja === '-') {
                        $updateData['unit_kerja'] = 'Umum';
                    }

                    if ($rawDivision !== '') {
                        $updateData['division'] = $division;
                    }

                    if ($rawPosition !== '') {
                        $updateData['position'] = $position;
                    }

                    if ($rawOperator !== '') {
                        $updateData['is_operator'] = $isOperator;
                        if ($rawSimper !== '' && $isOperator) {
                            $updateData['simper_expired_at'] = $simperExpiredAt;
                        }
                    } elseif ($rawSimper !== '' && $isExisting->is_operator) {
                        $updateData['simper_expired_at'] = SimpleXlsxImporter::normalizeDate($rawSimper);
                    }

                    $isExisting->update($updateData);
                    $updatedCount++;
                } else {
                    Employee::create([
                        'nik' => $nik,
                        'name' => $name,
                        'employee_type' => $employeeType,
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
            'nik' => ['required', 'string', 'max:50', Rule::unique('employees', 'nik')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'unit_kerja' => ['required', 'string'],
            'division' => ['required', 'string'],
            'position' => ['required', 'string'],
            'is_operator' => ['required', 'in:1,0'],
            'simper_expired_at' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'simper_assignments' => ['nullable', 'array'],
            'simper_assignments.*.expired_at' => ['nullable', 'date'],
            'simper_assignments.*.simper_number' => ['nullable', 'string', 'max:100'],
            'renew_simper_type_id' => ['nullable', 'string'],
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.unique' => 'NIK sudah terdaftar dalam sistem.',
            'name.required' => 'Nama karyawan wajib diisi.',
            'unit_kerja.required' => 'Unit kerja wajib diisi.',
            'division.required' => 'Divisi wajib diisi.',
            'position.required' => 'Posisi / Jabatan wajib diisi.',
            'is_operator.required' => 'Pilih apakah karyawan berstatus Operator / Driver.',
        ]);

        $isOperator = (bool) $validated['is_operator'];
        $simperExpiredAt = $isOperator ? ($validated['simper_expired_at'] ?? null) : null;

        // Validasi kustom penugasan SIMPER ketika driver aktif
        if ($isOperator && is_array($request->input('simper_assignments'))) {
            $today = Carbon::today()->toDateString();
            foreach ($request->input('simper_assignments') as $typeId => $data) {
                if (!empty($data['enabled'])) {
                    $st = SimperType::find($typeId);
                    $typeName = $st ? $st->name : "Tipe #{$typeId}";
                    if (empty($data['expired_at'])) {
                        return back()->withInput()->with('error', "Masa berlaku untuk jenis SIMPER '{$typeName}' wajib diisi ketika dicentang.");
                    }
                    if ($data['expired_at'] < $today) {
                        return back()->withInput()->with('error', "Masa berlaku untuk jenis SIMPER '{$typeName}' tidak boleh tanggal lampau (minimal hari ini).");
                    }
                }
            }
        }

        $employeeType = $request->input('employee_type');
        if ($employeeType === '__CUSTOM__' && $request->filled('custom_employee_type')) {
            $employeeType = trim($request->input('custom_employee_type'));
        }
        if (empty($employeeType)) {
            $employeeType = 'Karyawan';
        }

        $user = User::withTrashed()->where('username', $validated['nik'])->first();
        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->update([
                'name' => $validated['name'],
                'role' => $user->role ?: 'employee',
            ]);
        } else {
            $user = User::create([
                'username' => $validated['nik'],
                'name' => $validated['name'],
                'email' => strtolower(str_replace(['-', ' '], '', $validated['nik'])) . '@kmh.co.id',
                'password' => Hash::make(config('auth.default_employee_password', '123456')),
                'role' => 'employee',
            ]);
        }

        if (!$user->hasRole('employee')) {
            $user->assignRole('employee');
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
            $user->update(['photo_path' => $photoPath]);
        }

        $employee = Employee::withTrashed()->where('nik', $validated['nik'])->first();
        if ($employee) {
            if ($employee->trashed()) {
                $employee->restore();
            }
            $employee->update([
                'name' => $validated['name'],
                'employee_type' => $employeeType,
                'unit_kerja' => $validated['unit_kerja'],
                'division' => $validated['division'],
                'position' => $validated['position'],
                'is_operator' => $isOperator,
                'simper_expired_at' => $simperExpiredAt,
                'photo_path' => $photoPath ?? $employee->photo_path,
                'user_id' => $user->id,
            ]);
        } else {
            $employee = Employee::create([
                'nik' => $validated['nik'],
                'name' => $validated['name'],
                'employee_type' => $employeeType,
                'unit_kerja' => $validated['unit_kerja'],
                'division' => $validated['division'],
                'position' => $validated['position'],
                'is_operator' => $isOperator,
                'simper_expired_at' => $simperExpiredAt,
                'photo_path' => $photoPath,
                'user_id' => $user->id,
            ]);
        }

        // Sinkronisasi Multi-SIMPER Personil
        $this->syncEmployeeSimpers($employee, $request, $isOperator, $simperExpiredAt);

        return back()->with('success', "Data karyawan {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Tampilkan halaman khusus edit data personil & SIMPER (View Terpisah).
     */
    public function edit(int $id): View
    {
        $employee = Employee::with(['simpers.simperType'])->findOrFail($id);
        $availableSimperTypes = SimperType::active()->orderBy('name')->get();

        $employeeTypes = Employee::distinct()
            ->whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->pluck('employee_type')
            ->values()
            ->all();

        if (!in_array('Karyawan', $employeeTypes)) {
            array_unshift($employeeTypes, 'Karyawan');
        }
        if (!in_array('Sukon', $employeeTypes)) {
            $employeeTypes[] = 'Sukon';
        }

        $unitKerjaList = Employee::whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->distinct()
            ->orderBy('unit_kerja')
            ->pluck('unit_kerja')
            ->all();

        return view('hse.employees_edit', compact('employee', 'availableSimperTypes', 'employeeTypes', 'unitKerjaList'));
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
            'simper_assignments' => ['nullable', 'array'],
            'simper_assignments.*.expired_at' => ['nullable', 'date'],
            'simper_assignments.*.simper_number' => ['nullable', 'string', 'max:100'],
            'renew_simper_type_id' => ['nullable', 'string'],
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'is_operator.required' => 'Status driver wajib dipilih.',
        ]);

        $isOperator = (bool) $validated['is_operator'];
        $simperExpiredAt = $isOperator ? ($validated['simper_expired_at'] ?? $employee->simper_expired_at) : null;

        // Validasi kustom penugasan SIMPER ketika driver aktif
        if ($isOperator && is_array($request->input('simper_assignments'))) {
            $today = Carbon::today()->toDateString();
            foreach ($request->input('simper_assignments') as $typeId => $data) {
                if (!empty($data['enabled'])) {
                    $st = SimperType::find($typeId);
                    $typeName = $st ? $st->name : "Tipe #{$typeId}";
                    if (empty($data['expired_at'])) {
                        return back()->withInput()->with('error', "Masa berlaku untuk jenis SIMPER '{$typeName}' wajib diisi ketika dicentang.");
                    }
                    if ($data['expired_at'] < $today) {
                        // Cek apakah tanggal ini memang tanggal lama yang belum diubah oleh admin
                        $existingSimper = $employee->simpers->firstWhere('simper_type_id', (int) $typeId);
                        $isOldUnchanged = $existingSimper && $existingSimper->expired_at?->format('Y-m-d') === $data['expired_at'];
                        if (!$isOldUnchanged) {
                            return back()->withInput()->with('error', "Masa berlaku untuk jenis SIMPER '{$typeName}' tidak boleh tanggal lampau (minimal hari ini: {$today}).");
                        }
                    }
                }
            }
        }

        $employeeType = $request->input('employee_type');
        if ($employeeType === '__CUSTOM__' && $request->filled('custom_employee_type')) {
            $employeeType = trim($request->input('custom_employee_type'));
        }

        $photoPath = $employee->photo_path;
        if ($request->hasFile('photo')) {
            ImageCompressionService::deleteIfExists($employee->photo_path);
            $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'employees', 500, 500, 80);
        }

        $employee->update([
            'nik' => $validated['nik'] ?? $employee->nik,
            'name' => $validated['name'] ?? $employee->name,
            'employee_type' => $employeeType ?? $employee->employee_type ?? 'Karyawan',
            'unit_kerja' => $validated['unit_kerja'] ?? $employee->unit_kerja,
            'division' => $validated['division'] ?? $employee->division,
            'position' => $validated['position'] ?? $employee->position,
            'is_operator' => $isOperator,
            'simper_expired_at' => $simperExpiredAt,
            'photo_path' => $photoPath,
        ]);

        // Sinkronisasi Multi-SIMPER Personil
        $this->syncEmployeeSimpers($employee, $request, $isOperator, $simperExpiredAt);

        // Sinkronkan akun login User terkait
        $linkedUser = $employee->user ?: User::where('username', $employee->nik)->first();
        if ($linkedUser) {
            if (!$employee->user_id) {
                $employee->update(['user_id' => $linkedUser->id]);
            }
            $userData = [
                'name' => $employee->name,
                'username' => $employee->nik,
                'photo_path' => $photoPath,
            ];
            if (str_ends_with($linkedUser->email, '@kmh.co.id')) {
                $newCleanEmail = strtolower(str_replace(['-', ' '], '', $employee->nik)) . '@kmh.co.id';
                if (!User::where('email', $newCleanEmail)->where('id', '!=', $linkedUser->id)->exists()) {
                    $userData['email'] = $newCleanEmail;
                }
            }
            $linkedUser->update($userData);
        }

        return back()->with('success', "Data SIMPER untuk {$employee->name} berhasil diperbarui.");
    }

    /**
     * Hapus data personil / karyawan (Soft Delete masuk ke Tong Sampah).
     * Seluruh riwayat transaksi (Timesheet, Gate, P2H) tetap aman dan terhubung.
     */
    public function destroy(int $id): RedirectResponse
    {
        $employee = Employee::with(['user'])->findOrFail($id);

        $empName = $employee->name;
        $empNik = $employee->nik;
        $photoPath = $employee->photo_path;

        DB::transaction(function () use ($employee) {
            $user = $employee->user ?: User::where('username', $employee->nik)->first();

            // Berkas foto tidak dihapus saat soft delete agar foto tetap ada jika dipulihkan dari tong sampah.
            // Berkas baru dihapus permanen ketika dilakukan 'Hapus Permanen' (force delete) di TrashService.

            // Hapus data karyawan (soft delete - dipindahkan ke Tong Sampah)
            $employee->delete();

            // Nonaktifkan / soft delete akun login User terkait jika perannya murni hanya karyawan (bukan admin)
            if ($user && $user->role === 'employee' && ! $user->hasAnyRole(['super_admin', 'hse_admin', 'qma_admin', 'pg_admin', 'gate_officer'])) {
                $user->syncRoles([]);
                $user->delete();
            }
        });

        $message = "Data personil '{$empName}' ({$empNik}) berhasil dihapus dan dipindahkan ke Tong Sampah. Seluruh riwayat operasional masa lalu tetap tersimpan utuh.";

        // Jika request berasal dari halaman edit terpisah, arahkan kembali ke index
        if (url()->previous() === route('hse.employees.edit', $id)) {
            return redirect()->route('hse.employees.index')->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Helper sinkronisasi multi-SIMPER personil.
     */
    private function syncEmployeeSimpers(Employee $employee, Request $request, bool $isOperator, ?string $fallbackDate): void
    {
        if (! $isOperator) {
            $employee->simpers()->forceDelete();
            $employee->update(['simper_expired_at' => null]);
            return;
        }

        $simperAssignments = $request->input('simper_assignments') ?? $request->input('simpers');

        if (is_array($simperAssignments) && count($simperAssignments) > 0) {
            $activeTypeIds = [];
            $maxExpiredAt = null;

            foreach ($simperAssignments as $typeId => $data) {
                // Hanya aktif jika dicentang eksplisit, ATAU fallback jika form tidak menyertakan checkbox enabled namun memiliki tanggal
                $isEnabled = ! empty($data['enabled']);
                if (! $isEnabled && ! isset($data['enabled']) && ! empty($data['expired_at'])) {
                    $isEnabled = true;
                }

                if ($isEnabled && ! empty($data['expired_at'])) {
                    $expDate = $data['expired_at'];
                    $simperNum = ! empty($data['simper_number']) ? trim($data['simper_number']) : ('SMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT));

                    $simper = EmployeeSimper::withTrashed()->updateOrCreate(
                        ['employee_id' => $employee->id, 'simper_type_id' => (int) $typeId],
                        [
                            'simper_number' => $simperNum,
                            'expired_at' => $expDate,
                            'status' => 'active',
                            'deleted_at' => null,
                        ]
                    );

                    if ($simper->trashed()) {
                        $simper->restore();
                    }

                    $activeTypeIds[] = (int) $typeId;

                    if (! $maxExpiredAt || $expDate > $maxExpiredAt) {
                        $maxExpiredAt = $expDate;
                    }
                }
            }

            // Hapus jenis SIMPER yang sudah tidak dicentang (Gunakan forceDelete agar tidak memicu benturan Unique Index)
            EmployeeSimper::where('employee_id', $employee->id)
                ->whereNotIn('simper_type_id', $activeTypeIds)
                ->forceDelete();

            $employee->update(['simper_expired_at' => $maxExpiredAt]);
        } elseif ($request->filled('renew_simper_type_id')) {
            // Khusus alur Perpanjang SIMPER via Modal (#renewModal)
            $renewTypeId = $request->input('renew_simper_type_id');
            $newExpiry = $request->input('simper_expired_at');

            if ($newExpiry) {
                if ($renewTypeId === 'all') {
                    // Perpanjang semua SIMPER yang aktif dimiliki oleh karyawan
                    $existingSimpers = $employee->simpers;
                    if ($existingSimpers->isNotEmpty()) {
                        foreach ($existingSimpers as $s) {
                            $s->update([
                                'expired_at' => $newExpiry,
                                'status' => 'active',
                            ]);
                        }
                    } else {
                        // Jika belum ada SIMPER sama sekali, buatkan SIMPER-LV default
                        $lvTypeId = SimperType::where('code', 'SIMPER-LV')->value('id');
                        if ($lvTypeId) {
                            $simper = EmployeeSimper::withTrashed()->updateOrCreate(
                                ['employee_id' => $employee->id, 'simper_type_id' => $lvTypeId],
                                [
                                    'simper_number' => 'SMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                                    'expired_at' => $newExpiry,
                                    'status' => 'active',
                                    'deleted_at' => null,
                                ]
                            );
                            if ($simper->trashed()) {
                                $simper->restore();
                            }
                        }
                    }
                } else {
                    // Perpanjang jenis SIMPER spesifik yang dipilih pada dropdown
                    $targetTypeId = (int) $renewTypeId;
                    $simper = EmployeeSimper::withTrashed()->updateOrCreate(
                        ['employee_id' => $employee->id, 'simper_type_id' => $targetTypeId],
                        [
                            'simper_number' => 'SMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                            'expired_at' => $newExpiry,
                            'status' => 'active',
                            'deleted_at' => null,
                        ]
                    );
                    if ($simper->trashed()) {
                        $simper->restore();
                    }
                }

                // Sinkronkan simper_expired_at master karyawan dengan tanggal terlama di antara simper aktif
                $latestMax = $employee->simpers()->max('expired_at');
                $employee->update(['simper_expired_at' => $latestMax]);
            }
        } elseif ($fallbackDate) {
            // Fallback untuk pengujian sederhana yang mengirimkan 1 tanggal simper_expired_at
            $lvTypeId = SimperType::where('code', 'SIMPER-LV')->value('id');
            if ($lvTypeId) {
                $simper = EmployeeSimper::withTrashed()->updateOrCreate(
                    ['employee_id' => $employee->id, 'simper_type_id' => $lvTypeId],
                    [
                        'simper_number' => 'SMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
                        'expired_at' => $fallbackDate,
                        'status' => 'active',
                        'deleted_at' => null,
                    ]
                );
                if ($simper->trashed()) {
                    $simper->restore();
                }
                $employee->update(['simper_expired_at' => $fallbackDate]);
            }
        }
    }
}
