<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Hse\EmployeeController;
use App\Http\Controllers\Pg\GateInspectionController;
use App\Http\Controllers\Pg\SecurityGuardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\VehicleActivityFormController;
use App\Http\Controllers\Qma\ActivityLogController;
use App\Http\Controllers\Qma\VehicleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root
Route::get('/', function () {
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return match ($user->role) {
            'hse_admin' => redirect()->route('hse.dashboard'),
            'qma_admin' => redirect()->route('qma.dashboard'),
            'pg_admin' => redirect()->route('pg.guards.index'),
            'gate_officer' => redirect()->route('pg.gate.inspection'),
            default => redirect()->route('qma.dashboard'),
        };
    }
    return redirect()->route('login');
});

// Autentikasi Pengguna (Login & Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 1. Modul Publik: Form Pencatatan Aktivitas Kendaraan Ringan (Via Scan QR Code di Lapangan)
Route::prefix('gate-activity')->name('vehicle.activity.')->group(function () {
    Route::get('/{code}', [VehicleActivityFormController::class, 'show'])->name('form');
    Route::post('/{code}', [VehicleActivityFormController::class, 'store'])->name('store');
});

// Group Terproteksi Autentikasi Sistem
Route::middleware('auth')->group(function () {

    // 2. Modul HSE: Admin HSE (Mima - 1185) - Kelola Karyawan & SIMPER
    Route::prefix('hse')->name('hse.')->middleware('role:hse_admin')->group(function () {
        Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::post('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    // 3. Modul QMA: Admin QMA (Pinto Yuliandra - 1171) - Dashboard, Data Kendaraan & Log Aktivitas
    Route::prefix('qma')->name('qma.')->middleware('role:qma_admin')->group(function () {
        Route::get('/dashboard', [VehicleController::class, 'dashboard'])->name('dashboard');
        
        // Halaman Data Kendaraan Terpisah: Light Vehicle (LV) dan Unit Support
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/light-vehicle', [VehicleController::class, 'indexLv'])->name('vehicles.lv');
        Route::get('/vehicles/unit-support', [VehicleController::class, 'indexSupport'])->name('vehicles.support');
        
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::post('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log');
        Route::get('/activity-summary', [ActivityLogController::class, 'summary'])->name('activity.summary');
    });

    // 4. Modul Private Guard (PG): Admin PG (Admin PG - AdminPG) & Pos Gerbang Satpam
    Route::prefix('pg')->name('pg.')->group(function () {
        // Master Satpam (Hanya Admin PG)
        Route::middleware('role:pg_admin')->group(function () {
            Route::get('/guards', [SecurityGuardController::class, 'index'])->name('guards.index');
            Route::post('/guards', [SecurityGuardController::class, 'store'])->name('guards.store');
            Route::post('/guards/{id}/toggle', [SecurityGuardController::class, 'toggleStatus'])->name('guards.toggle');
        });

        // Verifikasi Pos Gerbang (Petugas Pos Gerbang & Admin PG)
        Route::middleware('role:gate_officer,pg_admin')->group(function () {
            Route::get('/gate-inspection', [GateInspectionController::class, 'index'])->name('gate.inspection');
            Route::post('/gate-inspection/{id}/approve', [GateInspectionController::class, 'approve'])->name('gate.approve');
            Route::post('/gate-inspection/{id}/reject', [GateInspectionController::class, 'reject'])->name('gate.reject');
        });
    });

    // 5. Modul Profil Saya (Seluruh Pengguna Terautentikasi)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
