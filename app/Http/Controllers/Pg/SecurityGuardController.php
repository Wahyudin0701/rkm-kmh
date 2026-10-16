<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Services\PgTablePdfExporter;
use App\Services\SimpleXlsxExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityGuardController extends Controller
{
    /**
     * Bangun query petugas Satpam berdasarkan filter request.
     */
    protected function getGuardsQuery(Request $request)
    {
        $query = SecurityGuard::with(['gate', 'user']);

        // 1. Pencarian Kata Kunci (ID Satpam / Nama Lengkap / Pos Gerbang)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('badge_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('gate', function ($gq) use ($search) {
                      $gq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Regu Satpam (Regu 1, 2, atau 3)
        if ($request->filled('team')) {
            $query->where('team', (int) $request->input('team'));
        }

        // 3. Filter Penempatan Pos Gerbang
        if ($request->filled('gate_id')) {
            $query->where('gate_id', (int) $request->input('gate_id'));
        }

        return $query;
    }

    /**
     * Tampilkan daftar petugas Satpam beserta daftar pos gerbang.
     */
    public function index(Request $request): View
    {
        $guards = $this->getGuardsQuery($request)
            ->orderBy('team')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $gates = Gate::where('is_active', true)->orderBy('name')->get();

        return view('pg.guards_index', compact('guards', 'gates'));
    }

    /**
     * Ekspor data petugas Satpam ke format Excel (.xlsx).
     */
    public function exportExcel(Request $request): Response
    {
        $guards = $this->getGuardsQuery($request)
            ->orderBy('team')
            ->orderBy('name')
            ->get();

        $filters = [];
        if ($request->filled('search')) {
            $filters[] = "Pencarian: \"{$request->input('search')}\"";
        }
        if ($request->filled('team')) {
            $filters[] = "Regu {$request->input('team')}";
        }
        if ($request->filled('gate_id')) {
            $gate = Gate::find($request->input('gate_id'));
            $filters[] = "Pos: " . ($gate ? $gate->name : 'ID ' . $request->input('gate_id'));
        }
        $filterDesc = empty($filters) ? 'Semua Petugas Satpam' : implode(' | ', $filters);

        $metaLines = [
            'Sistem: Modul Private Guard (PG) - PT Kerinci Merangin Hidro',
            'Filter: ' . $filterDesc,
            'Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i') . ' WIB',
            'Total Data: ' . $guards->count() . ' Personil Satpam',
        ];

        $headers = [
            'No' => 6,
            'ID Satpam' => 16,
            'Nama Lengkap' => 28,
            'Regu' => 14,
            'Penempatan Pos' => 24,
            'Status' => 14,
            'Username Akun' => 18,
        ];

        $rows = [];
        foreach ($guards as $idx => $g) {
            $badge = $g->badge_id ?? ('SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT));
            $rows[] = [
                $idx + 1,
                $badge,
                $g->name,
                'Regu ' . $g->team,
                $g->gate ? $g->gate->name : 'Belum Ditentukan',
                $g->is_active ? 'Aktif' : 'Nonaktif',
                $g->user ? $g->user->username : ($g->badge_id ?? '-'),
            ];
        }

        $alignments = ['center', 'center', 'left', 'center', 'left', 'center', 'center'];
        $xlsxContent = SimpleXlsxExporter::create('DATA PETUGAS SATPAM - PT KMH', $metaLines, $headers, $rows, $alignments);

        $fileName = 'data_satpam_' . date('Ymd_His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Ekspor data petugas Satpam ke format PDF (preview langsung di browser).
     */
    public function exportPdf(Request $request): Response
    {
        $guards = $this->getGuardsQuery($request)
            ->orderBy('team')
            ->orderBy('name')
            ->get();

        $teamDesc = $request->filled('team') ? 'Regu ' . $request->input('team') : 'Semua Regu';
        $gateDesc = 'Semua Penempatan';
        if ($request->filled('gate_id')) {
            $gate = Gate::find($request->input('gate_id'));
            $gateDesc = $gate ? $gate->name : 'ID ' . $request->input('gate_id');
        }

        $metaLeft = [
            'Modul' => 'Data Petugas Satpam Lapangan',
            'Filter Regu' => $teamDesc,
            'Pos Gerbang' => $gateDesc,
        ];

        if ($request->filled('search')) {
            $metaLeft['Pencarian'] = '"' . $request->input('search') . '"';
        }

        $metaRight = [
            'Tanggal Cetak' => now()->translatedFormat('d M Y, H:i') . ' WIB',
            'Total Personil' => $guards->count() . ' Orang',
            'Dicetak Oleh' => auth()->user()->name ?? 'Administrator PG',
        ];

        $columns = [
            ['title' => 'NO', 'width' => 12, 'align' => 'C', 'bold' => true],
            ['title' => 'ID SATPAM', 'width' => 32, 'align' => 'C', 'bold' => true],
            ['title' => 'NAMA LENGKAP', 'width' => 70, 'align' => 'L', 'bold' => true, 'max_char' => 38],
            ['title' => 'REGU', 'width' => 28, 'align' => 'C', 'is_status' => true],
            ['title' => 'PENEMPATAN POS', 'width' => 65, 'align' => 'L', 'max_char' => 34],
            ['title' => 'STATUS', 'width' => 28, 'align' => 'C', 'is_status' => true],
            ['title' => 'USERNAME AKUN', 'width' => 32, 'align' => 'C'],
        ];

        $rows = [];
        foreach ($guards as $idx => $g) {
            $badge = $g->badge_id ?? ('SEC-' . str_pad($g->id, 3, '0', STR_PAD_LEFT));
            $rows[] = [
                $idx + 1,
                $badge,
                $g->name,
                'Regu ' . $g->team,
                $g->gate ? $g->gate->name : 'Belum Ditentukan',
                $g->is_active ? 'Aktif' : 'Nonaktif',
                $g->user ? $g->user->username : ($g->badge_id ?? '-'),
            ];
        }

        $pdfContent = PgTablePdfExporter::generate(
            'Laporan Data Petugas Satpam',
            'Rekapitulasi Personil Jaga & Penempatan Pos Gerbang',
            'MODUL PRIVATE GUARD (PG) - RKM-KMH',
            $metaLeft,
            $metaRight,
            $columns,
            $rows
        );

        $fileName = 'laporan_data_satpam_' . date('Ymd_His') . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Simpan data petugas Satpam baru dan otomatis buatkan akun login pos.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'badge_id' => ['required', 'string', 'max:50', 'unique:security_guards,badge_id', 'unique:users,username'],
            'name' => ['required', 'string', 'max:100'],
            'team' => ['required', 'integer', 'in:1,2,3'],
            'gate_id' => ['required', 'exists:gates,id'],
            'password' => ['required', 'string', 'min:4'],
        ], [
            'badge_id.required' => 'ID Satpam wajib diisi.',
            'badge_id.unique' => 'ID Satpam ini sudah digunakan oleh petugas lain.',
            'name.required' => 'Nama lengkap Satpam wajib diisi.',
            'team.required' => 'Silakan pilih Regu Satpam (1, 2, atau 3).',
            'team.in' => 'Regu Satpam harus bernilai 1, 2, atau 3.',
            'gate_id.required' => 'Silakan tentukan penempatan Pos Gerbang.',
            'password.required' => 'Password akun login Satpam wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 4 karakter.',
        ]);

        DB::transaction(function () use ($request) {
            $badgeId = strtoupper(trim($request->input('badge_id')));

            // 1. Buat akun User untuk login Satpam di pos jaga
            $user = User::create([
                'name' => $request->input('name'),
                'username' => $badgeId,
                'email' => strtolower($badgeId) . '@kmh.co.id',
                'password' => Hash::make($request->input('password')),
                'role' => 'gate_officer',
                'gate_id' => $request->input('gate_id'),
            ]);

            // 2. Simpan master data Satpam
            SecurityGuard::create([
                'badge_id' => $badgeId,
                'name' => $request->input('name'),
                'team' => (int) $request->input('team'),
                'gate_id' => $request->input('gate_id'),
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        });

        return back()->with('success', "Petugas Satpam {$request->input('name')} (ID: {$request->input('badge_id')}) berhasil didaftarkan.");
    }

    /**
     * Perbarui data Satpam (Nama, Regu, Penempatan Pos, dan Password opsional).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $guard = SecurityGuard::with('user')->findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'team' => ['required', 'integer', 'in:1,2,3'],
            'gate_id' => ['required', 'exists:gates,id'],
            'password' => ['nullable', 'string', 'min:4'],
        ], [
            'name.required' => 'Nama lengkap Satpam wajib diisi.',
            'team.required' => 'Silakan pilih Regu Satpam (1, 2, atau 3).',
            'gate_id.required' => 'Silakan tentukan penempatan Pos Gerbang.',
            'password.min' => 'Password minimal terdiri dari 4 karakter.',
        ]);

        DB::transaction(function () use ($request, $guard) {
            $guard->update([
                'name' => $request->input('name'),
                'team' => (int) $request->input('team'),
                'gate_id' => $request->input('gate_id'),
            ]);

            if ($guard->user) {
                $userData = [
                    'name' => $request->input('name'),
                    'gate_id' => $request->input('gate_id'),
                ];

                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->input('password'));
                }

                $guard->user->update($userData);
            }
        });

        return back()->with('success', "Data petugas Satpam {$guard->name} berhasil diperbarui.");
    }

    /**
     * Ubah status aktif/nonaktif Satpam.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $guard = SecurityGuard::with('user')->findOrFail($id);
        $newStatus = !$guard->is_active;

        DB::transaction(function () use ($guard, $newStatus) {
            $guard->update([
                'is_active' => $newStatus,
            ]);
        });

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status petugas Satpam ({$guard->name}) berhasil {$statusText}.");
    }

    /**
     * Hapus data petugas Satpam beserta akun login terkait.
     */
    public function destroy(int $id): RedirectResponse
    {
        $guard = SecurityGuard::with('user')->findOrFail($id);
        $guardName = $guard->name;
        $badgeId = $guard->badge_id;

        DB::transaction(function () use ($guard) {
            $user = $guard->user;
            $guard->delete();

            if ($user) {
                $user->delete();
            }
        });

        return back()->with('success', "Data petugas Satpam {$guardName} ({$badgeId}) berhasil dihapus.");
    }
}
