<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama Dashboard Admin Private Guard (PG).
     */
    public function index(): View
    {
        // 5 Metrik Ringkasan Utama (Persis Gaya Kartu Pastel Gambar Referensi)
        $totalGuards = SecurityGuard::count();
        $activeGuards = SecurityGuard::where('is_active', true)->count();
        $totalGates = Gate::where('is_active', true)->count();
        $todayVerifications = VehicleActivity::whereDate('recorded_at', Carbon::today())->count();
        $totalRejections = VehicleActivity::where('validation_status', 'rejected')->count();

        // Log Verifikasi Gerbang Terkini (Panel Kiri)
        $recentActivities = VehicleActivity::with(['vehicle', 'driver', 'gate', 'securityGuard'])
            ->latest('recorded_at')
            ->take(5)
            ->get();

        // Personel Satpam Terkini per Regu (Panel Kanan Atas)
        $recentGuards = SecurityGuard::with('gate')
            ->orderBy('team')
            ->take(5)
            ->get();

        // Ringkasan Jumlah Personel per Regu Tugas (Panel Kanan Bawah)
        $team1Count = SecurityGuard::where('team', 1)->where('is_active', true)->count();
        $team2Count = SecurityGuard::where('team', 2)->where('is_active', true)->count();
        $team3Count = SecurityGuard::where('team', 3)->where('is_active', true)->count();

        return view('pg.dashboard', compact(
            'totalGuards',
            'activeGuards',
            'totalGates',
            'todayVerifications',
            'totalRejections',
            'recentActivities',
            'recentGuards',
            'team1Count',
            'team2Count',
            'team3Count'
        ));
    }
}
