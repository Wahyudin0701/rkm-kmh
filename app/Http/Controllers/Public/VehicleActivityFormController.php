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
        $query = Vehicle::lightVehicles();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->orderBy('code')->paginate(24)->withQueryString();

        $employee = null;
        if (auth()->check()) {
            $employee = auth()->user()->employee;
        }

        return view('mobilisasi.index', compact('vehicles', 'employee'));
    }

    /**
     * Tampilkan form pencatatan aktivitas kendaraan saat scan QR code.
     */
    public function show(Request $request, string $code): View
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'light_vehicle')
            ->firstOrFail();

        // Ambil daftar gerbang aktif
        $gates = Gate::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Tentukan arah yang diperbolehkan berdasarkan riwayat terakhir
        $allowedDirection = $vehicle->determineNextAllowedDirection();

        $verifiedDriver = null;
        $nikError = null;
        $nikErrorTitle = 'Verifikasi Gagal';
        $inputNik = trim((string) $request->input('nik', ''));

        // Cek jika ada pengemudi yang sudah terverifikasi sebelumnya (via form error redirect atau query string NIK)
        $oldDriverId = old('driver_id');
        if ($oldDriverId) {
            $emp = Employee::find($oldDriverId);
            if ($emp && $emp->is_operator && $emp->hasValidSimper()) {
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
            } elseif (!$emp->hasValidSimper()) {
                if ($emp->simper_expired_at) {
                    $expiredDate = $emp->simper_expired_at->format('d/m/Y');
                    $nikErrorTitle = 'Peringatan: SIMPER Kedaluwarsa';
                    $nikError = "SIMPER atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) telah KEDALUWARSA pada {$expiredDate}. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE untuk perpanjangan masa berlaku SIMPER.";
                } else {
                    $nikErrorTitle = 'SIMPER Belum Terdaftar';
                    $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) belum memiliki tanggal masa berlaku SIMPER aktif. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE.";
                }
            } else {
                $verifiedDriver = $emp;
            }
        } elseif (auth()->check() && auth()->user()->isEmployee()) {
            $emp = auth()->user()->employee;
            if ($emp) {
                if (!$emp->is_operator) {
                    $nikErrorTitle = 'Bukan Driver Terdaftar';
                    $nikError = "Karyawan atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) terdaftar sebagai BUKAN DRIVER dan tidak memiliki izin mengemudi (SIMPER). Anda tidak diizinkan mengoperasikan kendaraan unit. Silakan hubungi Divisi HSE.";
                } elseif (!$emp->hasValidSimper()) {
                    $nikErrorTitle = 'Peringatan: SIMPER Kedaluwarsa';
                    $nikError = "SIMPER atas nama {$emp->name} (NIK: {$emp->nik} - {$emp->division}) telah KEDALUWARSA. Anda tidak diizinkan mengoperasikan kendaraan. Silakan hubungi Divisi HSE untuk perpanjangan masa berlaku SIMPER.";
                } else {
                    $verifiedDriver = $emp;
                    $inputNik = $emp->nik;
                }
            }
        }

        return view('public.vehicle_activity_form', compact(
            'vehicle',
            'gates',
            'allowedDirection',
            'verifiedDriver',
            'nikError',
            'nikErrorTitle',
            'inputNik'
        ));
    }

    /**
     * Cek validitas NIK dan status SIMPER pengemudi via AJAX / real-time request.
     */
    public function checkNik(Request $request, string $code): JsonResponse
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'light_vehicle')
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

        // Pengecekan 2: Karyawan adalah Driver, tapi masa berlaku SIMPER kedaluwarsa
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
            'message' => "SIMPER valid. Selamat bertugas, {$employee->name}!",
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'nik' => $employee->nik,
                'division' => $employee->division ?? 'Umum',
                'position' => $employee->position ?? 'Karyawan',
                'simper_expired_at' => $employee->simper_expired_at->format('d/m/Y'),
                'is_operator' => true,
            ],
        ]);
    }

    /**
     * Simpan aktivitas kendaraan baru ke database.
     */
    public function store(StoreVehicleActivityRequest $request, string $code): RedirectResponse
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'light_vehicle')
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
            ->route('vehicle.activity.form', ['code' => $code])
            ->with('success', 'Aktivitas kendaraan berhasil dicatat! Silakan lapor ke petugas Satpam di pos gerbang untuk verifikasi fisik.');
    }
}
