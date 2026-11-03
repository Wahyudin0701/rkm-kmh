<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use App\Models\VehicleLocationHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanTestLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedSampleData();

        $this->superAdmin = User::where('role', 'super_admin')->first();
        $this->regularUser = User::where('role', 'employee')->first();
    }

    public function test_artisan_clean_test_logs_command_cleans_activities_and_admin_logs(): void
    {
        // Pastikan ada data aktivitas dan log admin
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Test Module',
            description: 'Uji coba aktivitas log admin'
        );

        $vehicle = Vehicle::first();
        $employee = Employee::first();
        $gate = Gate::first();

        VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $employee->id,
            'gate_id' => $gate->id,
            'direction' => 'in',
            'previous_km' => 95,
            'current_km' => 100,
            'distance' => 5,
            'purpose' => 'Uji Coba Lapangan',
            'recorded_at' => now(),
            'validation_status' => 'approved',
        ]);

        $this->assertGreaterThan(0, VehicleActivity::count());
        $this->assertGreaterThan(0, AdminActivityLog::count());

        // Jalankan perintah artisan pembersihan
        $this->artisan('kmh:clean-test-logs --force')
            ->assertSuccessful();

        // Verifikasi log bersih
        $this->assertEquals(0, VehicleActivity::count());
        $this->assertEquals(0, VehicleLocationHistory::count());
        $this->assertEquals(0, AdminActivityLog::count());

        // Verifikasi master data tetap utuh
        $this->assertGreaterThan(0, Vehicle::count());
        $this->assertGreaterThan(0, Employee::count());
        $this->assertGreaterThan(0, User::count());
    }

    public function test_super_admin_can_clear_activity_logs_via_web(): void
    {
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Test Module',
            description: 'Uji coba aktivitas log admin untuk web'
        );

        $this->assertGreaterThan(0, AdminActivityLog::count());

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.activity_logs.clear'), [
                'confirmation' => 'KOSONGKAN',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, AdminActivityLog::count());
    }

    public function test_non_super_admin_cannot_clear_activity_logs_via_web(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('admin.activity_logs.clear'), [
                'confirmation' => 'KOSONGKAN',
            ]);

        $response->assertStatus(403);
    }
}
