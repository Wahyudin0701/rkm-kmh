<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama karyawan / operator.
     */
    public function index(): View
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('employee.dashboard') && !$user->can('employee.timesheet') && !$user->can('employee.p2h')) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman operasional karyawan.');
        }

        $employee = $user->getOrCreateEmployee();
        $totalSupport = Vehicle::unitSupports()->count();
        $totalLv = Vehicle::lightVehicles()->count();

        $pendingActivity = null;
        $myRecentActivities = collect();

        if ($employee) {
            $pendingActivity = VehicleActivity::with(['vehicle', 'gate'])
                ->where('driver_id', $employee->id)
                ->where('validation_status', 'pending')
                ->latest('recorded_at')
                ->first();

            $myRecentActivities = VehicleActivity::with(['vehicle', 'gate', 'securityGuard'])
                ->where('driver_id', $employee->id)
                ->latest('recorded_at')
                ->take(6)
                ->get();
        }

        return view('employee.dashboard', compact(
            'employee',
            'totalSupport',
            'totalLv',
            'pendingActivity',
            'myRecentActivities'
        ));
    }

    /**
     * Koreksi angka KM pada tiket perjalanan yang masih berstatus pending oleh pengemudi.
     */
    public function updateKm(Request $request, int $id): RedirectResponse
    {
        $employee = auth()->user()->getOrCreateEmployee();
        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->driver_id !== $employee->id) {
            abort(403, 'Aksi Ditolak: Anda tidak memiliki akses untuk mengubah tiket ini.');
        }

        if ($activity->validation_status !== 'pending') {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Gagal: Tiket ini sudah diproses/disetujui oleh petugas Satpam di pos gerbang dan terkunci permanen.');
        }

        $validated = $request->validate([
            'current_km' => ['required', 'numeric', 'min:0'],
        ], [
            'current_km.required' => 'Nilai odometer KM saat ini wajib diisi.',
            'current_km.numeric' => 'Nilai odometer harus berupa angka.',
            'current_km.min' => 'Nilai odometer tidak boleh negatif.',
        ]);

        $previousKm = (float) $activity->previous_km;
        $newCurrentKm = (float) $validated['current_km'];

        if ($newCurrentKm < $previousKm) {
            return back()->withInput()->with('error', "Nilai kilometer input ({$newCurrentKm} KM) tidak boleh lebih kecil dari kilometer awal ({$previousKm} KM).");
        }

        $newDistance = max(0, $newCurrentKm - $previousKm);

        $activity->update([
            'current_km' => $newCurrentKm,
            'distance' => $newDistance,
        ]);

        return redirect()->route('employee.dashboard')
            ->with('success', "Odometer tiket {$activity->transaction_no} ({$activity->vehicle->code}) berhasil diperbaiki menjadi " . number_format($newCurrentKm, 0, ',', '.') . " KM.");
    }

    /**
     * Batalkan tiket perjalanan yang masih berstatus pending oleh pengemudi.
     */
    public function cancelActivity(Request $request, int $id): RedirectResponse
    {
        $employee = auth()->user()->getOrCreateEmployee();
        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->driver_id !== $employee->id) {
            abort(403, 'Aksi Ditolak: Anda tidak memiliki akses untuk membatalkan tiket ini.');
        }

        if ($activity->validation_status !== 'pending') {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Gagal: Tiket ini sudah diproses/disetujui oleh petugas Satpam di pos gerbang dan tidak dapat dibatalkan.');
        }

        $reason = trim($request->input('cancel_reason', 'Dibatalkan mandiri oleh pengemudi'));

        $activity->update([
            'validation_status' => 'cancelled',
            'rejection_note' => $reason,
        ]);

        return redirect()->route('employee.dashboard')
            ->with('success', "Tiket perjalanan {$activity->transaction_no} ({$activity->vehicle->code}) berhasil dibatalkan.");
    }
}
