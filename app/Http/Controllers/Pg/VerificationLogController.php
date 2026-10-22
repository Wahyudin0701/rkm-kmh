<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\VehicleActivity;
use App\Services\PgTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VerificationLogController extends Controller
{
    /**
     * Bangun query log verifikasi gerbang berdasarkan filter request.
     */
    protected function getVerificationLogsQuery(Request $request)
    {
        // Otomatis batalkan antrean pending yang melewati batas 5 jam
        VehicleActivity::expireOverduePendingActivities();

        $query = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->orderByRaw('COALESCE(validated_at, recorded_at) DESC');

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

        // Filter Regu Satpam (1, 2, 3)
        if ($request->filled('team')) {
            $team = (int) $request->input('team');
            $query->whereHas('securityGuard', function ($sq) use ($team) {
                $sq->where('team', $team);
            });
        }

        // Filter Petugas Satpam Spesifik
        if ($request->filled('security_guard_id')) {
            $query->where('security_guard_id', $request->input('security_guard_id'));
        }

        // Filter Pos Gerbang
        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->input('gate_id'));
        }

        // Filter Arah Gerbang (in / out)
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        // Filter Status Verifikasi (approved, rejected, pending)
        if ($request->filled('status')) {
            $query->where('validation_status', $request->input('status'));
        }

        // Filter Kata Kunci Pencarian (No Transaksi, Petugas Satpam, Badge, Driver, NIK, Plat, Kode LV)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('rejection_note', 'like', "%{$search}%")
                    ->orWhereHas('securityGuard', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('badge_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('code', 'like', "%{$search}%")
                            ->orWhere('brand_type', 'like', "%{$search}%")
                            ->orWhere('license_plate', 'like', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Tampilkan rekapitulasi audit dan log riwayat verifikasi gerbang oleh petugas Satpam.
     */
    public function index(Request $request): View
    {
        $activities = $this->getVerificationLogsQuery($request)->paginate(20)->withQueryString();
        $gates = Gate::where('is_active', true)->orderBy('name')->get();
        $guards = SecurityGuard::orderBy('name')->get();

        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);

        return view('pg.verification_logs', compact('activities', 'gates', 'guards', 'startDate', 'endDate'));
    }

    /**
     * Ekspor data log verifikasi gerbang ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $activities = $this->getVerificationLogsQuery($request)->get();

        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);
        $period = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : ($startDate ? "Sejak {$startDate}" : 'Semua Periode');

        $filters = ["Periode: {$period}"];
        if ($request->filled('team')) {
            $filters[] = "Regu {$request->input('team')}";
        }
        if ($request->filled('security_guard_id')) {
            $guard = SecurityGuard::find($request->input('security_guard_id'));
            $filters[] = "Satpam: " . ($guard ? $guard->name : 'ID ' . $request->input('security_guard_id'));
        }
        if ($request->filled('gate_id')) {
            $gate = Gate::find($request->input('gate_id'));
            $filters[] = "Pos: " . ($gate ? $gate->name : 'ID ' . $request->input('gate_id'));
        }
        if ($request->filled('direction')) {
            $filters[] = "Arah: " . ($request->input('direction') === 'in' ? 'Masuk' : 'Keluar');
        }
        if ($request->filled('status')) {
            $filters[] = "Status: " . ucfirst($request->input('status'));
        }
        if ($request->filled('search')) {
            $filters[] = "Pencarian: \"{$request->input('search')}\"";
        }

        $metaLines = [
            'Sistem: Modul Private Guard (PG) - PT Kerinci Merangin Hidro',
            'Filter: ' . implode(' | ', $filters),
            'Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i') . ' WIB',
            'Total Data: ' . $activities->count() . ' Riwayat Perlintasan Gerbang',
        ];

        $headers = [
            'No' => 6,
            'Waktu Verifikasi' => 20,
            'No. Transaksi' => 20,
            'Petugas Satpam' => 24,
            'Regu' => 12,
            'Pos Gerbang' => 20,
            'Plat Nomor' => 16,
            'Merek & Tipe' => 24,
            'Driver' => 24,
            'NIK Driver' => 16,
            'Arah' => 12,
            'Odometer (KM)' => 16,
            'Jarak (KM)' => 14,
            'Status' => 14,
            'Catatan Tolak' => 28,
        ];

        $rows = [];
        foreach ($activities as $idx => $item) {
            $validatedTime = $item->validated_at ?? $item->recorded_at;
            $waktu = $validatedTime ? $validatedTime->format('d/m/Y H:i') : '-';
            $satpam = $item->securityGuard ? $item->securityGuard->name : '-';
            $regu = $item->securityGuard ? 'Regu ' . $item->securityGuard->team : '-';
            $pos = $item->gate ? $item->gate->name : '-';
            $plat = $item->vehicle ? $item->vehicle->license_plate : '-';
            $merek = $item->vehicle ? ($item->vehicle->brand_type . ' (' . $item->vehicle->code . ')') : '-';
            $driver = $item->driver ? $item->driver->name : '-';
            $nik = $item->driver ? ($item->driver->nik ?? '-') : '-';
            $arah = ($item->direction === 'in') ? 'Masuk' : 'Keluar';
            $status = match($item->validation_status) {
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
                default => 'Menunggu',
            };

            $rows[] = [
                $idx + 1,
                $waktu,
                $item->transaction_no,
                $satpam,
                $regu,
                $pos,
                $plat,
                $merek,
                $driver,
                $nik,
                $arah,
                (int) $item->current_km,
                $item->distance > 0 ? (int) $item->distance : 0,
                $status,
                $item->rejection_note ?? '-',
            ];
        }

        $alignments = ['center', 'center', 'center', 'left', 'center', 'left', 'center', 'left', 'left', 'center', 'center', 'right', 'right', 'center', 'left'];
        $xlsxContent = SimpleXlsxExporter::create('LOG AKTIVITAS GERBANG - PT KMH', $metaLines, $headers, $rows, $alignments);

        $fileName = 'log_aktivitas_gerbang_' . date('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor data log verifikasi gerbang ke format PDF (preview langsung di browser).
     */
    public function exportPdf(Request $request): Response
    {
        $activities = $this->getVerificationLogsQuery($request)->get();

        $today = Carbon::today()->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);
        $period = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : ($startDate ? "Sejak {$startDate}" : 'Semua Periode');

        $gateDesc = 'Semua Pos';
        if ($request->filled('gate_id')) {
            $gate = Gate::find($request->input('gate_id'));
            $gateDesc = $gate ? $gate->name : 'ID ' . $request->input('gate_id');
        }

        $metaLeft = [
            'Modul' => 'Log Verifikasi Pos Gerbang',
            'Periode Tanggal' => $period,
            'Pos Gerbang' => $gateDesc,
        ];

        if ($request->filled('team')) {
            $metaLeft['Regu Satpam'] = 'Regu ' . $request->input('team');
        }

        if ($request->filled('direction')) {
            $metaLeft['Arah Gerbang'] = ($request->input('direction') === 'in') ? 'Masuk' : 'Keluar';
        }

        $metaRight = [
            'Tanggal Cetak' => now()->translatedFormat('d M Y, H:i') . ' WIB',
            'Total Transaksi' => $activities->count() . ' Record',
            'Dicetak Oleh' => auth()->user()->name ?? 'Administrator PG',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 9, 'align' => 'C', 'bold' => true],
            ['title' => 'WAKTU', 'width' => 26, 'align' => 'C'],
            ['title' => 'NO. TRANSAKSI', 'width' => 28, 'align' => 'C', 'bold' => true],
            ['title' => 'PETUGAS SATPAM', 'width' => 34, 'align' => 'L', 'max_char' => 20],
            ['title' => 'POS GERBANG', 'width' => 28, 'align' => 'L', 'max_char' => 16],
            ['title' => 'KENDARAAN', 'width' => 35, 'align' => 'L', 'max_char' => 20],
            ['title' => 'DRIVER / NIK', 'width' => 35, 'align' => 'L', 'max_char' => 20],
            ['title' => 'ARAH', 'width' => 16, 'align' => 'C', 'is_status' => true],
            ['title' => 'ODOMETER', 'width' => 22, 'align' => 'R'],
            ['title' => 'STATUS', 'width' => 34, 'align' => 'C', 'is_status' => true, 'max_char' => 22],
        ];

        $rows = [];
        foreach ($activities as $idx => $item) {
            $validatedTime = $item->validated_at ?? $item->recorded_at;
            $waktu = $validatedTime ? $validatedTime->format('d/m/Y H:i') : '-';
            $satpam = $item->securityGuard ? $item->securityGuard->name : '-';
            $pos = $item->gate ? $item->gate->name : '-';
            $kendaraan = $item->vehicle ? ($item->vehicle->license_plate . ' (' . $item->vehicle->code . ')') : '-';
            $driver = $item->driver ? ($item->driver->name . ' - ' . ($item->driver->nik ?? '')) : '-';
            $arah = ($item->direction === 'in') ? 'Masuk' : 'Keluar';
            $status = match($item->validation_status) {
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak' . ($item->rejection_note ? ' (' . $item->rejection_note . ')' : ''),
                default => 'Menunggu',
            };
            $odometer = number_format($item->current_km ?? 0, 0, ',', '.') . ' KM';

            $rows[] = [
                $idx + 1,
                $waktu,
                $item->transaction_no,
                $satpam,
                $pos,
                $kendaraan,
                $driver,
                $arah,
                $odometer,
                $status,
            ];
        }

        $pdfContent = PgTablePdfExporter::generate(
            'Laporan Log Riwayat Verifikasi Gerbang',
            'Rekapitulasi Audit Lalu Lintas Kendaraan Masuk & Keluar Pos Gerbang',
            'MODUL PRIVATE GUARD (PG) - RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'log_verifikasi_gerbang_' . date('Ymd_His') . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Hapus riwayat log verifikasi gerbang oleh Admin yang berwenang.
     */
    public function destroy(int $id): RedirectResponse
    {
        $activity = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])->findOrFail($id);

        $vehicleCode = $activity->vehicle?->code ?? '-';
        $trxNo = $activity->transaction_no;
        $driverName = $activity->driver?->name ?? 'Pengemudi';
        $gateName = $activity->gate?->name ?? 'Gerbang';

        DB::transaction(function () use ($activity, $vehicleCode, $trxNo, $driverName, $gateName) {
            // Jika aktivitas yang dihapus adalah aktivitas approved terakhir, kembalikan posisi & km master jika perlu
            if ($activity->validation_status === 'approved' && $activity->vehicle) {
                $prevApproved = VehicleActivity::where('vehicle_id', $activity->vehicle_id)
                    ->where('id', '!=', $activity->id)
                    ->where('validation_status', 'approved')
                    ->latest('recorded_at')
                    ->first();

                if ($prevApproved) {
                    $activity->vehicle->update([
                        'current_km' => $prevApproved->current_km,
                    ]);
                }
            }

            // Catat log audit ke AdminActivityLog
            AdminActivityLog::record(
                actionType: 'delete',
                module: 'Keamanan & Pos Satpam (PG)',
                description: "Menghapus riwayat verifikasi perlintasan gerbang {$trxNo} pada unit {$vehicleCode} di pos {$gateName} (Driver: {$driverName}).",
                targetType: 'VehicleActivity',
                targetId: $activity->id,
                targetName: "{$vehicleCode} ({$trxNo})",
                oldValues: [
                    'transaction_no' => $trxNo,
                    'current_km' => (float)$activity->current_km,
                    'direction' => $activity->direction,
                    'gate' => $gateName,
                    'validation_status' => $activity->validation_status,
                ],
                newValues: null
            );

            // Hapus relasi riwayat lokasi (jika ada)
            $activity->locationHistories()->delete();

            // Hapus aktivitas
            $activity->delete();
        });

        return back()->with('success', "Riwayat log verifikasi gerbang {$trxNo} ({$vehicleCode}) berhasil dihapus.");
    }
}
