<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\VehicleActivity;
use App\Models\VehicleLocationHistory;
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
        $user = $request->user();
        $currentGuard = $user ? $user->securityGuard : null;

        $allGates = Gate::where('is_active', true)->orderBy('name')->get();

        // Cek gate yang dipilih: query param > session 'active_gate_id' > user->gate_id
        $selectedGateId = $request->query('gate_id') 
            ?: session('active_gate_id') 
            ?: ($user?->gate_id);

        $needsGateSelection = false;
        $gate = null;

        if ($selectedGateId && $allGates->contains('id', (int) $selectedGateId)) {
            $gate = $allGates->firstWhere('id', (int) $selectedGateId);
            session(['active_gate_id' => $gate->id]);
            if ($user && $user->gate_id !== $gate->id) {
                $user->update(['gate_id' => $gate->id]);
            }
        } else {
            // Belum ada pos yang dipilih di sesi
            $gate = $allGates->first();
            $needsGateSelection = true;
        }

        // Otomatis bersihkan antrean pending yang sudah lewat batas 5 jam
        VehicleActivity::expireOverduePendingActivities();

        $pendingActivities = collect();
        if ($gate) {
            $pendingActivities = VehicleActivity::where('gate_id', $gate->id)
                ->where('validation_status', 'pending')
                ->with(['vehicle', 'driver'])
                ->orderBy('recorded_at', 'asc')
                ->get();
        }

        // Hitung antrean pending di setiap gerbang untuk pop-up pemilihan
        $gateQueueCounts = VehicleActivity::where('validation_status', 'pending')
            ->select('gate_id', DB::raw('count(*) as count'))
            ->groupBy('gate_id')
            ->pluck('count', 'gate_id');

        // Daftar satpam aktif untuk fallback pilihan
        $guards = SecurityGuard::where('is_active', true)->orderBy('name')->get();

        return view('pg.gate_inspection', compact(
            'gate', 
            'allGates',
            'gateQueueCounts',
            'needsGateSelection',
            'pendingActivities', 
            'guards', 
            'currentGuard'
        ));
    }

    /**
     * Set / ganti pos gerbang aktif yang sedang dijaga oleh petugas saat ini.
     */
    public function selectGate(Request $request): RedirectResponse
    {
        $request->validate([
            'gate_id' => ['required', 'exists:gates,id'],
        ], [
            'gate_id.required' => 'Silakan pilih salah satu pos gerbang.',
            'gate_id.exists' => 'Pos gerbang yang dipilih tidak valid.',
        ]);

        $gateId = (int) $request->input('gate_id');
        $gate = Gate::findOrFail($gateId);

        session(['active_gate_id' => $gate->id]);

        $user = $request->user();
        if ($user && $user->gate_id !== $gate->id) {
            $user->update(['gate_id' => $gate->id]);
        }

        return redirect()->route('pg.gate.inspection', ['gate_id' => $gate->id])
            ->with('success', "Pos jaga aktif berhasil dialihkan ke: {$gate->name}");
    }

    /**
     * Endpoint sync antrean realtime (Fetch) tanpa reload halaman.
     */
    public function liveQueue(Request $request): JsonResponse
    {
        $user = $request->user();
        $gateId = $request->query('gate_id') 
            ?: session('active_gate_id') 
            ?: ($user ? $user->gate_id : null);
        $gate = $gateId ? Gate::find($gateId) : Gate::first();

        // Otomatis bersihkan antrean pending yang sudah lewat batas 5 jam
        VehicleActivity::expireOverduePendingActivities();

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
            $request->validate([
                'security_guard_id' => ['required', 'exists:security_guards,id'],
            ], [
                'security_guard_id.required' => 'Silakan pilih nama petugas Satpam yang melakukan pemeriksaan fisik.',
                'security_guard_id.exists' => 'Petugas Satpam yang dipilih tidak valid dalam sistem.',
            ]);
            $guardId = $request->input('security_guard_id');
        }

        $activity = VehicleActivity::with(['vehicle', 'gate'])->findOrFail($id);

        if ($activity->validation_status !== 'pending') {
            return back()->with('error', 'Aktivitas ini sudah pernah divalidasi sebelumnya.');
        }

        $guard = SecurityGuard::find($guardId);
        $guardName = $guard?->name ?? ($user?->name ?? 'Petugas Pos Gerbang');

        DB::transaction(function () use ($guardId, $guardName, $activity, $user) {
            // 1. Update status aktivitas menjadi approved
            $activity->update([
                'validation_status' => 'approved',
                'security_guard_id' => $guardId,
                'validated_at' => Carbon::now(),
            ]);

            // 2. Tentukan posisi site (in = inside_site, out = outside_site)
            $isEntering = strtolower($activity->direction) === 'in';
            $siteStatus = $isEntering ? 'inside_site' : 'outside_site';
            $gateName = $activity->gate?->name ?? 'Pos Gerbang';

            $detailLocation = $isEntering
                ? $gateName
                : ($activity->purpose ? 'Luar Site - ' . $activity->purpose : 'Luar Area Proyek');

            $directionLabel = $isEntering ? 'Masuk' : 'Keluar';
            $note = "{$directionLabel} perlintasan {$gateName} (Trx: {$activity->transaction_no})";
            if (!$isEntering && $activity->purpose) {
                $note .= " - Keperluan: {$activity->purpose}";
            }

            // 3. Update odometer dan status posisi master kendaraan
            $activity->vehicle->update([
                'current_km' => $activity->current_km,
                'site_status' => $siteStatus,
                'location' => $detailLocation,
                'location_updated_at' => Carbon::now(),
                'location_updated_by' => $guardName,
                'location_note' => $note,
            ]);

            // 4. Catat riwayat perubahan posisi kendaraan (Audit Trail)
            VehicleLocationHistory::create([
                'vehicle_id' => $activity->vehicle_id,
                'site_status' => $siteStatus,
                'location' => $detailLocation,
                'note' => $note,
                'source' => 'gate_crossing',
                'vehicle_activity_id' => $activity->id,
                'user_id' => $user?->id,
                'user_name' => $guardName,
                'recorded_at' => Carbon::now(),
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
