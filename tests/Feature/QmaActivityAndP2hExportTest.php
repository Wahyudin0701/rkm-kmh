<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\P2hRecord;
use App\Models\P2hTemplate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmaActivityAndP2hExportTest extends TestCase
{
    use RefreshDatabase;

    private User $qmaAdmin;
    private User $unauthorizedUser;
    private Vehicle $lvVehicle;
    private Vehicle $supportVehicle;
    private Employee $driver;
    private Gate $gate;
    private SecurityGuard $guard;
    private P2hTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->qmaAdmin = User::factory()->create([
            'name' => 'Admin QMA Test',
            'username' => 'qma_test',
            'role' => 'qma_admin',
        ]);
        $this->qmaAdmin->assignRole('qma_admin');

        $this->unauthorizedUser = User::factory()->create([
            'name' => 'Unauthorized User',
            'username' => 'unauthorized_test',
            'role' => 'employee',
        ]);

        $this->lvVehicle = Vehicle::create([
            'code' => 'LV-01',
            'category' => 'light_vehicle',
            'brand' => 'Toyota',
            'brand_type' => 'Toyota Hilux 4x4',
            'unit_type' => 'Double Cabin',
            'license_plate' => 'BH 1234 XY',
            'ownership' => 'PT KMH',
            'department' => 'Civil & Inviro',
            'status' => 'Operation',
            'current_km' => 12500,
        ]);

        $this->supportVehicle = Vehicle::create([
            'code' => 'EXC-01',
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'brand' => 'Komatsu',
            'brand_type' => 'Komatsu PC200-8',
            'location' => 'Main Dam',
            'status' => 'Operation',
            'current_km' => 2400,
        ]);

        $this->driver = Employee::create([
            'nik' => '20001',
            'name' => 'Doni Driver',
            'position' => 'Driver LV',
            'unit_kerja' => 'Operasional',
            'is_operator' => true,
            'simper_expired_at' => now()->addYear()->toDateString(),
        ]);

        $this->gate = Gate::create([
            'name' => 'Main Gate KMH',
            'code' => 'MG-01',
            'location' => 'Pintu Masuk Utama',
            'is_active' => true,
        ]);

        $this->guard = SecurityGuard::create([
            'name' => 'Bambang Security',
            'badge_id' => 'SEC-001',
            'phone_number' => '08123456789',
            'status' => 'active',
            'assigned_gate_id' => $this->gate->id,
            'current_shift' => 'Shift 1',
        ]);

        $this->template = P2hTemplate::create([
            'name' => 'Checksheet Excavator',
            'code' => 'P2H-EXC',
            'is_active' => true,
        ]);

        // Seed Sample Vehicle Activity
        VehicleActivity::create([
            'transaction_no' => 'TRX-ACT-001',
            'vehicle_id' => $this->lvVehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 12450,
            'current_km' => 12500,
            'distance' => 50,
            'purpose' => 'Antar Logistik ke Quarry 2',
            'recorded_at' => now(),
            'security_guard_id' => $this->guard->id,
            'validation_status' => 'approved',
            'validated_at' => now(),
        ]);

        // Seed Sample P2H Record
        P2hRecord::create([
            'transaction_no' => 'P2H-20261016-0001',
            'vehicle_id' => $this->supportVehicle->id,
            'employee_id' => $this->driver->id,
            'template_id' => $this->template->id,
            'shift' => 'Shift 1',
            'start_hm' => 2400.5,
            'summary_condition' => 'normal',
            'defect_count' => 0,
            'general_notes' => 'Kondisi mesin dan hidrolik baik',
            'validation_status' => 'approved',
            'validated_by' => $this->qmaAdmin->id,
            'validated_at' => now(),
            'inspected_at' => now(),
        ]);
    }

    public function test_admin_qma_can_export_activity_log_excel(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.activity.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_Timesheet_LV_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_admin_qma_can_export_activity_log_excel_with_filters(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.activity.export_excel', [
                'direction' => 'out',
                'gate_id' => $this->gate->id,
                'status' => 'approved',
                'search' => 'Hilux',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }

    public function test_admin_qma_can_export_activity_log_pdf(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.activity.export_pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="Laporan_Timesheet_LV_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertNotEmpty($content);
    }

    public function test_admin_qma_can_export_p2h_records_excel(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_P2H_Heavy_Equipment_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_admin_qma_can_export_p2h_records_excel_with_filters(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.export_excel', [
                'validation_status' => 'approved',
                'summary_condition' => 'normal',
                'search' => 'EXC-01',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }

    public function test_admin_qma_can_export_p2h_records_pdf(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.export_pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="Laporan_P2H_Heavy_Equipment_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertNotEmpty($content);
    }

    public function test_admin_qma_can_export_single_p2h_record_pdf(): void
    {
        $record = P2hRecord::first();

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.export_single_pdf', $record->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="Lembar_P2H_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertNotEmpty($content);
    }

    public function test_admin_qma_can_export_single_p2h_record_excel(): void
    {
        $record = P2hRecord::first();

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.export_single_excel', $record->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('P2H_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_p2h_show_page_renders_export_buttons(): void
    {
        $record = P2hRecord::first();

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.p2h.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('Cetak / Ekspor PDF');
        $response->assertSee('Ekspor Excel (.xlsx)');
        $response->assertSee(route('qma.p2h.export_single_pdf', $record->id));
        $response->assertSee(route('qma.p2h.export_single_excel', $record->id));
    }

    public function test_unauthorized_user_cannot_export_activity_and_p2h(): void
    {
        $record = P2hRecord::first();
        $routes = [
            route('qma.activity.export_excel'),
            route('qma.activity.export_pdf'),
            route('qma.p2h.export_excel'),
            route('qma.p2h.export_pdf'),
            route('qma.p2h.export_single_pdf', $record->id),
            route('qma.p2h.export_single_excel', $record->id),
        ];

        foreach ($routes as $routeUrl) {
            $response = $this->actingAs($this->unauthorizedUser)->get($routeUrl);
            $response->assertStatus(403);
        }
    }
}
