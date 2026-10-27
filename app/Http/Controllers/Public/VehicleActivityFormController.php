<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleActivityRequest;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VehicleActivityFormController extends Controller
{

    /**
     * Tampilkan katalog / daftar kendaraan ringan (LV) untuk keperluan mobilisasi karyawan.
     */
    public function index(Request $request): View
    {
        $type = $request->input('type');
        $query = Vehicle::timesheetEligible();

        $user = auth()->user();
        $employee = null;
        $isPrivilegedUser = false;
        $activeSimpers = collect();
        $allowedScopes = [];
        $simperTypeIds = [];
        $hasUniversalScope = false;
        $canDriveLv = false;
        $heavyScopes = [];
        $isRestrictedEmployee = false;
        $noActiveSimper = false;

        if ($user) {
            $employee = $user->getOrCreateEmployee();

            // Admin privileged (Super Admin, QMA, Pos Gerbang) dapat memantau seluruh armada
            $isPrivilegedUser = $user->role === 'super-admin'
                || $user->hasRole('super-admin')
                || $user->can('qma.vehicles.view')
                || $user->can('qma.logs.view');

            // Pengemudi / karyawan biasa dibatasi hanya pada armada sesuai izin SIMPER aktif miliknya
            if (! $isPrivilegedUser && $employee) {
                $isRestrictedEmployee = true;

                $hasAnySimperRecord = $employee->simpers()->exists();
                if ($hasAnySimperRecord) {
                    $activeSimpers = $employee->simpers()->with('simperType')->get()->filter(fn($s) => $s->isValid());
                } elseif ($employee->is_operator && $employee->hasValidSimper()) {
                    // Fallback driver legacy sebelum ada multi-SIMPER
                    $allowedScopes[] = 'light_vehicle';
                    $canDriveLv = true;
                }

                if ($activeSimpers->isNotEmpty()) {
                    foreach ($activeSimpers as $s) {
                        if ($s->simperType) {
                            $simperTypeIds[] = $s->simperType->id;
                            $scopes = $s->simperType->scopes;
                            foreach ($scopes as $sc) {
                                $allowedScopes[] = $sc;
                                if ($sc === \App\Models\SimperType::SCOPE_ALL || $sc === 'all') {
                                    $hasUniversalScope = true;
                                }
                            }
                        }
                    }
                    $allowedScopes = array_unique($allowedScopes);
                    $canDriveLv = in_array(\App\Models\SimperType::SCOPE_LIGHT_VEHICLE, $allowedScopes, true)
                        || in_array('light_vehicle', $allowedScopes, true);
                    $heavyScopes = array_values(array_diff($allowedScopes, [\App\Models\SimperType::SCOPE_LIGHT_VEHICLE, 'light_vehicle']));
                }

                if (! $hasUniversalScope) {
                    if (empty($allowedScopes)) {
                        // Karyawan belum memiliki SIMPER aktif sama sekali
                        $noActiveSimper = true;
                        $query->whereRaw('1 = 0');
                    } else {
                        // Sesuaikan default tab type jika karyawan hanya berizin satu jenis
                        if ($canDriveLv && empty($heavyScopes)) {
                            if (! $type || $type === 'all' || $type === 'unit_support') {
                                $type = 'light_vehicle';
                            }
                        } elseif (! $canDriveLv && ! empty($heavyScopes)) {
                            if (! $type || $type === 'all' || $type === 'light_vehicle') {
                                $type = 'unit_support';
                            }
                        }

                        // Filter armada yang diizinkan sesuai SIMPER
                        $query->where(function ($subQ) use ($canDriveLv, $heavyScopes, $simperTypeIds) {
                            if ($canDriveLv) {
                                $subQ->orWhere('category', 'light_vehicle');
                            }
                            if (! empty($heavyScopes)) {
                                $subQ->orWhere(function ($hq) use ($heavyScopes, $simperTypeIds) {
                                    $hq->where('category', 'unit_support')
                                       ->where('is_timesheet_mobile', true)
                                       ->where(function ($filterQ) use ($heavyScopes, $simperTypeIds) {
                                           $filterQ->whereIn('unit_category', $heavyScopes)
                                                   ->orWhereIn('brand_type', $heavyScopes);

                                           if (! empty($simperTypeIds)) {
                                               $filterQ->orWhereIn('required_simper_type_id', $simperTypeIds);
                                           }

                                           if (in_array(\App\Models\SimperType::SCOPE_UNIT_SUPPORT_MOBILE, $heavyScopes, true)
                                               || in_array('SIMPER-DT', $heavyScopes, true)
                                               || in_array('unit_support_mobile', $heavyScopes, true)) {
                                               $filterQ->orWhere('is_timesheet_mobile', true);
                                           }
                                       });
                                });
                            }
                        });
                    }
                }
            }
        }

        // Filter tab kategori eksplisit
        $type = $type ?? 'all';
        if ($type === 'light_vehicle') {
            $query->where('category', 'light_vehicle');
        } elseif ($type === 'unit_support') {
            $query->where('category', 'unit_support')->where('is_timesheet_mobile', true);
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanSearch = preg_replace('/[^a-zA-Z0-9]/', '', $search);

            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%");

                if (! empty($cleanSearch)) {
                    $q->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '-', '') LIKE ?", ["%{$cleanSearch}%"])
                      ->orWhereRaw("REPLACE(REPLACE(license_plate, ' ', ''), '-', '') LIKE ?", ["%{$cleanSearch}%"]);
                }
            });
        }

        $vehicles = $query->orderBy('code')->paginate(24)->withQueryString();

        return view('mobilisasi.index', compact(
            'vehicles',
            'employee',
            'type',
            'canDriveLv',
            'heavyScopes',
            'isRestrictedEmployee',
            'noActiveSimper',
            'activeSimpers',
            'hasUniversalScope'
        ));
    }

    /**
     * Tampilkan form pencatatan aktivitas kendaraan saat scan QR code.
     */
    public function show(Request $request, string $code): View|RedirectResponse
    {
        $vehicle = Vehicle::timesheetEligible()
            ->where('code', $code)
            ->firstOrFail();

        // Jika pengemudi scan lewat kamera luar dan belum login, arahkan ke login dengan intended URL
        if (!auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('warning', "Silakan login terlebih dahulu untuk mengisi formulir timesheet unit {$vehicle->code}.");
        }

        // Ambil daftar gerbang aktif
        $gates = Gate::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Bersihkan data aktivitas pending yang telah kedaluwarsa (> 5 jam)
        VehicleActivity::expireOverduePendingActivities();

        // Tentukan arah yang diperbolehkan berdasarkan riwayat terakhir
        $allowedDirection = $vehicle->determineNextAllowedDirection();

        $verifiedDriver = null;
        $nikError = null;
        $nikErrorTitle = 'Verifikasi Gagal';
        $inputNik = trim((string) ($request->input('nik') ?? $request->input('driver_nik') ?? ''));

        $requiredSimper = $vehicle->requiredSimperType ? $vehicle->requiredSimperType->name : ($vehicle->isLightVehicle() ? 'SIMPER LV' : 'SIMPER Dump Truck / Heavy Mobile');

        // Cek jika ada pengemudi yang sudah terverifikasi sebelumnya (via form error redirect atau query string NIK)
        $oldDriverId = old('driver_id');
        if ($oldDriverId) {
            $emp = Employee::find($oldDriverId);
            if ($emp && $emp->is_operator && $emp->hasValidSimperForVehicle($vehicle)) {
                $verifiedDriver = $emp;
            }
        } elseif ($inputNik !== '') {
            $emp = Employee::where('nik', $inputNik)
                ->orWhereRaw('LOWER(nik) = ?', [strtolower($inputNik)])
                ->first();

            if (!$emp) {
                $nikErrorTitle = 'Verifikasi Gagal';
                $nikError = "NIK '{$inputNik}' tidak terdaftar dalam database SIMPER PT KMH. Silakan periksa kembali atau hubungi Divisi HSE.";
            } elseif (!$emp->is_operator) {
                $nikErrorTitle = 'Bukan Driver Terdaftar';
                $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) terdaftar sebagai BUKAN DRIVER dan tidak memiliki izin mengemudi (SIMPER). Anda tidak diizinkan mengoperasikan kendaraan unit. Silakan hubungi Divisi HSE.";
            } elseif (!$emp->hasValidSimperForVehicle($vehicle)) {
                if (!$emp->hasValidSimper()) {
                    if ($emp->simper_expired_at) {
                        $expiredDate = $emp->simper_expired_at->format('d/m/Y');
                        $nikErrorTitle = 'Peringatan: SIMPER Kedaluwarsa';
                        $nikError = "SIMPER atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) telah KEDALUWARSA pada {$expiredDate}. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE untuk perpanjangan masa berlaku SIMPER.";
                    } else {
                        $nikErrorTitle = 'SIMPER Belum Terdaftar';
                        $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) belum memiliki tanggal masa berlaku SIMPER aktif. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE.";
                    }
                } else {
                    $nikErrorTitle = 'Izin SIMPER Tidak Sesuai';
                    $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) tidak memiliki izin SIMPER aktif yang sesuai untuk mengoperasikan unit {$vehicle->code} (Wajib: {$requiredSimper}). Silakan hubungi Divisi HSE.";
                }
            } else {
                $verifiedDriver = $emp;
            }
        } else {
            $emp = auth()->user()->getOrCreateEmployee();
            if ($emp) {
                if (!$emp->is_operator) {
                    $nikErrorTitle = 'Bukan Driver Terdaftar';
                    $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) terdaftar sebagai BUKAN DRIVER dan tidak memiliki izin mengemudi (SIMPER). Anda tidak diizinkan mengoperasikan kendaraan unit. Silakan hubungi Divisi HSE.";
                } elseif (!$emp->hasValidSimperForVehicle($vehicle)) {
                    if (!$emp->hasValidSimper()) {
                        $nikErrorTitle = 'Peringatan: SIMPER Kedaluwarsa';
                        $nikError = "SIMPER atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) telah KEDALUWARSA. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE untuk perpanjangan masa berlaku SIMPER.";
                    } else {
                        $nikErrorTitle = 'Izin SIMPER Tidak Sesuai';
                        $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) tidak memiliki izin SIMPER aktif yang sesuai untuk mengoperasikan unit {$vehicle->code} (Wajib: {$requiredSimper}). Silakan hubungi Divisi HSE.";
                    }
                } else {
                    $verifiedDriver = $emp;
                    $inputNik = $emp->nik;
                }
            }
        }

        // Ambil 5 riwayat perjalanan terakhir dari unit ini
        $recentActivities = VehicleActivity::with(['driver', 'gate', 'securityGuard'])
            ->where('vehicle_id', $vehicle->id)
            ->latest('recorded_at')
            ->take(5)
            ->get();

        return view('public.vehicle_activity_form', compact(
            'vehicle',
            'gates',
            'allowedDirection',
            'verifiedDriver',
            'nikError',
            'nikErrorTitle',
            'inputNik',
            'recentActivities'
        ));
    }

    /**
     * Cek validitas NIK dan status SIMPER pengemudi via AJAX / real-time request.
     */
    public function checkNik(Request $request, string $code): JsonResponse
    {
        $vehicle = Vehicle::timesheetEligible()
            ->where('code', $code)
            ->firstOrFail();

        $rawNik = trim((string) $request->input('nik', ''));
        if ($rawNik === '') {
            return response()->json([
                'status' => 'empty',
                'title' => 'Input Kosong',
                'message' => 'Silakan masukkan Nomor Induk Karyawan (NIK) Anda terlebih dahulu.',
            ], 422);
        }

        $employee = Employee::where('nik', $rawNik)
            ->orWhereRaw('LOWER(nik) = ?', [strtolower($rawNik)])
            ->first();

        if (!$employee) {
            return response()->json([
                'status' => 'not_found',
                'title' => 'Verifikasi Gagal',
                'message' => "NIK '{$rawNik}' tidak terdaftar dalam database SIMPER PT KMH. Silakan periksa kembali atau hubungi Divisi HSE.",
            ], 404);
        }

        // Pengecekan 1: Karyawan terdaftar tapi BUKAN DRIVER
        if (!$employee->is_operator) {
            return response()->json([
                'status' => 'not_driver',
                'title' => 'Bukan Driver Terdaftar',
                'message' => "Karyawan atas nama {$employee->name} (NIK: {$employee->nik} - {$employee->division}) terdaftar sebagai BUKAN DRIVER dan tidak memiliki izin mengemudi (SIMPER). Anda tidak diizinkan mengoperasikan kendaraan unit. Silakan hubungi Divisi HSE.",
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nik' => $employee->nik,
                    'division' => $employee->division ?? 'Umum',
                    'is_operator' => false,
                ],
            ], 422);
        }

        $requiredSimper = $vehicle->requiredSimperType ? $vehicle->requiredSimperType->name : ($vehicle->isLightVehicle() ? 'SIMPER LV' : 'SIMPER Dump Truck / Heavy Mobile');

        // Pengecekan 2: Karyawan adalah Driver, tapi masa berlaku SIMPER tidak sesuai untuk unit ini
        if (!$employee->hasValidSimperForVehicle($vehicle)) {
            if (!$employee->hasValidSimper()) {
                if ($employee->simper_expired_at) {
                    $expiredDate = $employee->simper_expired_at->format('d/m/Y');
                    $title = 'Peringatan: SIMPER Kedaluwarsa';
                    $message = "SIMPER atas nama {$employee->name} (NIK: {$employee->nik} - {$employee->division}) telah KEDALUWARSA pada {$expiredDate}. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE untuk perpanjangan masa berlaku SIMPER.";
                } else {
                    $expiredDate = 'Belum Ada';
                    $title = 'SIMPER Belum Terdaftar';
                    $message = "Karyawan atas nama {$employee->name} (NIK: {$employee->nik} - {$employee->division}) belum memiliki tanggal masa berlaku SIMPER aktif. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE.";
                }
            } else {
                $expiredDate = $employee->simper_expired_at ? $employee->simper_expired_at->format('d/m/Y') : '-';
                $title = 'Izin SIMPER Tidak Sesuai';
                $message = "Karyawan atas nama {$employee->name} (NIK: {$employee->nik} - {$employee->division}) tidak memiliki izin SIMPER aktif yang sesuai untuk mengoperasikan unit {$vehicle->code} (Wajib: {$requiredSimper}). Silakan hubungi Divisi HSE.";
            }

            return response()->json([
                'status' => 'expired',
                'title' => $title,
                'message' => $message,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'nik' => $employee->nik,
                    'division' => $employee->division ?? 'Umum',
                    'simper_expired_at' => $expiredDate,
                    'is_operator' => true,
                ],
            ], 422);
        }

        return response()->json([
            'status' => 'valid',
            'title' => 'SIMPER Valid',
            'message' => "SIMPER valid ({$requiredSimper}). Selamat bertugas, {$employee->name}!",
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'nik' => $employee->nik,
                'division' => $employee->division ?? 'Umum',
                'position' => $employee->position ?? 'Karyawan',
                'simper_expired_at' => $employee->simper_expired_at ? $employee->simper_expired_at->format('d/m/Y') : '-',
                'is_operator' => true,
            ],
        ]);
    }

    /**
     * Simpan aktivitas kendaraan baru ke database.
     */
    public function store(StoreVehicleActivityRequest $request, string $code): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('warning', 'Sesi login Anda telah berakhir. Silakan login kembali.');
        }

        $vehicle = Vehicle::timesheetEligible()
            ->where('code', $code)
            ->firstOrFail();

        $previousKm = (float) $vehicle->current_km;
        $currentKm = (float) $request->input('current_km');
        $distance = max(0, $currentKm - $previousKm);

        DB::transaction(function () use ($request, $vehicle, $previousKm, $currentKm, $distance) {
            // Generate nomor transaksi unik
            $trxNo = 'TRX-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            VehicleActivity::create([
                'transaction_no' => $trxNo,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $request->input('driver_id'),
                'gate_id' => $request->input('gate_id'),
                'direction' => $request->input('direction'),
                'previous_km' => $previousKm,
                'current_km' => $currentKm,
                'distance' => $distance,
                'purpose' => $request->input('purpose'),
                'recorded_at' => Carbon::now(),
                'validation_status' => 'pending',
            ]);
        });

        return redirect()
            ->route('employee.dashboard')
            ->with('success', 'Form perjalanan berhasil dikirim! Silakan lapor ke petugas Satpam di pos gerbang untuk verifikasi fisik.');
    }
}
