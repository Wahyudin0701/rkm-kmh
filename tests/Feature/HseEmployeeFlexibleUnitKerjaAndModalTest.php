<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HseEmployeeFlexibleUnitKerjaAndModalTest extends TestCase
{
    use RefreshDatabase;

    private User $hseAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'hse.employees.view']);
        Permission::firstOrCreate(['name' => 'hse.employees.create']);
        Permission::firstOrCreate(['name' => 'hse.employees.edit']);

        $hseRole = Role::firstOrCreate(['name' => 'hse_admin']);
        $hseRole->syncPermissions(['hse.employees.view', 'hse.employees.create', 'hse.employees.edit']);

        Role::firstOrCreate(['name' => 'employee']);

        $this->hseAdmin = User::factory()->create([
            'username' => 'hseadmin_unit_test',
            'role' => 'hse_admin',
        ]);
        $this->hseAdmin->assignRole('hse_admin');
    }

    public function test_hse_employees_index_renders_static_backdrops_and_flexible_unit_kerja_input(): void
    {
        Employee::create([
            'nik' => 'KMH-TEST-001',
            'name' => 'Budi Santoso',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Maintenance Sipil',
            'division' => 'Operasional',
            'position' => 'Teknisi',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.index'));

        $response->assertStatus(200);
        $response->assertViewHas('unitKerjaList');

        $content = $response->getContent();

        // Verifikasi perlindungan backdrop modal form
        $this->assertStringContainsString('id="addKaryawanModal" data-backdrop="static"', $content);
        $this->assertStringContainsString('id="editModal" data-backdrop="static"', $content);
        $this->assertStringContainsString('id="renewModal" data-backdrop="static"', $content);
        $this->assertStringContainsString('id="importKaryawanModal" data-backdrop="static"', $content);

        // Verifikasi tidak ada inline onclick auto-close yang merusak blok teks
        $this->assertStringNotContainsString('if(event.target === this) this.classList.add(\'hidden\')', $content);

        // Verifikasi input isian unit kerja fleksibel dengan datalist saran
        $this->assertStringContainsString('name="unit_kerja" id="addUnitKerjaInput" list="unitKerjaSuggestions"', $content);
        $this->assertStringContainsString('name="unit_kerja" id="editUnitKerjaInput" list="unitKerjaSuggestions"', $content);
        $this->assertStringContainsString('<datalist id="unitKerjaSuggestions">', $content);
        $this->assertStringContainsString('<option value="Maintenance Sipil">', $content);
    }

    public function test_hse_can_store_employee_with_custom_flexible_unit_kerja(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->from(route('hse.employees.index'))
            ->post(route('hse.employees.store'), [
                'nik' => 'KMH-SIPIL-99',
                'name' => 'Ahmad Teknisi',
                'employee_type' => 'Sukon',
                'unit_kerja' => 'Subkontraktor Sipil & Piping',
                'division' => 'Proyek Konstruksi',
                'position' => 'Welder Specialist',
                'is_operator' => '0',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('hse.employees.index'));

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-SIPIL-99',
            'name' => 'Ahmad Teknisi',
            'employee_type' => 'Sukon',
            'unit_kerja' => 'Subkontraktor Sipil & Piping',
            'division' => 'Proyek Konstruksi',
            'position' => 'Welder Specialist',
            'is_operator' => 0,
        ]);
    }

    public function test_hse_can_update_employee_with_flexible_unit_kerja(): void
    {
        $employee = Employee::create([
            'nik' => 'KMH-UPD-001',
            'name' => 'Rian Hidayat',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Umum',
            'division' => 'Operasional',
            'position' => 'Staff',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($this->hseAdmin)
            ->from(route('hse.employees.index'))
            ->post(route('hse.employees.update', $employee->id), [
                'unit_kerja' => 'Klinik & Keselamatan Kerja',
                'is_operator' => '0',
            ]);

        $response->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertEquals('Klinik & Keselamatan Kerja', $employee->unit_kerja);
    }

    public function test_hse_employees_edit_page_renders_flexible_unit_kerja_input(): void
    {
        $employee = Employee::create([
            'nik' => 'KMH-EDIT-PAGE',
            'name' => 'Dewi Sartika',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Laboratorium Kimia',
            'division' => 'Operasional',
            'position' => 'Analis',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.edit', $employee->id));

        $response->assertStatus(200);
        $response->assertViewHas('unitKerjaList');

        $content = $response->getContent();
        $this->assertStringContainsString('name="unit_kerja" list="unitKerjaSuggestions"', $content);
        $this->assertStringContainsString('value="Laboratorium Kimia"', $content);
        $this->assertStringContainsString('<datalist id="unitKerjaSuggestions">', $content);
    }
}
