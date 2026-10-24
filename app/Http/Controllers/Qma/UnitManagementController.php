<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Services\QmaTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitManagementController extends Controller
{
    /**
     * Preset lokasi bawaan di dalam dan di luar site proyek Kerinci Hydropower.
     */
    public const PRESET_INSIDE_LOCATIONS = [
        'Main Dam',
        'Adit 1',
        'Adit 2',
        'Adit 3',
        'Powerhouse',
        'Regulating Weir',
        'Vertical Shaft',
        'Workshop GA',
        'Workshop Mekanik',
        'Batching Plant',
        'Crusher Plant',
        'Dermaga Jakon',
        'Bendungan Bedeng 6',
        'CCR DAM',
        'Crest DAM',
    ];

    public const PRESET_OUTSIDE_LOCATIONS = [
        'Kota Bangko',
        'Kota Jambi',
        'Mess Kumun',
        'Mess Kerinci',
        'Bengkel Rekanan Bangko',
        'Bengkel Resmi Padang',
        'Perjalanan Dinas Luar Kota',
        'Bandara Depati Parbo',
        'Pelabuhan Teluk Bayur',
    ];

    /**
     * Dapatkan daftar saran lokasi gabungan (preset + gerbang aktif + lokasi riwayat tersimpan).
     */
    public static function getAllLocationSuggestions(): array
    {
        $gateNames = Gate::where('is_active', true)->orderBy('name')->pluck('name')->all();

        $dbLocations = Vehicle::whereNotNull('location')
            ->where('location', '!=', '')
            ->pluck('location')
            ->merge(
                VehicleLocationHistory::whereNotNull('location')
                    ->where('location', '!=', '')
                    ->pluck('location')
            )
            ->map(fn($l) => trim($l))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return collect(array_merge(
            self::PRESET_INSIDE_LOCATIONS,
            self::PRESET_OUTSIDE_LOCATIONS,
            $gateNames,
            $dbLocations
        ))->unique()->sort()->values()->all();
    }

    /**
     * Query builder helper dengan filter aktif.
     */
    private function getFilteredQuery(Request $request): Builder
    {
        $query = Vehicle::trackableUnits()->with(['parent']);

        // Filter Kategori (all, light_vehicle, unit_support)
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Filter Status Posisi Site (all, inside_site, outside_site)
        if ($request->filled('site_status') && $request->input('site_status') !== 'all') {
            $query->where('site_status', $request->input('site_status'));
        }

        // Filter Status Operasional (Operation, Standby, Breakdown, Maintenance)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter Kata Kunci Pencarian (Kode, Plat, Tipe, Lokasi, No Seri)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('location_note', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        // Urutkan: Terakhir diperbarui posisinya lebih dulu, lalu kode
        return $query->orderByRaw("COALESCE(location_updated_at, updated_at) DESC")->orderBy('code');
    }

    /**
     * Tampilkan halaman utama Manajemen Unit & Tracking Posisi.
     */
    public function index(Request $request): View
    {
        $query = $this->getFilteredQuery($request);
        $units = $query->paginate(15)->withQueryString();

        // KPI Ringkasan
        $baseQuery = Vehicle::trackableUnits();
        $totalUnits = (clone $baseQuery)->count();
        $insideCount = (clone $baseQuery)->where('site_status', 'inside_site')->count();
        $outsideCount = (clone $baseQuery)->where('site_status', 'outside_site')->count();
        $operationCount = (clone $baseQuery)->where('status', 'Operation')->count();
        $breakdownCount = (clone $baseQuery)->where('status', 'Breakdown')->count();

        // Preset lokasi untuk datalist HTML
        $locationSuggestions = self::getAllLocationSuggestions();
        $insidePresets = self::PRESET_INSIDE_LOCATIONS;
        $outsidePresets = self::PRESET_OUTSIDE_LOCATIONS;

        return view('qma.unit_management', compact(
            'units',
            'totalUnits',
            'insideCount',
            'outsideCount',
            'operationCount',
            'breakdownCount',
            'locationSuggestions',
            'insidePresets',
            'outsidePresets'
        ));
    }

    /**
     * Update posisi unit secara manual oleh Admin QMA dengan pencatatan audit log riwayat.
     */
    public function updateLocation(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $vehicle = Vehicle::trackableUnits()->findOrFail($id);

        $validated = $request->validate([
            'site_status' => ['required', 'in:inside_site,outside_site'],
            'location' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'site_status.required' => 'Pilih status posisi unit (Di Dalam Site atau Di Luar Site).',
            'site_status.in' => 'Status posisi tidak valid.',
            'location.required' => 'Detail lokasi penempatan wajib diisi.',
            'location.max' => 'Detail lokasi maksimal 255 karakter.',
        ]);

        $updaterName = auth()->user()?->name ?? 'Admin QMA';
        $location = trim($validated['location']);
        $note = !empty($validated['note']) ? trim($validated['note']) : null;

        DB::transaction(function () use ($vehicle, $validated, $location, $note, $updaterName) {
            // 1. Update data master posisi kendaraan
            $vehicle->update([
                'site_status' => $validated['site_status'],
                'location' => $location,
                'location_updated_at' => Carbon::now(),
                'location_updated_by' => $updaterName,
                'location_note' => $note,
            ]);

            // 2. Simpan jejak riwayat pergerakan (audit trail)
            VehicleLocationHistory::create([
                'vehicle_id' => $vehicle->id,
                'site_status' => $validated['site_status'],
                'location' => $location,
                'note' => $note,
                'source' => 'manual',
                'user_id' => auth()->id(),
                'user_name' => $updaterName,
                'recorded_at' => Carbon::now(),
            ]);
        });

        $statusText = $validated['site_status'] === 'inside_site' ? 'Di Dalam Site' : 'Di Luar Site';
        $msg = "Posisi unit {$vehicle->code} berhasil diperbarui: {$statusText} ({$location}).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'vehicle' => $vehicle->fresh(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Ambil riwayat pergerakan posisi suatu unit (JSON) untuk modal timeline / audit trail.
     */
    public function history(int $id): JsonResponse
    {
        $vehicle = Vehicle::trackableUnits()->findOrFail($id);

        $histories = $vehicle->locationHistories()
            ->with(['user', 'activity.gate'])
            ->latest('recorded_at')
            ->take(50)
            ->get();

        $items = $histories->map(function (VehicleLocationHistory $h) {
            return [
                'id' => $h->id,
                'site_status' => $h->site_status,
                'site_status_label' => $h->site_status_label,
                'location' => $h->location,
                'note' => $h->note ?: '-',
                'source' => $h->source,
                'source_label' => $h->source_label,
                'updater' => $h->user_name ?: ($h->user?->name ?? 'Sistem'),
                'recorded_at' => $h->recorded_at ? $h->recorded_at->translatedFormat('d M Y, H:i') : '-',
                'time_ago' => $h->recorded_at ? $h->recorded_at->diffForHumans() : '-',
                'trx_no' => $h->activity?->transaction_no ?? null,
            ];
        });

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'code' => $vehicle->code,
                'brand_type' => $vehicle->brand_type,
                'license_plate' => $vehicle->license_plate ?: '-',
                'category' => $vehicle->isLightVehicle() ? 'Light Vehicle' : 'Heavy Equipment',
                'photo_url' => $vehicle->photo_url,
                'site_status' => $vehicle->site_status ?? 'inside_site',
                'site_status_label' => $vehicle->site_status_label,
                'location' => $vehicle->location ?: 'Belum diisi',
                'location_updated_at' => $vehicle->location_updated_at ? $vehicle->location_updated_at->translatedFormat('d M Y, H:i') : '-',
                'location_updated_by' => $vehicle->location_updated_by ?: '-',
            ],
            'count' => $histories->count(),
            'histories' => $items,
        ]);
    }

    /**
     * Helper query pencarian riwayat lokasi per unit dengan filter.
     */
    protected function getVehicleHistoryQuery(Vehicle $vehicle, Request $request): Builder
    {
        $query = VehicleLocationHistory::where('vehicle_id', $vehicle->id)
            ->with(['user', 'activity.gate'])
            ->latest('recorded_at');

        if ($request->filled('date_from')) {
            $query->whereDate('recorded_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('recorded_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('site_status') && $request->input('site_status') !== 'all') {
            $query->where('site_status', $request->input('site_status'));
        }

        if ($request->filled('source') && $request->input('source') !== 'all') {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Tampilan view khusus riwayat pergerakan posisi unit (Halaman Lega & Lengkap).
     */
    public function historyView(Request $request, int $id): View
    {
        $vehicle = Vehicle::trackableUnits()->findOrFail($id);
        $query = $this->getVehicleHistoryQuery($vehicle, $request);

        $totalHistories = (clone $query)->count();
        $insideCount = (clone $query)->where('site_status', 'inside_site')->count();
        $outsideCount = (clone $query)->where('site_status', 'outside_site')->count();
        $gateCount = (clone $query)->where('source', 'gate_crossing')->count();
        $transferCount = (clone $query)->where('source', 'transfer')->count();
        $manualCount = (clone $query)->where('source', 'manual')->count();

        $histories = $query->paginate(20)->withQueryString();

        return view('qma.unit_location_history', compact(
            'vehicle',
            'histories',
            'totalHistories',
            'insideCount',
            'outsideCount',
            'gateCount',
            'transferCount',
            'manualCount'
        ));
    }

    /**
     * Ekspor riwayat pergerakan posisi unit ke Excel (.xlsx).
     */
    public function exportHistoryExcel(Request $request, int $id): Response
    {
        $vehicle = Vehicle::trackableUnits()->findOrFail($id);
        $histories = $this->getVehicleHistoryQuery($vehicle, $request)->get();

        $metaLines = [
            'Laporan Riwayat Mutasi Posisi Kendaraan',
            "Unit: {$vehicle->code} - {$vehicle->brand_type} (" . ($vehicle->license_plate ?: ($vehicle->serial_number ?: '-')) . ")",
            'Posisi Terkini: ' . ($vehicle->location ?: 'Belum diisi') . ' (' . $vehicle->site_status_label . ')',
            'Waktu Ekspor: ' . Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Diekspor Oleh: ' . (auth()->user()?->name ?? 'Admin QMA'),
        ];

        $headers = [
            'NO' => 6,
            'WAKTU & TANGGAL' => 20,
            'STATUS AREA' => 16,
            'LOKASI TERDATA' => 26,
            'SUMBER MUTASI' => 22,
            'PETUGAS / USER' => 22,
            'KETERANGAN / AUDIT NOTE' => 50,
        ];

        $rows = [];
        foreach ($histories as $idx => $h) {
            $rows[] = [
                $idx + 1,
                $h->recorded_at ? $h->recorded_at->format('d/m/Y H:i') : '-',
                $h->site_status_label,
                $h->location ?: '-',
                $h->source_label,
                $h->user_name ?: ($h->user?->name ?? 'Sistem'),
                $h->note ?: '-',
            ];
        }

        $alignments = [
            'NO' => 'center',
            'WAKTU & TANGGAL' => 'center',
            'STATUS AREA' => 'center',
            'SUMBER MUTASI' => 'center',
        ];

        $xlsxContent = SimpleXlsxExporter::create(
            "Riwayat Posisi {$vehicle->code}",
            $headers,
            $rows,
            $metaLines,
            $alignments
        );

        $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $vehicle->code);
        $fileName = 'Riwayat_Posisi_' . $cleanCode . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($xlsxContent),
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor riwayat pergerakan posisi unit ke format PDF resmi.
     */
    public function exportHistoryPdf(Request $request, int $id): Response
    {
        $vehicle = Vehicle::trackableUnits()->findOrFail($id);
        $histories = $this->getVehicleHistoryQuery($vehicle, $request)->get();

        $metaLeft = [
            'Unit' => "{$vehicle->code} ({$vehicle->brand_type})",
            'Identitas' => $vehicle->license_plate ?: ($vehicle->serial_number ?: '-'),
            'Kategori' => $vehicle->isLightVehicle() ? 'Light Vehicle' : 'Heavy Equipment',
            'Posisi Saat Ini' => ($vehicle->location ?: 'Belum diisi') . ' (' . $vehicle->site_status_label . ')',
        ];

        $metaRight = [
            'Waktu Cetak' => Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Dicetak Oleh' => auth()->user()?->name ?? 'Admin QMA',
            'Total Riwayat' => $histories->count() . ' Mutasi',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C', 'bold' => true],
            ['title' => 'WAKTU', 'width' => 32, 'align' => 'C'],
            ['title' => 'STATUS', 'width' => 26, 'align' => 'C', 'is_status' => true],
            ['title' => 'LOKASI', 'width' => 45, 'align' => 'L', 'max_char' => 28],
            ['title' => 'SUMBER', 'width' => 36, 'align' => 'C'],
            ['title' => 'PETUGAS', 'width' => 32, 'align' => 'L', 'max_char' => 20],
            ['title' => 'KETERANGAN', 'width' => 96, 'align' => 'L', 'max_char' => 64],
        ];

        $rows = [];
        foreach ($histories as $idx => $h) {
            $rows[] = [
                $idx + 1,
                $h->recorded_at ? $h->recorded_at->format('d/m/Y H:i') : '-',
                $h->site_status_label,
                $h->location ?: '-',
                $h->source_label,
                $h->user_name ?: ($h->user?->name ?? 'Sistem'),
                $h->note ?: '-',
            ];
        }

        $title = "Riwayat Posisi Unit {$vehicle->code}";
        $subtitle = "Audit Trail Pergerakan Armada Proyek Kerinci Hydropower (350 MW)";

        $pdfBinary = QmaTablePdfExporter::generate(
            $title,
            $subtitle,
            'SISTEM QMA RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $vehicle->code);
        $fileName = 'Riwayat_Posisi_' . $cleanCode . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
        ]);
    }

    /**
     * Ekspor data monitoring posisi unit ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $units = $this->getFilteredQuery($request)->get();

        $metaLines = [
            'Laporan Monitoring & Tracking Posisi Unit (QMA)',
            'Filter Kategori: ' . ($request->filled('category') && $request->input('category') !== 'all' ? ($request->input('category') === 'light_vehicle' ? 'Light Vehicle' : 'Heavy Equipment') : 'Semua Unit'),
            'Filter Posisi Site: ' . ($request->filled('site_status') && $request->input('site_status') !== 'all' ? ($request->input('site_status') === 'inside_site' ? 'Di Dalam Site' : 'Di Luar Site') : 'Semua Posisi'),
            'Waktu Ekspor: ' . Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];

        $headers = [
            'NO' => 6,
            'KODE UNIT' => 14,
            'KATEGORI' => 16,
            'MEREK / MODEL' => 28,
            'PLAT / NO SERI' => 18,
            'STATUS SITE' => 16,
            'DETAIL LOKASI' => 26,
            'STATUS OPR' => 14,
            'UPDATE TERAKHIR' => 20,
            'PETUGAS UPDATE' => 22,
            'CATATAN' => 32,
        ];

        $rows = [];
        foreach ($units as $idx => $u) {
            $catLabel = $u->isLightVehicle() ? 'Light Vehicle' : 'Heavy Equipment';
            $plateOrSn = $u->license_plate ?: ($u->serial_number ?: '-');
            $updatedAt = $u->location_updated_at ? $u->location_updated_at->format('d/m/Y H:i') : ($u->updated_at ? $u->updated_at->format('d/m/Y H:i') : '-');

            $rows[] = [
                $idx + 1,
                $u->code,
                $catLabel,
                $u->brand_type ?: '-',
                $plateOrSn,
                $u->site_status_label,
                $u->location ?: '-',
                $u->status ?: 'Standby',
                $updatedAt,
                $u->location_updated_by ?: '-',
                $u->location_note ?: '-',
            ];
        }

        $alignments = [
            'NO' => 'center',
            'KODE UNIT' => 'center',
            'KATEGORI' => 'center',
            'PLAT / NO SERI' => 'center',
            'STATUS SITE' => 'center',
            'STATUS OPR' => 'center',
            'UPDATE TERAKHIR' => 'center',
        ];

        $xlsxContent = SimpleXlsxExporter::create(
            'DATA MONITORING POSISI UNIT KERINCI HYDROPOWER',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        $fileName = 'Manajemen_Posisi_Unit_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($xlsxContent),
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor data monitoring posisi unit ke format PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $units = $this->getFilteredQuery($request)->get();

        $metaLeft = [
            'Sub-Sistem' => 'Quality Management Asset (QMA)',
            'Kategori' => $request->filled('category') && $request->input('category') !== 'all' ? ($request->input('category') === 'light_vehicle' ? 'Light Vehicle' : 'Heavy Equipment') : 'Semua Unit',
            'Status Posisi' => $request->filled('site_status') && $request->input('site_status') !== 'all' ? ($request->input('site_status') === 'inside_site' ? 'Di Dalam Site' : 'Di Luar Site') : 'Semua Posisi',
        ];

        $metaRight = [
            'Waktu Cetak' => Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Dicetak Oleh' => auth()->user()?->name ?? 'Admin QMA',
            'Total Armada' => $units->count() . ' Unit Terdata',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C', 'bold' => true],
            ['title' => 'KODE', 'width' => 20, 'align' => 'C', 'bold' => true],
            ['title' => 'KATEGORI', 'width' => 26, 'align' => 'C'],
            ['title' => 'MEREK / TIPE', 'width' => 50, 'align' => 'L', 'max_char' => 32],
            ['title' => 'PLAT / SERI', 'width' => 28, 'align' => 'C'],
            ['title' => 'POSISI SITE', 'width' => 30, 'align' => 'C', 'is_status' => true],
            ['title' => 'DETAIL LOKASI', 'width' => 45, 'align' => 'L', 'max_char' => 28],
            ['title' => 'STATUS', 'width' => 26, 'align' => 'C', 'is_status' => true],
            ['title' => 'UPDATE', 'width' => 32, 'align' => 'C'],
        ];

        $rows = [];
        foreach ($units as $idx => $u) {
            $catLabel = $u->isLightVehicle() ? 'Light Vehicle' : 'Heavy Equipment';
            $plateOrSn = $u->license_plate ?: ($u->serial_number ?: '-');
            $updatedAt = $u->location_updated_at ? $u->location_updated_at->format('d/m/Y H:i') : ($u->updated_at ? $u->updated_at->format('d/m/Y H:i') : '-');

            $rows[] = [
                $idx + 1,
                $u->code,
                $catLabel,
                $u->brand_type ?: '-',
                $plateOrSn,
                $u->site_status_label,
                $u->location ?: '-',
                $u->status ?: 'Standby',
                $updatedAt,
            ];
        }

        $subtitle = 'Monitoring Posisi Armada LV & Heavy Equipment Proyek Kerinci Hydropower';
        $pdfBinary = QmaTablePdfExporter::generate(
            'Laporan Monitoring Posisi Unit',
            $subtitle,
            'SISTEM QMA RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Posisi_Unit_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
