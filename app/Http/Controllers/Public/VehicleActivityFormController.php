<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleActivityRequest;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VehicleActivityFormController extends Controller
{
    /**
     * Tampilkan form pencatatan aktivitas kendaraan saat scan QR code.
     */
    public function show(string $code): View
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'light_vehicle')
            ->firstOrFail();

        // Ambil semua driver/operator aktif
        $drivers = Employee::operators()
            ->orderBy('name')
            ->get();

        // Ambil daftar gerbang aktif
        $gates = Gate::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Tentukan arah yang diperbolehkan berdasarkan riwayat terakhir
        $allowedDirection = $vehicle->determineNextAllowedDirection();

        return view('public.vehicle_activity_form', compact('vehicle', 'drivers', 'gates', 'allowedDirection'));
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
