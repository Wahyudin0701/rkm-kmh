<?php

use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Hse\EmployeeController;
use App\Http\Controllers\Hse\SimperTypeController;
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
use App\Http\Controllers\Qma\SupportTimesheetController;
use App\Http\Controllers\Qma\UnitManagementController;
use App\Http\Controllers\Qma\UnitTransferController;
use App\Http\Controllers\Qma\VehicleController;
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

    // Backward Compatibility: Rute Super Admin Legacy -> Admin Dashboard Baru
    Route::prefix('superadmin')->name('superadmin.')->middleware('role:super_admin')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', fn() => redirect()->route('admin.dashboard'))->name('dashboard');
        Route::get('/roles', fn() => redirect()->route('admin.roles.index'))->name('roles.index');
    });

    // 2. Modul HSE: Admin HSE - Kelola Karyawan & SIMPER
    Route::prefix('hse')->name('hse.')->group(function () {
        Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard')->middleware('permission:hse.employees.view');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index')->middleware('permission:hse.employees.view');
        Route::get('/employees/export-excel', [EmployeeController::class, 'exportExcel'])->name('employees.export_excel')->middleware('permission:hse.employees.view');
        Route::get('/employees/export-pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export_pdf')->middleware('permission:hse.employees.view');
        Route::get('/employees/template-import', [EmployeeController::class, 'downloadImportTemplate'])->name('employees.import_template')->middleware('permission:hse.employees.create');
        Route::post('/employees/import', [EmployeeController::class, 'import'])->name('employees.import')->middleware('permission:hse.employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store')->middleware('permission:hse.employees.create');
        Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('employees.edit')->middleware('permission:hse.employees.edit');
        Route::post('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update')->middleware('permission:hse.employees.edit');

        // Master Jenis SIMPER (Konfigurasi Fleksibel HSE)
        Route::get('/simper-types', [SimperTypeController::class, 'index'])->name('simper_types.index')->middleware('permission:hse.employees.view');
        Route::post('/simper-types', [SimperTypeController::class, 'store'])->name('simper_types.store')->middleware('permission:hse.employees.create|hse.employees.edit');
        Route::put('/simper-types/{id}', [SimperTypeController::class, 'update'])->name('simper_types.update')->middleware('permission:hse.employees.edit');
        Route::delete('/simper-types/{id}', [SimperTypeController::class, 'destroy'])->name('simper_types.destroy')->middleware('permission:hse.employees.edit');
    });

    // 3. Modul QMA: Admin QMA - Dashboard, Data Kendaraan & Log Aktivitas
    Route::prefix('qma')->name('qma.')->group(function () {
        Route::get('/dashboard', [VehicleController::class, 'dashboard'])->name('dashboard')->middleware('permission:qma.vehicles.view');
        
        // Halaman Data Kendaraan Terpisah: Light Vehicle (LV) dan Heavy Equipment
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/light-vehicle', [VehicleController::class, 'indexLv'])->name('vehicles.lv')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/light-vehicle/export-excel', [VehicleController::class, 'exportLvExcel'])->name('vehicles.lv.export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/light-vehicle/export-pdf', [VehicleController::class, 'exportLvPdf'])->name('vehicles.lv.export_pdf')->middleware('permission:qma.vehicles.view');

        Route::get('/vehicles/unit-support', [VehicleController::class, 'indexSupport'])->name('vehicles.support')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/unit-support/export-excel', [VehicleController::class, 'exportSupportExcel'])->name('vehicles.support.export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/unit-support/export-pdf', [VehicleController::class, 'exportSupportPdf'])->name('vehicles.support.export_pdf')->middleware('permission:qma.vehicles.view');

        Route::get('/vehicles/unit-support/{id}/units', [VehicleController::class, 'indexSupportUnits'])->name('vehicles.support.units')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/unit-support/{id}/units/export-excel', [VehicleController::class, 'exportSupportUnitsExcel'])->name('vehicles.support.units.export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/vehicles/unit-support/{id}/units/export-pdf', [VehicleController::class, 'exportSupportUnitsPdf'])->name('vehicles.support.units.export_pdf')->middleware('permission:qma.vehicles.view');
        Route::post('/vehicles/unit-support/{id}/units', [VehicleController::class, 'storeSupportUnit'])->name('vehicles.support.units.store')->middleware('permission:qma.vehicles.create');
        
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store')->middleware('permission:qma.vehicles.create');
        Route::post('/vehicles/{id}', [VehicleController::class, 'update'])->name('vehicles.update')->middleware('permission:qma.vehicles.edit');
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy'])->name('vehicles.destroy')->middleware('permission:qma.vehicles.delete');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity.log')->middleware('permission:qma.logs.view');
        Route::get('/activity-log/support-timesheet', [SupportTimesheetController::class, 'index'])->name('activity.support_timesheet')->middleware('permission:qma.logs.view');
        Route::post('/activity-log/{id}/correct-odometer', [ActivityLogController::class, 'correctOdometer'])->name('activity.correct_odometer')->middleware('permission:qma.logs.edit_odometer');
        Route::delete('/activity-log/{id}', [ActivityLogController::class, 'destroy'])->name('activity.destroy')->middleware('permission:qma.logs.delete');
        Route::get('/activity-log/export-excel', [ActivityLogController::class, 'exportExcel'])->name('activity.export_excel')->middleware('permission:qma.logs.view');
        Route::get('/activity-log/export-pdf', [ActivityLogController::class, 'exportPdf'])->name('activity.export_pdf')->middleware('permission:qma.logs.view');
        Route::get('/activity-summary', [ActivityLogController::class, 'summary'])->name('activity.summary')->middleware('permission:qma.summary.view');
        Route::get('/activity-summary/export-excel', [ActivityLogController::class, 'exportSummaryExcel'])->name('activity.summary.export_excel')->middleware('permission:qma.summary.view');
        Route::get('/activity-summary/export-pdf', [ActivityLogController::class, 'exportSummaryPdf'])->name('activity.summary.export_pdf')->middleware('permission:qma.summary.view');

        // Timesheet Heavy Equipment (Pencatatan Jam Operasi Alat Berat / Support oleh QMA)
        Route::get('/support-timesheet', [SupportTimesheetController::class, 'index'])->name('support_timesheet.index')->middleware('permission:qma.logs.view');
        Route::post('/support-timesheet', [SupportTimesheetController::class, 'store'])->name('support_timesheet.store')->middleware('permission:qma.logs.edit_odometer|qma.vehicles.create');
        Route::put('/support-timesheet/{id}', [SupportTimesheetController::class, 'update'])->name('support_timesheet.update')->middleware('permission:qma.logs.edit_odometer|qma.vehicles.edit');
        Route::delete('/support-timesheet/{id}', [SupportTimesheetController::class, 'destroy'])->name('support_timesheet.destroy')->middleware('permission:qma.logs.edit_odometer|qma.vehicles.delete');
        Route::get('/support-timesheet/export-excel', [SupportTimesheetController::class, 'exportExcel'])->name('support_timesheet.export_excel')->middleware('permission:qma.logs.view');
        Route::get('/support-timesheet/export-pdf', [SupportTimesheetController::class, 'exportPdf'])->name('support_timesheet.export_pdf')->middleware('permission:qma.logs.view');

        // Manajemen Unit: Posisi Unit (Tracking & Monitoring Posisi LV & Heavy Equipment)
        Route::get('/unit-management', [UnitManagementController::class, 'index'])->name('unit_management.index')->middleware('permission:qma.vehicles.view');
        Route::post('/unit-management/{id}/location', [UnitManagementController::class, 'updateLocation'])->name('unit_management.update_location')->middleware('permission:qma.vehicles.edit');
        Route::get('/unit-management/{id}/history', [UnitManagementController::class, 'history'])->name('unit_management.history')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-management/{id}/riwayat', [UnitManagementController::class, 'historyView'])->name('unit_management.history_view')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-management/{id}/riwayat/export-excel', [UnitManagementController::class, 'exportHistoryExcel'])->name('unit_management.history_export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-management/{id}/riwayat/export-pdf', [UnitManagementController::class, 'exportHistoryPdf'])->name('unit_management.history_export_pdf')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-management/export-excel', [UnitManagementController::class, 'exportExcel'])->name('unit_management.export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-management/export-pdf', [UnitManagementController::class, 'exportPdf'])->name('unit_management.export_pdf')->middleware('permission:qma.vehicles.view');

        // Manajemen Unit: Transfer Unit Alat Berat (Pemindahan Lokasi, Truk Pengangkut & Berita Acara BAST)
        Route::get('/unit-transfers', [UnitTransferController::class, 'index'])->name('unit_transfers.index')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-transfers/create', [UnitTransferController::class, 'create'])->name('unit_transfers.create')->middleware('permission:qma.vehicles.edit|qma.vehicles.create');
        Route::post('/unit-transfers', [UnitTransferController::class, 'store'])->name('unit_transfers.store')->middleware('permission:qma.vehicles.edit|qma.vehicles.create');
        Route::get('/unit-transfers/export-excel', [UnitTransferController::class, 'exportExcel'])->name('unit_transfers.export_excel')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-transfers/export-pdf', [UnitTransferController::class, 'exportPdf'])->name('unit_transfers.export_pdf')->middleware('permission:qma.vehicles.view');
        Route::get('/unit-transfers/{id}', [UnitTransferController::class, 'show'])->name('unit_transfers.show')->middleware('permission:qma.vehicles.view');
        Route::delete('/unit-transfers/{id}', [UnitTransferController::class, 'destroy'])->name('unit_transfers.destroy')->middleware('permission:qma.vehicles.delete');

        // Log Pemeriksaan P2H Heavy Equipment & Approval Admin QMA
        Route::get('/p2h-records', [P2hInspectionController::class, 'index'])->name('p2h.index')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-records/export-excel', [P2hInspectionController::class, 'exportExcel'])->name('p2h.export_excel')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-records/export-pdf', [P2hInspectionController::class, 'exportPdf'])->name('p2h.export_pdf')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-summary', [P2hInspectionController::class, 'summary'])->name('p2h.summary')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-summary/export-excel', [P2hInspectionController::class, 'exportSummaryExcel'])->name('p2h.summary.export_excel')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-summary/export-pdf', [P2hInspectionController::class, 'exportSummaryPdf'])->name('p2h.summary.export_pdf')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-records/{id}', [P2hInspectionController::class, 'show'])->name('p2h.show')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-records/{id}/export-pdf', [P2hInspectionController::class, 'exportSinglePdf'])->name('p2h.export_single_pdf')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::get('/p2h-records/{id}/export-excel', [P2hInspectionController::class, 'exportSingleExcel'])->name('p2h.export_single_excel')->middleware('permission:qma.p2h.view|qma.vehicles.view');
        Route::post('/p2h-records/{id}/approve', [P2hInspectionController::class, 'approve'])->name('p2h.approve')->middleware('permission:qma.p2h.approve|qma.vehicles.edit');
        Route::post('/p2h-records/{id}/reject', [P2hInspectionController::class, 'reject'])->name('p2h.reject')->middleware('permission:qma.p2h.approve|qma.vehicles.edit');

        // Master 28 Template Checksheet P2H & Kelola Pertanyaan
        Route::get('/p2h-templates', [P2hTemplateController::class, 'index'])->name('p2h.templates')->middleware('permission:qma.p2h.templates|qma.vehicles.view');
        Route::get('/p2h-templates/{id}', [P2hTemplateController::class, 'show'])->name('p2h.templates.show')->middleware('permission:qma.p2h.templates|qma.vehicles.view');
        Route::post('/p2h-templates/{templateId}/items', [P2hTemplateController::class, 'storeItem'])->name('p2h.templates.items.store')->middleware('permission:qma.p2h.templates|qma.vehicles.create');
        Route::match(['PUT', 'POST'], '/p2h-templates/{templateId}/items/{itemId}', [P2hTemplateController::class, 'updateItem'])->name('p2h.templates.items.update')->middleware('permission:qma.p2h.templates|qma.vehicles.edit');
        Route::delete('/p2h-templates/{templateId}/items/{itemId}', [P2hTemplateController::class, 'destroyItem'])->name('p2h.templates.items.destroy')->middleware('permission:qma.p2h.templates|qma.vehicles.delete');
        Route::post('/p2h-templates/{templateId}/sections/reorder', [P2hTemplateController::class, 'reorderSections'])->name('p2h.templates.sections.reorder')->middleware('permission:qma.p2h.templates|qma.vehicles.edit');
    });

    // 4. Modul Private Guard (PG): Admin PG & Pos Gerbang Satpam
    Route::prefix('pg')->name('pg.')->group(function () {
        // Modul Admin PG (Dashboard, Data Satpam & Log Verifikasi Gerbang)
        Route::get('/dashboard', [PgDashboardController::class, 'index'])->name('dashboard')->middleware('permission:pg.guards.view');
        Route::get('/guards', [SecurityGuardController::class, 'index'])->name('guards.index')->middleware('permission:pg.guards.view');
        Route::get('/guards/export-excel', [SecurityGuardController::class, 'exportExcel'])->name('guards.export_excel')->middleware('permission:pg.guards.view');
        Route::get('/guards/export-pdf', [SecurityGuardController::class, 'exportPdf'])->name('guards.export_pdf')->middleware('permission:pg.guards.view');
        Route::post('/guards', [SecurityGuardController::class, 'store'])->name('guards.store')->middleware('permission:pg.guards.create');
        Route::post('/guards/{id}', [SecurityGuardController::class, 'update'])->name('guards.update')->middleware('permission:pg.guards.edit');
        Route::post('/guards/{id}/toggle', [SecurityGuardController::class, 'toggleStatus'])->name('guards.toggle')->middleware('permission:pg.guards.toggle');
        Route::delete('/guards/{id}', [SecurityGuardController::class, 'destroy'])->name('guards.destroy')->middleware('permission:pg.guards.delete');
        Route::get('/verification-logs', [VerificationLogController::class, 'index'])->name('logs.index')->middleware('permission:pg.logs.view');
        Route::delete('/verification-logs/{id}', [VerificationLogController::class, 'destroy'])->name('logs.destroy')->middleware('permission:pg.logs.delete');
        Route::get('/verification-logs/export-excel', [VerificationLogController::class, 'exportExcel'])->name('logs.export_excel')->middleware('permission:pg.logs.view');
        Route::get('/verification-logs/export-pdf', [VerificationLogController::class, 'exportPdf'])->name('logs.export_pdf')->middleware('permission:pg.logs.view');

        // Verifikasi Pos Gerbang (Khusus Petugas Satpam Pos Gerbang)
        Route::get('/gate-inspection', [GateInspectionController::class, 'index'])->name('gate.inspection')->middleware('permission:gate.inspect');
        Route::post('/gate-inspection/select-gate', [GateInspectionController::class, 'selectGate'])->name('gate.select_gate')->middleware('permission:gate.inspect');
        Route::get('/gate-inspection/live-queue', [GateInspectionController::class, 'liveQueue'])->name('gate.live_queue')->middleware('permission:gate.inspect');
        Route::post('/gate-inspection/{id}/approve', [GateInspectionController::class, 'approve'])->name('gate.approve')->middleware('permission:gate.approve');
        Route::post('/gate-inspection/{id}/reject', [GateInspectionController::class, 'reject'])->name('gate.reject')->middleware('permission:gate.reject');
    });

    // 5. Modul Pengisian Form P2H Heavy Equipment (Operator Wajib Login)
    Route::prefix('p2h')->name('p2h.')->middleware('permission:employee.p2h|qma.vehicles.view|qma.p2h.view')->group(function () {
        Route::get('/', [P2hFormController::class, 'index'])->name('index');
        Route::get('/{code}', [P2hFormController::class, 'show'])->name('form');
        Route::post('/{code}', [P2hFormController::class, 'store'])->name('store');
    });

    // 6. Modul Karyawan (Dashboard, P2H, Timesheet)
    Route::get('/karyawan/dashboard', [\App\Http\Controllers\Employee\DashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/karyawan/aktivitas/status-polling', [\App\Http\Controllers\Employee\DashboardController::class, 'activeTripStatus'])->name('employee.activity.status_polling');
    Route::post('/karyawan/aktivitas/{id}/update-km', [\App\Http\Controllers\Employee\DashboardController::class, 'updateKm'])->name('employee.activity.update_km');
    Route::post('/karyawan/aktivitas/{id}/cancel', [\App\Http\Controllers\Employee\DashboardController::class, 'cancelActivity'])->name('employee.activity.cancel');
    Route::get('/timesheet', [VehicleActivityFormController::class, 'index'])->name('timesheet.index')->middleware('permission:employee.timesheet|qma.vehicles.view|qma.logs.view');
    Route::get('/mobilisasi', [VehicleActivityFormController::class, 'index'])->name('mobilisasi.index')->middleware('permission:employee.timesheet|qma.vehicles.view|qma.logs.view');

    // 7. Modul Profil Saya (Seluruh Pengguna Terautentikasi)
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

        // Log Riwayat Aktivitas Super Admin
        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');
    });
});
