<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmaVehicleExportTest extends TestCase
{
    use RefreshDatabase;

    private User $qmaAdmin;
    private User $unauthorizedUser;
    private Vehicle $lvVehicle;
    private Vehicle $supportCategory;

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

        // Seed Sample Light Vehicle
        $this->lvVehicle = Vehicle::create([
            'code' => 'LV-01',
            'category' => 'light_vehicle',
            'brand' => 'Toyota',
            'brand_type' => 'Toyota Hilux Double Cabin 4x4',
            'unit_type' => 'Double Cabin',
            'license_plate' => 'BH 8888 KM',
            'ownership' => 'PT KMH',
            'department' => 'Civil & Inviro',
            'division' => 'Operasional',
            'status' => 'Operation',
            'current_km' => 15400,
        ]);

        // Seed Sample Unit Support Category
        $this->supportCategory = Vehicle::create([
            'code' => 'CAT-EXC-01',
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'brand' => 'Komatsu',
            'brand_type' => 'Komatsu PC200-8',
            'model_number' => 'PC200-8',
            'location' => 'Main Dam KMH',
            'ownership' => 'PT KMH Project',
            'status' => 'Operation',
            'parent_id' => null,
        ]);
    }

    public function test_admin_qma_can_export_lv_excel(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.lv.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_Data_Light_Vehicle_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_admin_qma_can_export_lv_excel_with_filters(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.lv.export_excel', [
                'status' => 'Operation',
                'search' => 'Hilux',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }

    public function test_admin_qma_can_export_lv_pdf(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.lv.export_pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline; filename="Laporan_Light_Vehicle_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertNotEmpty($content);
    }

    public function test_admin_qma_can_export_support_excel_with_category_fallback(): void
    {
        // Saat belum ada unit fisik, mengekspor daftar master kategori
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_Kategori_Unit_Support_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_admin_qma_can_export_support_excel_with_physical_units(): void
    {
        // Buat unit fisik alat berat di bawah kategori
        Vehicle::create([
            'code' => 'EXC-01',
            'category' => 'unit_support',
            'parent_id' => $this->supportCategory->id,
            'brand_type' => 'Komatsu PC200-8',
            'serial_number' => 'KMH-SN-998811',
            'production_year' => 2023,
            'license_plate' => 'BH 1234 EX',
            'location' => 'Powerhouse Intake',
            'current_km' => 3200,
            'status' => 'Operation',
            'silo_document' => 'silo_docs/sample.pdf',
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_Unit_Support_Lengkap_', (string) $response->headers->get('Content-Disposition'));
        
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);
    }

    public function test_admin_qma_can_export_support_pdf(): void
    {
        // 1. Fallback master kategori
        $responseFallback = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.export_pdf'));

        $responseFallback->assertStatus(200);
        $responseFallback->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $responseFallback->getContent());

        // 2. Dengan unit fisik
        Vehicle::create([
            'code' => 'EXC-02',
            'category' => 'unit_support',
            'parent_id' => $this->supportCategory->id,
            'brand_type' => 'Komatsu PC200-8',
            'serial_number' => 'KMH-SN-776655',
            'production_year' => 2022,
            'location' => 'Quarry 2',
            'current_km' => 1500,
            'status' => 'Standby',
        ]);

        $responsePhysical = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.export_pdf'));

        $responsePhysical->assertStatus(200);
        $responsePhysical->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Laporan_Unit_Support_', (string) $responsePhysical->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $responsePhysical->getContent());
    }

    public function test_admin_qma_can_export_category_specific_units_excel_and_pdf(): void
    {
        Vehicle::create([
            'code' => 'EXC-03',
            'category' => 'unit_support',
            'parent_id' => $this->supportCategory->id,
            'brand_type' => 'Komatsu PC200-8',
            'serial_number' => 'KMH-SN-443322',
            'production_year' => 2024,
            'location' => 'Dam Site',
            'current_km' => 450,
            'status' => 'Operation',
        ]);

        // Excel
        $responseExcel = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.units.export_excel', $this->supportCategory->id));

        $responseExcel->assertStatus(200);
        $responseExcel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $responseExcel->getContent());

        // PDF
        $responsePdf = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.units.export_pdf', $this->supportCategory->id));

        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $responsePdf->getContent());
    }

    public function test_unauthorized_user_cannot_export_qma_vehicles(): void
    {
        $routes = [
            route('qma.vehicles.lv.export_excel'),
            route('qma.vehicles.lv.export_pdf'),
            route('qma.vehicles.support.export_excel'),
            route('qma.vehicles.support.export_pdf'),
            route('qma.vehicles.support.units.export_excel', $this->supportCategory->id),
            route('qma.vehicles.support.units.export_pdf', $this->supportCategory->id),
        ];

        foreach ($routes as $routeUrl) {
            $response = $this->actingAs($this->unauthorizedUser)->get($routeUrl);
            $response->assertStatus(403);
        }
    }
}
