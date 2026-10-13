<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationLogController extends Controller
{
    /**
     * Tampilkan rekapitulasi audit dan log riwayat verifikasi gerbang oleh petugas Satpam.
     */
    public function index(Request $request): View
    {
        $query = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->orderByRaw('COALESCE(validated_at, recorded_at) DESC');

        // Filter Rentang Tanggal (Default Hari Ini)
        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('recorded_at', [$start, $end]);
        } elseif ($startDate) {
            $query->where('recorded_at', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('recorded_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        // Filter Regu Satpam (1, 2, 3)
        if ($request->filled('team')) {
            $team = (int) $request->input('team');
            $query->whereHas('securityGuard', function ($sq) use ($team) {
                $sq->where('team', $team);
            });
        }

        // Filter Petugas Satpam Spesifik
        if ($request->filled('security_guard_id')) {
            $query->where('security_guard_id', $request->input('security_guard_id'));
        }

        // Filter Pos Gerbang
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->input('gate_id'));
        }

        // Filter Arah Gerbang (in / out)
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        // Filter Status Verifikasi (approved, rejected, pending)
        if ($request->filled('status')) {
            $query->where('validation_status', $request->input('status'));
        }

        // Filter Kata Kunci Pencarian (No Transaksi, Petugas Satpam, Badge, Driver, NIK, Plat, Kode LV)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('rejection_note', 'like', "%{$search}%")
                    ->orWhereHas('securityGuard', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('badge_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('code', 'like', "%{$search}%")
                            ->orWhere('brand_type', 'like', "%{$search}%")
                            ->orWhere('license_plate', 'like', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        $activities = $query->paginate(20)->withQueryString();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();
        $guards = SecurityGuard::orderBy('name')->get();

        return view('pg.verification_logs', compact('activities', 'gates', 'guards', 'startDate', 'endDate'));
    }
}
