<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSimper;
use App\Models\SimperType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HseSimperTypeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminHse;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan permission & role
        Permission::firstOrCreate(['name' => 'hse.employees.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'hse.employees.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'hse.employees.edit', 'guard_name' => 'web']);

        $roleHse = Role::firstOrCreate(['name' => 'admin_hse', 'guard_name' => 'web']);
        $roleHse->givePermissionTo(['hse.employees.view', 'hse.employees.create', 'hse.employees.edit']);

        $this->adminHse = User::create([
            'name' => 'Admin HSE Tester',
            'username' => 'admin_hse_tester',
            'email' => 'admin_hse@kmh.co.id',
            'role' => 'admin_hse',
            'password' => bcrypt('password'),
        ]);
        $this->adminHse->assignRole('admin_hse');

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'username' => 'regular_user',
            'email' => 'user@kmh.co.id',
            'role' => 'employee',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Tamu diarahkan ke login.
     */
    public function test_guest_cannot_access_simper_types(): void
    {
        $response = $this->get(route('hse.simper_types.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Admin HSE dapat melihat halaman master jenis SIMPER.
     */
    public function test_admin_hse_can_view_simper_types_index(): void
    {
        $response = $this->actingAs($this->adminHse)->get(route('hse.simper_types.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Jenis SIMPER');
        $response->assertSee('SIMPER-LV');
        $response->assertSee('SIMPER-DT');
    }

    /**
     * Admin HSE dapat menambahkan jenis SIMPER baru.
     */
    public function test_admin_hse_can_create_new_simper_type(): void
    {
        $data = [
            'code' => 'SIMPER-FORKLIFT',
            'name' => 'SIMPER Forklift & Material Handler',
            'category_scope' => 'unit_support_heavy',
            'description' => 'Izin mengoperasikan unit forklift di gudang.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminHse)
            ->from(route('hse.simper_types.index'))
            ->post(route('hse.simper_types.store'), $data);

        $response->assertRedirect(route('hse.simper_types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('simper_types', [
            'code' => 'SIMPER-FORKLIFT',
            'name' => 'SIMPER Forklift & Material Handler',
            'category_scope' => 'unit_support_heavy',
        ]);
    }

    /**
     * Validasi menolak kode duplikat.
     */
    public function test_cannot_create_duplicate_simper_code(): void
    {
        $existing = SimperType::first();

        $data = [
            'code' => $existing->code,
            'name' => 'Nama Lain',
            'category_scope' => 'all',
        ];

        $response = $this->actingAs($this->adminHse)
            ->from(route('hse.simper_types.index'))
            ->post(route('hse.simper_types.store'), $data);

        $response->assertSessionHasErrors('code');
    }

    /**
     * Admin HSE dapat mengupdate jenis SIMPER.
     */
    public function test_admin_hse_can_update_simper_type(): void
    {
        $type = SimperType::first();

        $data = [
            'code' => $type->code,
            'name' => 'Nama Terkoreksi',
            'category_scope' => 'light_vehicle',
            'description' => 'Deskripsi baru',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminHse)
            ->from(route('hse.simper_types.index'))
            ->put(route('hse.simper_types.update', $type->id), $data);

        $response->assertRedirect(route('hse.simper_types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('simper_types', [
            'id' => $type->id,
            'name' => 'Nama Terkoreksi',
        ]);
    }

    /**
     * Admin HSE dapat mendaftarkan personil (Sukon / Karyawan) dengan multi-SIMPER.
     */
    public function test_admin_hse_can_assign_multi_simper_to_employee(): void
    {
        $typeLv = SimperType::where('code', 'SIMPER-LV')->first();
        $typeDt = SimperType::where('code', 'SIMPER-DT')->first();

        $data = [
            'nik' => 'SKN-9901',
            'name' => 'Bambang Sukon DT',
            'employee_type' => 'Sukon',
            'unit_kerja' => 'PT Mandiri Sukon Perkasa',
            'position' => 'Driver Dump Truck',
            'division' => 'Hauling Operation',
            'is_operator' => '1',
            'simpers' => [
                $typeLv->id => [
                    'simper_number' => 'SMP-LV-001',
                    'expired_at' => Carbon::now()->addYear()->format('Y-m-d'),
                ],
                $typeDt->id => [
                    'simper_number' => 'SMP-DT-001',
                    'expired_at' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                ],
            ],
        ];

        $response = $this->actingAs($this->adminHse)
            ->from(route('hse.employees.index'))
            ->post(route('hse.employees.store'), $data);

        $response->assertRedirect(route('hse.employees.index'));
        $response->assertSessionHas('success');

        $emp = Employee::where('nik', 'SKN-9901')->first();
        $this->assertNotNull($emp);
        $this->assertTrue($emp->isSukon());
        $this->assertEquals(2, $emp->simpers()->count());

        $this->assertTrue($emp->hasValidSimperForType('SIMPER-LV'));
        $this->assertTrue($emp->hasValidSimperForType('SIMPER-DT'));
        $this->assertFalse($emp->hasValidSimperForType('SIMPER-HEAVY'));
    }

    /**
     * Admin HSE dapat membuka view tersendiri (halaman penuh) untuk edit SIMPER personil.
     */
    public function test_admin_hse_can_access_dedicated_employee_edit_page(): void
    {
        $emp = Employee::create([
            'nik' => 'KMH-EDIT-TEST',
            'name' => 'Operator Ekskavator',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Teknik',
            'division' => 'Operasi',
            'position' => 'Operator',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);

        $response = $this->actingAs($this->adminHse)->get(route('hse.employees.edit', $emp->id));
        $response->assertStatus(200);
        $response->assertSee('Kelola SIMPER Personil');
        $response->assertSee('KMH-EDIT-TEST');
        $response->assertSee('Operator Ekskavator');
    }

    /**
     * Admin HSE dapat membuat jenis SIMPER dengan kategori cakupan alat berat yang tersinkronisasi.
     */
    public function test_admin_hse_can_create_simper_with_synced_heavy_equipment_category(): void
    {
        $data = [
            'code' => 'SIMPER-EXCAVATOR',
            'name' => 'SIMPER Excavator Unit',
            'category_scope' => 'Excavator',
            'description' => 'Izin mengoperasikan unit Excavator.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminHse)
            ->from(route('hse.simper_types.index'))
            ->post(route('hse.simper_types.store'), $data);

        $response->assertRedirect(route('hse.simper_types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('simper_types', [
            'code' => 'SIMPER-EXCAVATOR',
            'category_scope' => 'Excavator',
        ]);
    }
}
