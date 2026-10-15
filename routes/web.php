<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Hse\EmployeeController;
use App\Http\Controllers\Pg\DashboardController as PgDashboardController;
use App\Http\Controllers\Pg\GateInspectionController;
use App\Http\Controllers\Pg\SecurityGuardController;
use App\Http\Controllers\Pg\VerificationLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\VehicleActivityFormController;
use App\Http\Controllers\Qma\ActivityLogController;
use App\Http\Controllers\Qma\VehicleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman Utama (Homepage Sistem RKM Publik & Ringkasan Sistem)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Autentikasi Pengguna (Login & Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 1. Modul Publik: Pencatatan Aktivitas Kendaraan Ringan (Via Scan QR Code Mobil Unit di Lapangan)
Route::prefix('gate-activity')->name('vehicle.activity.')->group(function () {
    Route::get('/{code}', [VehicleActivityFormController::class, 'show'])->name('form');
    Route::post('/{code}/check-nik', [VehicleActivityFormController::class, 'checkNik'])->name('check.nik');
    Route::post('/{code}', [VehicleActivityFormController::class, 'store'])->name('store');
});

// Group Terproteksi Autentikasi Sistem
Route::middleware('auth')->group(function () {

    // 2. Modul HSE: Admin HSE - Kelola Karyawan & SIMPER
    Route::prefix('hse')->name('hse.')->group(function () {
        Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard')->middleware('permission:hse.employees.view');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index')->middleware('permission:hse.employees.view');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store')->middleware('permission:hse.employees.create');
        Route::post('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update')->middleware('permission:hse.employees.edit');
    });

    // 3. Modul QMA: Admin QMA - Dashboard, Data Kendaraan & Log Aktivitas
    Route::prefix('qma')->name('qma.')->group(function () {
        Route::get('/dashboard', [VehicleController::class, 'dashboard'])->name('dashboard')->middleware('permission:qma.vehicles.view');
        
        // Halaman Data Kendaraan Terpisah: Light Vehicle (LV) dan Unit Support
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/light-vehicle', [VehicleController::class, 'indexLv'])->name('vehicles.lv')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/unit-support', [VehicleController::class, 'indexSupport'])->name('vehicles.support')->middleware('permission:qma.vehicles.view');
        
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store')->middleware('permission:qma.vehicles.create');
        Route::post('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update')->middleware('permission:qma.vehicles.edit');
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy'])->name('vehicles.destroy')->middleware('permission:qma.vehicles.delete');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log')->middleware('permission:qma.logs.view');
        Route::get('/activity-summary', [ActivityLogController::class, 'summary'])->name('activity.summary')->middleware('permission:qma.summary.view');
    });

    // 4. Modul Private Guard (PG): Admin PG & Pos Gerbang Satpam
    Route::prefix('pg')->name('pg.')->group(function () {
        // Modul Admin PG (Dashboard, Data Satpam & Log Verifikasi Gerbang)
        Route::get('/dashboard', [PgDashboardController::class, 'index'])->name('dashboard')->middleware('permission:pg.guards.view');
        Route::get('/guards', [SecurityGuardController::class, 'index'])->name('guards.index')->middleware('permission:pg.guards.view');
        Route::post('/guards', [SecurityGuardController::class, 'store'])->name('guards.store')->middleware('permission:pg.guards.create');
        Route::post('/guards/{id}', [SecurityGuardController::class, 'update'])->name('guards.update')->middleware('permission:pg.guards.edit');
        Route::post('/guards/{id}/toggle', [SecurityGuardController::class, 'toggleStatus'])->name('guards.toggle')->middleware('permission:pg.guards.toggle');
        Route::delete('/guards/{id}', [SecurityGuardController::class, 'destroy'])->name('guards.destroy')->middleware('permission:pg.guards.delete');
        Route::get('/verification-logs', [VerificationLogController::class, 'index'])->name('logs.index')->middleware('permission:pg.logs.view');

        // Verifikasi Pos Gerbang (Khusus Petugas Satpam Pos Gerbang)
        Route::get('/gate-inspection', [GateInspectionController::class, 'index'])->name('gate.inspection')->middleware('permission:gate.inspect');
        Route::get('/gate-inspection/live-queue', [GateInspectionController::class, 'liveQueue'])->name('gate.live_queue')->middleware('permission:gate.inspect');
        Route::post('/gate-inspection/{id}/approve', [GateInspectionController::class, 'approve'])->name('gate.approve')->middleware('permission:gate.approve');
        Route::post('/gate-inspection/{id}/reject', [GateInspectionController::class, 'reject'])->name('gate.reject')->middleware('permission:gate.reject');
    });

    // 5. Modul Profil Saya (Seluruh Pengguna Terautentikasi)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // 6. Modul Super Admin (RBAC, Manajemen Pengguna & Hak Akses)
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Manajemen User
        Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:users.view');
        Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:users.create');
        Route::post('/users/{id}', [UserController::class, 'update'])->name('users.update')->middleware('permission:users.edit');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('permission:users.delete');

        // Manajemen Roles & Permissions (RBAC Matrix)
        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index')->middleware('permission:roles.view');
        Route::post('/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store')->middleware('permission:roles.create');
        Route::post('/roles/{id}/permissions', [RolePermissionController::class, 'updatePermissions'])->name('roles.permissions.update')->middleware('permission:roles.edit_permissions');
        Route::delete('/roles/{id}', [RolePermissionController::class, 'destroyRole'])->name('roles.destroy')->middleware('permission:roles.delete');
    });
});
