<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdometerCorrectionAndTicketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $qmaAdmin;
    protected User $driverUser;
    protected Employee $driverEmployee;
    protected Vehicle $vehicle;
    protected Gate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::where('username', 'superadmin')->first();

        // 1. Setup QMA Admin
        $this->qmaAdmin = User::factory()->create([
            'role' => 'qma_admin',
            'username' => 'qma_tester',
        ]);
        $this->qmaAdmin->assignRole('qma_admin');

        // 2. Setup Driver User & Employee
        $this->driverUser = User::factory()->create([
            'role' => 'employee',
            'username' => 'driver_anto',
        ]);
        $this->driverUser->assignRole('employee');

        $this->driverEmployee = Employee::create([
            'user_id' => $this->driverUser->id,
            'nik' => 'DRV-001',
            'name' => 'Anto Pengemudi',
            'unit_kerja' => 'Transport',
            'division' => 'Operasional',
            'position' => 'Driver',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);

        // 3. Setup Gate & Vehicle
        $this->gate = Gate::create([
            'code' => 'GATE-01',
            'name' => 'Pos Gerbang Utama',
            'location' => 'Main Gate KM 1',
            'is_active' => true,
        ]);

        $this->vehicle = Vehicle::create([
            'code' => 'LV-99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'license_plate' => 'BM 9999 XX',
            'ownership' => 'Operasional KMH',
            'division' => 'Civil',
            'status' => 'Operation',
            'current_km' => 50000,
        ]);
    }

    public function test_driver_can_view_active_pending_ticket_card_on_dashboard(): void
    {
        // Buat tiket pending untuk driver
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST1',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50050,
            'distance' => 50,
            'purpose' => 'Antar logistik ke Powerhouse',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($this->driverUser)->get(route('employee.dashboard'));
        $response->assertOk();
        $response->assertSee('Perjalanan Aktif');
        $response->assertSee('TRX-20261019-TEST1');
        $response->assertSee('LV-99');
        $response->assertSee('50.050 KM');
        $response->assertSee('Koreksi Angka KM');
        $response->assertSee('Batalkan');
    }

    public function test_driver_can_update_km_while_ticket_is_pending(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST2',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 500000, // Typo kelebihan nol
            'distance' => 450000,
            'purpose' => 'Antar logistik',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Driver koreksi KM menjadi 50.120
        $response = $this->actingAs($this->driverUser)
            ->post(route('employee.activity.update_km', $activity->id), [
                'current_km' => 50120,
            ]);

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('success');

        $activity->refresh();
        $this->assertEquals(50120, (float) $activity->current_km);
        $this->assertEquals(120, (float) $activity->distance); // 50120 - 50000 = 120

        // Master vehicle tetap belum berubah karena belum di-approve satpam
        $this->vehicle->refresh();
        $this->assertEquals(50000, (float) $this->vehicle->current_km);
    }

    public function test_driver_cannot_update_km_lower_than_previous_km(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST3',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50100,
            'distance' => 100,
            'purpose' => 'Antar logistik',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($this->driverUser)
            ->post(route('employee.activity.update_km', $activity->id), [
                'current_km' => 49000, // Error: lebih kecil dari 50.000
            ]);

        $response->assertSessionHas('error');
        $activity->refresh();
        $this->assertEquals(50100, (float) $activity->current_km);
    }

    public function test_driver_cannot_update_km_if_already_approved_by_guard(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST4',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50080,
            'distance' => 80,
            'purpose' => 'Antar logistik',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved', // Sudah disetujui satpam
        ]);

        $response = $this->actingAs($this->driverUser)
            ->post(route('employee.activity.update_km', $activity->id), [
                'current_km' => 50150,
            ]);

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('error');

        $activity->refresh();
        $this->assertEquals(50080, (float) $activity->current_km); // Angka tidak berubah
    }

    public function test_driver_can_cancel_ticket_while_pending(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST5',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50080,
            'distance' => 80,
            'purpose' => 'Antar logistik',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($this->driverUser)
            ->post(route('employee.activity.cancel', $activity->id), [
                'cancel_reason' => 'Salah pilih mobil',
            ]);

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('success');

        $activity->refresh();
        $this->assertEquals('cancelled', $activity->validation_status);
        $this->assertEquals('Salah pilih mobil', $activity->rejection_note);

        // Setelah dibatalkan, arah yang diperbolehkan kendaraan tidak terkunci
        $this->assertEquals('ANY', $this->vehicle->determineNextAllowedDirection());
    }

    public function test_qma_admin_can_correct_odometer_on_approved_activity(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST6',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 550000, // Typo lolos approved
            'distance' => 500000,
            'purpose' => 'Antar tim teknis',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);
        $this->vehicle->update(['current_km' => 550000]);

        // Admin QMA mengoreksi menjadi 50.050 KM
        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.activity.correct_odometer', $activity->id), [
                'new_current_km' => 50050,
                'correction_reason' => 'Driver salah ketik kelebihan digit nol saat scan QR form.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $activity->refresh();
        $this->assertEquals(50050, (float) $activity->current_km);
        $this->assertEquals(50, (float) $activity->distance);
        $this->assertTrue($activity->is_corrected);
        $this->assertEquals(550000, (float) $activity->original_current_km);
        $this->assertEquals($this->qmaAdmin->id, $activity->corrected_by_user_id);
        $this->assertNotNull($activity->corrected_at);

        // Master vehicle tersinkron kembali menjadi 50.050
        $this->vehicle->refresh();
        $this->assertEquals(50050, (float) $this->vehicle->current_km);

        // Terdata di Audit Trail
        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'update',
            'module' => 'Operasional Kendaraan (QMA)',
            'target_type' => 'VehicleActivity',
            'target_id' => $activity->id,
        ]);
    }

    public function test_unauthorized_user_cannot_correct_qma_odometer(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST7',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50100,
            'distance' => 100,
            'purpose' => 'Antar tim teknis',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        // Driver biasa mencoba mengakses endpoint koreksi QMA -> Wajib Ditolak 403
        $response = $this->actingAs($this->driverUser)
            ->post(route('qma.activity.correct_odometer', $activity->id), [
                'new_current_km' => 50050,
                'correction_reason' => 'Mencoba koreksi tanpa hak akses.',
            ]);

        $response->assertForbidden();
    }

    public function test_corrected_activity_shows_badge_and_button_in_qma_log_table(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-20261019-TEST8',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 50000,
            'current_km' => 50050,
            'distance' => 50,
            'purpose' => 'Antar tim teknis',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
            'is_corrected' => true,
            'original_current_km' => 550000,
            'correction_reason' => 'Koreksi kelebihan angka 0',
            'corrected_by_user_id' => $this->qmaAdmin->id,
            'corrected_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.activity.log'));
        $response->assertOk();
        $response->assertSee('Terkoreksi');
        $response->assertSee('Koreksi');
        $response->assertSee('TRX-20261019-TEST8');
    }
}
