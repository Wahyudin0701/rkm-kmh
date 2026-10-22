<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Illuminate\Http\JsonResponse;
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
            // Otomatis batalkan perjalanan pending yang sudah melewati batas waktu 5 jam
            VehicleActivity::expireOverduePendingActivities();

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
     * Koreksi angka KM pada perjalanan yang masih berstatus pending oleh pengemudi.
     */
    public function updateKm(Request $request, int $id): RedirectResponse
    {
        $employee = auth()->user()->getOrCreateEmployee();
        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->driver_id !== $employee->id) {
            abort(403, 'Aksi Ditolak: Anda tidak memiliki akses untuk mengubah perjalanan ini.');
        }

        if ($activity->validation_status !== 'pending') {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Gagal: Perjalanan ini sudah diproses/disetujui oleh petugas Satpam di pos gerbang dan terkunci permanen.');
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
            ->with('success', "Odometer perjalanan {$activity->transaction_no} ({$activity->vehicle->code}) berhasil diperbaiki menjadi " . number_format($newCurrentKm, 0, ',', '.') . " KM.");
    }

    /**
     * Batalkan perjalanan yang masih berstatus pending oleh pengemudi.
     */
    public function cancelActivity(Request $request, int $id): RedirectResponse
    {
        $employee = auth()->user()->getOrCreateEmployee();
        if (!$employee) {
            return redirect()->route('employee.dashboard')->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->driver_id !== $employee->id) {
            abort(403, 'Aksi Ditolak: Anda tidak memiliki akses untuk membatalkan perjalanan ini.');
        }

        if ($activity->validation_status !== 'pending') {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Gagal: Perjalanan ini sudah diproses/disetujui oleh petugas Satpam di pos gerbang dan tidak dapat dibatalkan.');
        }

        $reason = trim($request->input('cancel_reason', 'Dibatalkan mandiri oleh pengemudi'));

        $activity->update([
            'validation_status' => 'cancelled',
            'rejection_note' => $reason,
        ]);

        return redirect()->route('employee.dashboard')
            ->with('success', "Perjalanan {$activity->transaction_no} ({$activity->vehicle->code}) berhasil dibatalkan.");
    }

    /**
     * Endpoint micro-polling super ringan (JSON) untuk memantau status validasi tiket perjalanan driver.
     * Menghemat bandwidth & CPU server dibanding me-refresh halaman penuh secara berulang.
     */
    public function activeTripStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $employee = $user->getOrCreateEmployee();
        if (!$employee) {
            return response()->json(['status' => 'no_employee', 'has_pending' => false]);
        }

        $activityId = $request->query('activity_id');

        $activity = null;
        if ($activityId) {
            $activity = VehicleActivity::with(['vehicle', 'gate', 'securityGuard'])
                ->where('driver_id', $employee->id)
                ->find($activityId);
        }

        if (!$activity) {
            // Ambil tiket pending terbaru yang masih aktif
            $activity = VehicleActivity::with(['vehicle', 'gate', 'securityGuard'])
                ->where('driver_id', $employee->id)
                ->where('validation_status', 'pending')
                ->latest('recorded_at')
                ->first();
        }

        // Jika masih null, ambil tiket paling akhir dari driver ini
        if (!$activity) {
            $activity = VehicleActivity::with(['vehicle', 'gate', 'securityGuard'])
                ->where('driver_id', $employee->id)
                ->latest('recorded_at')
                ->first();
        }

        if (!$activity) {
            return response()->json([
                'has_pending' => false,
                'status' => 'none',
            ]);
        }

        return response()->json([
            'id' => $activity->id,
            'transaction_no' => $activity->transaction_no,
            'vehicle_code' => $activity->vehicle?->code ?? '-',
            'vehicle_name' => $activity->vehicle?->brand_type ?? '-',
            'status' => $activity->validation_status,
            'has_pending' => $activity->validation_status === 'pending',
            'is_approved' => $activity->validation_status === 'approved',
            'is_rejected' => $activity->validation_status === 'rejected',
            'is_cancelled' => $activity->validation_status === 'cancelled',
            'cancellation_source' => $activity->cancellation_source_label,
            'gate_name' => $activity->gate?->name ?? 'Pos Gerbang',
            'guard_name' => $activity->securityGuard?->name ?? 'Petugas Satpam',
            'rejection_note' => $activity->rejection_note,
            'recorded_at' => $activity->recorded_at ? $activity->recorded_at->format('H:i') . ' WIB' : null,
            'validated_at' => $activity->validated_at ? $activity->validated_at->format('H:i') . ' WIB' : null,
        ]);
    }
}
