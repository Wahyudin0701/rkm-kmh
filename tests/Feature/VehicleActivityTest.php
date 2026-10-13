<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed data dasar
        $this->seed();
    }

    public function test_form_qr_loads_successfully_for_light_vehicle(): void
    {
        $response = $this->get('/gate-activity/LV62');
        $response->assertStatus(200);
        $response->assertSee('Input Timesheet Unit');
        $response->assertSee('LV62');
        $response->assertSee('MITSUBISHI - L300 - PICK UP');
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('userProfileDropdownBtn');
        $response->assertDontSee('openDrawerBtn');
    }

    public function test_form_qr_hides_sidebar_and_topbar_when_accessed_by_authenticated_admin(): void
    {
        $admin = User::where('role', 'qma_admin')->first();

        $response = $this->actingAs($admin)->get('/gate-activity/LV62');
        $response->assertStatus(200);
        $response->assertSee('Input Timesheet Unit');
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('userProfileDropdownBtn');
        $response->assertDontSee('openDrawerBtn');
    }

    public function test_check_nik_endpoint_returns_not_found_for_unregistered_nik(): void
    {
        $response = $this->postJson('/gate-activity/LV62/check-nik', [
            'nik' => 'KMH-9999-999',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'not_found',
        ]);
        $response->assertJsonFragment([
            'message' => "NIK 'KMH-9999-999' tidak terdaftar dalam database SIMPER PT KMH. Silakan periksa kembali atau hubungi Divisi HSE.",
        ]);
    }

    public function test_check_nik_endpoint_returns_expired_for_expired_simper(): void
    {
        // Randi Kurniawan (KMH-2023-012) memiliki SIMPER kedaluwarsa
        $response = $this->postJson('/gate-activity/LV62/check-nik', [
            'nik' => 'KMH-2023-012',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'expired',
        ]);
        $this->assertStringContainsString('telah KEDALUWARSA', $response->json('message'));
    }

    public function test_check_nik_endpoint_returns_valid_for_active_simper(): void
    {
        // Eka Sufiatna (KMH-2022-001) memiliki SIMPER aktif
        $response = $this->postJson('/gate-activity/LV62/check-nik', [
            'nik' => 'KMH-2022-001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'valid',
            'employee' => [
                'name' => 'Eka Sufiatna',
                'nik' => 'KMH-2022-001',
            ],
        ]);
    }

    public function test_form_qr_auto_fills_driver_when_valid_nik_provided(): void
    {
        $response = $this->get('/gate-activity/LV62?nik=KMH-2022-001');

        $response->assertStatus(200);
        $response->assertSee('Eka Sufiatna');
        $response->assertSee('KMH-2022-001');
        $response->assertSee('Otomatis Terisi');
        $response->assertSee('SIMPER Aktif');
    }

    public function test_check_nik_endpoint_returns_not_driver_when_employee_is_not_operator(): void
    {
        // Siti Rahma (KMH-2021-088) adalah Staff Finance (Bukan Driver)
        $response = $this->postJson('/gate-activity/LV62/check-nik', [
            'nik' => 'KMH-2021-088',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'not_driver',
            'title' => 'Bukan Driver Terdaftar',
        ]);
        $this->assertStringContainsString('terdaftar sebagai BUKAN DRIVER', $response->json('message'));
    }

    public function test_form_qr_shows_error_when_invalid_or_expired_or_non_driver_nik_provided_via_query(): void
    {
        // 1. NIK Tidak terdaftar
        $responseUnregistered = $this->get('/gate-activity/LV62?nik=KMH-9999-999');
        $responseUnregistered->assertStatus(200);
        $responseUnregistered->assertSee('tidak terdaftar dalam database SIMPER');

        // 2. NIK Kedaluwarsa
        $responseExpired = $this->get('/gate-activity/LV62?nik=KMH-2023-012');
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee('telah KEDALUWARSA');

        // 3. NIK Karyawan Bukan Driver (Staff Finance)
        $responseNotDriver = $this->get('/gate-activity/LV62?nik=KMH-2021-088');
        $responseNotDriver->assertStatus(200);
        $responseNotDriver->assertSee('Bukan Driver Terdaftar');
        $responseNotDriver->assertSee('terdaftar sebagai BUKAN DRIVER');
    }

    public function test_submit_blocked_if_driver_simper_is_expired(): void
    {
        $vehicle = Vehicle::where('code', 'LV62')->first();
        $gate = Gate::first();
        // Driver dengan SIMPER kedaluwarsa (Randi Kurniawan)
        $expiredDriver = Employee::where('nik', 'KMH-2023-012')->first();

        $response = $this->post("/gate-activity/{$vehicle->code}", [
            'driver_id' => $expiredDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'current_km' => 12600,
            'purpose' => 'Inspeksi Adit 1',
        ]);

        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseMissing('vehicle_activities', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $expiredDriver->id,
        ]);
    }

    public function test_submit_blocked_if_odometer_is_less_than_previous(): void
    {
        $vehicle = Vehicle::where('code', 'LV62')->first(); // current_km: 12500
        $gate = Gate::first();
        $validDriver = Employee::where('nik', 'KMH-2022-001')->first(); // Eka Sufiatna (SIMPER Aktif)

        // Coba input KM lebih kecil dari 12500
        $response = $this->post("/gate-activity/{$vehicle->code}", [
            'driver_id' => $validDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'current_km' => 12400, // Error: < 12500
            'purpose' => 'Perjalanan dinas',
        ]);

        $response->assertSessionHasErrors('current_km');
    }

    public function test_submit_successful_with_valid_data_and_enforces_alternating_direction(): void
    {
        $vehicle = Vehicle::where('code', 'LV62')->first(); // current_km: 12500
        $gate = Gate::first();
        $validDriver = Employee::where('nik', 'KMH-2022-001')->first();

        // 1. Transaksi pertama: KELUAR (Out)
        $response1 = $this->post("/gate-activity/{$vehicle->code}", [
            'driver_id' => $validDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'current_km' => 12550,
            'purpose' => 'Antar teknisi ke Power House',
        ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('vehicle_activities', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $validDriver->id,
            'direction' => 'out',
            'current_km' => 12550,
            'validation_status' => 'pending',
        ]);

        // 2. Coba kirim KELUAR (Out) lagi -> Wajib Ditolak karena harus MASUK (In)!
        $response2 = $this->post("/gate-activity/{$vehicle->code}", [
            'driver_id' => $validDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'out', // Error: Harus 'in'
            'current_km' => 12580,
            'purpose' => 'Coba keluar lagi',
        ]);

        $response2->assertSessionHasErrors('direction');

        // 3. Kirim MASUK (In) -> Berhasil!
        $response3 = $this->post("/gate-activity/{$vehicle->code}", [
            'driver_id' => $validDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'in',
            'current_km' => 12600,
            'purpose' => 'Kembali ke kantor',
        ]);

        $response3->assertRedirect();
        $this->assertDatabaseHas('vehicle_activities', [
            'vehicle_id' => $vehicle->id,
            'direction' => 'in',
            'current_km' => 12600,
        ]);
    }

    public function test_security_guard_can_approve_activity_and_updates_master_odometer(): void
    {
        $vehicle = Vehicle::where('code', 'LV62')->first();
        $gate = Gate::first();
        $validDriver = Employee::where('nik', 'KMH-2022-001')->first();
        $guard = SecurityGuard::first();

        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $validDriver->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'previous_km' => 12500,
            'current_km' => 12580,
            'distance' => 80,
            'purpose' => 'Perjalanan dinas Adit 2',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Satpam approve (Petugas Pos Gerbang login)
        $gateOfficer = User::where('role', 'gate_officer')->first();
        $response = $this->actingAs($gateOfficer)->post("/pg/gate-inspection/{$activity->id}/approve", [
            'security_guard_id' => $guard->id,
        ]);

        $response->assertRedirect();

        // Verifikasi status transaksi
        $this->assertDatabaseHas('vehicle_activities', [
            'id' => $activity->id,
            'validation_status' => 'approved',
            'security_guard_id' => $guard->id,
        ]);

        // Verifikasi master odometer terupdate
        $this->assertEquals(12580, (float) $vehicle->fresh()->current_km);
    }

    public function test_qma_log_renders_10_columns(): void
    {
        $qmaAdmin = User::where('role', 'qma_admin')->first();
        $response = $this->actingAs($qmaAdmin)->get('/qma/activity-log');
        $response->assertStatus(200);
        $response->assertSee('Log Aktivitas Kendaraan');
        $response->assertSee('Tanggal');
        $response->assertSee('Kode LV');
        $response->assertSee('Unit');
        $response->assertSee('Penanggung Jawab');
        $response->assertSee('Driver');
        $response->assertSee('Gate');
        $response->assertSee('Akses');
        $response->assertSee('KM Sebelumnya');
        $response->assertSee('KM Berjalan');
        $response->assertSee('Aktivitas');
    }

    public function test_qma_log_reset_button_shown_only_when_filtered(): void
    {
        $qmaAdmin = User::where('role', 'qma_admin')->first();

        // 1. Unfiltered request should NOT see Reset button
        $response = $this->actingAs($qmaAdmin)->get('/qma/activity-log');
        $response->assertStatus(200);
        $response->assertDontSee('<span>Reset Filter</span>', false);

        // 2. Request with empty filter params should NOT see Reset button
        $responseEmpty = $this->actingAs($qmaAdmin)->get('/qma/activity-log?search=&direction=&gate_id=');
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertDontSee('<span>Reset Filter</span>', false);

        // 3. Request with active filter should see Reset button
        $responseFiltered = $this->actingAs($qmaAdmin)->get('/qma/activity-log?direction=in');
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('<span>Reset Filter</span>', false);
    }

    public function test_qma_log_defaults_date_filter_to_today(): void
    {
        $qmaAdmin = User::where('role', 'qma_admin')->first();
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($qmaAdmin)->get('/qma/activity-log');
        $response->assertStatus(200);
        $response->assertSee('value="' . $today . '"', false);
    }

    public function test_admin_pg_can_store_security_guard_and_creates_login_account(): void
    {
        $adminPg = User::where('role', 'pg_admin')->first();
        $gate = Gate::first();

        $response = $this->actingAs($adminPg)->post('/pg/guards', [
            'badge_id' => 'SEC-999',
            'name' => 'Kurniawan Pratama',
            'team' => 2,
            'gate_id' => $gate->id,
            'password' => 'satpam123',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('security_guards', [
            'badge_id' => 'SEC-999',
            'name' => 'Kurniawan Pratama',
            'team' => 2,
            'gate_id' => $gate->id,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'SEC-999',
            'role' => 'gate_officer',
            'gate_id' => $gate->id,
        ]);
    }

    public function test_guard_can_login_with_badge_id_and_auto_approve_without_manual_guard_id(): void
    {
        // Login with satpam SEC-001
        $loginResponse = $this->post('/login', [
            'username' => 'SEC-001',
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect(route('pg.gate.inspection'));

        $guard = SecurityGuard::where('badge_id', 'SEC-001')->first();
        $vehicle = Vehicle::first();
        $driver = Employee::first();

        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-AUTO-01',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $guard->gate_id,
            'direction' => 'out',
            'previous_km' => $vehicle->current_km,
            'current_km' => $vehicle->current_km + 15,
            'distance' => 15,
            'purpose' => 'Uji Verifikasi Otomatis',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $guardUser = $guard->user;
        $approveResponse = $this->actingAs($guardUser)->post("/pg/gate-inspection/{$activity->id}/approve");

        $approveResponse->assertRedirect();

        $this->assertDatabaseHas('vehicle_activities', [
            'id' => $activity->id,
            'validation_status' => 'approved',
            'security_guard_id' => $guard->id,
        ]);
    }

    public function test_admin_pg_can_view_and_filter_verification_logs(): void
    {
        $pgUser = User::where('role', 'pg_admin')->first();
        $guard = SecurityGuard::first();
        $vehicle = Vehicle::first();
        $driver = Employee::first();

        // Buat data aktivitas verifikasi
        VehicleActivity::create([
            'transaction_no' => 'TRX-LOG-PG-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $guard->gate_id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Dinas Luar',
            'recorded_at' => Carbon::now(),
            'validated_at' => Carbon::now(),
            'security_guard_id' => $guard->id,
            'validation_status' => 'approved',
        ]);

        $response = $this->actingAs($pgUser)->get(route('pg.logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Log Riwayat Verifikasi Gerbang');
        $response->assertSee('TRX-LOG-PG-001');
        $response->assertSee($guard->name);

        // Test filter parameter
        $filterResponse = $this->actingAs($pgUser)->get(route('pg.logs.index', [
            'team' => $guard->team,
            'status' => 'approved',
            'search' => 'TRX-LOG-PG',
        ]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('TRX-LOG-PG-001');
    }

    public function test_admin_pg_can_view_dashboard_with_summary_metrics(): void
    {
        $pgUser = User::where('role', 'pg_admin')->first();

        $response = $this->actingAs($pgUser)->get(route('pg.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('TOTAL SATPAM');
        $response->assertSee('SATPAM AKTIF');
        $response->assertSee('POS GERBANG');
        $response->assertSee('VERIFIKASI HARI INI');
        $response->assertSee('PENOLAKAN GERBANG');
        $response->assertSee('Log Verifikasi Gerbang Terkini');
        $response->assertSee('Petugas Satpam per Regu');
    }

    public function test_guard_can_fetch_live_queue_json_endpoint(): void
    {
        $gateOfficer = User::where('role', 'gate_officer')->first();
        $vehicle = Vehicle::where('code', 'LV62')->first();
        $driver = Employee::where('nik', 'KMH-2022-001')->first();
        $gateId = $gateOfficer->gate_id ?? Gate::first()->id;

        VehicleActivity::create([
            'transaction_no' => 'TRX-LIVE-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $gateId,
            'direction' => 'out',
            'previous_km' => 12500,
            'current_km' => 12600,
            'distance' => 100,
            'purpose' => 'Pengujian Live Queue',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($gateOfficer)->get(route('pg.gate.live_queue'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['count', 'html']);
        $this->assertGreaterThanOrEqual(1, $response->json('count'));
        $this->assertStringContainsString('BH 8893 DI', $response->json('html'));
        $this->assertStringContainsString('Pengujian Live Queue', $response->json('html'));
    }
}
