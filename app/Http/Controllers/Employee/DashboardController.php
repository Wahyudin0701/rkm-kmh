<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\P2hRecord;
use App\Models\UnitSupportTimesheet;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use App\Services\P2hInspectionPdfExporter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * Tampilkan halaman Riwayat Aktivitas Pribadi Karyawan.
     * Mengisolasi data hanya milik karyawan yang sedang login (P2H, Timesheet Alat Berat, dan Perjalanan LV).
     * Tab yang tampil dinamis menyesuaikan hak akses dan catatan riwayat yang dimiliki karyawan.
     */
    public function history(Request $request): View
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->can('employee.dashboard') && !$user->can('employee.timesheet') && !$user->can('employee.p2h') && !$user->isEmployee()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman riwayat aktivitas karyawan.');
        }

        $employee = $user->getOrCreateEmployee();

        // 1. Periksa ketersediaan modul/tab berdasarkan wewenang (permission) & riwayat aktual karyawan
        $canP2h = $user->can('employee.p2h') || ($employee && P2hRecord::where('employee_id', $employee->id)->exists());
        $canTimesheet = ($employee && $employee->is_operator) || ($employee && UnitSupportTimesheet::where('operator_id', $employee->id)->exists()) || $user->can('qma.logs.view');
        $canTrips = $user->can('employee.timesheet') || ($employee && VehicleActivity::where('driver_id', $employee->id)->exists());

        // Hitung total catatan riwayat per kategori untuk badge tab
        $p2hCount = $employee ? P2hRecord::where('employee_id', $employee->id)->count() : 0;
        $timesheetCount = $employee ? UnitSupportTimesheet::where('operator_id', $employee->id)->count() : 0;
        $tripCount = $employee ? VehicleActivity::where('driver_id', $employee->id)->count() : 0;

        // Tentukan tab aktif
        $availableTabs = [];
        if ($canP2h) {
            $availableTabs[] = 'p2h';
        }
        if ($canTimesheet) {
            $availableTabs[] = 'timesheet';
        }
        if ($canTrips) {
            $availableTabs[] = 'trips';
        }

        $requestedTab = $request->query('tab');
        if ($requestedTab && in_array($requestedTab, $availableTabs, true)) {
            $activeTab = $requestedTab;
        } else {
            $activeTab = $availableTabs[0] ?? 'none';
        }

        // Filter parameter
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $status = $request->query('status');

        $p2hRecords = collect();
        $timesheetRecords = collect();
        $tripRecords = collect();

        // Query data khusus tab aktif
        if ($employee && $activeTab === 'p2h') {
            $query = P2hRecord::with(['vehicle', 'template', 'validator'])
                ->where('employee_id', $employee->id);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_no', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', function ($vq) use ($search) {
                            $vq->where('code', 'like', "%{$search}%")
                               ->orWhere('brand_type', 'like', "%{$search}%");
                        });
                });
            }

            if ($dateFrom) {
                $query->whereDate('inspected_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('inspected_at', '<=', $dateTo);
            }

            if ($status) {
                if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
                    $query->where('validation_status', $status);
                } elseif (in_array($status, ['good', 'has_defect'], true)) {
                    $query->where('summary_condition', $status);
                }
            }

            $p2hRecords = $query->latest('inspected_at')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        } elseif ($employee && $activeTab === 'timesheet') {
            $query = UnitSupportTimesheet::with(['vehicle'])
                ->where('operator_id', $employee->id);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('timesheet_no', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('task_description', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', function ($vq) use ($search) {
                            $vq->where('code', 'like', "%{$search}%")
                               ->orWhere('brand_type', 'like', "%{$search}%");
                        });
                });
            }

            if ($dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $timesheetRecords = $query->latest('date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        } elseif ($employee && $activeTab === 'trips') {
            $query = VehicleActivity::with(['vehicle', 'gate', 'securityGuard'])
                ->where('driver_id', $employee->id);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_no', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('vehicle', function ($vq) use ($search) {
                            $vq->where('code', 'like', "%{$search}%")
                               ->orWhere('brand_type', 'like', "%{$search}%")
                               ->orWhere('license_plate', 'like', "%{$search}%");
                        })
                        ->orWhereHas('gate', function ($gq) use ($search) {
                            $gq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($dateFrom) {
                $query->whereDate('recorded_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('recorded_at', '<=', $dateTo);
            }

            if ($status) {
                $query->where('validation_status', $status);
            }

            $tripRecords = $query->latest('recorded_at')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        }

        // Ringkasan KPI pribadi karyawan
        $stats = [
            'p2h_total' => $p2hCount,
            'p2h_good' => $employee ? P2hRecord::where('employee_id', $employee->id)->where('summary_condition', 'good')->count() : 0,
            'p2h_defects' => $employee ? P2hRecord::where('employee_id', $employee->id)->where('summary_condition', 'has_defect')->count() : 0,
            'timesheet_total' => $timesheetCount,
            'timesheet_hours' => $employee ? (float) UnitSupportTimesheet::where('operator_id', $employee->id)->sum('operating_hours') : 0.0,
            'trip_total' => $tripCount,
            'trip_distance' => $employee ? (float) VehicleActivity::where('driver_id', $employee->id)->sum('distance') : 0.0,
        ];

        return view('employee.history', compact(
            'employee',
            'canP2h',
            'canTimesheet',
            'canTrips',
            'p2hCount',
            'timesheetCount',
            'tripCount',
            'activeTab',
            'p2hRecords',
            'timesheetRecords',
            'tripRecords',
            'stats',
            'search',
            'dateFrom',
            'dateTo',
            'status'
        ));
    }

    /**
     * Detail checklist P2H pribadi karyawan (Format JSON untuk modal inspeksi).
     */
    public function p2hDetail(int $id): JsonResponse
    {
        $user = auth()->user();
        $employee = $user->getOrCreateEmployee();

        $record = P2hRecord::with(['vehicle', 'employee', 'template', 'validator', 'items'])
            ->findOrFail($id);

        if (!$user->isSuperAdmin() && (!$employee || $record->employee_id !== $employee->id)) {
            abort(403, 'Aksi Ditolak: Anda hanya dapat melihat lembar pemeriksaan P2H milik Anda sendiri.');
        }

        return response()->json([
            'id' => $record->id,
            'transaction_no' => $record->transaction_no,
            'vehicle_code' => $record->vehicle?->code ?? '-',
            'vehicle_name' => $record->vehicle?->brand_type ?? '-',
            'template_name' => $record->template?->name ?? 'P2H Checksheet',
            'shift' => $record->shift,
            'start_hm' => $record->start_hm,
            'start_km' => $record->start_km,
            'summary_condition' => $record->summary_condition,
            'defect_count' => $record->defect_count,
            'general_notes' => $record->general_notes,
            'validation_status' => $record->validation_status,
            'validator_name' => $record->validator?->name,
            'validation_notes' => $record->validation_notes,
            'validated_at' => $record->validated_at ? $record->validated_at->format('d/m/Y H:i') : null,
            'inspected_at' => $record->inspected_at ? $record->inspected_at->format('d/m/Y H:i') : null,
            'items' => $record->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_name' => $item->title,
                    'section' => $item->section,
                    'condition' => $item->condition_status,
                    'notes' => $item->notes,
                ];
            }),
        ]);
    }

    /**
     * Download / Cetak PDF lembar pemeriksaan P2H pribadi milik karyawan.
     */
    public function p2hPdf(int $id): Response
    {
        $user = auth()->user();
        $employee = $user->getOrCreateEmployee();

        $record = P2hRecord::with(['vehicle', 'employee', 'template', 'validator', 'items'])
            ->findOrFail($id);

        if (!$user->isSuperAdmin() && (!$employee || $record->employee_id !== $employee->id)) {
            abort(403, 'Aksi Ditolak: Anda hanya dapat mengunduh lembar pemeriksaan P2H milik Anda sendiri.');
        }

        $pdfBinary = P2hInspectionPdfExporter::generate($record);
        $fileName = 'Lembar_P2H_' . ($record->vehicle->code ?? 'Unit') . '_' . $record->transaction_no . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
