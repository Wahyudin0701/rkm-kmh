<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Employee;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Ringkasan Super Admin & RBAC.
     */
    public function index(): View
    {
        $today = Carbon::today();

        $metrics = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_employees' => Employee::count(),
            'total_vehicles' => Vehicle::count(),
            'total_guards' => SecurityGuard::count(),
            'activities_today' => VehicleActivity::whereDate('created_at', $today)->count(),
            'activities_pending' => VehicleActivity::pending()->count(),
            'activities_approved' => VehicleActivity::approved()->whereDate('created_at', $today)->count(),
        ];

        $usersByRole = User::with('roles')
            ->get()
            ->groupBy(function ($u) {
                return $u->roles->first()?->name ?? $u->role ?? 'Tanpa Role';
            })
            ->map->count();

        $recentUsers = User::with(['roles', 'gate'])
            ->latest()
            ->take(6)
            ->get();

        $recentActivities = VehicleActivity::with(['vehicle', 'driver', 'gate'])
            ->latest()
            ->take(5)
            ->get();

        $recentAdminLogs = AdminActivityLog::latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('metrics', 'usersByRole', 'recentUsers', 'recentActivities', 'recentAdminLogs'));
    }
}
