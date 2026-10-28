<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\P2hRecord;
use App\Models\P2hTemplate;
use App\Models\SimperType;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use App\Services\ImageCompressionService;
use App\Services\SimpleXlsxExporter;
use App\Services\VehiclePdfExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama Admin QMA (Quality Management Asset).
     */
    public function dashboard(): View
    {
        $totalLv = Vehicle::lightVehicles()->count();
        $totalSupport = Vehicle::unitSupportPhysical()->count() ?: Vehicle::unitSupports()->count();
        $totalVehicles = Vehicle::count();

        $today = Carbon::today()->toDateString();
        $todayActivitiesCount = VehicleActivity::whereDate('recorded_at', $today)->count();
        $todayP2hCount = P2hRecord::whereDate('inspected_at', $today)->count();

        // 1. Log Pergerakan Gerbang Terkini (6 data pergerakan armada terbaru)
        $recentActivities = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->latest('recorded_at')
            ->take(6)
            ->get();

        // 2. Monitoring Kelayakan P2H Terkini (5 checksheet inspeksi terbaru)
        $recentP2hRecords = P2hRecord::with(['vehicle', 'employee', 'template'])
            ->latest('inspected_at')
            ->take(5)
            ->get();

        // 3. Status Kesiapan Heavy Equipment
        $supportOperation = Vehicle::unitSupports()->where('status', 'Operation')->count();
        $supportStandby = Vehicle::unitSupports()->where('status', 'Standby')->count();
        $supportBreakdown = Vehicle::unitSupports()->where('status', 'Breakdown')->count();

        $recentUnits = Vehicle::unitSupports()
            ->orderByRaw("CASE WHEN status = 'Breakdown' THEN 0 WHEN status = 'Standby' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->take(4)
            ->get();

        return view('qma.dashboard', compact(
            'totalLv',
            'totalSupport',
            'totalVehicles',
            'todayActivitiesCount',
            'todayP2hCount',
            'recentActivities',
            'recentP2hRecords',
            'supportOperation',
            'supportStandby',
            'supportBreakdown',
            'recentUnits'
        ));
    }

    /**
     * Fallback atau redirect dari route index umum ke halaman Light Vehicle.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('qma.vehicles.lv');
    }

    /**
     * Helper query builder untuk memfilter data Light Vehicle (LV).
     */
    private function getFilteredLvQuery(Request $request): Builder
    {
        $query = Vehicle::lightVehicles();

        // Filter Status Operasional
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Pencarian Keyword LV
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code');
    }

    /**
     * Halaman khusus pengelolaan armada Kendaraan Ringan (Light Vehicle / LV).
     */
    public function indexLv(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 50, 100])) {
            $perPage = 15;
        }

        $vehicles = $this->getFilteredLvQuery($request)->paginate($perPage)->withQueryString();

        // Statistik Ringkasan Khusus LV
        $totalLv = Vehicle::lightVehicles()->count();
        $totalOperation = Vehicle::lightVehicles()->where('status', 'Operation')->count();
        $totalStandby = Vehicle::lightVehicles()->where('status', 'Standby')->count();
        $totalBreakdown = Vehicle::lightVehicles()->where('status', 'Breakdown')->count();

        // Daftar semua kode kendaraan LV untuk validasi unik langsung di form
        $existingCodes = Vehicle::lightVehicles()->pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        return view('qma.vehicles_lv', compact(
            'vehicles',
            'totalLv',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'existingCodes'
        ));
    }

    /**
     * Ekspor data Light Vehicle ke format Excel (.xlsx).
     */
    public function exportLvExcel(Request $request): Response
    {
        $vehicles = $this->getFilteredLvQuery($request)->get();

        $today = now()->translatedFormat('d F Y, H:i');
        $statusFilter = $request->filled('status') ? 'Status: ' . $request->input('status') : 'Semua Status Operasional';
        $searchFilter = $request->filled('search') ? 'Pencarian: "' . $request->input('search') . '"' : '';

        $metaLines = [
            'Filter: ' . $statusFilter . ($searchFilter ? ' | ' . $searchFilter : ''),
            'Total Data: ' . $vehicles->count() . ' Unit LV | Tanggal Dicetak: ' . $today . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Kode LV' => 16,
            'Merek & Tipe' => 32,
            'No. Polisi' => 18,
            'Divisi' => 24,
            'Kepemilikan' => 20,
            'Odometer (KM)' => 20,
            'Status Operasional' => 22,
        ];

        $rows = [];
        foreach ($vehicles as $idx => $v) {
            $odo = $v->current_km ? number_format($v->current_km, 0, ',', '.') . ' KM' : '0 KM';
            $rows[] = [
                $idx + 1,
                $v->code,
                $v->brand_type ?: '-',
                $v->license_plate ?: '-',
                $v->division ?: '-',
                $v->ownership ?: '-',
                $odo,
                $v->status ?: 'Standby',
            ];
        }

        $alignments = ['center', 'center', 'left', 'center', 'left', 'left', 'center', 'center'];

        $fileName = 'Laporan_Data_Light_Vehicle_' . now()->format('Ymd_Hi') . '.xlsx';
        $binary = SimpleXlsxExporter::create(
            'LAPORAN DATA KENDARAAN RINGAN (LIGHT VEHICLE) - PT KERINCI MERANGIN HIDRO',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($binary),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Ekspor data Light Vehicle ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportLvPdf(Request $request): Response
    {
        $vehicles = $this->getFilteredLvQuery($request)->get();

        $today = now()->translatedFormat('d F Y, H:i');
        $statusFilter = $request->filled('status') ? $request->input('status') : 'Semua Status Operasional';

        $totalOp = $vehicles->where('status', 'Operation')->count();
        $totalSt = $vehicles->where('status', 'Standby')->count();
        $totalBd = $vehicles->where('status', 'Breakdown')->count();

        $metaLeft = [
            'Kategori' => 'Light Vehicle (LV)',
            'Status Operasional' => $statusFilter,
            'Tanggal Cetak' => $today . ' WIB',
        ];

        $metaRight = [
            'Total Kendaraan' => $vehicles->count() . ' Unit LV',
            'Rincian Status' => "{$totalOp} Operation, {$totalSt} Standby, {$totalBd} Breakdown",
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C'],
            ['title' => 'KODE LV', 'width' => 25, 'align' => 'C', 'bold' => true],
            ['title' => 'MEREK & TIPE', 'width' => 64, 'align' => 'L', 'bold' => true, 'max_char' => 38],
            ['title' => 'NO. POLISI', 'width' => 28, 'align' => 'C'],
            ['title' => 'DIVISI', 'width' => 45, 'align' => 'L', 'max_char' => 28],
            ['title' => 'KEPEMILIKAN', 'width' => 35, 'align' => 'L', 'max_char' => 22],
            ['title' => 'ODOMETER', 'width' => 30, 'align' => 'C'],
            ['title' => 'STATUS', 'width' => 30, 'align' => 'C', 'is_status' => true],
        ];

        $rows = [];
        foreach ($vehicles as $idx => $v) {
            $odo = $v->current_km ? number_format($v->current_km, 0, ',', '.') . ' KM' : '0 KM';
            $rows[] = [
                $idx + 1,
                $v->code,
                $v->brand_type ?: '-',
                $v->license_plate ?: '-',
                $v->division ?: '-',
                $v->ownership ?: '-',
                $odo,
                $v->status ?: 'Standby',
            ];
        }

        $subtitle = 'Armada Kendaraan Operasional Light Vehicle - ' . now()->translatedFormat('d F Y');
        $pdfBinary = VehiclePdfExporter::generate(
            'Laporan Data Light Vehicle (LV)',
            $subtitle,
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Light_Vehicle_' . now()->format('Ymd_Hi') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Halaman khusus pengelolaan Kategori Kendaraan Berat (Heavy Equipment / Alat Berat).
     */
    public function indexSupport(Request $request): View
    {
        $query = Vehicle::unitSupportCategories()->withCount('units');

        // Filter Status Operasional
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Kategori Heavy Equipment (Excavator, Crane, Bulldozer, dll)
        if ($request->filled('unit_category')) {
            $query->where('unit_category', $request->input('unit_category'));
        }

        // Filter Pencarian Keyword Heavy Equipment
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%")
                  ->orWhere('unit_type', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 50, 100])) {
            $perPage = 15;
        }

        $vehicles = $query->orderBy('code')->paginate($perPage)->withQueryString();

        // Statistik Ringkasan Khusus Heavy Equipment
        $totalSupport = Vehicle::unitSupportCategories()->count();
        $totalUnits = Vehicle::unitSupportPhysical()->count();
        $totalOperation = Vehicle::unitSupportCategories()->where('status', 'Operation')->count();
        $totalStandby = Vehicle::unitSupportCategories()->where('status', 'Standby')->count();
        $totalBreakdown = Vehicle::unitSupportCategories()->where('status', 'Breakdown')->count();

        // Daftar kategori alat berat yang terdaftar untuk opsi filter
        $availableCategories = Vehicle::unitSupportCategories()
            ->whereNotNull('unit_category')
            ->distinct()
            ->pluck('unit_category');

        // Master data daftar divisi perusahaan (gabungan standar + data existing di Employee & Vehicle)
        $standardDivisions = collect([
            'Civil & Inviro',
            'GA',
            'HR',
            'INFORMATION & TECHNOLOGY',
            'K3',
            'Keuangan',
            'Logistik',
            'Mechanical',
            'Operasional',
            'PG',
            'Quality Management Asset (QMA)',
        ]);

        $availableDivisions = $standardDivisions
            ->merge(Employee::whereNotNull('division')->where('division', '!=', '')->pluck('division'))
            ->merge(Vehicle::whereNotNull('division')->where('division', '!=', '')->pluck('division'))
            ->map(fn($d) => trim($d))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Daftar semua kode Heavy Equipment untuk validasi unik langsung di form
        $existingCodes = Vehicle::unitSupports()->pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        return view('qma.vehicles_support', compact(
            'vehicles',
            'totalSupport',
            'totalUnits',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'availableCategories',
            'availableDivisions',
            'existingCodes'
        ));
    }

    /**
     * Halaman daftar unit fisik berdasarkan kategori Heavy Equipment yang dipilih.
     */
    public function indexSupportUnits(Request $request, int $id): View
    {
        $category = Vehicle::unitSupportCategories()->withCount('units')->findOrFail($id);

        $query = $category->units();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('production_year', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('silo_document', 'like', "%{$search}%")
                  ->orWhere('kir_document', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [15, 50, 100])) {
            $perPage = 15;
        }

        $units = $query->paginate($perPage)->withQueryString();

        $totalUnits = $category->units()->count();
        $totalOperation = $category->units()->where('status', 'Operation')->count();
        $totalStandby = $category->units()->where('status', 'Standby')->count();
        $totalBreakdown = $category->units()->where('status', 'Breakdown')->count();
        $totalMaintenance = $category->units()->where('status', 'Maintenance')->count();

        $nextUnitNumber = $category->getNextUnitNumber();
        $nextUnitCode = $category->getNextUnitCode();

        $existingCodes = Vehicle::pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        // Master data lokasi penempatan (gabungan pos gerbang aktif & lokasi operasional proyek)
        $masterLocations = Gate::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Vehicle::whereNotNull('location')
                    ->where('location', '!=', '')
                    ->pluck('location')
            )
            ->map(fn($loc) => trim($loc))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $simperTypes = SimperType::active()->orderBy('name')->get();

        return view('qma.vehicles_support_units', compact(
            'category',
            'units',
            'totalUnits',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'totalMaintenance',
            'nextUnitNumber',
            'nextUnitCode',
            'existingCodes',
            'masterLocations',
            'simperTypes'
        ));
    }

    /**
     * Helper query builder untuk unit fisik alat berat (Heavy Equipment Physical).
     */
    private function getFilteredSupportPhysicalQuery(Request $request): Builder
    {
        $query = Vehicle::unitSupportPhysical()->with('parent');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('unit_category')) {
            $cat = $request->input('unit_category');
            $query->where(function ($q) use ($cat) {
                $q->where('unit_category', $cat)
                  ->orWhereHas('parent', fn ($pq) => $pq->where('unit_category', $cat));
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhereHas('parent', function ($pq) use ($search) {
                      $pq->where('code', 'like', "%{$search}%")
                         ->orWhere('unit_category', 'like', "%{$search}%")
                         ->orWhere('brand_type', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('code');
    }

    /**
     * Helper query builder untuk master kategori Heavy Equipment.
     */
    private function getFilteredSupportCategoriesQuery(Request $request): Builder
    {
        $query = Vehicle::unitSupportCategories()->withCount('units');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('unit_category')) {
            $query->where('unit_category', $request->input('unit_category'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code');
    }

    /**
     * Ekspor data Heavy Equipment ke format Excel (.xlsx).
     */
    public function exportSupportExcel(Request $request): Response
    {
        $hasPhysical = Vehicle::unitSupportPhysical()->exists();
        $today = now()->translatedFormat('d F Y, H:i');

        if ($hasPhysical) {
            $units = $this->getFilteredSupportPhysicalQuery($request)->get();

            $metaLines = [
                'Filter: ' . ($request->filled('status') ? 'Status: ' . $request->input('status') : 'Semua Status') .
                ($request->filled('unit_category') ? ' | Kategori: ' . $request->input('unit_category') : '') .
                ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
                'Total Data: ' . $units->count() . ' Unit Fisik Alat Berat | Tanggal Dicetak: ' . $today . ' WIB',
            ];

            $headers = [
                'No' => 6,
                'Kode Unit' => 16,
                'Kategori Alat' => 28,
                'No. Seri / Rangka' => 24,
                'Tahun' => 12,
                'No. Polisi' => 18,
                'Lokasi Kerja' => 24,
                'Odometer (HM/KM)' => 20,
                'Dokumen Izin' => 20,
                'Status Operasional' => 22,
            ];

            $rows = [];
            foreach ($units as $idx => $u) {
                $categoryName = $u->parent?->unit_category ?: ($u->parent?->brand_type ?: ($u->unit_category ?: 'Heavy Equipment'));
                $odo = $u->current_km ? number_format($u->current_km, 0, ',', '.') . ' HM' : '0 HM';
                $docs = trim(($u->silo_document ? 'SILO ' : '') . ($u->kir_document ? 'KIR ' : '')) ?: '-';

                $rows[] = [
                    $idx + 1,
                    $u->code,
                    $categoryName,
                    $u->serial_number ?: '-',
                    $u->production_year ?: '-',
                    $u->license_plate ?: '-',
                    $u->location ?: '-',
                    $odo,
                    $docs,
                    $u->status ?: 'Standby',
                ];
            }

            $alignments = ['center', 'center', 'left', 'left', 'center', 'center', 'left', 'center', 'center', 'center'];
            $title = 'LAPORAN DAFTAR UNIT FISIK ALAT BERAT (HEAVY EQUIPMENT) - PT KERINCI MERANGIN HIDRO';
            $fileName = 'Laporan_Heavy_Equipment_Lengkap_' . now()->format('Ymd_Hi') . '.xlsx';
        } else {
            // Fallback ke master kategori jika unit fisik belum diinput
            $categories = $this->getFilteredSupportCategoriesQuery($request)->get();

            $metaLines = [
                'Filter: ' . ($request->filled('status') ? 'Status: ' . $request->input('status') : 'Semua Status') .
                ($request->filled('unit_category') ? ' | Kategori: ' . $request->input('unit_category') : ''),
                'Total Data: ' . $categories->count() . ' Kategori Alat Berat | Tanggal Dicetak: ' . $today . ' WIB',
            ];

            $headers = [
                'No' => 6,
                'Kode Kategori' => 16,
                'Kategori Alat Berat' => 30,
                'Merek & Tipe Model' => 30,
                'Total Unit Fisik' => 18,
                'Lokasi Operasional' => 24,
                'Status Operasional' => 22,
            ];

            $rows = [];
            foreach ($categories as $idx => $cat) {
                $rows[] = [
                    $idx + 1,
                    $cat->code,
                    $cat->unit_category ?: $cat->brand_type,
                    $cat->brand_type ?: '-',
                    $cat->units_count . ' Unit',
                    $cat->location ?: '-',
                    $cat->status ?: 'Standby',
                ];
            }

            $alignments = ['center', 'center', 'left', 'left', 'center', 'left', 'center'];
            $title = 'LAPORAN MASTER KATEGORI ALAT BERAT (HEAVY EQUIPMENT) - PT KERINCI MERANGIN HIDRO';
            $fileName = 'Laporan_Kategori_Heavy_Equipment_' . now()->format('Ymd_Hi') . '.xlsx';
        }

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
     * Ekspor data Heavy Equipment ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportSupportPdf(Request $request): Response
    {
        $hasPhysical = Vehicle::unitSupportPhysical()->exists();
        $today = now()->translatedFormat('d F Y, H:i');
        $statusFilter = $request->filled('status') ? $request->input('status') : 'Semua Status Operasional';

        if ($hasPhysical) {
            $units = $this->getFilteredSupportPhysicalQuery($request)->get();

            $totalOp = $units->where('status', 'Operation')->count();
            $totalSt = $units->where('status', 'Standby')->count();
            $totalBd = $units->where('status', 'Breakdown')->count();

            $metaLeft = [
                'Kategori' => 'Heavy Equipment (Alat Berat Fisik)',
                'Status Operasional' => $statusFilter,
                'Tanggal Cetak' => $today . ' WIB',
            ];

            $metaRight = [
                'Total Unit Fisik' => $units->count() . ' Unit Alat Berat',
                'Rincian Status' => "{$totalOp} Operation, {$totalSt} Standby, {$totalBd} Breakdown",
            ];

            $columns = [
                ['title' => 'NO', 'width' => 10, 'align' => 'C'],
                ['title' => 'KODE UNIT', 'width' => 24, 'align' => 'C', 'bold' => true],
                ['title' => 'KATEGORI ALAT', 'width' => 45, 'align' => 'L', 'bold' => true, 'max_char' => 26],
                ['title' => 'NO. SERI / RANGKA', 'width' => 42, 'align' => 'L', 'max_char' => 24],
                ['title' => 'THN', 'width' => 14, 'align' => 'C'],
                ['title' => 'NO. POLISI', 'width' => 24, 'align' => 'C'],
                ['title' => 'LOKASI KERJA', 'width' => 50, 'align' => 'L', 'max_char' => 28],
                ['title' => 'ODOMETER', 'width' => 28, 'align' => 'C'],
                ['title' => 'STATUS', 'width' => 30, 'align' => 'C', 'is_status' => true],
            ];

            $rows = [];
            foreach ($units as $idx => $u) {
                $categoryName = $u->parent?->unit_category ?: ($u->parent?->brand_type ?: ($u->unit_category ?: 'Heavy Equipment'));
                $odo = $u->current_km ? number_format($u->current_km, 0, ',', '.') . ' HM' : '0 HM';

                $rows[] = [
                    $idx + 1,
                    $u->code,
                    $categoryName,
                    $u->serial_number ?: '-',
                    $u->production_year ?: '-',
                    $u->license_plate ?: '-',
                    $u->location ?: '-',
                    $odo,
                    $u->status ?: 'Standby',
                ];
            }

            $title = 'Laporan Data Heavy Equipment (Alat Berat)';
            $subtitle = 'Daftar Seluruh Unit Fisik Alat Berat Lapangan - ' . now()->translatedFormat('d F Y');
            $fileName = 'Laporan_Heavy_Equipment_' . now()->format('Ymd_Hi') . '.pdf';
        } else {
            $categories = $this->getFilteredSupportCategoriesQuery($request)->get();

            $totalOp = $categories->where('status', 'Operation')->count();
            $totalSt = $categories->where('status', 'Standby')->count();
            $totalBd = $categories->where('status', 'Breakdown')->count();

            $metaLeft = [
                'Kategori' => 'Master Kategori Heavy Equipment',
                'Status Operasional' => $statusFilter,
                'Tanggal Cetak' => $today . ' WIB',
            ];

            $metaRight = [
                'Total Kategori' => $categories->count() . ' Kategori Alat',
                'Rincian Status' => "{$totalOp} Operation, {$totalSt} Standby, {$totalBd} Breakdown",
            ];

            $columns = [
                ['title' => 'NO', 'width' => 10, 'align' => 'C'],
                ['title' => 'KODE', 'width' => 25, 'align' => 'C', 'bold' => true],
                ['title' => 'KATEGORI ALAT BERAT', 'width' => 64, 'align' => 'L', 'bold' => true, 'max_char' => 38],
                ['title' => 'MEREK & MODEL', 'width' => 55, 'align' => 'L', 'max_char' => 32],
                ['title' => 'TOTAL UNIT', 'width' => 28, 'align' => 'C', 'bold' => true],
                ['title' => 'LOKASI OPERASIONAL', 'width' => 55, 'align' => 'L', 'max_char' => 32],
                ['title' => 'STATUS', 'width' => 30, 'align' => 'C', 'is_status' => true],
            ];

            $rows = [];
            foreach ($categories as $idx => $cat) {
                $rows[] = [
                    $idx + 1,
                    $cat->code,
                    $cat->unit_category ?: $cat->brand_type,
                    $cat->brand_type ?: '-',
                    $cat->units_count . ' Unit',
                    $cat->location ?: '-',
                    $cat->status ?: 'Standby',
                ];
            }

            $title = 'Laporan Master Kategori Heavy Equipment';
            $subtitle = 'Master 28 Kategori Alat Berat Proyek KMH - ' . now()->translatedFormat('d F Y');
            $fileName = 'Laporan_Kategori_Heavy_Equipment_' . now()->format('Ymd_Hi') . '.pdf';
        }

        $pdfBinary = VehiclePdfExporter::generate($title, $subtitle, $metaLeft, $metaRight, $columns, $rows);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Ekspor data unit fisik khusus kategori tertentu ke format Excel (.xlsx).
     */
    public function exportSupportUnitsExcel(Request $request, int $id): Response
    {
        $category = Vehicle::unitSupportCategories()->withCount('units')->findOrFail($id);
        $query = $category->units();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('code')->get();
        $today = now()->translatedFormat('d F Y, H:i');

        $metaLines = [
            'Kategori: ' . ($category->unit_category ?: $category->brand_type) . ' (' . $category->code . ')' .
            ($request->filled('status') ? ' | Status: ' . $request->input('status') : '') .
            ($request->filled('search') ? ' | Pencarian: "' . $request->input('search') . '"' : ''),
            'Total Data: ' . $units->count() . ' Unit Fisik | Tanggal Dicetak: ' . $today . ' WIB',
        ];

        $headers = [
            'No' => 6,
            'Kode Unit' => 16,
            'No. Seri / Rangka' => 24,
            'Tahun' => 12,
            'No. Polisi' => 18,
            'Lokasi Kerja' => 24,
            'Odometer (HM/KM)' => 20,
            'Dokumen Izin' => 20,
            'Status Operasional' => 22,
        ];

        $rows = [];
        foreach ($units as $idx => $u) {
            $odo = $u->current_km ? number_format($u->current_km, 0, ',', '.') . ' HM' : '0 HM';
            $docs = trim(($u->silo_document ? 'SILO ' : '') . ($u->kir_document ? 'KIR ' : '')) ?: '-';

            $rows[] = [
                $idx + 1,
                $u->code,
                $u->serial_number ?: '-',
                $u->production_year ?: '-',
                $u->license_plate ?: '-',
                $u->location ?: '-',
                $odo,
                $docs,
                $u->status ?: 'Standby',
            ];
        }

        $alignments = ['center', 'center', 'left', 'center', 'center', 'left', 'center', 'center', 'center'];
        $title = 'LAPORAN UNIT FISIK ' . strtoupper($category->unit_category ?: $category->brand_type) . ' - PT KERINCI MERANGIN HIDRO';
        $fileName = 'Laporan_Unit_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $category->code) . '_' . now()->format('Ymd_Hi') . '.xlsx';

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
     * Ekspor data unit fisik khusus kategori tertentu ke format PDF (Pratinjau Asli dengan Logo PT KMH).
     */
    public function exportSupportUnitsPdf(Request $request, int $id): Response
    {
        $category = Vehicle::unitSupportCategories()->withCount('units')->findOrFail($id);
        $query = $category->units();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('code')->get();
        $today = now()->translatedFormat('d F Y, H:i');
        $statusFilter = $request->filled('status') ? $request->input('status') : 'Semua Status Operasional';

        $totalOp = $units->where('status', 'Operation')->count();
        $totalSt = $units->where('status', 'Standby')->count();
        $totalBd = $units->where('status', 'Breakdown')->count();

        $metaLeft = [
            'Kategori Alat' => ($category->unit_category ?: $category->brand_type) . ' (' . $category->code . ')',
            'Status Operasional' => $statusFilter,
            'Tanggal Cetak' => $today . ' WIB',
        ];

        $metaRight = [
            'Total Unit Fisik' => $units->count() . ' Unit Terdaftar',
            'Rincian Status' => "{$totalOp} Operation, {$totalSt} Standby, {$totalBd} Breakdown",
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C'],
            ['title' => 'KODE UNIT', 'width' => 28, 'align' => 'C', 'bold' => true],
            ['title' => 'NO. SERI / RANGKA', 'width' => 50, 'align' => 'L', 'max_char' => 30],
            ['title' => 'TAHUN', 'width' => 18, 'align' => 'C'],
            ['title' => 'NO. POLISI', 'width' => 28, 'align' => 'C'],
            ['title' => 'LOKASI KERJA', 'width' => 65, 'align' => 'L', 'max_char' => 38],
            ['title' => 'ODOMETER', 'width' => 34, 'align' => 'C'],
            ['title' => 'STATUS', 'width' => 34, 'align' => 'C', 'is_status' => true],
        ];

        $rows = [];
        foreach ($units as $idx => $u) {
            $odo = $u->current_km ? number_format($u->current_km, 0, ',', '.') . ' HM' : '0 HM';
            $rows[] = [
                $idx + 1,
                $u->code,
                $u->serial_number ?: '-',
                $u->production_year ?: '-',
                $u->license_plate ?: '-',
                $u->location ?: '-',
                $odo,
                $u->status ?: 'Standby',
            ];
        }

        $title = 'Laporan Unit Fisik ' . ($category->unit_category ?: $category->brand_type);
        $subtitle = 'Kategori ' . $category->code . ' - ' . now()->translatedFormat('d F Y');
        $fileName = 'Laporan_Unit_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $category->code) . '_' . now()->format('Ymd_Hi') . '.pdf';

        $pdfBinary = VehiclePdfExporter::generate($title, $subtitle, $metaLeft, $metaRight, $columns, $rows);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Simpan penambahan unit fisik baru di bawah kategori Heavy Equipment tertentu.
     */
    public function storeSupportUnit(Request $request, int $id): RedirectResponse
    {
        $category = Vehicle::unitSupportCategories()->findOrFail($id);

        $request->validate([
            'unit_number' => ['nullable', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'status' => ['required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:150'],
            'silo_document' => ['nullable', 'string', 'max:150'],
            'kir_document' => ['nullable', 'string', 'max:150'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'is_timesheet_mobile' => ['nullable', 'boolean'],
            'required_simper_type_id' => ['nullable', 'exists:simper_types,id'],
        ]);

        return DB::transaction(function () use ($request, $category) {
            // Tentukan nomor unit dan kode unit
            $rawUnitNumber = trim((string)$request->input('unit_number'));
            if ($rawUnitNumber !== '') {
                if (ctype_digit($rawUnitNumber)) {
                    $unitNumber = str_pad($rawUnitNumber, 3, '0', STR_PAD_LEFT);
                } else {
                    $unitNumber = $rawUnitNumber;
                }
                $code = strtoupper(trim($category->code . '-' . $unitNumber));
            } elseif ($request->filled('code')) {
                $code = strtoupper(trim($request->input('code')));
            } else {
                $code = $category->getNextUnitCode();
            }

            // Validasi unik dengan lock for update
            if (Vehicle::where('code', $code)->lockForUpdate()->exists()) {
                return back()->withInput()->with('error', "Kode unit [{$code}] sudah terdaftar di sistem. Silakan gunakan nomor urut lain.");
            }

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = ImageCompressionService::uploadAndCompress($request->file('photo'), 'vehicles', 800, 800, 75);
            }

            $unit = Vehicle::create([
                'code' => $code,
                'category' => 'unit_support',
                'parent_id' => $category->id,
                'p2h_template_id' => $category->p2h_template_id,
                'brand_type' => $category->brand_type,
                'unit_category' => $category->unit_category,
                'unit_type' => $category->unit_type,
                'brand' => $category->brand,
                'model_number' => $category->model_number,
                'license_plate' => $request->input('license_plate'),
                'serial_number' => $request->input('serial_number'),
                'production_year' => $request->input('production_year'),
                'status' => $request->input('status', 'Operation'),
                'current_km' => $request->input('current_km', 0),
                'location' => $request->input('location', $category->location),
                'ownership' => $request->input('ownership', $category->ownership),
                'division' => $request->input('division', $category->division),
                'fuel_consumption' => $category->fuel_consumption,
                'silo_document' => $request->input('silo_document'),
                'kir_document' => $request->input('kir_document'),
                'photo_path' => $photoPath,
                'is_timesheet_mobile' => $request->boolean('is_timesheet_mobile'),
                'required_simper_type_id' => $request->input('required_simper_type_id') ?: null,
            ]);

            return back()->with('success', "Unit fisik [{$unit->code}] berhasil ditambahkan ke kategori {$category->unit_category}.");
        });
    }

    /**
     * Simpan data aset kendaraan baru (Kendaraan Ringan atau Kategori Kendaraan Berat).
     */
    public function store(Request $request): RedirectResponse
    {
        $category = $request->input('category', 'light_vehicle');

        $rules = [
            'code' => ['required', 'string', 'max:50', 'unique:vehicles,code'],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => [$category === 'unit_support' ? 'nullable' : 'required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => [$category === 'unit_support' ? 'nullable' : 'required', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'is_timesheet_mobile' => ['nullable', 'boolean'],
            'required_simper_type_id' => ['nullable', 'exists:simper_types,id'],
        ];

        // Atribut opsional spesifik Heavy Equipment (Kendaraan Berat)
        if ($category === 'unit_support') {
            $rules = array_merge($rules, [
                'unit_category' => ['nullable', 'string', 'max:100'],
                'unit_type' => ['nullable', 'string', 'max:100'],
                'brand' => ['nullable', 'string', 'max:100'],
                'model_number' => ['nullable', 'string', 'max:100'],
                'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
                'location' => ['nullable', 'string', 'max:150'],
                'fuel_consumption' => ['nullable', 'numeric', 'min:0'],
                'silo_document' => ['nullable', 'string', 'max:150'],
                'stnk_document' => ['nullable', 'string', 'max:150'],
                'pajak_document' => ['nullable', 'string', 'max:150'],
                'kir_document' => ['nullable', 'string', 'max:150'],
            ]);
        }

        $validated = $request->validate($rules, [
            'code.required' => 'Kode kendaraan wajib diisi.',
            'code.unique' => 'Kode sudah ada, silakan gunakan kode lain.',
            'brand_type.required' => 'Merek dan spesifikasi/tipe wajib diisi.',
            'current_km.required' => 'Nilai KM / HM awal wajib diisi.',
            'status.required' => 'Status operasional wajib dipilih.',
        ]);

        if ($category === 'unit_support') {
            $validated['status'] = $validated['status'] ?? 'Operation';
            $validated['current_km'] = $validated['current_km'] ?? 0;
        }

        $validated['is_timesheet_mobile'] = $request->boolean('is_timesheet_mobile');
        $validated['required_simper_type_id'] = $request->input('required_simper_type_id') ?: null;

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = ImageCompressionService::uploadAndCompress($request->file('photo'), 'vehicles', 800, 800, 75);
        }
        unset($validated['photo']);

        $vehicle = Vehicle::create($validated);

        if ($vehicle->isSupportCategory()) {
            // Otomatis buatkan Master Template P2H baru (default kosong tanpa butir pertanyaan)
            $template = P2hTemplate::firstOrCreate(
                ['code' => $vehicle->code],
                [
                    'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                    'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$vehicle->code}).",
                    'is_active' => true,
                ]
            );
            $vehicle->update(['p2h_template_id' => $template->id]);
        }

        $categoryLabel = $vehicle->isLightVehicle() ? 'Light Vehicle (LV)' : 'Kategori Heavy Equipment';

        return back()->with('success', "Data {$categoryLabel} [{$vehicle->code}] berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui data aset kendaraan (LV atau Heavy Equipment).
     * Jika kode Kategori Heavy Equipment diubah, otomatis sinkronisasikan kode seluruh unit fisik di bawahnya serta Master Template P2H.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $oldCode = $vehicle->code;
        $newCode = strtoupper(trim($request->input('code')));

        $rules = [
            'code' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($vehicle->id)],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => [$vehicle->isSupportCategory() ? 'nullable' : 'required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => [$vehicle->isSupportCategory() ? 'nullable' : 'required', 'numeric', 'min:0'],
            'unit_category' => ['nullable', 'string', 'max:100'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'location' => ['nullable', 'string', 'max:150'],
            'fuel_consumption' => ['nullable', 'numeric', 'min:0'],
            'silo_document' => ['nullable', 'string', 'max:150'],
            'stnk_document' => ['nullable', 'string', 'max:150'],
            'pajak_document' => ['nullable', 'string', 'max:150'],
            'kir_document' => ['nullable', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'is_timesheet_mobile' => ['nullable', 'boolean'],
            'required_simper_type_id' => ['nullable', 'exists:simper_types,id'],
        ];

        $validated = $request->validate($rules, [
            'code.required' => 'Kode kendaraan wajib diisi.',
            'code.unique' => 'Kode sudah ada, silakan gunakan kode lain.',
            'brand_type.required' => 'Merek dan tipe wajib diisi.',
            'current_km.required' => 'Nilai KM / HM wajib diisi.',
        ]);

        if ($request->hasFile('photo')) {
            ImageCompressionService::deleteIfExists($vehicle->photo_path);
            $validated['photo_path'] = ImageCompressionService::uploadAndCompress($request->file('photo'), 'vehicles', 800, 800, 75);
        }
        unset($validated['photo']);

        $validated['is_timesheet_mobile'] = $request->boolean('is_timesheet_mobile');
        $validated['required_simper_type_id'] = $request->input('required_simper_type_id') ?: null;

        DB::transaction(function () use ($vehicle, $validated, $oldCode, $newCode) {
            $vehicle->update($validated);

            // Jika ini kategori Heavy Equipment, sinkronkan Master Template P2H dan unit-unit fisik di bawahnya
            if ($vehicle->isSupportCategory()) {
                $template = $vehicle->p2hTemplate ?: P2hTemplate::where('code', $oldCode)->first();
                if ($template) {
                    $template->update([
                        'code' => $newCode,
                        'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                        'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$newCode}).",
                    ]);
                    if ($vehicle->p2h_template_id !== $template->id) {
                        $vehicle->update(['p2h_template_id' => $template->id]);
                    }
                } else {
                    $template = P2hTemplate::firstOrCreate(
                        ['code' => $newCode],
                        [
                            'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                            'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$newCode}).",
                            'is_active' => true,
                        ]
                    );
                    $vehicle->update(['p2h_template_id' => $template->id]);
                }

                if ($oldCode !== $newCode) {
                    $childUnits = $vehicle->units()->lockForUpdate()->get();
                    foreach ($childUnits as $child) {
                        if (str_starts_with($child->code, $oldCode . '-')) {
                            $suffix = substr($child->code, strlen($oldCode . '-'));
                            $child->code = $newCode . '-' . $suffix;
                        } else {
                            $child->code = $newCode . '-' . $child->code;
                        }
                        $child->p2h_template_id = $template->id;
                        $child->save();
                    }
                }
            }
        });

        return back()->with('success', "Data unit kendaraan [{$vehicle->code}] berhasil diperbarui.");
    }

    /**
     * Hapus data aset kendaraan dari sistem.
     */
    public function destroy(int $id): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        // Cegah penghapusan jika unit sudah tercatat di transaksi pergerakan gerbang
        if ($vehicle->activities()->exists()) {
            return back()->with('error', "Unit {$vehicle->code} tidak dapat dihapus karena telah memiliki catatan riwayat pergerakan di pos gerbang.");
        }

        // Cegah jika memiliki catatan P2H
        if ($vehicle->p2hRecords()->exists()) {
            return back()->with('error', "Aset {$vehicle->code} tidak dapat dihapus karena telah memiliki riwayat inspeksi P2H.");
        }

        // Jika kategori memiliki unit fisik yang punya P2H atau riwayat aktivitas
        if ($vehicle->isSupportCategory()) {
            foreach ($vehicle->units as $u) {
                if ($u->activities()->exists() || $u->p2hRecords()->exists()) {
                    return back()->with('error', "Kategori {$vehicle->code} tidak dapat dihapus karena unit fisik [{$u->code}] di bawahnya telah memiliki riwayat data operasional.");
                }
            }

            // Hapus juga master template jika belum ada riwayat inspeksi
            $template = $vehicle->p2hTemplate ?: P2hTemplate::where('code', $vehicle->code)->first();
            if ($template && !$template->records()->exists()) {
                $template->items()->delete();
                $template->delete();
            }
        }

        // Berkas fisik foto tidak dihapus di sini karena data masuk ke Tong Sampah (Soft Delete).
        // Berkas foto akan tetap aman sehingga jika dipulihkan (restore), foto unit tetap ada.
        // Berkas fisik baru dihapus permanen ketika dieksekusi 'Hapus Permanen' di TrashService.

        $code = $vehicle->code;
        $vehicle->delete();

        return back()->with('success', "Unit/Kategori kendaraan {$code} berhasil dihapus dari sistem.");
    }
}
