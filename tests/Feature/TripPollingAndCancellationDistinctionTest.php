<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPollingAndCancellationDistinctionTest extends TestCase
{
    use RefreshDatabase;

    protected User $driverUser;
    protected Employee $driver;
    protected Vehicle $vehicle;
    protected Gate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gate = Gate::create([
            'code' => 'GATE-01',
            'name' => 'Pos Gerbang Utama',
            'location' => 'Main Gate Site',
            'is_active' => true,
        ]);

        $this->vehicle = Vehicle::create([
            'category' => 'light_vehicle',
            'code' => 'LV-TEST-01',
            'brand_type' => 'Toyota Hilux 2.4G',
            'license_plate' => 'BH 1234 XX',
            'division' => 'Operasional',
            'ownership' => 'PT KMH',
            'status' => 'Operation',
            'current_km' => 1000.0,
        ]);

        $this->driver = Employee::create([
            'name' => 'Bambang Supriadi',
            'nik' => 'DRV-999',
            'position' => 'Driver',
            'division' => 'Operasional',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addMonths(6),
        ]);

        $this->driverUser = User::factory()->create([
            'username' => 'DRV-999',
            'name' => 'Bambang Supriadi',
        ]);
        $this->driver->update(['user_id' => $this->driverUser->id]);
        $this->driverUser->assignRole('employee');
    }

    /**
     * 1. Pengisian formulir aktivitas kendaraan langsung me-redirect ke employee dashboard.
     */
    public function test_submitting_vehicle_activity_redirects_to_employee_dashboard(): void
    {
        $direction = $this->vehicle->determineNextAllowedDirection();
        if ($direction === 'ANY') {
            $direction = 'out';
        }

        $response = $this->actingAs($this->driverUser)->post("/gate-activity/{$this->vehicle->code}", [
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => $direction,
            'current_km' => 1025.0,
            'purpose' => 'Mobilisasi dinas operasional site',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicle_activities', [
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'validation_status' => 'pending',
            'purpose' => 'Mobilisasi dinas operasional site',
        ]);
    }

    /**
     * 2. Status pembatalan membedakan antara dibatalkan sistem (timeout) vs dibatalkan pengemudi.
     */
    public function test_distinguishes_cancellation_source(): void
    {
        // A. Tiket dibatalkan sistem
        $systemCancelled = VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-SYS01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1020,
            'distance' => 20,
            'purpose' => 'Test timeout',
            'recorded_at' => Carbon::now()->subHours(6),
            'validation_status' => 'cancelled',
            'rejection_note' => 'Dibatalkan sistem: Melebihi batas waktu verifikasi (5 jam)',
        ]);

        $this->assertTrue($systemCancelled->isCancelled());
        $this->assertTrue($systemCancelled->isCancelledBySystem());
        $this->assertFalse($systemCancelled->isCancelledByUser());
        $this->assertEquals('Batal Sistem (Timeout)', $systemCancelled->cancellation_source_label);

        // B. Tiket dibatalkan mandiri oleh driver
        $driverCancelled = VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-DRV01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1020,
            'distance' => 20,
            'purpose' => 'Test batal mandiri',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'cancelled',
            'rejection_note' => 'Salah input tujuan operasional',
        ]);

        $this->assertTrue($driverCancelled->isCancelled());
        $this->assertFalse($driverCancelled->isCancelledBySystem());
        $this->assertTrue($driverCancelled->isCancelledByUser());
        $this->assertEquals('Dibatalkan Pengemudi', $driverCancelled->cancellation_source_label);
    }

    /**
     * 3. Endpoint polling status tiket mengembalikan data JSON micro-response yang valid.
     */
    public function test_active_trip_status_polling_endpoint(): void
    {
        $pendingActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-POLL-001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1030,
            'distance' => 30,
            'purpose' => 'Uji endpoint polling',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Cek status saat masih pending
        $response = $this->actingAs($this->driverUser)
            ->getJson(route('employee.activity.status_polling', ['activity_id' => $pendingActivity->id]));

        $response->assertOk();
        $response->assertJson([
            'id' => $pendingActivity->id,
            'transaction_no' => 'TRX-POLL-001',
            'status' => 'pending',
            'has_pending' => true,
            'is_approved' => false,
            'is_rejected' => false,
            'is_cancelled' => false,
        ]);

        // Ubah menjadi approved oleh PG
        $pendingActivity->update([
            'validation_status' => 'approved',
            'validated_at' => Carbon::now(),
        ]);

        $responseApproved = $this->actingAs($this->driverUser)
            ->getJson(route('employee.activity.status_polling', ['activity_id' => $pendingActivity->id]));

        $responseApproved->assertOk();
        $responseApproved->assertJson([
            'id' => $pendingActivity->id,
            'status' => 'approved',
            'has_pending' => false,
            'is_approved' => true,
            'is_rejected' => false,
        ]);
    }

    /**
     * 4. Filter log aktivitas dapat menyaring tiket dibatalkan sistem vs dibatalkan pengemudi.
     */
    public function test_filter_logs_by_cancellation_sources(): void
    {
        $qmaUser = User::factory()->create();
        $qmaUser->assignRole('qma_admin');

        // Tiket Sistem
        VehicleActivity::create([
            'transaction_no' => 'TRX-FLT-SYS',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1010,
            'distance' => 10,
            'purpose' => 'Aktivitas Timeout',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'cancelled',
            'rejection_note' => 'Dibatalkan sistem: Melebihi batas waktu verifikasi (5 jam)',
        ]);

        // Tiket Pengemudi
        VehicleActivity::create([
            'transaction_no' => 'TRX-FLT-DRV',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1010,
            'current_km' => 1020,
            'distance' => 10,
            'purpose' => 'Aktivitas Batal Sopir',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'cancelled',
            'rejection_note' => 'Dibatalkan mandiri oleh pengemudi',
        ]);

        // Filter cancelled_system
        $responseSys = $this->actingAs($qmaUser)->get(route('qma.activity.log', [
            'status' => 'cancelled_system',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
        ]));
        $responseSys->assertOk();
        $responseSys->assertSee('TRX-FLT-SYS');
        $responseSys->assertDontSee('TRX-FLT-DRV');

        // Filter cancelled_driver
        $responseDrv = $this->actingAs($qmaUser)->get(route('qma.activity.log', [
            'status' => 'cancelled_driver',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
        ]));
        $responseDrv->assertOk();
        $responseDrv->assertSee('TRX-FLT-DRV');
        $responseDrv->assertDontSee('TRX-FLT-SYS');
    }
}
