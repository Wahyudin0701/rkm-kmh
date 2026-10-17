<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
}
