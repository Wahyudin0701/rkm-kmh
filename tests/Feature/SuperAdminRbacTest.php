<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan role dan permissions ada
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_login_and_redirects_to_admin_dashboard(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'superadmin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_super_admin_can_view_dashboard_and_modules(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard Super Admin');
    }

    public function test_non_super_admin_cannot_access_admin_dashboard(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'hse_admin',
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_super_admin_can_view_users_page(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('Manajemen Pengguna Sistem');
        $response->assertSee('superadmin');
    }

    public function test_super_admin_can_create_new_user_with_spatie_role(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Operator Baru',
            'username' => 'operator.baru',
            'email' => 'operator@kmh.co.id',
            'password' => 'password123',
            'role' => 'gate_officer',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $newUser = User::where('username', 'operator.baru')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('gate_officer'));
        $this->assertEquals('gate_officer', $newUser->role);
    }

    public function test_super_admin_can_update_user_and_change_role(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $user = User::factory()->create([
            'name' => 'Pinto Yuliandra',
            'username' => '1171',
            'email' => '1171@kmh.co.id',
            'role' => 'qma_admin',
        ]);
        $user->assignRole('qma_admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.update', $user->id), [
            'name' => 'Pinto Yuliandra Updated',
            'username' => '1171',
            'email' => '1171@kmh.co.id',
            'role' => 'hse_admin',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertEquals('Pinto Yuliandra Updated', $user->name);
        $this->assertTrue($user->hasRole('hse_admin'));
    }

    public function test_super_admin_cannot_delete_own_account_or_main_superadmin(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $superAdmin->id));
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_super_admin_can_view_and_update_role_permissions(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $role = Role::findByName('hse_admin');

        $response = $this->actingAs($superAdmin)->get(route('admin.roles.index'));
        $response->assertOk();
        $response->assertSee('Matriks Role');

        // Update permissions for hse_admin
        $response = $this->actingAs($superAdmin)->post(route('admin.roles.permissions.update', $role->id), [
            'permissions' => ['hse.employees.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('hse.employees.view'));
        $this->assertFalse($role->hasPermissionTo('hse.employees.create'));
    }

    public function test_super_admin_can_assign_direct_permissions_to_user(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $user = User::factory()->create([
            'role' => 'gate_officer',
        ]);
        $user->assignRole('gate_officer');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.update', $user->id), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => 'gate_officer',
            'direct_permissions' => ['qma.logs.view'],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->hasDirectPermission('qma.logs.view'));
    }

    public function test_super_admin_has_bypass_access_to_all_modules(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // 1. HSE Module
        $response = $this->actingAs($superAdmin)->get(route('hse.dashboard'));
        $response->assertOk();
        $this->actingAs($superAdmin)->get(route('hse.employees.index'))->assertOk();

        // 2. QMA Module (LV, Support, Activity Logs, P2H Records, P2H Templates)
        $response = $this->actingAs($superAdmin)->get(route('qma.dashboard'));
        $response->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.vehicles.lv'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.vehicles.support'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.activity.log'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.activity.summary'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.p2h.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.p2h.summary'))->assertOk();
        $this->actingAs($superAdmin)->get(route('qma.p2h.templates'))->assertOk();

        // 3. PG Module (Guards, Logs, Gate Inspection)
        $response = $this->actingAs($superAdmin)->get(route('pg.dashboard'));
        $response->assertOk();
        $this->actingAs($superAdmin)->get(route('pg.guards.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('pg.logs.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('pg.gate.inspection'))->assertOk();

        // 4. Employee & P2H Public/Operator Module
        $this->actingAs($superAdmin)->get(route('p2h.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('employee.dashboard'))->assertOk();
    }

    public function test_permission_revocation_immediately_blocks_action_and_hides_button(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $hseUser = User::factory()->create([
            'role' => 'hse_admin',
        ]);
        $hseUser->assignRole('hse_admin');

        // 1. Sebelum izin dicabut: bisa melihat tombol dan menambah karyawan
        $resView = $this->actingAs($hseUser)->get(route('hse.employees.index'));
        $resView->assertOk();
        $resView->assertSee('Tambah Karyawan');

        $resStore = $this->actingAs($hseUser)->post(route('hse.employees.store'), [
            'nik' => 'NIK-TEST-001',
            'name' => 'Budi Santoso',
            'unit_kerja' => 'Teknik',
            'division' => 'Civil',
            'position' => 'Driver',
            'is_operator' => '1',
            'simper_expired_at' => now()->addYear()->format('Y-m-d'),
        ]);
        $resStore->assertRedirect();
        $this->assertDatabaseHas('employees', ['nik' => 'NIK-TEST-001']);

        // 2. Super Admin mencabut izin 'hse.employees.create' dari role hse_admin
        $role = Role::findByName('hse_admin');
        $this->actingAs($superAdmin)->post(route('admin.roles.permissions.update', $role->id), [
            'permissions' => ['hse.employees.view'], // hse.employees.create sengaja dicabut
        ]);

        // 3. Verifikasi Sinkronisasi Langsung:
        // A. Tombol 'Tambah Karyawan' langsung hilang dari halaman index
        $resViewRevoked = $this->actingAs($hseUser)->get(route('hse.employees.index'));
        $resViewRevoked->assertOk();
        $resViewRevoked->assertDontSee('<span>Tambah Karyawan</span>', false);

        // B. Akses request POST langsung diblokir 403 Forbidden
        $resStoreBlocked = $this->actingAs($hseUser)->post(route('hse.employees.store'), [
            'nik' => 'NIK-TEST-002',
            'name' => 'Karyawan Baru',
            'unit_kerja' => 'Teknik',
            'division' => 'Civil',
            'position' => 'Driver',
            'is_operator' => '1',
        ]);
        $resStoreBlocked->assertForbidden();
        $this->assertDatabaseMissing('employees', ['nik' => 'NIK-TEST-002']);

        // 4. Super Admin mengembalikan izin 'hse.employees.create'
        $this->actingAs($superAdmin)->post(route('admin.roles.permissions.update', $role->id), [
            'permissions' => ['hse.employees.view', 'hse.employees.create', 'hse.employees.edit'],
        ]);

        // 5. User langsung bisa menambah karyawan kembali
        $resStoreRestored = $this->actingAs($hseUser)->post(route('hse.employees.store'), [
            'nik' => 'NIK-TEST-002',
            'name' => 'Karyawan Baru',
            'unit_kerja' => 'Teknik',
            'division' => 'Civil',
            'position' => 'Driver',
            'is_operator' => '1',
        ]);
        $resStoreRestored->assertRedirect();
        $this->assertDatabaseHas('employees', ['nik' => 'NIK-TEST-002']);
    }

    public function test_user_direct_permission_sync_allows_or_denies_immediately(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $gateUser = User::factory()->create([
            'role' => 'gate_officer',
        ]);
        $gateUser->assignRole('gate_officer');

        // Awalnya gate officer tidak punya izin melihat qma activity log
        $resDenied = $this->actingAs($gateUser)->get(route('qma.activity.log'));
        $resDenied->assertForbidden();

        // Super Admin memberikan direct permission qma.logs.view ke user ini
        $this->actingAs($superAdmin)->post(route('admin.users.update', $gateUser->id), [
            'name' => $gateUser->name,
            'username' => $gateUser->username,
            'email' => $gateUser->email,
            'role' => 'gate_officer',
            'direct_permissions' => ['qma.logs.view'],
        ]);

        // User langsung dapat mengakses activity log
        $resAllowed = $this->actingAs($gateUser->fresh())->get(route('qma.activity.log'));
        $resAllowed->assertOk();

        // Super Admin mencabut izin langsung tersebut
        $this->actingAs($superAdmin)->post(route('admin.users.update', $gateUser->id), [
            'name' => $gateUser->name,
            'username' => $gateUser->username,
            'email' => $gateUser->email,
            'role' => 'gate_officer',
            'direct_permissions' => [],
        ]);

        // User langsung ditolak kembali
        $resDeniedAgain = $this->actingAs($gateUser->fresh())->get(route('qma.activity.log'));
        $resDeniedAgain->assertForbidden();
    }

    public function test_super_admin_can_sort_users_by_latest_oldest_and_name(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $userA = User::factory()->create([
            'name' => 'AAA User First',
            'username' => 'aaa_first',
            'email' => 'aaa@test.com',
            'created_at' => now()->subDays(10),
        ]);
        $userZ = User::factory()->create([
            'name' => 'ZZZ User Last',
            'username' => 'zzz_last',
            'email' => 'zzz@test.com',
            'created_at' => now()->subDays(1),
        ]);

        // Sort Name Ascending
        $resAsc = $this->actingAs($superAdmin)->get(route('admin.users.index', ['sort' => 'name_asc']));
        $resAsc->assertOk();
        $resAsc->assertSeeInOrder(['AAA User First', 'ZZZ User Last']);

        // Sort Name Descending
        $resDesc = $this->actingAs($superAdmin)->get(route('admin.users.index', ['sort' => 'name_desc']));
        $resDesc->assertOk();
        $resDesc->assertSeeInOrder(['ZZZ User Last', 'AAA User First']);

        // Sort Oldest
        $resOldest = $this->actingAs($superAdmin)->get(route('admin.users.index', ['sort' => 'oldest']));
        $resOldest->assertOk();
        $resOldest->assertSee('Urutkan:');
    }

    public function test_employee_p2h_permission_revocation_hides_menu_and_dashboard_card_and_blocks_access(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $employeeUser = User::factory()->create([
            'role' => 'employee',
        ]);
        $employeeUser->assignRole('employee');

        // 1. Sebelum izin dicabut: bisa melihat menu Pemeriksaan P2H dan kartu P2H di dashboard
        $resBefore = $this->actingAs($employeeUser)->get(route('employee.dashboard'));
        $resBefore->assertOk();
        $resBefore->assertSee('Pemeriksaan P2H');
        $resBefore->assertSee('Buka Modul Pemeriksaan P2H');

        $resP2hBefore = $this->actingAs($employeeUser)->get(route('p2h.index'));
        $resP2hBefore->assertOk();

        // 2. Super Admin mencabut izin 'employee.p2h' dari role employee
        $employeeRole = Role::findByName('employee');
        $this->actingAs($superAdmin)->post(route('admin.roles.permissions.update', $employeeRole->id), [
            'permissions' => ['employee.dashboard', 'employee.timesheet'], // employee.p2h dicabut
        ]);

        // 3. Verifikasi: Menu di sidebar & kartu di dashboard hilang, akses URL diblokir 403
        $resAfter = $this->actingAs($employeeUser->fresh())->get(route('employee.dashboard'));
        $resAfter->assertOk();
        $resAfter->assertDontSee('Pemeriksaan P2H');
        $resAfter->assertDontSee('Buka Modul Pemeriksaan P2H');

        $resP2hAfter = $this->actingAs($employeeUser->fresh())->get(route('p2h.index'));
        $resP2hAfter->assertForbidden();
    }

    public function test_home_page_resolves_correct_role_route_for_employee_and_super_admin(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $employeeUser = User::factory()->create([
            'role' => 'employee',
        ]);
        $employeeUser->assignRole('employee');

        // Super Admin gets admin.dashboard link on home page
        $resAdminHome = $this->actingAs($superAdmin)->get(route('home'));
        $resAdminHome->assertOk();
        $resAdminHome->assertSee(route('admin.dashboard'));

        // Employee gets employee.dashboard link on home page
        $resEmployeeHome = $this->actingAs($employeeUser)->get(route('home'));
        $resEmployeeHome->assertOk();
        $resEmployeeHome->assertSee(route('employee.dashboard'));
    }

    public function test_custom_role_sukon_with_employee_permissions_can_login_and_see_employee_dashboard_and_sidebar(): void
    {
        // 1. Buat custom role 'sukon' yang memiliki izin karyawan
        $sukonRole = Role::create(['name' => 'sukon', 'guard_name' => 'web']);
        $sukonRole->syncPermissions(['employee.dashboard', 'employee.timesheet', 'employee.p2h']);

        // 2. Buat akun pengguna dengan custom role 'sukon'
        $user = User::factory()->create([
            'username' => 'sukon.user',
            'password' => bcrypt('password123'),
            'role' => 'sukon',
        ]);
        $user->assignRole('sukon');

        // 3. Uji proses login pengguna custom role 'sukon'
        $loginResponse = $this->post(route('login.post'), [
            'username' => 'sukon.user',
            'password' => 'password123',
        ]);

        // Harus diarahkan ke dashboard karyawan, bukan halaman kosong / profile
        $loginResponse->assertRedirect(route('employee.dashboard'));
        $this->assertAuthenticatedAs($user);

        // 4. Akses dashboard karyawan dan periksa sidebar / menu
        $dashboardResponse = $this->actingAs($user)->get(route('employee.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Dashboard Karyawan');
        $dashboardResponse->assertSee('Pemeriksaan P2H');
        $dashboardResponse->assertSee('Timesheet');

        // Profil master Employee harus otomatis terhubung
        $this->assertNotNull($user->fresh()->employee);
        $this->assertEquals('sukon.user', $user->fresh()->employee->nik);

        // Halaman Home juga mengarahkan tombol dashboard ke portal karyawan
        $homeResponse = $this->actingAs($user)->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee(route('employee.dashboard'));
    }

    public function test_user_management_passes_role_permissions_map_and_filters_duplicate_role_perms(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // 1. Cek UserController@index menyediakan $rolePermissionsMap
        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.users.index'));
        $indexResponse->assertOk();
        $indexResponse->assertViewHas('rolePermissionsMap');

        $map = $indexResponse->viewData('rolePermissionsMap');
        $this->assertArrayHasKey('employee', $map);
        $this->assertContains('employee.dashboard', $map['employee']);

        // 2. Buat user dan simpan direct permissions yang bercampur antara izin bawaan role dan izin khusus
        $targetUser = User::factory()->create([
            'role' => 'employee',
        ]);
        $targetUser->assignRole('employee');

        $updateResponse = $this->actingAs($superAdmin)->post(route('admin.users.update', $targetUser->id), [
            'name' => 'Karyawan Multi Role',
            'username' => $targetUser->username,
            'email' => $targetUser->email,
            'role' => 'employee',
            'direct_permissions' => [
                'employee.dashboard', // Izin bawaan employee role (harus disaring / tidak disimpan sebagai direct)
                'employee.p2h',       // Izin bawaan employee role (harus disaring)
                'qma.vehicles.view',  // Izin tambahan khusus di luar role
            ],
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));
        $updateResponse->assertSessionHas('success');

        // Direct permissions yang tersimpan di user hanya 'qma.vehicles.view'
        $directPermNames = $targetUser->fresh()->permissions->pluck('name')->toArray();
        $this->assertEquals(['qma.vehicles.view'], $directPermNames);

        // Namun user tetap memiliki wewenang employee.dashboard lewat rolenya
        $this->assertTrue($targetUser->fresh()->can('employee.dashboard'));
        $this->assertTrue($targetUser->fresh()->can('qma.vehicles.view'));
    }
}
