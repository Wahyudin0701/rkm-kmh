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

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('recorded_at', [$startDate, $endDate]);
        }

        // Filter Gerbang
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->input('gate_id'));
        }

        // Filter Arah Gerbang (in/out)
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        // Filter Status Validasi (pending, approved, rejected)
        if ($request->filled('status')) {
            $query->where('validation_status', $request->input('status'));
        }

        $activities = $query->paginate(20)->withQueryString();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('qma.activity_log', compact('activities', 'gates'));
    }
}
