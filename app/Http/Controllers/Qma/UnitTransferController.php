<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\UnitTransfer;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Services\QmaTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UnitTransferController extends Controller
{
    /**
     * Dapatkan daftar saran lokasi (dari UnitManagementController).
     */
    public static function getLocationSuggestions(): array
    {
        return UnitManagementController::getAllLocationSuggestions();
    }

    /**
     * Helper query filter transfer unit.
     */
    private function getFilteredQuery(Request $request): Builder
    {
        $query = UnitTransfer::with([
            'vehicle.parent',
            'transporterVehicle',
            'transporterDriver',
            'user',
        ]);

        // Filter Alat Berat
        if ($request->filled('vehicle_id') && $request->input('vehicle_id') !== 'all') {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        // Filter Area Tujuan (inside_site / outside_site)
        if ($request->filled('to_site_status') && $request->input('to_site_status') !== 'all') {
            $query->where('to_site_status', $request->input('to_site_status'));
        }

        // Filter Tanggal Mulai
        if ($request->filled('start_date')) {
            $query->whereDate('transfer_date', '>=', $request->input('start_date'));
        }

        // Filter Tanggal Akhir
        if ($request->filled('end_date')) {
            $query->whereDate('transfer_date', '<=', $request->input('end_date'));
        }

        // Filter Kata Kunci Pencarian (No Transfer, No Surat, Judul, Lokasi, Driver, Pelapor, Kode Unit)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('transfer_no', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('document_title', 'like', "%{$search}%")
                  ->orWhere('to_location', 'like', "%{$search}%")
                  ->orWhere('from_location', 'like', "%{$search}%")
                  ->orWhere('reported_by', 'like', "%{$search}%")
                  ->orWhere('transporter_driver_name', 'like', "%{$search}%")
                  ->orWhere('transporter_unit_name', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function (Builder $vq) use ($search) {
                      $vq->where('code', 'like', "%{$search}%")
                         ->orWhere('brand_type', 'like', "%{$search}%")
                         ->orWhere('serial_number', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('transfer_date', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Tampilkan riwayat transfer unit alat berat.
     */
    public function index(Request $request): View
    {
        $query = $this->getFilteredQuery($request);
        $transfers = $query->paginate(15)->withQueryString();

        // KPI Ringkasan
        $totalTransfers = UnitTransfer::count();
        $thisMonthCount = UnitTransfer::whereYear('transfer_date', Carbon::now()->year)
            ->whereMonth('transfer_date', Carbon::now()->month)
            ->count();
        $insideCount = UnitTransfer::where('to_site_status', 'inside_site')->count();
        $outsideCount = UnitTransfer::where('to_site_status', 'outside_site')->count();

        // Daftar alat berat untuk dropdown filter
        $heavyUnits = Vehicle::trackableUnits()
            ->where('category', 'unit_support')
            ->orderBy('code')
            ->get(['id', 'code', 'brand_type', 'serial_number']);

        return view('qma.unit_transfers_index', compact(
            'transfers',
            'totalTransfers',
            'thisMonthCount',
            'insideCount',
            'outsideCount',
            'heavyUnits'
        ));
    }

    /**
     * Tampilkan formulir pembuatan transfer unit alat berat baru.
     */
    public function create(Request $request): View
    {
        $selectedVehicleId = $request->filled('vehicle_id') ? (int) $request->input('vehicle_id') : null;

        // 1. Daftar unit alat berat yang bisa dipindahkan
        $heavyUnits = Vehicle::trackableUnits()
            ->where('category', 'unit_support')
            ->with(['parent'])
            ->orderBy('code')
            ->get();

        // Cari lokasi awal jika vehicle_id disertakan
        $selectedOriginLocation = null;
        if ($selectedVehicleId) {
            $selectedVeh = $heavyUnits->firstWhere('id', $selectedVehicleId);
            if ($selectedVeh) {
                $selectedOriginLocation = $selectedVeh->location ?: 'Site Kerinci';
            }
        }

        // Daftar lokasi awal unik yang saat ini memiliki unit alat berat beserta jumlah unitnya
        $originLocations = $heavyUnits->groupBy(fn($u) => $u->location ?: 'Site Kerinci')
            ->map(fn($group) => $group->count())
            ->sortKeys();

        // 2. Daftar unit kendaraan kita untuk armada pengangkut (Truk, Flatbed, Lowbed, Towing, LV, dsb.)
        $transporterUnits = Vehicle::trackableUnits()
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        // 3. Daftar pengemudi / operator
        $drivers = Employee::where('is_operator', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nik', 'position']);

        // 4. Rekomendasi lokasi site
        $locationSuggestions = self::getLocationSuggestions();

        // 5. Default nomor surat template
        $currentMonthRoman = self::getRomanMonth(Carbon::now()->month);
        $defaultDocNumber = sprintf('.../BAST-AB/QMA-KMH/%s/%s', $currentMonthRoman, Carbon::now()->year);

        return view('qma.unit_transfers_create', compact(
            'heavyUnits',
            'originLocations',
            'selectedOriginLocation',
            'transporterUnits',
            'drivers',
            'locationSuggestions',
            'defaultDocNumber',
            'selectedVehicleId'
        ));
    }

    /**
     * Simpan transaksi transfer unit alat berat dan sinkronkan posisi serta riwayat audit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'transfer_date' => ['required', 'date'],
            'to_location' => ['required', 'string', 'max:255'],
            'to_site_status' => ['required', 'in:inside_site,outside_site'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reported_by' => ['required', 'string', 'max:255'],
            'transporter_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'transporter_unit_name' => ['nullable', 'string', 'max:255'],
            'transporter_driver_id' => ['nullable', 'integer', 'exists:employees,id'],
            'transporter_driver_name' => ['nullable', 'string', 'max:255'],
            'transporter_description' => ['nullable', 'string', 'max:2000'],
            'update_transporter_location' => ['nullable', 'boolean'],
            'document_number' => ['required', 'string', 'max:255'],
            'document_title' => ['required', 'string', 'max:255'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'vehicle_id.required' => 'Pilih unit alat berat yang dipindahkan.',
            'vehicle_id.exists' => 'Unit alat berat tidak ditemukan.',
            'transfer_date.required' => 'Tanggal pemindahan wajib diisi.',
            'to_location.required' => 'Lokasi tujuan pemindahan wajib diisi.',
            'to_site_status.required' => 'Pilih area tujuan (Di Dalam atau Di Luar Site).',
            'reported_by.required' => 'Nama pelapor / penanggung jawab wajib diisi.',
            'document_number.required' => 'Nomor surat / berita acara wajib diisi.',
            'document_title.required' => 'Judul surat / perihal berita acara wajib diisi.',
            'document_file.max' => 'Ukuran berkas surat maksimal 5 MB.',
        ]);

        $filePath = null;
        if ($request->hasFile('document_file')) {
            $filePath = $request->file('document_file')->store('transfers/documents', 'public');
        }

        $updateTransporter = $request->boolean('update_transporter_location', true);

        $transfer = DB::transaction(function () use ($validated, $filePath, $updateTransporter) {
            $vehicle = Vehicle::trackableUnits()->findOrFail($validated['vehicle_id']);

            // Lokasi dan status site awal sebelum dipindahkan
            $fromLocation = $vehicle->location ?: ($validated['from_location'] ?? 'Site Kerinci');
            $fromSiteStatus = $vehicle->site_status ?: 'inside_site';

            // Nama armada pengangkut
            $transporterUnitName = !empty($validated['transporter_unit_name']) ? trim($validated['transporter_unit_name']) : null;
            if (empty($transporterUnitName) && !empty($validated['transporter_vehicle_id'])) {
                $transporterVeh = Vehicle::find($validated['transporter_vehicle_id']);
                if ($transporterVeh) {
                    $plate = $transporterVeh->license_plate ? " ({$transporterVeh->license_plate})" : '';
                    $transporterUnitName = "{$transporterVeh->code} - {$transporterVeh->brand_type}{$plate}";
                }
            }

            // Nama driver pengangkut
            $transporterDriverName = !empty($validated['transporter_driver_name']) ? trim($validated['transporter_driver_name']) : null;
            if (empty($transporterDriverName) && !empty($validated['transporter_driver_id'])) {
                $emp = Employee::find($validated['transporter_driver_id']);
                if ($emp) {
                    $transporterDriverName = $emp->name;
                }
            }

            $transferDate = Carbon::parse($validated['transfer_date']);
            $transferNo = UnitTransfer::generateTransferNo($transferDate);
            $toLocation = trim($validated['to_location']);
            $reportedBy = trim($validated['reported_by']);

            // 1. Simpan Data Transfer Unit
            $transfer = UnitTransfer::create([
                'transfer_no' => $transferNo,
                'vehicle_id' => $vehicle->id,
                'transfer_date' => $transferDate->format('Y-m-d'),
                'from_location' => $fromLocation,
                'from_site_status' => $fromSiteStatus,
                'to_location' => $toLocation,
                'to_site_status' => $validated['to_site_status'],
                'description' => !empty($validated['description']) ? trim($validated['description']) : null,
                'reported_by' => $reportedBy,
                'user_id' => auth()->id(),
                'transporter_vehicle_id' => $validated['transporter_vehicle_id'] ?? null,
                'transporter_unit_name' => $transporterUnitName,
                'transporter_driver_id' => $validated['transporter_driver_id'] ?? null,
                'transporter_driver_name' => $transporterDriverName,
                'transporter_description' => !empty($validated['transporter_description']) ? trim($validated['transporter_description']) : null,
                'document_number' => trim($validated['document_number']),
                'document_title' => trim($validated['document_title']),
                'document_file_path' => $filePath,
                'status' => 'completed',
            ]);

            // 2. Perbarui posisi master unit alat berat
            $vehicle->update([
                'location' => $toLocation,
                'site_status' => $validated['to_site_status'],
                'location_updated_at' => Carbon::now(),
                'location_updated_by' => $reportedBy,
                'location_note' => "Transfer Unit (BAST: {$validated['document_number']}) ke {$toLocation}",
            ]);

            // 3. Catat di audit log riwayat pergerakan unit (VehicleLocationHistory)
            $transporterSummary = $transporterUnitName ? " Pengangkut: {$transporterUnitName}" : "";
            if ($transporterDriverName) {
                $transporterSummary .= " (Driver: {$transporterDriverName})";
            }
            $historyNote = "Transfer BAST {$validated['document_number']}: {$validated['document_title']}.{$transporterSummary}";
            if (!empty($validated['description'])) {
                $historyNote .= " | " . trim($validated['description']);
            }

            VehicleLocationHistory::create([
                'vehicle_id' => $vehicle->id,
                'site_status' => $validated['to_site_status'],
                'location' => $toLocation,
                'note' => $historyNote,
                'source' => 'transfer',
                'user_id' => auth()->id(),
                'user_name' => $reportedBy,
                'recorded_at' => Carbon::now(),
            ]);

            // 4. Jika opsi perbarui posisi truk pengangkut aktif
            if ($updateTransporter && !empty($validated['transporter_vehicle_id'])) {
                $transporterVeh = Vehicle::find($validated['transporter_vehicle_id']);
                if ($transporterVeh && $transporterVeh->id !== $vehicle->id) {
                    $transporterVeh->update([
                        'location' => $toLocation,
                        'site_status' => $validated['to_site_status'],
                        'location_updated_at' => Carbon::now(),
                        'location_updated_by' => $reportedBy,
                        'location_note' => "Armada Pengangkut BAST {$validated['document_number']} untuk {$vehicle->code} ke {$toLocation}",
                    ]);

                    VehicleLocationHistory::create([
                        'vehicle_id' => $transporterVeh->id,
                        'site_status' => $validated['to_site_status'],
                        'location' => $toLocation,
                        'note' => "Armada Pengangkut: Mengantar {$vehicle->code} ({$vehicle->brand_type}) via BAST {$validated['document_number']} ke {$toLocation}",
                        'source' => 'transfer',
                        'user_id' => auth()->id(),
                        'user_name' => $reportedBy,
                        'recorded_at' => Carbon::now(),
                    ]);
                }
            }

            return $transfer;
        });

        return redirect()->route('qma.unit_transfers.show', $transfer->id)
            ->with('success', "Pemindahan unit {$transfer->vehicle->code} berhasil diproses. Berita Acara {$transfer->document_number} telah diterbitkan.");
    }

    /**
     * Tampilkan detail berita acara serah terima & pemindahan unit.
     */
    public function show(int $id): View
    {
        $transfer = UnitTransfer::with([
            'vehicle.parent',
            'transporterVehicle',
            'transporterDriver',
            'user',
        ])->findOrFail($id);

        return view('qma.unit_transfers_show', compact('transfer'));
    }

    /**
     * Hapus data transfer unit (jika diperlukan pembatalan oleh Admin).
     */
    public function destroy(int $id): RedirectResponse
    {
        $transfer = UnitTransfer::findOrFail($id);

        // Hapus file dokumen jika tersimpan
        if ($transfer->document_file_path && Storage::disk('public')->exists($transfer->document_file_path)) {
            Storage::disk('public')->delete($transfer->document_file_path);
        }

        $docNo = $transfer->document_number;
        $unitCode = $transfer->vehicle?->code ?? 'Unit';
        $transfer->delete();

        return redirect()->route('qma.unit_transfers.index')
            ->with('success', "Data transfer unit {$unitCode} dengan Berita Acara {$docNo} berhasil dihapus.");
    }

    /**
     * Ekspor data transfer unit ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $transfers = $this->getFilteredQuery($request)->get();

        $metaLines = [
            'Laporan Riwayat Transfer Unit Alat Berat (QMA)',
            'Waktu Ekspor: ' . Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Total Data: ' . $transfers->count() . ' pemindahan unit',
        ];

        $headers = [
            'NO' => 6,
            'NO TRANSFER' => 16,
            'TANGGAL' => 14,
            'KODE UNIT' => 14,
            'ALAT BERAT' => 26,
            'LOKASI ASAL' => 22,
            'LOKASI TUJUAN' => 22,
            'AREA TUJUAN' => 16,
            'PENGANGKUT' => 26,
            'DRIVER' => 20,
            'NO BERITA ACARA' => 26,
            'JUDUL SURAT' => 30,
            'PELAPOR' => 20,
        ];

        $rows = [];
        foreach ($transfers as $idx => $t) {
            $rows[] = [
                $idx + 1,
                $t->transfer_no,
                $t->transfer_date ? $t->transfer_date->format('d/m/Y') : '-',
                $t->vehicle?->code ?? '-',
                $t->vehicle?->brand_type ?? '-',
                $t->from_location,
                $t->to_location,
                $t->to_site_status_label,
                $t->transporter_unit_name ?: ($t->transporterVehicle?->code ?? '-'),
                $t->transporter_driver_name ?: ($t->transporterDriver?->name ?? '-'),
                $t->document_number,
                $t->document_title,
                $t->reported_by,
            ];
        }

        $alignments = [
            'NO' => 'center',
            'NO TRANSFER' => 'center',
            'TANGGAL' => 'center',
            'KODE UNIT' => 'center',
            'AREA TUJUAN' => 'center',
        ];

        $xlsxContent = SimpleXlsxExporter::create(
            'LAPORAN RIWAYAT TRANSFER UNIT ALAT BERAT - PT KMH',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        $filename = 'Transfer_Unit_Alat_Berat_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor data transfer unit ke format PDF landscape.
     */
    public function exportPdf(Request $request): Response
    {
        $transfers = $this->getFilteredQuery($request)->get();

        $metaLeft = [
            'Sub-Sistem' => 'Quality Management Asset (QMA)',
            'Modul' => 'Transfer Unit Alat Berat (BAST)',
            'Filter Area' => $request->filled('to_site_status') && $request->input('to_site_status') !== 'all' ? ($request->input('to_site_status') === 'inside_site' ? 'Di Dalam Site' : 'Di Luar Site') : 'Semua Area',
        ];

        $metaRight = [
            'Waktu Cetak' => Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Dicetak Oleh' => auth()->user()?->name ?? 'Admin QMA',
            'Total Pemindahan' => $transfers->count() . ' Dokumen',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C', 'bold' => true],
            ['title' => 'TANGGAL', 'width' => 22, 'align' => 'C'],
            ['title' => 'NO BAST', 'width' => 45, 'align' => 'L', 'max_char' => 28],
            ['title' => 'ALAT BERAT', 'width' => 45, 'align' => 'L', 'max_char' => 28],
            ['title' => 'LOKASI ASAL', 'width' => 35, 'align' => 'L', 'max_char' => 22],
            ['title' => 'LOKASI TUJUAN', 'width' => 35, 'align' => 'L', 'max_char' => 22],
            ['title' => 'AREA', 'width' => 26, 'align' => 'C', 'is_status' => true],
            ['title' => 'PENGANGKUT', 'width' => 35, 'align' => 'L', 'max_char' => 22],
            ['title' => 'PELAPOR', 'width' => 24, 'align' => 'L', 'max_char' => 16],
        ];

        $rows = [];
        foreach ($transfers as $idx => $t) {
            $unitDesc = ($t->vehicle?->code ?? '-') . ' - ' . ($t->vehicle?->brand_type ?? '');
            $transporter = ($t->transporter_unit_name ?: '-') . ($t->transporter_driver_name ? ' (' . $t->transporter_driver_name . ')' : '');

            $rows[] = [
                $idx + 1,
                $t->transfer_date ? $t->transfer_date->format('d/m/Y') : '-',
                $t->document_number,
                $unitDesc,
                $t->from_location,
                $t->to_location,
                $t->to_site_status_label,
                $transporter,
                $t->reported_by,
            ];
        }

        $subtitle = 'Laporan Riwayat Pemindahan & Relokasi Alat Berat Proyek Kerinci Hydropower';
        $pdfBinary = QmaTablePdfExporter::generate(
            'Laporan Transfer Unit Alat Berat',
            $subtitle,
            'SISTEM QMA RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $filename = 'Transfer_Unit_Alat_Berat_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Konversi angka bulan ke format Romawi.
     */
    private static function getRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? 'I';
    }
}
