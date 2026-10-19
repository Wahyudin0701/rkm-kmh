<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Gate;
use App\Models\VehicleActivity;
use App\Services\QmaTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Helper query builder untuk log aktivitas kendaraan dengan seluruh filter aktif.
     */
    private function getFilteredActivityQuery(Request $request): Builder
    {
        $query = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->latest('recorded_at');

        // Filter Rentang Tanggal (Default Hari Ini jika tidak diisi)
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

        return $query;
    }

    /**
     * Tampilkan Log Aktivitas QMA 10 Kolom lengkap dengan filter tanggal dan gate.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        $activities = $this->getFilteredActivityQuery($request)->paginate(20)->withQueryString();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('qma.activity_log', compact('activities', 'gates', 'startDate', 'endDate'));
    }

    /**
     * Ekspor Log Aktivitas Kendaraan (Timesheet LV) ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $activities = $this->getFilteredActivityQuery($request)->get();

        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        $gateText = 'Semua Gerbang';
        if ($request->filled('gate_id')) {
            $gateObj = Gate::find($request->input('gate_id'));
            if ($gateObj) $gateText = $gateObj->name;
        }

        $dirText = $request->filled('direction') ? ($request->input('direction') === 'in' ? 'Masuk (In)' : 'Keluar (Out)') : 'Semua Arah';
        $statusText = $request->filled('status') ? ($request->input('status') === 'approved' ? 'Disetujui PG' : 'Menunggu PG') : 'Semua Status';
        $printedAt = now()->translatedFormat('d F Y, H:i');

        $metaLines = [
            'Periode: ' . Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y') .
            ' | Gerbang: ' . $gateText . ' | Arah: ' . $dirText . ' | Status: ' . $statusText,
            'Total Data: ' . $activities->count() . ' Catatan Pergerakan | Tanggal Dicetak: ' . $printedAt . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Tanggal & Waktu' => 20,
            'Kode LV' => 14,
            'Unit Kendaraan' => 28,
            'No. Polisi' => 16,
            'Penanggung Jawab' => 22,
            'Pengemudi (Driver)' => 26,
            'Pos Gerbang' => 20,
            'Arah' => 12,
            'KM Lalu' => 14,
            'KM Kini' => 14,
            'Selisih KM' => 14,
            'Aktivitas / Keperluan' => 34,
            'Status PG' => 18,
            'Petugas Security' => 22,
        ];

        $rows = [];
        foreach ($activities as $idx => $act) {
            $formattedDate = $act->recorded_at ? $act->recorded_at->translatedFormat('d/m/Y H:i') . ' WIB' : '-';
            $vehicleCode = $act->vehicle?->code ?: '-';
            $vehicleUnit = $act->vehicle?->brand_type ?: '-';
            $plat = $act->vehicle?->license_plate ?: '-';
            $dept = $act->vehicle?->department ?: ($act->vehicle?->division ?: '-');
            $driverName = $act->driver ? $act->driver->name . ' (' . $act->driver->nik . ')' : '-';
            $gateName = $act->gate?->name ?: '-';
            $direction = strtoupper($act->direction ?: '-');
            $prevKm = $act->previous_km !== null ? number_format($act->previous_km, 0, ',', '.') : '-';
            $currKm = $act->current_km !== null ? number_format($act->current_km, 0, ',', '.') : '-';
            $diffKm = $act->distance !== null ? number_format($act->distance, 0, ',', '.') : '-';
            $purpose = $act->purpose ?: '-';
            $statusPg = $act->validation_status === 'approved' ? 'Disetujui' : ($act->validation_status === 'rejected' ? 'Ditolak' : ($act->validation_status === 'cancelled' ? 'Dibatalkan' : 'Menunggu PG'));
            if ($act->is_corrected) {
                $statusPg .= ' (Terkoreksi)';
            }
            $guard = $act->securityGuard?->name ?: '-';

            $rows[] = [
                $idx + 1,
                $formattedDate,
                $vehicleCode,
                $vehicleUnit,
                $plat,
                $dept,
                $driverName,
                $gateName,
                $direction,
                $prevKm,
                $currKm,
                $diffKm,
                $purpose,
                $statusPg,
                $guard,
            ];
        }

        $alignments = ['center', 'center', 'center', 'left', 'center', 'left', 'left', 'center', 'center', 'right', 'right', 'right', 'left', 'center', 'left'];
        $title = 'LAPORAN TIMESHEET & LOG AKTIVITAS LIGHT VEHICLE - PT KERINCI MERANGIN HIDRO';
        $fileName = 'Laporan_Timesheet_LV_' . now()->format('Ymd_Hi') . '.xlsx';

        $binary = SimpleXlsxExporter::create($title, $metaLines, $headers, $rows, $alignments);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Ekspor Log Aktivitas Kendaraan (Timesheet LV) ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportPdf(Request $request): Response
    {
        $activities = $this->getFilteredActivityQuery($request)->get();

        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        $gateText = 'Semua Gerbang';
        if ($request->filled('gate_id')) {
            $gateObj = Gate::find($request->input('gate_id'));
            if ($gateObj) $gateText = $gateObj->name;
        }

        $dirText = $request->filled('direction') ? ($request->input('direction') === 'in' ? 'Masuk (In)' : 'Keluar (Out)') : 'Semua Arah';
        $statusText = $request->filled('status') ? ($request->input('status') === 'approved' ? 'Disetujui PG' : 'Menunggu PG') : 'Semua Status';
        $dateRangeStr = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');

        $totalKm = $activities->sum('distance');
        $inCount = $activities->where('direction', 'in')->count();
        $outCount = $activities->where('direction', 'out')->count();

        $metaLeft = [
            'Periode Tanggal' => $dateRangeStr,
            'Pos Gerbang' => $gateText,
            'Arah / Status' => "Arah: {$dirText} | Status: {$statusText}",
        ];

        $metaRight = [
            'Total Catatan' => $activities->count() . ' Pergerakan Armada',
            'Total Jarak Tempuh' => number_format($totalKm, 1, ',', '.') . ' KM',
            'Rincian Arah' => "{$inCount} Masuk, {$outCount} Keluar",
        ];

        $columns = [
            ['title' => 'NO', 'width' => 8, 'align' => 'C'],
            ['title' => 'TANGGAL & JAM', 'width' => 25, 'align' => 'C'],
            ['title' => 'KODE LV', 'width' => 16, 'align' => 'C', 'bold' => true],
            ['title' => 'UNIT KENDARAAN', 'width' => 34, 'align' => 'L', 'max_char' => 22],
            ['title' => 'DRIVER (PENGEMUDI)', 'width' => 30, 'align' => 'L', 'max_char' => 18],
            ['title' => 'GERBANG', 'width' => 22, 'align' => 'C', 'max_char' => 14],
            ['title' => 'AKSES', 'width' => 16, 'align' => 'C', 'is_status' => true],
            ['title' => 'KM LALU', 'width' => 18, 'align' => 'R'],
            ['title' => 'KM KINI', 'width' => 18, 'align' => 'R'],
            ['title' => 'JARAK', 'width' => 16, 'align' => 'R'],
            ['title' => 'AKTIVITAS / KEPERLUAN', 'width' => 42, 'align' => 'L', 'max_char' => 30],
            ['title' => 'STATUS PG', 'width' => 22, 'align' => 'C', 'is_status' => true],
        ];

        $rows = [];
        foreach ($activities as $idx => $act) {
            $formattedDate = $act->recorded_at ? $act->recorded_at->translatedFormat('d/m/y H:i') : '-';
            $vehicleCode = $act->vehicle?->code ?: '-';
            $vehicleUnit = $act->vehicle?->brand_type ?: '-';
            $driverName = $act->driver?->name ?: '-';
            $gateName = $act->gate?->name ?: '-';
            $direction = strtoupper($act->direction ?: '-');
            $prevKm = $act->previous_km !== null ? number_format($act->previous_km, 0, ',', '.') : '-';
            $currKm = $act->current_km !== null ? number_format($act->current_km, 0, ',', '.') : '-';
            $diffKm = $act->distance !== null ? number_format($act->distance, 0, ',', '.') . ' KM' : '-';
            $purpose = $act->purpose ?: '-';
            $statusPg = $act->validation_status === 'approved' ? 'Disetujui' : ($act->validation_status === 'rejected' ? 'Ditolak' : ($act->validation_status === 'cancelled' ? 'Dibatalkan' : 'Menunggu PG'));
            if ($act->is_corrected) {
                $statusPg .= ' (Terkoreksi)';
            }

            $rows[] = [
                $idx + 1,
                $formattedDate,
                $vehicleCode,
                $vehicleUnit,
                $driverName,
                $gateName,
                $direction,
                $prevKm,
                $currKm,
                $diffKm,
                $purpose,
                $statusPg,
            ];
        }

        $title = 'Laporan Timesheet & Aktivitas Light Vehicle (LV)';
        $subtitle = 'Log Pergerakan Armada Gerbang Proyek KMH - Periode: ' . $dateRangeStr;
        $pdfBinary = QmaTablePdfExporter::generate(
            $title,
            $subtitle,
            'LOG AKTIVITAS RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Timesheet_LV_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
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

    /**
     * Ekspor Rekapitulasi Summary Aktivitas Kendaraan ke format Excel (.xlsx).
     */
    public function exportSummaryExcel(Request $request): Response
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

        $vehicleSummaries = (clone $query)
            ->selectRaw("vehicle_id, COUNT(*) as total_trips, SUM(CASE WHEN direction = 'in' THEN 1 ELSE 0 END) as in_trips, SUM(CASE WHEN direction = 'out' THEN 1 ELSE 0 END) as out_trips, SUM(distance) as total_km")
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->orderByDesc('total_km')
            ->get();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $vehicleSummaries = $vehicleSummaries->filter(function ($item) use ($search) {
                $veh = $item->vehicle;
                if (!$veh) return false;
                $haystack = strtolower(($veh->code ?? '') . ' ' . ($veh->brand_type ?? '') . ' ' . ($veh->license_plate ?? '') . ' ' . ($veh->ownership ?? '') . ' ' . ($veh->division ?? ''));
                return str_contains($haystack, strtolower($search));
            })->values();
        }

        $gateText = 'Semua Gerbang';
        if ($request->filled('gate_id')) {
            $g = Gate::find($gateId);
            if ($g) $gateText = $g->name;
        }

        $typeText = 'Semua Armada';
        if ($type === 'lv' || $type === 'light_vehicle') $typeText = 'Light Vehicle';
        elseif ($type === 'support' || $type === 'unit_support') $typeText = 'Unit Support';

        $totalKmAll = $vehicleSummaries->sum('total_km');
        $totalTripsAll = $vehicleSummaries->sum('total_trips');
        $dateRangeStr = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        $printedAt = now()->translatedFormat('d F Y, H:i');

        $metaLines = [
            'Periode: ' . $dateRangeStr . ' | Kategori Armada: ' . $typeText . ' | Gerbang: ' . $gateText .
            ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
            'Total: ' . $vehicleSummaries->count() . ' Unit Aktif | Akumulasi Trip: ' . number_format($totalTripsAll, 0, ',', '.') . ' Trip | Total Jarak: ' . number_format($totalKmAll, 1, ',', '.') . ' KM | Dicetak: ' . $printedAt . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Kode Unit' => 16,
            'Unit / Merek' => 30,
            'No. Polisi' => 16,
            'Kategori' => 18,
            'Penanggung Jawab' => 24,
            'Divisi' => 20,
            'Trip Masuk' => 14,
            'Trip Keluar' => 14,
            'Total Trip' => 14,
            'Total Jarak (KM)' => 18,
            'Odometer Terkini' => 18,
        ];

        $rows = [];
        foreach ($vehicleSummaries as $idx => $vs) {
            $veh = $vs->vehicle;
            $rows[] = [
                $idx + 1,
                $veh?->code ?: '-',
                $veh?->brand_type ?: '-',
                $veh?->license_plate ?: '-',
                $veh?->isLightVehicle() ? 'Light Vehicle' : 'Unit Support',
                $veh?->ownership ?: '-',
                $veh?->division ?: ($veh?->department ?: '-'),
                (int) $vs->in_trips,
                (int) $vs->out_trips,
                (int) $vs->total_trips,
                number_format($vs->total_km, 1, ',', '.'),
                $veh?->current_km !== null ? number_format($veh->current_km, 0, ',', '.') . ' KM' : '-',
            ];
        }

        $alignments = ['center', 'center', 'left', 'center', 'center', 'left', 'left', 'center', 'center', 'center', 'right', 'right'];
        $title = 'LAPORAN REKAPITULASI SUMMARY AKTIVITAS PER KENDARAAN - PT KERINCI MERANGIN HIDRO';
        $fileName = 'Laporan_Summary_Aktivitas_Kendaraan_' . now()->format('Ymd_Hi') . '.xlsx';

        $binary = SimpleXlsxExporter::create($title, $metaLines, $headers, $rows, $alignments);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Ekspor Rekapitulasi Summary Aktivitas Kendaraan ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportSummaryPdf(Request $request): Response
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

        $vehicleSummaries = (clone $query)
            ->selectRaw("vehicle_id, COUNT(*) as total_trips, SUM(CASE WHEN direction = 'in' THEN 1 ELSE 0 END) as in_trips, SUM(CASE WHEN direction = 'out' THEN 1 ELSE 0 END) as out_trips, SUM(distance) as total_km")
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->orderByDesc('total_km')
            ->get();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $vehicleSummaries = $vehicleSummaries->filter(function ($item) use ($search) {
                $veh = $item->vehicle;
                if (!$veh) return false;
                $haystack = strtolower(($veh->code ?? '') . ' ' . ($veh->brand_type ?? '') . ' ' . ($veh->license_plate ?? '') . ' ' . ($veh->ownership ?? '') . ' ' . ($veh->division ?? ''));
                return str_contains($haystack, strtolower($search));
            })->values();
        }

        $gateText = 'Semua Gerbang';
        if ($request->filled('gate_id')) {
            $g = Gate::find($gateId);
            if ($g) $gateText = $g->name;
        }

        $typeText = 'Semua Armada';
        if ($type === 'lv' || $type === 'light_vehicle') $typeText = 'Light Vehicle';
        elseif ($type === 'support' || $type === 'unit_support') $typeText = 'Unit Support';

        $totalKmAll = $vehicleSummaries->sum('total_km');
        $totalTripsAll = $vehicleSummaries->sum('total_trips');
        $dateRangeStr = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');

        $metaLeft = [
            'Periode Tanggal' => $dateRangeStr,
            'Kategori Armada' => $typeText,
            'Pos Gerbang' => $gateText,
        ];

        $metaRight = [
            'Armada Beroperasi' => $vehicleSummaries->count() . ' Unit Aktif',
            'Akumulasi Trip' => number_format($totalTripsAll, 0, ',', '.') . ' Pergerakan',
            'Total Jarak Tempuh' => number_format($totalKmAll, 1, ',', '.') . ' KM',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C'],
            ['title' => 'KODE UNIT', 'width' => 22, 'align' => 'C', 'bold' => true],
            ['title' => 'UNIT / MEREK', 'width' => 48, 'align' => 'L', 'max_char' => 30],
            ['title' => 'NO. POLISI', 'width' => 22, 'align' => 'C'],
            ['title' => 'KATEGORI', 'width' => 26, 'align' => 'C'],
            ['title' => 'PENANGGUNG JAWAB', 'width' => 40, 'align' => 'L', 'max_char' => 26],
            ['title' => 'MASUK', 'width' => 18, 'align' => 'C'],
            ['title' => 'KELUAR', 'width' => 18, 'align' => 'C'],
            ['title' => 'TOTAL TRIP', 'width' => 20, 'align' => 'C', 'bold' => true],
            ['title' => 'TOTAL JARAK', 'width' => 25, 'align' => 'R', 'bold' => true],
            ['title' => 'ODOMETER', 'width' => 18, 'align' => 'R'],
        ];

        $rows = [];
        foreach ($vehicleSummaries as $idx => $vs) {
            $veh = $vs->vehicle;
            $rows[] = [
                $idx + 1,
                $veh?->code ?: '-',
                $veh?->brand_type ?: '-',
                $veh?->license_plate ?: '-',
                $veh?->isLightVehicle() ? 'Light Vehicle' : 'Unit Support',
                $veh?->ownership ?: '-',
                (int) $vs->in_trips,
                (int) $vs->out_trips,
                (int) $vs->total_trips,
                number_format($vs->total_km, 1, ',', '.') . ' KM',
                $veh?->current_km !== null ? number_format($veh->current_km, 0, ',', '.') : '-',
            ];
        }

        $title = 'Laporan Summary Aktivitas per Armada Kendaraan';
        $subtitle = 'Rekapitulasi Pergerakan, Frekuensi Trip, dan Jarak Tempuh - Periode: ' . $dateRangeStr;
        $pdfBinary = QmaTablePdfExporter::generate(
            $title,
            $subtitle,
            'SUMMARY AKTIVITAS RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Summary_Aktivitas_Kendaraan_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Koreksi odometer perjalanan (Timesheet LV) oleh Admin QMA / Super Admin.
     */
    public function correctOdometer(Request $request, int $id): RedirectResponse
    {
        $activity = VehicleActivity::with(['vehicle', 'driver'])->findOrFail($id);

        $validated = $request->validate([
            'new_current_km' => ['required', 'numeric', 'min:0'],
            'correction_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'new_current_km.required' => 'Nilai odometer koreksi wajib diisi.',
            'new_current_km.numeric' => 'Nilai odometer harus berupa angka.',
            'new_current_km.min' => 'Nilai odometer tidak boleh negatif.',
            'correction_reason.required' => 'Alasan perbaikan odometer wajib diisi.',
            'correction_reason.min' => 'Alasan perbaikan minimal 5 karakter.',
            'correction_reason.max' => 'Alasan perbaikan maksimal 500 karakter.',
        ]);

        $previousKm = (float) $activity->previous_km;
        $newCurrentKm = (float) $validated['new_current_km'];
        $oldCurrentKm = (float) $activity->current_km;
        $oldDistance = (float) $activity->distance;
        $reason = trim($validated['correction_reason']);

        if ($newCurrentKm < $previousKm) {
            return back()->withInput()->with('error', "Nilai kilometer koreksi ({$newCurrentKm} KM) tidak boleh lebih kecil dari kilometer awal ({$previousKm} KM).");
        }

        $newDistance = max(0, $newCurrentKm - $previousKm);

        DB::transaction(function () use ($activity, $newCurrentKm, $oldCurrentKm, $oldDistance, $newDistance, $reason) {
            // 1. Simpan original_current_km jika baru pertama kali dikoreksi
            $originalKm = $activity->is_corrected ? $activity->original_current_km : $oldCurrentKm;

            $activity->update([
                'current_km' => $newCurrentKm,
                'distance' => $newDistance,
                'is_corrected' => true,
                'original_current_km' => $originalKm,
                'correction_reason' => $reason,
                'corrected_by_user_id' => auth()->id(),
                'corrected_at' => Carbon::now(),
            ]);

            // 2. Sinkronkan Master Kendaraan jika ini adalah aktivitas approved terakhir / terbaru dari unit tersebut
            $latestActivity = VehicleActivity::where('vehicle_id', $activity->vehicle_id)
                ->where('validation_status', 'approved')
                ->latest('recorded_at')
                ->first();

            if ($latestActivity && $latestActivity->id === $activity->id) {
                $activity->vehicle->update([
                    'current_km' => $newCurrentKm,
                ]);
            }

            // 3. Rekam ke Riwayat Aktivitas Super Admin (Audit Trail)
            $vehicleCode = $activity->vehicle->code;
            AdminActivityLog::record(
                actionType: 'update',
                module: 'Operasional Kendaraan (QMA)',
                description: "Mengoreksi angka odometer tiket {$activity->transaction_no} pada unit {$vehicleCode} dari " . number_format($oldCurrentKm, 0, ',', '.') . " KM menjadi " . number_format($newCurrentKm, 0, ',', '.') . " KM. Alasan: {$reason}",
                targetType: 'VehicleActivity',
                targetId: $activity->id,
                targetName: "{$vehicleCode} ({$activity->transaction_no})",
                oldValues: [
                    'current_km' => $oldCurrentKm,
                    'distance' => $oldDistance,
                ],
                newValues: [
                    'current_km' => $newCurrentKm,
                    'distance' => $newDistance,
                    'correction_reason' => $reason,
                ]
            );
        });

        return back()->with('success', "Odometer transaksi {$activity->transaction_no} ({$activity->vehicle->code}) berhasil dikoreksi menjadi " . number_format($newCurrentKm, 0, ',', '.') . " KM.");
    }
}
