<?php

namespace App\Http\Controllers\P2h;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\P2hRecord;
use App\Models\P2hRecordItem;
use App\Models\P2hTemplate;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class P2hFormController extends Controller
{
    /**
     * Halaman katalog/pemilihan Heavy Equipment untuk pengisian P2H.
     */
    public function index(Request $request): View
    {
        $query = Vehicle::unitSupports()->where(function ($q) {
            $q->whereNotNull('parent_id')
              ->orWhere(function ($sub) {
                  $sub->whereNull('parent_id')->whereDoesntHave('units');
              });
        });

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanSearch = preg_replace('/[^a-zA-Z0-9]/', '', $search);

            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%");

                if (!empty($cleanSearch)) {
                    $q->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '-', '') LIKE ?", ["%{$cleanSearch}%"])
                      ->orWhereRaw("REPLACE(REPLACE(license_plate, ' ', ''), '-', '') LIKE ?", ["%{$cleanSearch}%"]);
                }
            });
        }

        if ($request->filled('unit_category')) {
            $query->where('unit_category', $request->input('unit_category'));
        }

        $vehicles = $query->with('p2hTemplate')->orderBy('code')->paginate(32)->withQueryString();
        $categories = P2hTemplate::orderBy('name')->pluck('name');

        $user = Auth::user();
        $employee = $user ? $user->employee : null;

        return view('p2h.index', compact('vehicles', 'categories', 'employee'));
    }

    /**
     * Tampilkan formulir pengisian P2H untuk Heavy Equipment tertentu.
     */
    public function show(Request $request, string $code): View|RedirectResponse
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'unit_support')
            ->firstOrFail();

        $user = Auth::user();
        $employee = $user->getOrCreateEmployee();

        // Informasi identitas operator / pengisi P2H (informatif, tidak memblokir hak akses)
        $simperNotice = null;
        $simperError = null;
        if ($user->isQmaAdmin() || $user->isSuperAdmin()) {
            $simperNotice = "Anda login sebagai Administrator / Verifikator QMA. Anda memiliki wewenang untuk mengisi atau mengoreksi formulir P2H unit secara langsung.";
        } elseif ($employee && !$employee->is_operator) {
            $simperNotice = "Catatan: Profil karyawan Anda ({$employee->name}) belum ditandai sebagai Operator di modul HSE. Formulir tetap dapat dikirim sesuai izin akses (RBAC) yang diberikan.";
        } elseif ($employee && !$employee->hasValidSimper()) {
            $expDate = $employee->simper_expired_at ? $employee->simper_expired_at->format('d/m/Y') : 'Belum Terdaftar';
            $simperNotice = "Catatan: Masa berlaku SIMPER ({$expDate}) perlu diperbarui ke Divisi HSE. Formulir tetap dapat dikirim sesuai izin akses (RBAC) yang diberikan.";
        }

        // Tentukan template P2H yang digunakan
        $template = $vehicle->p2hTemplate;
        if (!$template && $vehicle->unit_category) {
            $template = P2hTemplate::where('name', 'like', "%{$vehicle->unit_category}%")
                ->orWhere('code', 'like', "%{$vehicle->unit_category}%")
                ->first();
        }

        if (!$template) {
            // Ambil template default pertama jika belum terpetakan spesifik
            $template = P2hTemplate::first();
        }

        $items = $template ? $template->items : collect();
        $groupedItems = [];
        foreach ($items as $item) {
            $groupedItems[$item->section][] = $item;
        }

        return view('p2h.form', compact(
            'vehicle',
            'employee',
            'simperNotice',
            'simperError',
            'template',
            'groupedItems'
        ));
    }

    /**
     * Simpan hasil pengisian formulir P2H ke database.
     */
    public function store(Request $request, string $code): RedirectResponse
    {
        $vehicle = Vehicle::where('code', $code)
            ->where('category', 'unit_support')
            ->firstOrFail();

        $user = Auth::user();
        $employee = $user->getOrCreateEmployee();

        // Validasi hak akses RBAC sudah ditangani secara merata oleh route middleware
        // Seluruh akun yang memiliki izin berhak mengirim formulir P2H tanpa diskriminasi role/SIMPER.

        $validated = $request->validate([
            'template_id' => ['required', 'exists:p2h_templates,id'],
            'shift' => ['nullable', 'string'],
            'start_hm' => ['required', 'numeric', 'min:0'],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.condition' => ['nullable', 'string', 'in:baik,rusak'],
            'items.*.answer' => ['nullable', 'string'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'start_hm.required' => 'Nilai Hour Meter (HM) wajib diisi.',
            'start_hm.numeric' => 'Nilai Hour Meter (HM) harus berupa angka valid.',
            'items.required' => 'Daftar butir pemeriksaan P2H tidak boleh kosong.',
        ]);

        $template = P2hTemplate::with('items')->findOrFail($validated['template_id']);
        $submittedItems = $validated['items'];

        $trxNo = 'P2H-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        $now = Carbon::now();

        DB::transaction(function () use ($trxNo, $vehicle, $employee, $template, $validated, $submittedItems, $now) {
            $defectCount = 0;
            $itemsToInsert = [];

            foreach ($template->items as $tItem) {
                $itemId = $tItem->id;
                $userAns = $submittedItems[$itemId] ?? null;

                $condition = null;
                $answerVal = null;
                $notes = null;

                if (in_array($tItem->question_type, ['choice', 'condition', 'Multiple choice: only one answer', 'Matrix'])) {
                    // Pilihan Baik atau Rusak (default 'baik' jika belum terisi)
                    $condition = isset($userAns['condition']) && $userAns['condition'] === 'rusak' ? 'rusak' : 'baik';
                    if ($condition === 'rusak') {
                        $defectCount++;
                    }
                } elseif (in_array($tItem->question_type, ['number', 'Numerical Value'])) {
                    $answerVal = $userAns['answer'] ?? $validated['start_hm'];
                } elseif (in_array($tItem->question_type, ['datetime', 'Datetime'])) {
                    $answerVal = $userAns['answer'] ?? $now->format('Y-m-d H:i');
                } elseif (in_array($tItem->question_type, ['date', 'Date'])) {
                    $answerVal = $userAns['answer'] ?? $now->format('Y-m-d');
                } else {
                    $answerVal = $userAns['answer'] ?? null;
                }

                if (!empty($userAns['notes'])) {
                    $notes = trim($userAns['notes']);
                }

                $itemsToInsert[] = [
                    'template_item_id' => $tItem->id,
                    'section' => $tItem->section,
                    'title' => $tItem->title,
                    'question_type' => $tItem->question_type,
                    'condition_status' => $condition,
                    'answer_value' => $answerVal,
                    'notes' => $notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $summaryCondition = ($defectCount > 0) ? 'has_defect' : 'good';

            $record = P2hRecord::create([
                'transaction_no' => $trxNo,
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employee->id,
                'template_id' => $template->id,
                'shift' => !empty($validated['shift']) ? $validated['shift'] : 'Shift 1 (Siang)',
                'start_hm' => $validated['start_hm'],
                'start_time' => $now,
                'summary_condition' => $summaryCondition,
                'defect_count' => $defectCount,
                'general_notes' => $validated['general_notes'] ?? null,
                'validation_status' => 'pending', // Menunggu approval Admin QMA
                'inspected_at' => $now,
            ]);

            foreach ($itemsToInsert as $ins) {
                $ins['p2h_record_id'] = $record->id;
                P2hRecordItem::create($ins);
            }

            // Perbarui HM unit di master data kendaraan jika lebih besar
            if ((float)$validated['start_hm'] > (float)$vehicle->current_km) {
                $vehicle->update(['current_km' => $validated['start_hm']]);
            }

            // Jika ada kerusakan kritis, tandai status kendaraan
            if ($defectCount > 0 && $vehicle->status === 'Operation') {
                $vehicle->update(['status' => 'Breakdown']);
            }
        });

        return redirect()
            ->route('p2h.form', ['code' => $code])
            ->with('success', "Inspeksi P2H unit [{$vehicle->code}] berhasil dikirim dengan Nomor Transaksi {$trxNo}. Laporan saat ini berstatus 'Menunggu Approval' dari Admin QMA.");
    }
}
