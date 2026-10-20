<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\P2hRecord;
use App\Models\Vehicle;
use App\Services\P2hInspectionPdfExporter;
use App\Services\QmaTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class P2hInspectionController extends Controller
{
    /**
     * Helper query builder untuk riwayat pemeriksaan P2H Unit Support dengan filter aktif.
     */
    private function getFilteredP2hQuery(Request $request): Builder
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

        return $query->orderByDesc('inspected_at');
    }

    /**
     * Tampilkan riwayat dan log pemeriksaan P2H Unit Support untuk Admin QMA.
     */
    public function index(Request $request): View
    {
        $records = $this->getFilteredP2hQuery($request)->paginate(15)->withQueryString();

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
     * Ekspor Riwayat Pemeriksaan P2H Unit Support ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $records = $this->getFilteredP2hQuery($request)->get();

        $dateFilter = $request->filled('date') ? Carbon::parse($request->input('date'))->translatedFormat('d F Y') : 'Semua Tanggal';
        $statusFilter = $request->filled('validation_status') ? 
            ($request->input('validation_status') === 'approved' ? 'Disetujui QMA' : 
            ($request->input('validation_status') === 'rejected' ? 'Ditolak' : 'Menunggu Review')) : 'Semua Status';
        $condFilter = $request->filled('summary_condition') ? 
            ($request->input('summary_condition') === 'has_defect' ? 'Ada Kerusakan' : 'Normal') : 'Semua Kondisi';
        $printedAt = now()->translatedFormat('d F Y, H:i');

        $metaLines = [
            'Filter: Tanggal: ' . $dateFilter . ' | Status QMA: ' . $statusFilter . ' | Kondisi Fisik: ' . $condFilter .
            ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
            'Total Data: ' . $records->count() . ' Form Pemeriksaan P2H | Tanggal Dicetak: ' . $printedAt . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Waktu Inspeksi' => 20,
            'No. Transaksi' => 22,
            'Kode Unit' => 16,
            'Kategori & Model Alat' => 30,
            'Operator (SIMPER)' => 28,
            'NIK' => 16,
            'Shift' => 14,
            'HM Awal Operasi' => 18,
            'Kondisi Fisik' => 20,
            'Jumlah Temuan' => 16,
            'Status QMA' => 20,
            'Catatan Inspeksi / Temuan' => 36,
            'Validator QMA' => 24,
        ];

        $rows = [];
        foreach ($records as $idx => $rec) {
            $waktu = $rec->inspected_at ? $rec->inspected_at->translatedFormat('d/m/Y H:i') . ' WIB' : '-';
            $unitCode = $rec->vehicle?->code ?: '-';
            $unitModel = $rec->vehicle?->brand_type ?: ($rec->vehicle?->unit_category ?: '-');
            $opName = $rec->employee?->name ?: '-';
            $opNik = $rec->employee?->nik ?: '-';
            $shift = $rec->shift ?: '-';
            $hm = $rec->start_hm !== null ? number_format($rec->start_hm, 1, ',', '.') . ' HM' : '-';
            $kondisi = $rec->hasDefects() ? 'Ada Kerusakan (' . $rec->defect_count . ' Item)' : 'Normal (Baik)';
            $defectCount = $rec->defect_count ?: '0';
            $statusQma = $rec->isApproved() ? 'Disetujui QMA' : ($rec->isRejected() ? 'Ditolak' : 'Menunggu Review');
            $notes = $rec->general_notes ?: ($rec->validation_notes ?: '-');
            $validator = $rec->validator?->name ?: '-';

            $rows[] = [
                $idx + 1,
                $waktu,
                $rec->transaction_no ?: '-',
                $unitCode,
                $unitModel,
                $opName,
                $opNik,
                $shift,
                $hm,
                $kondisi,
                $defectCount,
                $statusQma,
                $notes,
                $validator,
            ];
        }

        $alignments = ['center', 'center', 'center', 'center', 'left', 'left', 'center', 'center', 'right', 'center', 'center', 'center', 'left', 'left'];
        $title = 'LAPORAN PEMERIKSAAN HARIAN (P2H) ALAT BERAT & UNIT SUPPORT - PT KERINCI MERANGIN HIDRO';
        $fileName = 'Laporan_P2H_Unit_Support_' . now()->format('Ymd_Hi') . '.xlsx';

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
     * Ekspor Riwayat Pemeriksaan P2H Unit Support ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportPdf(Request $request): Response
    {
        $records = $this->getFilteredP2hQuery($request)->get();

        $dateFilter = $request->filled('date') ? Carbon::parse($request->input('date'))->translatedFormat('d F Y') : 'Semua Tanggal';
        $statusFilter = $request->filled('validation_status') ? 
            ($request->input('validation_status') === 'approved' ? 'Disetujui QMA' : 
            ($request->input('validation_status') === 'rejected' ? 'Ditolak' : 'Menunggu Review')) : 'Semua Status';
        $condFilter = $request->filled('summary_condition') ? 
            ($request->input('summary_condition') === 'has_defect' ? 'Ada Kerusakan' : 'Normal') : 'Semua Kondisi';

        $approvedCount = $records->where('validation_status', 'approved')->count();
        $pendingCount = $records->where('validation_status', 'pending')->count();
        $rejectedCount = $records->where('validation_status', 'rejected')->count();
        $defectCount = $records->where('summary_condition', 'has_defect')->count();
        $normalCount = $records->where('summary_condition', '!=', 'has_defect')->count();

        $metaLeft = [
            'Filter Tanggal' => $dateFilter,
            'Status Approval' => $statusFilter,
            'Kondisi Fisik' => $condFilter,
        ];

        $metaRight = [
            'Total Pemeriksaan' => $records->count() . ' Form P2H Terverifikasi',
            'Rincian Kondisi' => "{$normalCount} Normal, {$defectCount} Ada Kerusakan",
            'Rincian Status' => "{$approvedCount} Disetujui, {$pendingCount} Menunggu, {$rejectedCount} Ditolak",
        ];

        $columns = [
            ['title' => 'NO', 'width' => 8, 'align' => 'C'],
            ['title' => 'WAKTU', 'width' => 24, 'align' => 'C'],
            ['title' => 'NO. TRANSAKSI', 'width' => 26, 'align' => 'C', 'bold' => true],
            ['title' => 'KODE', 'width' => 16, 'align' => 'C', 'bold' => true],
            ['title' => 'MODEL ALAT', 'width' => 34, 'align' => 'L', 'max_char' => 22],
            ['title' => 'OPERATOR', 'width' => 32, 'align' => 'L', 'max_char' => 20],
            ['title' => 'SHIFT', 'width' => 14, 'align' => 'C'],
            ['title' => 'HM', 'width' => 18, 'align' => 'R'],
            ['title' => 'KONDISI', 'width' => 28, 'align' => 'C', 'is_status' => true],
            ['title' => 'STATUS QMA', 'width' => 25, 'align' => 'C', 'is_status' => true],
            ['title' => 'VALIDATOR', 'width' => 22, 'align' => 'L', 'max_char' => 15],
            ['title' => 'CATATAN', 'width' => 20, 'align' => 'L', 'max_char' => 14],
        ];

        $rows = [];
        foreach ($records as $idx => $rec) {
            $waktu = $rec->inspected_at ? $rec->inspected_at->translatedFormat('d/m/y H:i') : '-';
            $unitCode = $rec->vehicle?->code ?: '-';
            $unitModel = $rec->vehicle?->brand_type ?: '-';
            $opName = $rec->employee?->name ?: '-';
            $shift = $rec->shift ?: '-';
            $hm = $rec->start_hm !== null ? number_format($rec->start_hm, 1, ',', '.') : '-';
            $kondisi = $rec->hasDefects() ? 'Ada Kerusakan (' . $rec->defect_count . ')' : 'Normal (Baik)';
            $statusQma = $rec->isApproved() ? 'Disetujui QMA' : ($rec->isRejected() ? 'Ditolak' : 'Menunggu Review');
            $validator = $rec->validator?->name ?: '-';
            $notes = $rec->general_notes ?: ($rec->validation_notes ?: '-');

            $rows[] = [
                $idx + 1,
                $waktu,
                $rec->transaction_no ?: '-',
                $unitCode,
                $unitModel,
                $opName,
                $shift,
                $hm,
                $kondisi,
                $statusQma,
                $validator,
                $notes,
            ];
        }

        $title = 'Laporan Pemeriksaan Harian (P2H) Unit Support';
        $subtitle = 'Rekapitulasi Kelaikan Alat Berat & Unit Pendukung Lapangan - PT KMH';
        $pdfBinary = QmaTablePdfExporter::generate(
            $title,
            $subtitle,
            'P2H UNIT SUPPORT RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_P2H_Unit_Support_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
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
     * Helper untuk memproses data rekapitulasi summary P2H per unit support.
     */
    private function getP2hSummaryData(Request $request): array
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

        return [
            'records' => $records,
            'unitSummary' => $unitSummary,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Ekspor Rekapitulasi Summary P2H Unit Support ke format Excel (.xlsx).
     */
    public function exportSummaryExcel(Request $request): Response
    {
        $data = $this->getP2hSummaryData($request);
        $records = $data['records'];
        $unitSummary = $data['unitSummary'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];

        $dateRangeStr = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        $printedAt = now()->translatedFormat('d F Y, H:i');

        $statusFilter = $request->filled('validation_status') ? 
            ($request->input('validation_status') === 'approved' ? 'Disetujui QMA' : 
            ($request->input('validation_status') === 'rejected' ? 'Ditolak' : 'Menunggu Approval')) : 'Semua Status';
        $shiftFilter = $request->filled('shift') ? $request->input('shift') : 'Semua Shift';

        $totalP2hAll = $records->count();
        $totalApprovedAll = $records->where('validation_status', 'approved')->count();
        $totalDefectsAll = $records->where('summary_condition', 'has_defect')->count();

        $metaLines = [
            'Periode: ' . $dateRangeStr . ' | Status: ' . $statusFilter . ' | Shift: ' . $shiftFilter .
            ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
            'Total: ' . $unitSummary->count() . ' Unit Alat Berat | Total Form: ' . $totalP2hAll . ' P2H (' . $totalApprovedAll . ' Disetujui, ' . $totalDefectsAll . ' Ada Kerusakan) | Dicetak: ' . $printedAt . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Kode Unit' => 16,
            'Model & Kategori Alat' => 32,
            'Kategori' => 20,
            'Total P2H' => 14,
            'Disetujui' => 14,
            'Menunggu' => 14,
            'Ditolak' => 14,
            'Temuan Rusak' => 16,
            'HM Terakhir' => 18,
            'Terakhir Diperiksa' => 22,
        ];

        $rows = [];
        $idx = 1;
        foreach ($unitSummary as $us) {
            $veh = $us['vehicle'];
            $latestInspection = $us['latest_inspection'] ? Carbon::parse($us['latest_inspection'])->translatedFormat('d/m/Y H:i') . ' WIB' : '-';
            $hm = $us['latest_hm'] !== null ? number_format($us['latest_hm'], 1, ',', '.') . ' HM' : '-';

            $rows[] = [
                $idx++,
                $veh?->code ?: '-',
                $veh?->brand_type ?: '-',
                $veh?->unit_category ?: 'Alat Berat',
                (int) $us['total'],
                (int) $us['approved'],
                (int) $us['pending'],
                (int) $us['rejected'],
                (int) $us['has_defect'],
                $hm,
                $latestInspection,
            ];
        }

        $alignments = ['center', 'center', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'right', 'center'];
        $title = 'LAPORAN REKAPITULASI SUMMARY P2H UNIT SUPPORT - PT KERINCI MERANGIN HIDRO';
        $fileName = 'Laporan_Summary_P2H_Unit_Support_' . now()->format('Ymd_Hi') . '.xlsx';

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
     * Ekspor Rekapitulasi Summary P2H Unit Support ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportSummaryPdf(Request $request): Response
    {
        $data = $this->getP2hSummaryData($request);
        $records = $data['records'];
        $unitSummary = $data['unitSummary'];
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];

        $dateRangeStr = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');

        $statusFilter = $request->filled('validation_status') ? 
            ($request->input('validation_status') === 'approved' ? 'Disetujui QMA' : 
            ($request->input('validation_status') === 'rejected' ? 'Ditolak' : 'Menunggu Approval')) : 'Semua Status';
        $shiftFilter = $request->filled('shift') ? $request->input('shift') : 'Semua Shift';

        $totalApproved = $records->where('validation_status', 'approved')->count();
        $totalPending = $records->where('validation_status', 'pending')->count();
        $totalRejected = $records->where('validation_status', 'rejected')->count();
        $totalDefects = $records->where('summary_condition', 'has_defect')->count();

        $metaLeft = [
            'Periode Tanggal' => $dateRangeStr,
            'Status Approval' => $statusFilter,
            'Filter Shift' => $shiftFilter,
        ];

        $metaRight = [
            'Unit Support Aktif' => $unitSummary->count() . ' Unit Alat Berat',
            'Total Pemeriksaan' => $records->count() . ' Form P2H Terkumpul',
            'Rincian Status' => "{$totalApproved} Disetujui, {$totalPending} Menunggu, {$totalRejected} Ditolak",
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C'],
            ['title' => 'KODE UNIT', 'width' => 24, 'align' => 'C', 'bold' => true],
            ['title' => 'MODEL & KATEGORI ALAT', 'width' => 65, 'align' => 'L', 'max_char' => 42],
            ['title' => 'TOTAL P2H', 'width' => 22, 'align' => 'C', 'bold' => true],
            ['title' => 'DISETUJUI', 'width' => 22, 'align' => 'C', 'is_status' => true],
            ['title' => 'MENUNGGU', 'width' => 22, 'align' => 'C', 'is_status' => true],
            ['title' => 'DITOLAK', 'width' => 20, 'align' => 'C', 'is_status' => true],
            ['title' => 'TEMUAN', 'width' => 24, 'align' => 'C', 'is_status' => true],
            ['title' => 'HM TERAKHIR', 'width' => 26, 'align' => 'R'],
            ['title' => 'TERAKHIR DIPERIKSA', 'width' => 32, 'align' => 'C'],
        ];

        $rows = [];
        $idx = 1;
        foreach ($unitSummary as $us) {
            $veh = $us['vehicle'];
            $latestInspection = $us['latest_inspection'] ? Carbon::parse($us['latest_inspection'])->translatedFormat('d/m/y H:i') : '-';
            $hm = $us['latest_hm'] !== null ? number_format($us['latest_hm'], 1, ',', '.') : '-';
            $modelCat = ($veh?->brand_type ?: '-') . ($veh?->unit_category ? ' (' . $veh->unit_category . ')' : '');

            $rows[] = [
                $idx++,
                $veh?->code ?: '-',
                $modelCat,
                (int) $us['total'],
                (int) $us['approved'],
                (int) $us['pending'],
                (int) $us['rejected'],
                (int) $us['has_defect'],
                $hm,
                $latestInspection,
            ];
        }

        $title = 'Laporan Summary Rekapitulasi P2H Unit Support';
        $subtitle = 'Kepatuhan & Kelayakan Operasional Alat Berat Lapangan - Periode: ' . $dateRangeStr;
        $pdfBinary = QmaTablePdfExporter::generate(
            $title,
            $subtitle,
            'SUMMARY P2H RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Summary_P2H_Unit_Support_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
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
     * Ekspor lembar hasil pemeriksaan P2H tunggal ke format PDF resmi.
     */
    public function exportSinglePdf(int $id): Response
    {
        $record = P2hRecord::with(['vehicle', 'employee', 'template', 'validator', 'items'])->findOrFail($id);

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

    /**
     * Ekspor lembar hasil pemeriksaan P2H tunggal ke format Excel (.xlsx).
     */
    public function exportSingleExcel(int $id): Response
    {
        $record = P2hRecord::with(['vehicle', 'employee', 'template', 'validator', 'items'])->findOrFail($id);

        $statusLabel = match($record->validation_status) {
            'approved' => 'Disetujui QMA (Approved)',
            'rejected' => 'Ditolak (Rejected)',
            default => 'Menunggu Review QMA (Pending)',
        };

        $conditionLabel = $record->hasDefects() 
            ? 'Ada Kerusakan (' . $record->defect_count . ' Item Rusak)' 
            : 'Normal (Laik Operasi)';

        $printedAt = now()->translatedFormat('d F Y, H:i');

        $metaLines = [
            'No. Transaksi: ' . $record->transaction_no . ' | Waktu Pemeriksaan: ' . ($record->inspected_at ? $record->inspected_at->format('d/m/Y H:i') : '-') . ' WIB | Shift: ' . $record->shift,
            'Unit Support: ' . ($record->vehicle->code ?? '-') . ' (' . ($record->vehicle->brand_type ?? '-') . ') | Kategori: ' . ($record->vehicle->unit_category ?? ($record->template->name ?? '-')) . ' | Lokasi: ' . ($record->vehicle->location ?? '-'),
            'Operator: ' . ($record->employee->name ?? '-') . ' (NIK: ' . ($record->employee->nik ?? '-') . ') | SIMPER: ' . ($record->employee?->simper_expired_at ? $record->employee->simper_expired_at->format('d/m/Y') : 'Non-SIMPER'),
            'Hour Meter (HM): ' . number_format($record->start_hm, 1, ',', '.') . ' HM | Kondisi Fisik: ' . $conditionLabel . ' | Status QMA: ' . $statusLabel,
            'Verifikator: ' . ($record->validator->name ?? '-') . ' | Waktu Validasi: ' . ($record->validated_at ? $record->validated_at->format('d/m/Y H:i') . ' WIB' : '-'),
            'Catatan Verifikator: ' . ($record->validation_notes ?: '-') . ' | Catatan Operator: ' . ($record->general_notes ?: '-'),
            'Dicetak pada: ' . $printedAt . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Seksi / Komponen' => 26,
            'Butir Pemeriksaan' => 45,
            'Status Kondisi' => 18,
            'Nilai Jawaban' => 20,
            'Catatan / Temuan Kerusakan' => 38,
        ];

        $rows = [];
        $idx = 1;
        foreach ($record->items as $item) {
            $cond = strtolower((string)$item->condition_status);
            $condText = match($cond) {
                'baik' => 'BAIK',
                'rusak' => 'RUSAK',
                default => '-',
            };

            $rows[] = [
                $idx++,
                $item->section ?? '-',
                $item->title ?? '-',
                $condText,
                $item->answer_value ?? '-',
                $item->notes ?? '-',
            ];
        }

        $alignments = [
            0 => 'center',
            1 => 'left',
            2 => 'left',
            3 => 'center',
            4 => 'center',
            5 => 'left',
        ];

        $xlsxContent = SimpleXlsxExporter::create(
            'LEMBAR PEMERIKSAAN HARIAN (P2H) UNIT SUPPORT - ' . ($record->vehicle->code ?? 'UNIT'),
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        $fileName = 'P2H_' . ($record->vehicle->code ?? 'Unit') . '_' . $record->transaction_no . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($xlsxContent),
            'Cache-Control' => 'max-age=0',
        ]);
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
