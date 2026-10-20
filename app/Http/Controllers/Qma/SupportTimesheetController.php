<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\UnitSupportTimesheet;
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
use Illuminate\View\View;

class SupportTimesheetController extends Controller
{
    public const PRESET_ACTIVITIES = [
        'Galian Material',
        'Timbunan / Compacting',
        'Loading Material',
        'Hauling Material',
        'Lifting / Erection',
        'Drilling / Pengeboran',
        'Pembersihan / Land Clearing',
        'Perbaikan Jalan Proyek',
        'Mobilisasi Alat / Material',
        'Standby / Siaga',
        'Lainnya',
    ];

    public const SHIFTS = [
        'Shift 1 (Siang)',
        'Shift 2 (Malam)',
        'Non-Shift',
    ];

    /**
     * Helper query builder untuk memfilter timesheet unit support.
     */
    private function getFilteredQuery(Request $request): Builder
    {
        $query = UnitSupportTimesheet::with(['vehicle', 'operator', 'createdByUser'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->input('start_date'));
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->input('end_date'));
        }

        // Filter Unit
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        // Filter Operator
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->input('operator_id'));
        }

        // Filter Shift
        if ($request->filled('shift')) {
            $query->where('shift', $request->input('shift'));
        }

        // Filter Status Operasi
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Pencarian Kata Kunci
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('timesheet_no', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('activity_type', 'like', "%{$search}%")
                  ->orWhere('task_description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function (Builder $vq) use ($search) {
                      $vq->where('code', 'like', "%{$search}%")
                         ->orWhere('brand_type', 'like', "%{$search}%")
                         ->orWhere('license_plate', 'like', "%{$search}%")
                         ->orWhere('serial_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('operator', function (Builder $oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('nik', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Tampilkan halaman utama Timesheet Unit Support.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        // Jika user belum mengisi filter tanggal dan ingin semua, bisa dikosongkan.
        // Tapi default awal memuat hari ini agar cepat dan relevan.
        $query = $this->getFilteredQuery($request);
        $timesheets = $query->paginate(15)->withQueryString();

        // KPI Ringkasan Data Terfilter
        $statsQuery = clone $this->getFilteredQuery($request);
        $totalEntries = (clone $statsQuery)->count();
        $totalOperatingHours = (clone $statsQuery)->sum('operating_hours');
        $totalFuelLiters = (clone $statsQuery)->sum('fuel_liters');
        $activeUnitsCount = (clone $statsQuery)->distinct('vehicle_id')->count('vehicle_id');
        $breakdownCount = (clone $statsQuery)->where('status', 'Breakdown')->count();

        // Data Master untuk Form & Filter
        $unitSupports = Vehicle::unitSupports()
            ->where(function (Builder $q) {
                $q->whereNotNull('parent_id')
                  ->orWhereDoesntHave('units');
            })
            ->orderBy('code')
            ->get();

        $operators = Employee::orderBy('name')
            ->get(['id', 'nik', 'name', 'position', 'is_operator']);

        $shifts = self::SHIFTS;
        $activityTypes = self::PRESET_ACTIVITIES;
        $locationSuggestions = UnitManagementController::getAllLocationSuggestions();

        return view('qma.support_timesheet', compact(
            'timesheets',
            'startDate',
            'endDate',
            'totalEntries',
            'totalOperatingHours',
            'totalFuelLiters',
            'activeUnitsCount',
            'breakdownCount',
            'unitSupports',
            'operators',
            'shifts',
            'activityTypes',
            'locationSuggestions'
        ));
    }

    /**
     * Simpan entri timesheet baru yang diinput oleh Admin QMA.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'operator_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'shift' => ['required', 'string', 'max:30'],
            'meter_type' => ['required', 'in:HM,KM'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'start_meter' => ['required', 'numeric', 'min:0'],
            'end_meter' => ['required', 'numeric', 'gte:start_meter'],
            'standby_hours' => ['nullable', 'numeric', 'min:0'],
            'breakdown_hours' => ['nullable', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', 'string', 'max:100'],
            'task_description' => ['nullable', 'string', 'max:1000'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Operation,Standby,Breakdown,Maintenance'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'vehicle_id.required' => 'Pilih unit support yang beroperasi.',
            'operator_id.required' => 'Pilih pengemudi / operator unit.',
            'date.required' => 'Tanggal operasional wajib diisi.',
            'end_meter.gte' => 'Angka meter akhir (End Meter) tidak boleh lebih kecil dari meter awal.',
            'location.required' => 'Lokasi kerja wajib ditentukan.',
            'activity_type.required' => 'Jenis aktivitas / pekerjaan wajib dipilih.',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $operator = Employee::findOrFail($validated['operator_id']);

        $startMeter = (float) $validated['start_meter'];
        $endMeter = (float) $validated['end_meter'];
        $operatingHours = max(0, $endMeter - $startMeter);

        $timesheetNo = UnitSupportTimesheet::generateTimesheetNo(Carbon::parse($validated['date']));
        $updaterName = auth()->user()?->name ?? 'Admin QMA';

        DB::transaction(function () use (
            $validated,
            $vehicle,
            $operator,
            $timesheetNo,
            $operatingHours,
            $endMeter,
            $updaterName
        ) {
            // 1. Simpan Data Timesheet Unit Support
            $timesheet = UnitSupportTimesheet::create([
                'timesheet_no' => $timesheetNo,
                'vehicle_id' => $vehicle->id,
                'operator_id' => $operator->id,
                'date' => $validated['date'],
                'shift' => $validated['shift'],
                'meter_type' => $validated['meter_type'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'start_meter' => $validated['start_meter'],
                'end_meter' => $validated['end_meter'],
                'operating_hours' => $operatingHours,
                'standby_hours' => $validated['standby_hours'] ?? 0,
                'breakdown_hours' => $validated['breakdown_hours'] ?? 0,
                'location' => trim($validated['location']),
                'activity_type' => trim($validated['activity_type']),
                'task_description' => $validated['task_description'] ?? null,
                'fuel_liters' => $validated['fuel_liters'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);

            // 2. Sinkronisasi Master Kendaraan (Odometer & Posisi di Manajemen Unit)
            $updateData = [
                'status' => $validated['status'],
                'site_status' => 'inside_site',
                'location' => trim($validated['location']),
                'location_updated_at' => Carbon::now(),
                'location_updated_by' => $updaterName,
                'location_note' => "Timesheet {$timesheetNo} ({$validated['activity_type']}) - Operator: {$operator->name}",
            ];

            if ($endMeter > (float) $vehicle->current_km) {
                $updateData['current_km'] = $endMeter;
            }

            $vehicle->update($updateData);

            // 3. Catat Riwayat Pergerakan Posisi Unit (Audit Trail)
            VehicleLocationHistory::create([
                'vehicle_id' => $vehicle->id,
                'site_status' => 'inside_site',
                'location' => trim($validated['location']),
                'note' => "Timesheet {$timesheetNo} ({$validated['activity_type']}) - Operator: {$operator->name}",
                'source' => 'manual',
                'user_id' => auth()->id(),
                'user_name' => $updaterName,
                'recorded_at' => Carbon::now(),
            ]);
        });

        return back()->with('success', "Timesheet {$timesheetNo} untuk unit {$vehicle->code} berhasil disimpan.");
    }

    /**
     * Perbarui entri timesheet unit support.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $timesheet = UnitSupportTimesheet::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'operator_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'shift' => ['required', 'string', 'max:30'],
            'meter_type' => ['required', 'in:HM,KM'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'start_meter' => ['required', 'numeric', 'min:0'],
            'end_meter' => ['required', 'numeric', 'gte:start_meter'],
            'standby_hours' => ['nullable', 'numeric', 'min:0'],
            'breakdown_hours' => ['nullable', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:255'],
            'activity_type' => ['required', 'string', 'max:100'],
            'task_description' => ['nullable', 'string', 'max:1000'],
            'fuel_liters' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:Operation,Standby,Breakdown,Maintenance'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startMeter = (float) $validated['start_meter'];
        $endMeter = (float) $validated['end_meter'];
        $operatingHours = max(0, $endMeter - $startMeter);

        $timesheet->update([
            'vehicle_id' => $validated['vehicle_id'],
            'operator_id' => $validated['operator_id'],
            'date' => $validated['date'],
            'shift' => $validated['shift'],
            'meter_type' => $validated['meter_type'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'start_meter' => $validated['start_meter'],
            'end_meter' => $validated['end_meter'],
            'operating_hours' => $operatingHours,
            'standby_hours' => $validated['standby_hours'] ?? 0,
            'breakdown_hours' => $validated['breakdown_hours'] ?? 0,
            'location' => trim($validated['location']),
            'activity_type' => trim($validated['activity_type']),
            'task_description' => $validated['task_description'] ?? null,
            'fuel_liters' => $validated['fuel_liters'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "Timesheet {$timesheet->timesheet_no} berhasil diperbarui.");
    }

    /**
     * Hapus entri timesheet unit support.
     */
    public function destroy(int $id): RedirectResponse
    {
        $timesheet = UnitSupportTimesheet::findOrFail($id);
        $no = $timesheet->timesheet_no;
        $timesheet->delete();

        return back()->with('success', "Timesheet {$no} berhasil dihapus.");
    }

    /**
     * Ekspor data timesheet unit support ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $timesheets = $this->getFilteredQuery($request)->get();

        $metaLines = [
            'Laporan Timesheet Operasional Unit Support (Alat Berat)',
            'Periode: ' . ($request->filled('start_date') ? Carbon::parse($request->input('start_date'))->format('d/m/Y') : 'Semua') . ' s/d ' . ($request->filled('end_date') ? Carbon::parse($request->input('end_date'))->format('d/m/Y') : 'Semua'),
            'Waktu Ekspor: ' . Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Total Record: ' . $timesheets->count() . ' Entri Timesheet',
        ];

        $headers = [
            'NO' => 6,
            'NO TIMESHEET' => 20,
            'TANGGAL' => 14,
            'SHIFT' => 18,
            'KODE UNIT' => 14,
            'MEREK / MODEL' => 26,
            'OPERATOR' => 24,
            'NIK' => 16,
            'SATUAN' => 10,
            'START' => 14,
            'END' => 14,
            'JAM OPERASI' => 14,
            'STANDBY' => 12,
            'BREAKDOWN' => 12,
            'LOKASI KERJA' => 22,
            'AKTIVITAS' => 24,
            'SOLAR (L)' => 12,
            'STATUS' => 14,
            'CATATAN' => 30,
        ];

        $rows = [];
        foreach ($timesheets as $idx => $ts) {
            $rows[] = [
                $idx + 1,
                $ts->timesheet_no,
                $ts->date ? $ts->date->format('d/m/Y') : '-',
                $ts->shift,
                $ts->vehicle?->code ?: '-',
                $ts->vehicle?->brand_type ?: '-',
                $ts->operator?->name ?: '-',
                $ts->operator?->nik ?: '-',
                $ts->meter_type,
                number_format($ts->start_meter, 2, ',', '.'),
                number_format($ts->end_meter, 2, ',', '.'),
                number_format($ts->operating_hours, 2, ',', '.'),
                number_format($ts->standby_hours, 2, ',', '.'),
                number_format($ts->breakdown_hours, 2, ',', '.'),
                $ts->location,
                $ts->activity_type,
                $ts->fuel_liters ? number_format($ts->fuel_liters, 2, ',', '.') : '-',
                $ts->status,
                $ts->notes ?: ($ts->task_description ?: '-'),
            ];
        }

        $alignments = [
            'NO' => 'center',
            'NO TIMESHEET' => 'center',
            'TANGGAL' => 'center',
            'SHIFT' => 'center',
            'KODE UNIT' => 'center',
            'NIK' => 'center',
            'SATUAN' => 'center',
            'START' => 'right',
            'END' => 'right',
            'JAM OPERASI' => 'right',
            'STANDBY' => 'right',
            'BREAKDOWN' => 'right',
            'SOLAR (L)' => 'right',
            'STATUS' => 'center',
        ];

        $xlsxContent = SimpleXlsxExporter::create(
            'LAPORAN TIMESHEET OPERASIONAL UNIT SUPPORT',
            $metaLines,
            $headers,
            $rows,
            $alignments
        );

        $fileName = 'Timesheet_Unit_Support_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($xlsxContent),
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor data timesheet unit support ke format PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $timesheets = $this->getFilteredQuery($request)->get();

        $metaLeft = [
            'Sub-Sistem' => 'Quality Management Asset (QMA)',
            'Modul' => 'Timesheet Operasional Unit Support',
            'Periode' => ($request->filled('start_date') ? Carbon::parse($request->input('start_date'))->format('d/m/Y') : 'Semua') . ' s/d ' . ($request->filled('end_date') ? Carbon::parse($request->input('end_date'))->format('d/m/Y') : 'Semua'),
        ];

        $metaRight = [
            'Waktu Cetak' => Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB',
            'Dicetak Oleh' => auth()->user()?->name ?? 'Admin QMA',
            'Total Record' => $timesheets->count() . ' Entri Timesheet',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 10, 'align' => 'C', 'bold' => true],
            ['title' => 'TANGGAL', 'width' => 20, 'align' => 'C'],
            ['title' => 'KODE', 'width' => 18, 'align' => 'C', 'bold' => true],
            ['title' => 'TIPE ALAT', 'width' => 38, 'align' => 'L', 'max_char' => 25],
            ['title' => 'OPERATOR', 'width' => 35, 'align' => 'L', 'max_char' => 22],
            ['title' => 'SHIFT', 'width' => 25, 'align' => 'C'],
            ['title' => 'START', 'width' => 18, 'align' => 'R'],
            ['title' => 'END', 'width' => 18, 'align' => 'R'],
            ['title' => 'JAM (HM)', 'width' => 18, 'align' => 'R', 'bold' => true],
            ['title' => 'LOKASI KERJA', 'width' => 40, 'align' => 'L', 'max_char' => 26],
            ['title' => 'STATUS', 'width' => 25, 'align' => 'C', 'is_status' => true],
        ];

        $rows = [];
        foreach ($timesheets as $idx => $ts) {
            $rows[] = [
                $idx + 1,
                $ts->date ? $ts->date->format('d/m/Y') : '-',
                $ts->vehicle?->code ?: '-',
                $ts->vehicle?->brand_type ?: '-',
                $ts->operator?->name ?: '-',
                $ts->shift,
                number_format($ts->start_meter, 1, ',', '.'),
                number_format($ts->end_meter, 1, ',', '.'),
                number_format($ts->operating_hours, 1, ',', '.') . ' ' . $ts->meter_type,
                $ts->location,
                $ts->status,
            ];
        }

        $subtitle = 'Monitoring Jam Operasi Alat Berat & Unit Support Proyek Kerinci Hydropower';
        $pdfBinary = QmaTablePdfExporter::generate(
            'Laporan Timesheet Unit Support',
            $subtitle,
            'SISTEM QMA RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'Laporan_Timesheet_Unit_Support_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfBinary),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
