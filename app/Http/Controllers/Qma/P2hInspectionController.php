<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\P2hRecord;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class P2hInspectionController extends Controller
{
    /**
     * Tampilkan riwayat dan log pemeriksaan P2H Unit Support untuk Admin QMA.
     */
    public function index(Request $request): View
    {
        $query = P2hRecord::with(['vehicle', 'employee', 'template', 'validator']);

        // Filter Status Approval
        if ($request->filled('validation_status')) {
            $query->where('validation_status', $request->input('validation_status'));
        }

        // Filter Status Kelayakan (Kondisi)
        if ($request->filled('summary_condition')) {
            $query->where('summary_condition', $request->input('summary_condition'));
        }

        // Filter Tanggal
        if ($request->filled('date')) {
            $query->whereDate('inspected_at', $request->input('date'));
        }

        // Filter Pencarian Keyword (Nomor Transaksi, Kode Unit, Nama Operator, NIK)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', fn($v) => $v->where('code', 'like', "%{$search}%")->orWhere('brand_type', 'like', "%{$search}%"))
                  ->orWhereHas('employee', fn($e) => $e->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%"));
            });
        }

        $records = $query->orderByDesc('inspected_at')->paginate(15)->withQueryString();

        // Statistik Ringkasan P2H
        $totalP2h = P2hRecord::count();
        $totalPending = P2hRecord::where('validation_status', 'pending')->count();
        $totalApproved = P2hRecord::where('validation_status', 'approved')->count();
        $totalRejected = P2hRecord::where('validation_status', 'rejected')->count();
        $totalDefects = P2hRecord::where('summary_condition', 'has_defect')->count();

        return view('qma.p2h_index', compact(
            'records',
            'totalP2h',
            'totalPending',
            'totalApproved',
            'totalRejected',
            'totalDefects'
        ));
    }

    /**
     * Tampilkan Summary Pemeriksaan P2H Unit Support (Analitik, Rekapitulasi Alat Berat & Status).
     */
    public function summary(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $today);
        $vehicleId = $request->input('vehicle_id');
        $validationStatus = $request->input('validation_status');
        $shift = $request->input('shift');
        $search = trim($request->input('search', ''));

        $query = P2hRecord::with(['vehicle', 'employee', 'template']);

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('inspected_at', [$start, $end]);
        } elseif ($startDate) {
            $query->where('inspected_at', '>=', Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('inspected_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $vehicleId);
        }

        if ($request->filled('validation_status')) {
            $query->where('validation_status', $validationStatus);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $shift);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', fn($v) => $v->where('code', 'like', "%{$search}%")->orWhere('brand_type', 'like', "%{$search}%"))
                  ->orWhereHas('employee', fn($e) => $e->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%"));
            });
        }

        $records = $query->orderByDesc('inspected_at')->get();

        // 4 KPI Metrik Utama
        $totalInspections = $records->count();
        $totalApproved = $records->where('validation_status', 'approved')->count();
        $totalPending = $records->where('validation_status', 'pending')->count();
        $totalRejected = $records->where('validation_status', 'rejected')->count();
        $totalDefects = $records->where('summary_condition', 'has_defect')->count();
        $totalNormal = $records->where('summary_condition', 'good')->count();
        $totalHm = $records->sum('start_hm');

        // Rekapitulasi per Unit Support
        $unitSummary = $records->groupBy('vehicle_id')->map(function ($group) {
            $vehicle = $group->first()->vehicle;
            return [
                'vehicle' => $vehicle,
                'total' => $group->count(),
                'approved' => $group->where('validation_status', 'approved')->count(),
                'pending' => $group->where('validation_status', 'pending')->count(),
                'rejected' => $group->where('validation_status', 'rejected')->count(),
                'has_defect' => $group->where('summary_condition', 'has_defect')->count(),
                'good' => $group->where('summary_condition', 'good')->count(),
                'latest_hm' => $group->max('start_hm'),
                'latest_inspection' => $group->max('inspected_at'),
            ];
        })->sortByDesc('total');

        // Daftar Unit Support untuk opsi filter
        $supportVehicles = Vehicle::where('category', 'unit_support')->orderBy('code')->get();

        return view('qma.p2h_summary', compact(
            'records',
            'totalInspections',
            'totalApproved',
            'totalPending',
            'totalRejected',
            'totalDefects',
            'totalNormal',
            'totalHm',
            'unitSummary',
            'supportVehicles',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Tampilkan detail lembar pemeriksaan P2H (seluruh section & butir jawaban).
     */
    public function show(int $id): View
    {
        $record = P2hRecord::with(['vehicle', 'employee', 'template', 'validator', 'items'])->findOrFail($id);

        $groupedItems = [];
        foreach ($record->items as $item) {
            $groupedItems[$item->section][] = $item;
        }

        return view('qma.p2h_show', compact('record', 'groupedItems'));
    }

    /**
     * Approval / Persetujuan lembar P2H oleh Admin QMA.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $record = P2hRecord::with('vehicle')->findOrFail($id);

        $record->update([
            'validation_status' => 'approved',
            'validated_by' => Auth::id(),
            'validated_at' => Carbon::now(),
            'validation_notes' => $request->input('validation_notes', 'Pemeriksaan P2H disetujui oleh QMA.'),
        ]);

        // Jika unit dinyatakan aman dan sebelumnya breakdown karena review, status unit disetujui operasi
        if (!$record->hasDefects() && $record->vehicle->status === 'Breakdown') {
            $record->vehicle->update(['status' => 'Operation']);
        }

        return back()->with('success', "Inspeksi P2H [{$record->transaction_no}] untuk unit {$record->vehicle->code} berhasil DISETUJUI.");
    }

    /**
     * Penolakan lembar P2H oleh Admin QMA (misal data tidak valid atau unit dilarang beroperasi).
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'validation_notes' => ['required', 'string', 'max:1000'],
        ], [
            'validation_notes.required' => 'Alasan penolakan / instruksi perbaikan wajib diisi.',
        ]);

        $record = P2hRecord::with('vehicle')->findOrFail($id);

        $record->update([
            'validation_status' => 'rejected',
            'validated_by' => Auth::id(),
            'validated_at' => Carbon::now(),
            'validation_notes' => $request->input('validation_notes'),
        ]);

        // Kunci status kendaraan ke Breakdown atau Standby jika P2H ditolak
        $record->vehicle->update(['status' => 'Breakdown']);

        return back()->with('success', "Inspeksi P2H [{$record->transaction_no}] untuk unit {$record->vehicle->code} DITOLAK. Status unit diubah ke Breakdown.");
    }
}
