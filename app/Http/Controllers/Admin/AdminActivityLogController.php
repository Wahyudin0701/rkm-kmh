<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    /**
     * Tampilkan riwayat log aktivitas yang dilakukan Super Admin.
     */
    public function index(Request $request): View
    {
        $query = AdminActivityLog::with('user')->latest();

        // 1. Filter Pencarian Kata Kunci
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('user_username', 'like', "%{$search}%")
                  ->orWhere('target_name', 'like', "%{$search}%");
            });
        }

        // 2. Filter Tipe Aksi
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        // 3. Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $logs = $query->paginate(20)->withQueryString();

        // Metrik Ringkasan di Bagian Atas
        $today = Carbon::today();
        $stats = [
            'today_count' => AdminActivityLog::whereDate('created_at', $today)->count(),
            'create_count' => AdminActivityLog::where('action_type', 'create')->count(),
            'update_count' => AdminActivityLog::whereIn('action_type', ['update', 'permission'])->count(),
            'delete_count' => AdminActivityLog::where('action_type', 'delete')->count(),
        ];

        return view('admin.activity_logs.index', compact('logs', 'stats'));
    }

    /**
     * Kosongkan seluruh riwayat log aktivitas admin (Hanya Super Admin).
     */
    public function clear(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('super_admin') && $user->role !== 'super_admin')) {
            abort(403, 'Akses ditolak. Hanya Super Admin yang berhak mengosongkan log aktivitas sistem.');
        }

        $confirmed = false;
        if ($request->filled('confirmation') && trim($request->input('confirmation')) === 'KOSONGKAN') {
            $confirmed = true;
        } elseif ($request->filled('password') && Hash::check($request->input('password'), $user->password)) {
            $confirmed = true;
        }

        if (!$confirmed) {
            return back()->with('error', 'Konfirmasi keamanan tidak sesuai. Silakan ketik kata "KOSONGKAN" atau masukkan password Super Admin Anda.');
        }

        $count = AdminActivityLog::count();
        AdminActivityLog::truncate();

        return back()->with('success', "Seluruh rekaman log aktivitas admin berhasil dikosongkan ({$count} catatan log dibersihkan). Sistem siap untuk fase operasional baru.");
    }
}
