<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GateInspectionController extends Controller
{
    /**
     * Dashboard antrean validasi di pos gerbang bersangkutan.
     */
    public function index(Request $request): View
    {
        // Ambil gerbang yang terikat pada akun petugas pos jaga yang sedang login
        $user = $request->user();
        $currentGuard = $user ? $user->securityGuard : null;
        $gateId = $user ? $user->gate_id : $request->query('gate_id');

        // Jika tidak ada gate_id terikat, ambil gerbang pertama sebagai fallback
        $gate = $gateId ? Gate::find($gateId) : Gate::first();

        $pendingActivities = collect();
        if ($gate) {
            $pendingActivities = VehicleActivity::where('gate_id', $gate->id)
                ->where('validation_status', 'pending')
                ->with(['vehicle', 'driver'])
                ->orderBy('recorded_at', 'asc')
                ->get();
        }

        // Daftar satpam aktif untuk fallback pilihan
        $guards = SecurityGuard::where('is_active', true)->orderBy('name')->get();

        return view('pg.gate_inspection', compact(
            'gate', 
            'pendingActivities', 
            'guards', 
            'currentGuard'
        ));
    }

    /**
     * Endpoint sync antrean realtime (Fetch) tanpa reload halaman.
     */
    public function liveQueue(Request $request): JsonResponse
    {
        $user = $request->user();
        $gateId = $user ? $user->gate_id : $request->query('gate_id');
        $gate = $gateId ? Gate::find($gateId) : Gate::first();

        $pendingActivities = collect();
        if ($gate) {
            $pendingActivities = VehicleActivity::where('gate_id', $gate->id)
                ->where('validation_status', 'pending')
                ->with(['vehicle', 'driver'])
                ->orderBy('recorded_at', 'asc')
                ->get();
        }

        $currentGuard = $user ? $user->securityGuard : null;
        $guards = SecurityGuard::where('is_active', true)->orderBy('name')->get();

        $items = $pendingActivities->map(function ($act) {
            $unitText = trim(($act->vehicle->brand_type ?? 'Kendaraan') . ' ' . ($act->vehicle->code ?? ''));
            $driverText = $act->driver->name ?? 'Pengemudi';
            $directionText = strtolower($act->direction) === 'in' ? 'akan masuk gerbang' : 'akan keluar gerbang';

            return [
                'id' => $act->id,
                'code' => $act->vehicle->code ?? '',
                'brand_type' => $act->vehicle->brand_type ?? '',
                'license_plate' => $act->vehicle->license_plate ?? '',
                'driver_name' => $driverText,
                'direction' => strtolower($act->direction),
                'announcement' => "Perhatian, Unit {$unitText}, pengemudi {$driverText}, {$directionText}.",
            ];
        });

        return response()->json([
            'count' => $pendingActivities->count(),
            'items' => $items,
            'html' => view('pg.partials.queue_cards', compact('pendingActivities', 'currentGuard', 'guards'))->render(),
        ]);
    }

    /**
     * Setujui aktivitas kendaraan oleh Satpam di pos gerbang.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $currentGuard = $user ? $user->securityGuard : null;
        $guardId = $request->input('security_guard_id') ?? ($currentGuard ? $currentGuard->id : null);

        if (!$guardId) {
            $fallbackGuard = SecurityGuard::where('is_active', true)->first() ?? SecurityGuard::first();
            if ($fallbackGuard) {
                $guardId = $fallbackGuard->id;
            } else {
                $request->validate([
                    'security_guard_id' => ['required', 'exists:security_guards,id'],
                ], [
                    'security_guard_id.required' => 'Silakan pilih nama petugas Satpam yang melakukan pemeriksaan.',
                ]);
                $guardId = $request->input('security_guard_id');
            }
        }

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->validation_status !== 'pending') {
            return back()->with('error', 'Aktivitas ini sudah pernah divalidasi sebelumnya.');
        }

        DB::transaction(function () use ($guardId, $activity) {
            // 1. Update status aktivitas menjadi approved
            $activity->update([
                'validation_status' => 'approved',
                'security_guard_id' => $guardId,
                'validated_at' => Carbon::now(),
            ]);

            // 2. Update odometer master kendaraan
            $activity->vehicle->update([
                'current_km' => $activity->current_km,
            ]);
        });

        return back()->with('success', "Kendaraan {$activity->vehicle->code} berhasil disetujui untuk melintas gerbang.");
    }

    /**
     * Tolak aktivitas kendaraan oleh Satpam.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $currentGuard = $user ? $user->securityGuard : null;
        $guardId = $request->input('security_guard_id') ?? ($currentGuard ? $currentGuard->id : null);

        if (!$guardId) {
            $request->validate([
                'security_guard_id' => ['required', 'exists:security_guards,id'],
            ], [
                'security_guard_id.required' => 'Silakan pilih nama petugas Satpam.',
            ]);
            $guardId = $request->input('security_guard_id');
        }

        $request->validate([
            'rejection_note' => ['required', 'string', 'max:500'],
        ], [
            'rejection_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $activity = VehicleActivity::with('vehicle')->findOrFail($id);

        if ($activity->validation_status !== 'pending') {
            return back()->with('error', 'Aktivitas ini sudah pernah divalidasi sebelumnya.');
        }

        $activity->update([
            'validation_status' => 'rejected',
            'security_guard_id' => $guardId,
            'rejection_note' => $request->input('rejection_note'),
            'validated_at' => Carbon::now(),
        ]);

        return back()->with('success', "Aktivitas kendaraan {$activity->vehicle->code} telah ditolak.");
    }
}
