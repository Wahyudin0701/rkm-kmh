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
}
