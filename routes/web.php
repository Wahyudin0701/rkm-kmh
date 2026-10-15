<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Hse\EmployeeController;
use App\Http\Controllers\P2h\P2hFormController;
use App\Http\Controllers\Pg\DashboardController as PgDashboardController;
use App\Http\Controllers\Pg\GateInspectionController;
use App\Http\Controllers\Pg\SecurityGuardController;
use App\Http\Controllers\Pg\VerificationLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\VehicleActivityFormController;
use App\Http\Controllers\Qma\ActivityLogController;
use App\Http\Controllers\Qma\P2hInspectionController;
use App\Http\Controllers\Qma\P2hTemplateController;
use App\Http\Controllers\Qma\VehicleController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman Utama (Homepage Sistem RKM Publik & Ringkasan Sistem)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Autentikasi Pengguna (Login & Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 1. Modul Publik: Pencatatan Aktivitas Kendaraan Ringan (Via Scan QR Code Mobil Unit di Lapangan)
Route::prefix('gate-activity')->name('vehicle.activity.')->group(function () {
    Route::get('/{code}', [VehicleActivityFormController::class, 'show'])->name('form');
    Route::post('/{code}/check-nik', [VehicleActivityFormController::class, 'checkNik'])->middleware('throttle:60,1')->name('check.nik');
    Route::post('/{code}', [VehicleActivityFormController::class, 'store'])->name('store');
});

// Group Terproteksi Autentikasi Sistem
Route::middleware('auth')->group(function () {

    // 1. Modul Super Admin (Kategori Khusus Admin: Dashboard & Manajemen Role)
    Route::prefix('superadmin')->name('superadmin.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index']);
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/roles', [SuperAdminUserController::class, 'index'])->name('roles.index');
        Route::post('/users', [SuperAdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [SuperAdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/reset-password', [SuperAdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{id}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');
    });

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
        Route::get('/vehicles/unit-support/{id}/units', [VehicleController::class, 'indexSupportUnits'])->name('vehicles.support.units');
        Route::post('/vehicles/unit-support/{id}/units', [VehicleController::class, 'storeSupportUnit'])->name('vehicles.support.units.store');
        
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::post('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log');
        Route::get('/activity-summary', [ActivityLogController::class, 'summary'])->name('activity.summary');

        // Log Pemeriksaan P2H Unit Support & Approval Admin QMA
        Route::get('/p2h-records', [P2hInspectionController::class, 'index'])->name('p2h.index');
        Route::get('/p2h-summary', [P2hInspectionController::class, 'summary'])->name('p2h.summary');
        Route::get('/p2h-records/{id}', [P2hInspectionController::class, 'show'])->name('p2h.show');
        Route::post('/p2h-records/{id}/approve', [P2hInspectionController::class, 'approve'])->name('p2h.approve');
        Route::post('/p2h-records/{id}/reject', [P2hInspectionController::class, 'reject'])->name('p2h.reject');

        // Master 28 Template Checksheet P2H & Kelola Pertanyaan
        Route::get('/p2h-templates', [P2hTemplateController::class, 'index'])->name('p2h.templates');
        Route::get('/p2h-templates/{id}', [P2hTemplateController::class, 'show'])->name('p2h.templates.show');
        Route::post('/p2h-templates/{templateId}/items', [P2hTemplateController::class, 'storeItem'])->name('p2h.templates.items.store');
        Route::match(['PUT', 'POST'], '/p2h-templates/{templateId}/items/{itemId}', [P2hTemplateController::class, 'updateItem'])->name('p2h.templates.items.update');
        Route::delete('/p2h-templates/{templateId}/items/{itemId}', [P2hTemplateController::class, 'destroyItem'])->name('p2h.templates.items.destroy');
        Route::post('/p2h-templates/{templateId}/sections/reorder', [P2hTemplateController::class, 'reorderSections'])->name('p2h.templates.sections.reorder');
    });

    // 4. Modul Private Guard (PG): Admin PG & Pos Gerbang Satpam
    Route::prefix('pg')->name('pg.')->group(function () {
        // Modul Admin PG (Dashboard, Data Satpam & Log Verifikasi Gerbang)
        Route::middleware('role:pg_admin')->group(function () {
            Route::get('/dashboard', [PgDashboardController::class, 'index'])->name('dashboard');
            Route::get('/guards', [SecurityGuardController::class, 'index'])->name('guards.index');
            Route::post('/guards', [SecurityGuardController::class, 'store'])->name('guards.store');
            Route::post('/guards/{id}', [SecurityGuardController::class, 'update'])->name('guards.update');
            Route::post('/guards/{id}/toggle', [SecurityGuardController::class, 'toggleStatus'])->name('guards.toggle');
            Route::delete('/guards/{id}', [SecurityGuardController::class, 'destroy'])->name('guards.destroy');
            Route::get('/verification-logs', [VerificationLogController::class, 'index'])->name('logs.index');
        });

        // Verifikasi Pos Gerbang (Khusus Petugas Satpam Pos Gerbang)
        Route::middleware('role:gate_officer')->group(function () {
            Route::get('/gate-inspection', [GateInspectionController::class, 'index'])->name('gate.inspection');
            Route::get('/gate-inspection/live-queue', [GateInspectionController::class, 'liveQueue'])->name('gate.live_queue');
            Route::post('/gate-inspection/{id}/approve', [GateInspectionController::class, 'approve'])->name('gate.approve');
            Route::post('/gate-inspection/{id}/reject', [GateInspectionController::class, 'reject'])->name('gate.reject');
        });
    });

    // 5. Modul Pengisian Form P2H Unit Support (Operator Wajib Login)
    Route::prefix('p2h')->name('p2h.')->group(function () {
        Route::get('/', [P2hFormController::class, 'index'])->name('index');
        Route::get('/{code}', [P2hFormController::class, 'show'])->name('form');
        Route::post('/{code}', [P2hFormController::class, 'store'])->name('store');
    });

    // 6. Modul Karyawan (Dashboard, P2H, Timesheet)
    Route::get('/karyawan/dashboard', [\App\Http\Controllers\Employee\DashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/timesheet', [VehicleActivityFormController::class, 'index'])->name('timesheet.index');
    Route::get('/mobilisasi', [VehicleActivityFormController::class, 'index'])->name('mobilisasi.index');

    // 7. Modul Profil Saya (Seluruh Pengguna Terautentikasi)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
