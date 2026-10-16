<?php

namespace Tests\Feature;

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

class PgExportTest extends TestCase
{
    use RefreshDatabase;

    private User $pgAdmin;
    private User $unauthorizedUser;
    private Gate $gate1;
    private Gate $gate2;
    private SecurityGuard $guard1;
    private SecurityGuard $guard2;
    private Vehicle $vehicle;
    private Employee $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->pgAdmin = User::factory()->create([
            'name' => 'Admin PG Test',
            'username' => 'pg_test',
            'role' => 'pg_admin',
        ]);
        $this->pgAdmin->assignRole('pg_admin');

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Unauthorized User',
            'username' => 'unauthorized_test',
            'role' => 'employee',
        ]);

        $this->gate1 = Gate::create([
            'name' => 'Pos Gerbang Utama',
            'code' => 'GATE-01',
            'location' => 'Main Entrance',
            'is_active' => true,
        ]);

        $this->gate2 = Gate::create([
            'name' => 'Pos Gerbang Bendungan',
            'code' => 'GATE-02',
            'location' => 'Dam Access',
            'is_active' => true,
        ]);

        $userGuard1 = User::factory()->create([
            'name' => 'Budi Santoso',
            'username' => 'SEC001',
            'role' => 'gate_officer',
            'gate_id' => $this->gate1->id,
        ]);

        $this->guard1 = SecurityGuard::create([
            'badge_id' => 'SEC001',
            'name' => 'Budi Santoso',
            'team' => 1,
            'gate_id' => $this->gate1->id,
            'user_id' => $userGuard1->id,
            'is_active' => true,
        ]);

        $userGuard2 = User::factory()->create([
            'name' => 'Agus Wijaya',
            'username' => 'SEC002',
            'role' => 'gate_officer',
            'gate_id' => $this->gate2->id,
        ]);

        $this->guard2 = SecurityGuard::create([
            'badge_id' => 'SEC002',
            'name' => 'Agus Wijaya',
            'team' => 2,
            'gate_id' => $this->gate2->id,
            'user_id' => $userGuard2->id,
            'is_active' => false,
        ]);

        $this->vehicle = Vehicle::create([
            'code' => 'LV-01',
            'category' => 'light_vehicle',
            'brand' => 'Toyota',
            'brand_type' => 'Toyota Hilux 4x4',
            'unit_type' => 'Double Cabin',
            'license_plate' => 'BH 1234 XY',
            'ownership' => 'PT KMH',
            'department' => 'Civil & Inviro',
            'status' => 'Operation',
            'current_km' => 15000,
        ]);

        $this->driver = Employee::create([
            'name' => 'Rahmat Hidayat',
            'nik' => 'KMH-1001',
            'department' => 'Civil & Inviro',
            'position' => 'Driver',
            'status' => 'active',
        ]);
    }

    public function test_admin_pg_can_export_guards_excel(): void
    {
        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.guards.export_excel'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('data_satpam_', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_pg_can_export_guards_excel_with_filters(): void
    {
        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.guards.export_excel', [
                'team' => 1,
                'gate_id' => $this->gate1->id,
                'search' => 'Budi',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_pg_can_export_guards_pdf(): void
    {
        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.guards.export_pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('laporan_data_satpam_', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_admin_pg_can_export_verification_logs_excel(): void
    {
        VehicleActivity::create([
            'transaction_no' => 'TRX-20261016-0001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate1->id,
            'security_guard_id' => $this->guard1->id,
            'direction' => 'out',
            'previous_km' => 14950,
            'current_km' => 15000,
            'distance' => 50,
            'purpose' => 'Mobilisasi material ke Dam Site',
            'validation_status' => 'approved',
            'recorded_at' => Carbon::now(),
            'validated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.logs.export_excel'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('log_aktivitas_gerbang_', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_pg_can_export_verification_logs_excel_with_filters(): void
    {
        VehicleActivity::create([
            'transaction_no' => 'TRX-20261016-0002',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate1->id,
            'security_guard_id' => $this->guard1->id,
            'direction' => 'in',
            'previous_km' => 15000,
            'current_km' => 15050,
            'distance' => 50,
            'purpose' => 'Kembali ke workshop',
            'validation_status' => 'rejected',
            'rejection_note' => 'Odometer tidak sesuai',
            'recorded_at' => Carbon::now(),
            'validated_at' => Carbon::now(),
        ]);

        $today = Carbon::today()->toDateString();
        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.logs.export_excel', [
                'team' => 1,
                'gate_id' => $this->gate1->id,
                'direction' => 'in',
                'status' => 'rejected',
                'start_date' => $today,
                'end_date' => $today,
                'search' => 'TRX',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_pg_can_export_verification_logs_pdf(): void
    {
        VehicleActivity::create([
            'transaction_no' => 'TRX-20261016-0003',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate1->id,
            'security_guard_id' => $this->guard1->id,
            'direction' => 'out',
            'previous_km' => 15050,
            'current_km' => 15100,
            'distance' => 50,
            'purpose' => 'Inspeksi lapangan',
            'validation_status' => 'approved',
            'recorded_at' => Carbon::now(),
            'validated_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->pgAdmin)
            ->get(route('pg.logs.export_pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('log_verifikasi_gerbang_', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_unauthorized_user_cannot_export_pg_guards_or_logs(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('pg.guards.export_excel'))
            ->assertForbidden();

        $this->actingAs($this->unauthorizedUser)
            ->get(route('pg.guards.export_pdf'))
            ->assertForbidden();

        $this->actingAs($this->unauthorizedUser)
            ->get(route('pg.logs.export_excel'))
            ->assertForbidden();

        $this->actingAs($this->unauthorizedUser)
            ->get(route('pg.logs.export_pdf'))
            ->assertForbidden();
    }
}
