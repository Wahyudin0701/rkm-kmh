<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama karyawan / operator.
     */
    public function index(): View
    {
        $employee = auth()->user()->employee;
        $totalSupport = Vehicle::unitSupports()->count();
        $totalLv = Vehicle::lightVehicles()->count();

        return view('employee.dashboard', compact('employee', 'totalSupport', 'totalLv'));
    }
}
