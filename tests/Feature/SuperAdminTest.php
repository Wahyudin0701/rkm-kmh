<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan Super Admin dapat login dan diarahkan langsung ke dashboard Super Admin.
     */
    public function test_super_admin_can_login_and_redirects_to_superadmin_dashboard(): void
    {
        $response = $this->post('/login', [
            'username' => 'superadmin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('superadmin', $user->username);
        $this->assertEquals('super_admin', $user->role);
        $this->assertTrue($user->isSuperAdmin());
    }

    /**
     * Memastikan user non-super-admin ditolak saat mengakses rute /superadmin.
     */
    public function test_non_super_admin_cannot_access_superadmin_dashboard(): void
    {
        $hseUser = User::where('role', 'hse_admin')->first();

        $response = $this->actingAs($hseUser)->get('/superadmin');
        $response->assertStatus(403);
    }

    /**
     * Memastikan Super Admin dapat melihat halaman dashboard admin kosong (placeholder awal).
     */
    public function test_super_admin_can_view_admin_dashboard(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $response = $this->actingAs($superAdmin)->get('/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Admin');
        $response->assertSee('Halaman Dashboard Sedang Disiapkan');
        $response->assertSee('Buka Manajemen Role');
    }

    /**
     * Memastikan Super Admin dapat melihat halaman manajemen role & hak akses.
     */
    public function test_super_admin_can_view_role_management_page(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $response = $this->actingAs($superAdmin)->get('/superadmin/roles');

        $response->assertStatus(200);
        $response->assertSee('Manajemen Role');
        $response->assertSee('Hak Akses');
        $response->assertSee('Tambah Akun Baru');
        $response->assertSee('openDetailUserModal');
        $response->assertSee('detailUserModal');
        $response->assertSee('Detail Akun');
        $response->assertDontSee('HIRARKI TERTINGGI SISTEM');
        $response->assertDontSee('Pintasan Modul Langsung');
    }

    /**
     * Memastikan Super Admin dapat mengakses seluruh modul departemen (HSE, QMA, PG, Gate).
     */
    public function test_super_admin_has_full_access_to_all_modules(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        // 1. Modul HSE
        $this->actingAs($superAdmin)->get('/hse/dashboard')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/hse/employees')->assertStatus(200);

        // 2. Modul QMA
        $this->actingAs($superAdmin)->get('/qma/dashboard')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/qma/vehicles/light-vehicle')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/qma/activity-log')->assertStatus(200);

        // 3. Modul PG
        $this->actingAs($superAdmin)->get('/pg/dashboard')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/pg/guards')->assertStatus(200);
        $this->actingAs($superAdmin)->get('/pg/verification-logs')->assertStatus(200);

        // 4. Modul Pos Gerbang
        $this->actingAs($superAdmin)->get('/pg/gate-inspection')->assertStatus(200);
    }

    /**
     * Memastikan Super Admin dapat membuat akun pengguna baru.
     */
    public function test_super_admin_can_create_new_user(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $response = $this->actingAs($superAdmin)->post('/superadmin/users', [
            'name' => 'Admin Baru HSE',
            'username' => 'hse.baru',
            'email' => 'hse.baru@kmh.co.id',
            'password' => 'secret123',
            'role' => 'hse_admin',
        ]);

        $response->assertRedirect('/superadmin/roles');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'hse.baru',
            'email' => 'hse.baru@kmh.co.id',
            'role' => 'hse_admin',
        ]);

        // Verifikasi ketika password tidak dikirim, otomatis memakai default 'password'
        $respDefault = $this->actingAs($superAdmin)->post('/superadmin/users', [
            'name' => 'Admin Baru QMA',
            'username' => 'qma.baru',
            'email' => 'qma.baru@kmh.co.id',
            'role' => 'qma_admin',
        ]);
        $respDefault->assertRedirect('/superadmin/roles');

        $newUser = User::where('username', 'qma.baru')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('password', $newUser->password));
    }

    /**
     * Memastikan Super Admin dapat memperbarui akun pengguna.
     */
    public function test_super_admin_can_update_user(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();
        $targetUser = User::where('username', '1185')->first();

        $response = $this->actingAs($superAdmin)->put("/superadmin/users/{$targetUser->id}", [
            'name' => 'Ibu Mima Updated',
            'username' => '1185',
            'email' => '1185.updated@kmh.co.id',
            'role' => 'hse_admin',
        ]);

        $response->assertRedirect('/superadmin/roles');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Ibu Mima Updated',
            'email' => '1185.updated@kmh.co.id',
        ]);
    }

    /**
     * Memastikan Super Admin tidak dapat menghapus akunnya sendiri.
     */
    public function test_super_admin_cannot_delete_own_account(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $response = $this->actingAs($superAdmin)->delete("/superadmin/users/{$superAdmin->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
        ]);
    }

    /**
     * Memastikan Super Admin dapat menghapus akun pengguna lain.
     */
    public function test_super_admin_can_delete_other_user(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $dummyUser = User::create([
            'name' => 'User Dihapus',
            'username' => 'user.hapus',
            'email' => 'hapus@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'gate_officer',
        ]);

        $response = $this->actingAs($superAdmin)->delete("/superadmin/users/{$dummyUser->id}");

        $response->assertRedirect('/superadmin/roles');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $dummyUser->id,
        ]);
    }

    /**
     * Memastikan ketika akun Karyawan dihapus, record di tabel Employee juga terhapus (sinkron).
     */
    public function test_deleting_employee_user_also_deletes_employee_record(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $employeeUser = User::create([
            'name' => 'Karyawan Uji Hapus',
            'username' => 'NIK-HAPUS-01',
            'email' => 'nikhapus01@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        $employee = \App\Models\Employee::create([
            'user_id' => $employeeUser->id,
            'nik' => 'NIK-HAPUS-01',
            'name' => 'Karyawan Uji Hapus',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Mechanical',
            'position' => 'Teknisi',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($superAdmin)->delete("/superadmin/users/{$employeeUser->id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $employeeUser->id]);
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    /**
     * Memastikan akun Karyawan yang memiliki rekam jejak aktivitas operasional TIDAK DAPAT dihapus.
     */
    public function test_cannot_delete_employee_user_with_activity_history(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();
        $gate = \App\Models\Gate::first();
        $vehicle = \App\Models\Vehicle::first();

        $employeeUser = User::create([
            'name' => 'Driver Audit Trail',
            'username' => 'NIK-AUDIT-01',
            'email' => 'audit01@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        $employee = \App\Models\Employee::create([
            'user_id' => $employeeUser->id,
            'nik' => 'NIK-AUDIT-01',
            'name' => 'Driver Audit Trail',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Operasional',
            'position' => 'Driver',
            'is_operator' => true,
        ]);

        \App\Models\VehicleActivity::create([
            'transaction_no' => 'KMH-ACT-TEST-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $employee->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'previous_km' => 100,
            'current_km' => 150,
            'distance' => 50,
            'purpose' => 'Uji Coba Audit Trail',
            'recorded_at' => now(),
            'validation_status' => 'approved',
        ]);

        $response = $this->actingAs($superAdmin)->delete("/superadmin/users/{$employeeUser->id}");
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $employeeUser->id]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('vehicle_activities', ['transaction_no' => 'KMH-ACT-TEST-001']);
    }

    /**
     * Memastikan Super Admin dapat mereset password akun pengguna ke default "password".
     */
    public function test_super_admin_can_reset_user_password_to_default(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        $user = User::create([
            'name' => 'Akun Uji Reset',
            'username' => 'uji.reset',
            'email' => 'uji.reset@kmh.co.id',
            'password' => Hash::make('customPassword123!'),
            'role' => 'gate_officer',
        ]);

        $this->assertTrue(Hash::check('customPassword123!', $user->password));

        $response = $this->actingAs($superAdmin)->post("/superadmin/users/{$user->id}/reset-password");

        $response->assertRedirect('/superadmin/roles');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * Memastikan Super Admin dapat melihat, memfilter, dan membuat akun role Karyawan (employee).
     */
    public function test_super_admin_can_manage_employee_role(): void
    {
        $superAdmin = User::where('role', 'super_admin')->first();

        // 1. Filter role employee
        $response = $this->actingAs($superAdmin)->get('/superadmin/roles?role=employee');
        $response->assertOk();
        $response->assertSee('Karyawan');

        // 2. Tambah akun role employee
        $storeResponse = $this->actingAs($superAdmin)->post('/superadmin/users', [
            'name' => 'Karyawan Baru Test',
            'username' => 'KMH-TEST-999',
            'email' => 'kmhtest999@kmh.co.id',
            'password' => 'password',
            'role' => 'employee',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Mechanical',
            'position' => 'Teknisi Turbin',
        ]);

        $storeResponse->assertRedirect('/superadmin/roles');
        $storeResponse->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'KMH-TEST-999',
            'email' => 'kmhtest999@kmh.co.id',
            'role' => 'employee',
        ]);

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-TEST-999',
            'name' => 'Karyawan Baru Test',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Mechanical',
            'position' => 'Teknisi Turbin',
        ]);
    }
}
