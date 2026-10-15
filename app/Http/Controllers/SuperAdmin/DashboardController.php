<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard utama Super Admin (halaman kosong / persiapan awal).
     */
    public function index(): View
    {
        return view('superadmin.dashboard');
    }
}
