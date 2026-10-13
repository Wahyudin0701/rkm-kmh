<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Tampilkan halaman utama (Homepage) Sistem RKM.
     * Menyajikan ringkasan sistem, statistik real-time,
     * serta tombol akses ke menu aktivitas kendaraan lewat gate.
     */
    public function index(): View
    {
        // 1. Data Statistik Ringkasan Sistem
        $totalLv = Vehicle::lightVehicles()->count();
        $operationalLv = Vehicle::lightVehicles()->where('status', 'Operation')->count();
        $standbyLv = Vehicle::lightVehicles()->where('status', 'Standby')->count();
        
        $totalDrivers = Employee::operators()->count();
        $validSimperCount = Employee::operators()
            ->whereNotNull('simper_expired_at')
            ->where('simper_expired_at', '>=', Carbon::today())
            ->count();
        $expiredSimperCount = max(0, $totalDrivers - $validSimperCount);

        $totalGates = Gate::where('is_active', true)->count();
        $todayActivitiesCount = VehicleActivity::whereDate('recorded_at', Carbon::today())->count();
        $totalActivities = VehicleActivity::count();

        // 2. Daftar Seluruh Pos Gerbang Proyek
        $gates = Gate::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('home', compact(
            'totalLv',
            'operationalLv',
            'standbyLv',
            'totalDrivers',
            'validSimperCount',
            'expiredSimperCount',
            'totalGates',
            'todayActivitiesCount',
            'totalActivities',
            'gates'
        ));
    }
}
