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
        $this->seedSampleData();
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
        $this->assertEquals(config('auth.hse_admin.name', 'Mima'), $user->name);
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
        $this->assertEquals(config('auth.qma_admin.name', 'Pinto Yuliandra'), $user->name);
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

        $response->assertRedirect('/pg/dashboard');
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

        // 4. Admin PG dapat akses modul PG dashboard, guards & log verifikasi, ditolak dari pos gerbang verifikasi & HSE
        $this->actingAs($pgUser)->get('/pg/dashboard')->assertStatus(200);
        $this->actingAs($pgUser)->get('/pg/guards')->assertStatus(200);
        $this->actingAs($pgUser)->get('/pg/verification-logs')->assertStatus(200);
        $this->actingAs($pgUser)->get('/pg/gate-inspection')->assertStatus(403);
        $this->actingAs($pgUser)->get('/hse/employees')->assertStatus(403);

        // 5. Petugas Satpam Pos Gerbang dapat akses verifikasi pos gerbang, ditolak dari master guards & logs & dashboard
        $gateOfficer = User::where('role', 'gate_officer')->first();
        $this->actingAs($gateOfficer)->get('/pg/gate-inspection')->assertStatus(200);
        $this->actingAs($gateOfficer)->get('/pg/dashboard')->assertStatus(403);
        $this->actingAs($gateOfficer)->get('/pg/guards')->assertStatus(403);
        $this->actingAs($gateOfficer)->get('/pg/verification-logs')->assertStatus(403);
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

    /**
     * Memastikan Admin HSE dapat memfilter data SIMPER berdasarkan Status Driver dan Masa Berlaku.
     */
    public function test_admin_hse_can_filter_employees_by_driver_status_and_simper_validity(): void
    {
        $hseUser = User::where('username', '1185')->first();

        // 1. Tanpa filter: semua tampil dan tombol Reset tidak muncul
        $response = $this->actingAs($hseUser)->get('/hse/employees');
        $response->assertStatus(200);
        $response->assertSee('Semua Status Driver');
        $response->assertSee('Semua Masa Berlaku');
        $response->assertDontSee('<span>Reset</span>', false);

        // 2. Filter status driver = driver
        $responseDriver = $this->actingAs($hseUser)->get('/hse/employees?driver_status=driver');
        $responseDriver->assertStatus(200);
        $responseDriver->assertSee('KMH-2022-001'); // Driver
        $responseDriver->assertDontSee('KMH-2021-088'); // Bukan driver
        $responseDriver->assertSee('<span>Reset Filter</span>', false);

        // 3. Filter status driver = non_driver
        $responseNonDriver = $this->actingAs($hseUser)->get('/hse/employees?driver_status=non_driver');
        $responseNonDriver->assertStatus(200);
        $responseNonDriver->assertSee('KMH-2021-088'); // Bukan driver
        $responseNonDriver->assertDontSee('KMH-2022-001'); // Driver
        $responseNonDriver->assertSee('<span>Reset Filter</span>', false);

        // 4. Filter SIMPER valid (masih berlaku)
        $responseValid = $this->actingAs($hseUser)->get('/hse/employees?simper_status=valid');
        $responseValid->assertStatus(200);
        $responseValid->assertSee('KMH-2022-001'); // Valid
        $responseValid->assertDontSee('KMH-2023-012'); // Expired
        $responseValid->assertSee('<span>Reset Filter</span>', false);

        // 5. Filter SIMPER expired (kedaluwarsa)
        $responseExpired = $this->actingAs($hseUser)->get('/hse/employees?simper_status=expired');
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee('KMH-2023-012'); // Expired
        $responseExpired->assertDontSee('KMH-2022-001'); // Valid
        $responseExpired->assertSee('<span>Reset Filter</span>', false);

        // 6. Request dengan query param kosong (default) tidak menampilkan tombol Reset
        $responseEmpty = $this->actingAs($hseUser)->get('/hse/employees?search=&driver_status=&simper_status=');
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertDontSee('<span>Reset Filter</span>', false);
    }

    public function test_login_page_renders_remember_me_checkbox(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="remember"', false);
        $response->assertSee('Ingat Saya');
        $response->assertSee('Tetap masuk selama 30 hari');
        $response->assertSee('checked', false);
    }

    public function test_login_with_remember_me_generates_30_days_cookie_and_remembers_user(): void
    {
        $user = User::where('role', 'employee')->first();
        $recallerName = \Illuminate\Support\Facades\Auth::guard('web')->getRecallerName();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Memastikan cookie remember_web terpasang pada response
        $response->assertCookie($recallerName);

        // Verifikasi durasi cookie adalah sekitar 30 hari (43.200 menit)
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $recallerName);

        $this->assertNotNull($cookie);
        $remainingDays = round(($cookie->getExpiresTime() - time()) / 86400);
        $this->assertEquals(30, $remainingDays);

        // Memastikan user di database memiliki remember_token terisi
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    public function test_login_without_remember_me_does_not_set_recaller_cookie(): void
    {
        $user = User::where('role', 'employee')->first();
        $recallerName = \Illuminate\Support\Facades\Auth::guard('web')->getRecallerName();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
            // remember checkbox tidak dicentang
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Memastikan cookie remember_web tidak terpasang
        $response->assertCookieMissing($recallerName);
    }
}
