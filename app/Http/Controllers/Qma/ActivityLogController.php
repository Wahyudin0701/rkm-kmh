<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan Log Aktivitas QMA 10 Kolom lengkap dengan filter tanggal dan gate.
     */
    public function index(Request $request): View
    {
        $query = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->latest('recorded_at');

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

        // Filter Gerbang
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->input('gate_id'));
        }

        // Filter Arah Gerbang (in/out)
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        // Filter Kata Kunci Pencarian (Kode LV, Tipe Kendaraan, Plat, Driver, Keperluan)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vq) use ($search) {
                    $vq->where('code', 'like', "%{$search}%")
                        ->orWhere('brand_type', 'like', "%{$search}%")
                        ->orWhere('license_plate', 'like', "%{$search}%");
                })->orWhereHas('driver', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                })->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        // Filter Status Validasi (pending, approved, rejected)
        if ($request->filled('status')) {
            $query->where('validation_status', $request->input('status'));
        }

        $activities = $query->paginate(20)->withQueryString();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('qma.activity_log', compact('activities', 'gates', 'startDate', 'endDate'));
    }

    /**
     * Tampilkan Summary Aktivitas Kendaraan (Rekapitulasi Armada, Driver, Pos Gerbang & Statistik).
     */
    public function summary(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);
        $type = $request->input('type');
        $gateId = $request->input('gate_id');

        $query = VehicleActivity::query();

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('recorded_at', [$start, $end]);
        } elseif ($startDate) {
            $query->where('recorded_at', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('recorded_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($request->filled('gate_id')) {
            $query->where('gate_id', $gateId);
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->whereHas('vehicle', function ($q) use ($type) {
                if ($type === 'lv' || $type === 'light_vehicle') {
                    $q->where('category', 'light_vehicle');
                } elseif ($type === 'support' || $type === 'unit_support') {
                    $q->where('category', 'unit_support');
                }
            });
        }

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_activities,
            COALESCE(SUM(distance), 0) as total_distance,
            COUNT(CASE WHEN direction = 'in' THEN 1 END) as in_count,
            COUNT(CASE WHEN direction = 'out' THEN 1 END) as out_count,
            COUNT(CASE WHEN validation_status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN validation_status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN validation_status = 'rejected' THEN 1 END) as rejected_count
        ")->first();

        $totalActivities = (int) ($stats->total_activities ?? 0);
        $totalDistance = (float) ($stats->total_distance ?? 0);
        $inCount = (int) ($stats->in_count ?? 0);
        $outCount = (int) ($stats->out_count ?? 0);
        $approvedCount = (int) ($stats->approved_count ?? 0);
        $pendingCount = (int) ($stats->pending_count ?? 0);
        $rejectedCount = (int) ($stats->rejected_count ?? 0);

        // Rekapitulasi per Kendaraan
        $vehicleSummaries = (clone $query)
            ->selectRaw("vehicle_id, COUNT(*) as total_trips, SUM(CASE WHEN direction = 'in' THEN 1 ELSE 0 END) as in_trips, SUM(CASE WHEN direction = 'out' THEN 1 ELSE 0 END) as out_trips, SUM(distance) as total_km")
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->orderByDesc('total_km')
            ->get();

        $activeVehiclesCount = $vehicleSummaries->count();
        $avgDistancePerTrip = $totalActivities > 0 ? round($totalDistance / $totalActivities, 1) : 0;

        // Rekapitulasi per Driver (Top Driver)
        $driverSummaries = (clone $query)
            ->selectRaw("driver_id, COUNT(*) as total_trips, SUM(distance) as total_km")
            ->groupBy('driver_id')
            ->with('driver')
            ->orderByDesc('total_km')
            ->take(10)
            ->get();

        // Rekapitulasi per Pos Gerbang
        $gateSummaries = (clone $query)
            ->selectRaw("gate_id, COUNT(*) as total_trips, SUM(CASE WHEN direction = 'in' THEN 1 ELSE 0 END) as in_trips, SUM(CASE WHEN direction = 'out' THEN 1 ELSE 0 END) as out_trips")
            ->groupBy('gate_id')
            ->with('gate')
            ->get();

        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('qma.activity_summary', compact(
            'startDate',
            'endDate',
            'totalActivities',
            'totalDistance',
            'activeVehiclesCount',
            'avgDistancePerTrip',
            'inCount',
            'outCount',
            'approvedCount',
            'pendingCount',
            'rejectedCount',
            'vehicleSummaries',
            'driverSummaries',
            'gateSummaries',
            'gates'
        ));
    }
}
