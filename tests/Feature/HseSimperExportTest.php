<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HseSimperExportTest extends TestCase
{
    use RefreshDatabase;

    private User $hseAdmin;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $roleHse = Role::firstOrCreate(['name' => 'hse_admin', 'guard_name' => 'web']);
        $permissionView = Permission::firstOrCreate(['name' => 'hse.employees.view', 'guard_name' => 'web']);
        $roleHse->givePermissionTo($permissionView);

        $this->hseAdmin = User::factory()->create();
        $this->hseAdmin->assignRole('hse_admin');

        $this->unauthorizedUser = User::factory()->create();

        // Seed sample employees
        Employee::create([
            'nik' => '10001',
            'name' => 'Budi Operator',
            'position' => 'Operator Excavator',
            'unit_kerja' => 'Operasional',
            'division' => 'Alat Berat',
            'is_operator' => true,
            'simper_expired_at' => now()->addMonths(6)->toDateString(),
        ]);

        Employee::create([
            'nik' => '10002',
            'name' => 'Siti Staff',
            'position' => 'Admin Keuangan',
            'unit_kerja' => 'Kantor Utama',
            'division' => 'Finance',
            'is_operator' => false,
            'simper_expired_at' => null,
        ]);
    }

    public function test_admin_hse_can_export_simper_excel(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.export_excel'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Laporan_Data_Simper_', $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_hse_can_export_simper_excel_with_filters(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.export_excel', [
                'driver_status' => 'driver',
                'simper_status' => 'valid',
                'search' => 'Budi',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_hse_can_export_simper_pdf(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.export_pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Laporan_Daftar_Simper_', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertNotEmpty($response->getContent());
    }

    public function test_admin_hse_can_export_simper_pdf_with_driver_filter(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.export_pdf', ['driver_status' => 'driver']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertNotEmpty($response->getContent());
        $this->assertStringContainsString('Laporan_Daftar_Simper_', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_unauthorized_user_cannot_export_simper(): void
    {
        $responseExcel = $this->actingAs($this->unauthorizedUser)
            ->get(route('hse.employees.export_excel'));
        $responseExcel->assertStatus(403);

        $responsePdf = $this->actingAs($this->unauthorizedUser)
            ->get(route('hse.employees.export_pdf'));
        $responsePdf->assertStatus(403);
    }
}
