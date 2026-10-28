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

    public function test_super_admin_and_authorized_user_sees_delete_button_and_modal(): void
    {
        Permission::firstOrCreate(['name' => 'hse.employees.delete']);
        $this->hseAdmin->givePermissionTo('hse.employees.delete');

        $superAdmin = User::factory()->create([
            'username' => 'superadmin_test',
            'role' => 'super_admin',
        ]);
        $superRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->assignRole($superRole);

        $employee = Employee::create([
            'nik' => 'KMH-DEL-001',
            'name' => 'Bambang Sudirman',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Umum',
            'division' => 'Operasional',
            'position' => 'Staff',
            'is_operator' => false,
        ]);

        // Verifikasi pada halaman index (tabel Data SIMPER)
        $responseIndex = $this->actingAs($superAdmin)->get(route('hse.employees.index'));
        $responseIndex->assertStatus(200);
        $contentIndex = $responseIndex->getContent();

        $this->assertStringContainsString('title="Hapus Data Karyawan / Personil"', $contentIndex);
        $this->assertStringContainsString('openDeleteEmployeeModal', $contentIndex);
        $this->assertStringContainsString('id="deleteEmployeeModal"', $contentIndex);

        // Verifikasi pada halaman edit data karyawan
        $responseEdit = $this->actingAs($superAdmin)->get(route('hse.employees.edit', $employee->id));
        $responseEdit->assertStatus(200);
        $contentEdit = $responseEdit->getContent();

        $this->assertStringContainsString('Hapus Karyawan', $contentEdit);
        $this->assertStringContainsString('id="deleteEmployeeModal"', $contentEdit);
    }

    public function test_user_without_delete_permission_cannot_see_delete_button_or_delete(): void
    {
        $officer = User::factory()->create([
            'username' => 'viewer_only',
            'role' => 'guest_officer',
        ]);
        $role = Role::firstOrCreate(['name' => 'guest_officer']);
        $role->syncPermissions(['hse.employees.view']);
        $officer->assignRole($role);

        $employee = Employee::create([
            'nik' => 'KMH-NO-DEL',
            'name' => 'Pegawai Aman',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Teknik',
            'division' => 'Teknik',
            'position' => 'Staff',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($officer)->get(route('hse.employees.index'));
        $response->assertStatus(200);
        $response->assertDontSee('title="Hapus Data Karyawan / Personil"', false);

        // Percobaan request hapus langsung ditolak 403
        $delResponse = $this->actingAs($officer)->delete(route('hse.employees.destroy', $employee->id));
        $delResponse->assertStatus(403);
    }

    public function test_authorized_user_can_delete_employee_and_cleans_up_linked_user(): void
    {
        $superAdmin = User::factory()->create([
            'username' => 'superadmin_deleter',
            'role' => 'super_admin',
        ]);

        $linkedUser = User::create([
            'username' => 'KMH-EMP-LINKED',
            'name' => 'Karyawan Dihapus',
            'email' => 'kmhemplinked@kmh.co.id',
            'password' => bcrypt('123456'),
            'role' => 'employee',
        ]);
        $linkedUser->assignRole('employee');

        $employee = Employee::create([
            'nik' => 'KMH-EMP-LINKED',
            'name' => 'Karyawan Dihapus',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Logistik',
            'division' => 'Gudang',
            'position' => 'Staff',
            'is_operator' => false,
            'user_id' => $linkedUser->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->from(route('hse.employees.index'))
            ->delete(route('hse.employees.destroy', $employee->id));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $response->assertRedirect(route('hse.employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        $this->assertDatabaseMissing('users', ['id' => $linkedUser->id]);
    }

    public function test_cannot_delete_employee_with_operational_records(): void
    {
        $superAdmin = User::factory()->create([
            'username' => 'superadmin_check',
            'role' => 'super_admin',
        ]);

        $employee = Employee::create([
            'nik' => 'KMH-DRIVER-01',
            'name' => 'Driver Senior',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Umum',
            'division' => 'Pool',
            'position' => 'Driver',
            'is_operator' => true,
        ]);

        $gate = \App\Models\Gate::create([
            'code' => 'GATE-01',
            'name' => 'Main Gate',
            'is_active' => true,
        ]);

        $vehicle = \App\Models\Vehicle::create([
            'code' => 'LV-TEST-01',
            'brand_type' => 'Toyota Hilux Single Cabin',
            'license_plate' => 'BH 1234 XX',
            'division' => 'Umum',
            'category' => 'LV',
            'ownership' => 'KMH',
            'status' => 'Operation',
            'current_km' => 1000,
        ]);

        \App\Models\VehicleActivity::create([
            'transaction_no' => 'TRX-TEST-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $employee->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Operasional lapangan',
            'recorded_at' => now(),
            'validation_status' => 'approved',
            'validated_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->from(route('hse.employees.index'))
            ->delete(route('hse.employees.destroy', $employee->id));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_hse_edit_modal_and_edit_page_render_division_and_position_inputs(): void
    {
        $employee = Employee::create([
            'nik' => 'KMH-DIV-POS-01',
            'name' => 'Hendro Siswanto',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Operasi',
            'division' => 'Pembangkit Listrik',
            'position' => 'Supervisor Turbin',
            'is_operator' => false,
        ]);

        // Verifikasi pada index editModal
        $responseIndex = $this->actingAs($this->hseAdmin)->get(route('hse.employees.index'));
        $responseIndex->assertStatus(200);
        $contentIndex = $responseIndex->getContent();

        $this->assertStringContainsString('name="division" id="editDivisionInput"', $contentIndex);
        $this->assertStringContainsString('name="position" id="editPositionInput"', $contentIndex);

        // Verifikasi pada full page edit
        $responseEdit = $this->actingAs($this->hseAdmin)->get(route('hse.employees.edit', $employee->id));
        $responseEdit->assertStatus(200);
        $contentEdit = $responseEdit->getContent();

        $this->assertStringContainsString('name="division"', $contentEdit);
        $this->assertStringContainsString('name="position"', $contentEdit);
        $this->assertStringContainsString('value="Pembangkit Listrik"', $contentEdit);
        $this->assertStringContainsString('value="Supervisor Turbin"', $contentEdit);
    }

    public function test_hse_can_update_employee_division_and_position(): void
    {
        $employee = Employee::create([
            'nik' => 'KMH-DIV-POS-02',
            'name' => 'Siti Nurhaliza',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Umum',
            'division' => 'Operasi',
            'position' => 'Staff',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($this->hseAdmin)
            ->from(route('hse.employees.index'))
            ->post(route('hse.employees.update', $employee->id), [
                'unit_kerja' => 'K3 & Lingkungan',
                'division' => 'HSE',
                'position' => 'Officer K3',
                'is_operator' => '0',
            ]);

        $response->assertSessionHasNoErrors();
        $employee->refresh();

        $this->assertEquals('K3 & Lingkungan', $employee->unit_kerja);
        $this->assertEquals('HSE', $employee->division);
        $this->assertEquals('Officer K3', $employee->position);
    }

    public function test_security_guard_and_gate_officer_auto_populates_private_guard_and_anggota(): void
    {
        // 1. Pembuatan via SecurityGuardController
        $adminPg = User::factory()->create([
            'username' => 'admin_pg_test',
            'role' => 'pg_admin',
        ]);
        $pgRole = Role::firstOrCreate(['name' => 'pg_admin']);
        $adminPg->assignRole($pgRole);

        $responseGuard = $this->actingAs($adminPg)
            ->post(route('pg.guards.store'), [
                'badge_id' => 'SEC-999',
                'name' => 'Satpam Bambang',
                'team' => 1,
                'password' => 'secret123',
            ]);

        $responseGuard->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'nik' => 'SEC-999',
            'name' => 'Satpam Bambang',
            'unit_kerja' => 'Private Guard',
            'division' => 'Private Guard',
            'position' => 'Anggota',
        ]);

        // 2. Pembuatan via Super Admin UserController
        $superAdmin = User::factory()->create([
            'username' => 'superadmin_pg_test',
            'role' => 'super_admin',
        ]);
        $superRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->assignRole($superRole);

        Role::firstOrCreate(['name' => 'gate_officer']);

        $responseUser = $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Satpam Wawan',
                'username' => 'SEC-888',
                'email' => 'sec888@kmh.co.id',
                'password' => 'password123',
                'role' => 'gate_officer',
            ]);

        $responseUser->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', [
            'nik' => 'SEC-888',
            'name' => 'Satpam Wawan',
            'unit_kerja' => 'Private Guard',
            'division' => 'Private Guard',
            'position' => 'Anggota',
        ]);
    }
}
