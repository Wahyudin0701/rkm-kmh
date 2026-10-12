<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Memastikan halaman login dapat diakses publik.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('PLTA Merangin 350 MW');
        $response->assertSee('Username');
        $response->assertSee('Password');
    }

    /**
     * Memastikan Admin HSE (Mima - 1185) dapat login dan diarahkan ke dashboard HSE.
     */
    public function test_admin_hse_can_login_and_redirects_to_hse_module(): void
    {
        $response = $this->post('/login', [
            'username' => '1185',
            'password' => 'password',
        ]);

        $response->assertRedirect('/hse/dashboard');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('1185', $user->username);
        $this->assertEquals('Mima', $user->name);
        $this->assertEquals('hse_admin', $user->role);
    }

    /**
     * Memastikan Admin QMA (Pinto Yuliandra - 1171) dapat login dan diarahkan ke Log QMA.
     */
    public function test_admin_qma_can_login_and_redirects_to_qma_module(): void
    {
        $response = $this->post('/login', [
            'username' => '1171',
            'password' => 'password',
        ]);

        $response->assertRedirect('/qma/dashboard');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('1171', $user->username);
        $this->assertEquals('Pinto Yuliandra', $user->name);
        $this->assertEquals('qma_admin', $user->role);
    }

    /**
     * Memastikan Admin PG (Admin PG - AdminPG) dapat login dan diarahkan ke Master Satpam PG.
     */
    public function test_admin_pg_can_login_and_redirects_to_pg_module(): void
    {
        $response = $this->post('/login', [
            'username' => 'AdminPG',
            'password' => 'password',
        ]);

        $response->assertRedirect('/pg/guards');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('AdminPG', $user->username);
        $this->assertEquals('Admin PG', $user->name);
        $this->assertEquals('pg_admin', $user->role);
    }

    /**
     * Memastikan login gagal dengan pesan error jika kredensial tidak cocok.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => '1185',
            'password' => 'salah-password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Memastikan Role-Based Access Control (RBAC) melindungi rute secara ketat.
     */
    public function test_role_based_access_control_protection(): void
    {
        $hseUser = User::where('username', '1185')->first();
        $qmaUser = User::where('username', '1171')->first();
        $pgUser = User::where('username', 'AdminPG')->first();

        // 1. Guest mencoba akses rute terproteksi -> dialihkan ke login
        $this->get('/hse/employees')->assertRedirect('/login');
        $this->get('/qma/activity-log')->assertRedirect('/login');
        $this->get('/pg/guards')->assertRedirect('/login');

        // 2. Admin HSE dapat akses modul HSE, ditolak dari modul QMA & PG
        $this->actingAs($hseUser)->get('/hse/employees')->assertStatus(200);
        $this->actingAs($hseUser)->get('/qma/activity-log')->assertStatus(403);
        $this->actingAs($hseUser)->get('/pg/guards')->assertStatus(403);

        // 3. Admin QMA dapat akses modul QMA, ditolak dari modul HSE & PG
        $this->actingAs($qmaUser)->get('/qma/activity-log')->assertStatus(200);
        $this->actingAs($qmaUser)->get('/hse/employees')->assertStatus(403);
        $this->actingAs($qmaUser)->get('/pg/guards')->assertStatus(403);

        // 4. Admin PG dapat akses modul PG guards & gate inspection, ditolak dari HSE
        $this->actingAs($pgUser)->get('/pg/guards')->assertStatus(200);
        $this->actingAs($pgUser)->get('/pg/gate-inspection')->assertStatus(200);
        $this->actingAs($pgUser)->get('/hse/employees')->assertStatus(403);
    }

    /**
     * Memastikan Admin HSE dapat mengedit data karyawan & SIMPER.
     */
    public function test_admin_hse_can_edit_employee_simper_data(): void
    {
        $hseUser = User::where('username', '1185')->first();
        $employee = \App\Models\Employee::first();

        $response = $this->actingAs($hseUser)->post("/hse/employees/{$employee->id}", [
            'nik' => $employee->nik,
            'name' => 'Nama Karyawan Diperbarui',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Mechanical',
            'position' => 'Senior Technician',
            'is_operator' => '1',
            'simper_expired_at' => '2028-12-31',
        ]);

        $response->assertSessionHas('success');
        $employee->refresh();
        $this->assertEquals('Nama Karyawan Diperbarui', $employee->name);
        $this->assertEquals('Senior Technician', $employee->position);
        $this->assertEquals('2028-12-31', $employee->simper_expired_at->format('Y-m-d'));
    }

    /**
     * Memastikan jika bukan driver maka tanggal SIMPER otomatis bernilai null.
     */
    public function test_non_driver_employee_has_null_simper_date(): void
    {
        $hseUser = User::where('username', '1185')->first();
        $employee = \App\Models\Employee::first();

        $response = $this->actingAs($hseUser)->post("/hse/employees/{$employee->id}", [
            'nik' => $employee->nik,
            'name' => $employee->name,
            'unit_kerja' => $employee->unit_kerja,
            'division' => $employee->division,
            'position' => 'Staff Keuangan',
            'is_operator' => '0',
            'simper_expired_at' => '2029-01-01',
        ]);

        $response->assertSessionHas('success');
        $employee->refresh();
        $this->assertFalse((bool) $employee->is_operator);
        $this->assertNull($employee->simper_expired_at);
    }

    /**
     * Memastikan user dapat logout dengan aman.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('username', '1185')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Memastikan halaman profil dapat diakses oleh pengguna yang login.
     */
    public function test_authenticated_user_can_access_and_update_profile(): void
    {
        $user = User::where('username', '1185')->first();

        // Guest dilarang akses
        $this->get('/profile')->assertRedirect('/login');

        // User terautentikasi dapat melihat halaman profil
        $this->actingAs($user)
            ->get('/profile')
            ->assertStatus(200)
            ->assertSee('Profil Saya');

        // User dapat memperbarui nama & email
        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Mima HSE Updated',
            'email' => 'mima.updated@kerincihydro.co.id',
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertEquals('Mima HSE Updated', $user->name);
        $this->assertEquals('mima.updated@kerincihydro.co.id', $user->email);
    }
}
