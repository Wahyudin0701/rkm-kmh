<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmaHeavyEquipmentThreeLevelNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $qmaAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->qmaAdmin = User::factory()->create([
            'name' => 'Admin QMA Test',
            'username' => 'qma_test_3level',
            'role' => 'qma_admin',
        ]);
        $this->qmaAdmin->assignRole('qma_admin');

        // 1. Hauling Units (Dump Truck & Mixer Truck)
        Vehicle::create([
            'code' => 'DT-01',
            'category' => 'unit_support',
            'operational_group' => 'HAULING',
            'unit_category' => 'Dump Truck',
            'unit_type' => 'DUMP TRUCK',
            'brand' => 'HOWO',
            'brand_type' => 'HOWO SINOTRUK 336',
            'model_number' => 'ZZ3257N3847',
            'serial_number' => 'HW123456',
            'location' => 'Main Dam',
            'site_status' => 'inside_site',
            'status' => 'Operation',
        ]);

        Vehicle::create([
            'code' => 'DT-02',
            'category' => 'unit_support',
            'operational_group' => 'HAULING',
            'unit_category' => 'Dump Truck',
            'unit_type' => 'DUMP TRUCK',
            'brand' => 'HOWO',
            'brand_type' => 'HOWO SINOTRUK 336',
            'location' => 'Adit 3',
            'site_status' => 'inside_site',
            'status' => 'Standby',
        ]);

        Vehicle::create([
            'code' => 'MT-01',
            'category' => 'unit_support',
            'operational_group' => 'HAULING',
            'unit_category' => 'Truk Mixer',
            'unit_type' => 'MIXER TRUCK',
            'brand' => 'YUCHAI',
            'brand_type' => 'YUCHAI DIECI',
            'location' => 'Batching Plant',
            'site_status' => 'inside_site',
            'status' => 'Operation',
        ]);

        // 2. Loader Units (Excavator)
        Vehicle::create([
            'code' => 'EX-01',
            'category' => 'unit_support',
            'operational_group' => 'LOADER',
            'unit_category' => 'Excavator',
            'unit_type' => 'EXCAVATOR',
            'brand' => 'KOMATSU',
            'brand_type' => 'KOMATSU PC200-8',
            'location' => 'Power House',
            'site_status' => 'inside_site',
            'status' => 'Operation',
        ]);

        // 3. Support Units (Compressor)
        Vehicle::create([
            'code' => 'CP-01',
            'category' => 'unit_support',
            'operational_group' => 'SUPPORT',
            'unit_category' => 'Engine Compressor',
            'unit_type' => 'COMPRESSOR',
            'brand' => 'AIRMAN',
            'brand_type' => 'AIRMAN PDS655S',
            'location' => 'Adit 2',
            'site_status' => 'inside_site',
            'status' => 'Breakdown',
        ]);
    }

    /**
     * Level 1: Halaman utama menampilkan daftar Kelompok Operasional (HAULING, LOADER, SUPPORT, dll).
     */
    public function test_level_1_shows_operational_groups(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/unit-support');

        $response->assertStatus(200);
        $response->assertViewIs('qma.vehicles_support');
        $response->assertViewHas('level', 'groups');
        $response->assertViewHas('groups');

        // Memastikan nama-nama kelompok operasional tampil di tabel Level 1
        $response->assertSee('HAULING');
        $response->assertSee('LOADER');
        $response->assertSee('SUPPORT');
        $response->assertSee('Lihat Kategori');
    }

    /**
     * Level 2: Memilih salah satu Kelompok Operasional menampilkan daftar tipe alat di dalamnya.
     */
    public function test_level_2_shows_types_in_selected_group(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/unit-support?group=HAULING');

        $response->assertStatus(200);
        $response->assertViewIs('qma.vehicles_support');
        $response->assertViewHas('level', 'types');
        $response->assertViewHas('selectedGroup', 'HAULING');

        // Tipe dalam kelompok HAULING
        $response->assertSee('DUMP TRUCK');
        $response->assertSee('MIXER TRUCK');

        $typesInView = collect($response->viewData('types')->items())->pluck('unit_type')->all();
        $this->assertContains('DUMP TRUCK', $typesInView);
        $this->assertContains('MIXER TRUCK', $typesInView);
        $this->assertNotContains('EXCAVATOR', $typesInView);
        $this->assertNotContains('COMPRESSOR', $typesInView);

        // Breadcrumb & tombol kembali
        $response->assertSee('Semua Kelompok');
        $response->assertSee('Buka Unit');
    }

    /**
     * Level 3: Memilih tipe alat menampilkan daftar unit fisik individual.
     */
    public function test_level_3_shows_physical_units(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/unit-support?group=HAULING&type=DUMP+TRUCK');

        $response->assertStatus(200);
        $response->assertViewIs('qma.vehicles_support');
        $response->assertViewHas('level', 'units');
        $response->assertViewHas('selectedGroup', 'HAULING');
        $response->assertViewHas('selectedType', 'DUMP TRUCK');

        // Unit fisik Dump Truck
        $response->assertSee('DT-01');
        $response->assertSee('DT-02');
        $response->assertSee('HOWO SINOTRUK 336');
        $response->assertSee('Main Dam');

        $codesInView = collect($response->viewData('typeUnits')->items())->pluck('code')->all();
        $this->assertContains('DT-01', $codesInView);
        $this->assertContains('DT-02', $codesInView);
        $this->assertNotContains('MT-01', $codesInView);
        $this->assertNotContains('EX-01', $codesInView);
    }

    /**
     * Auto-detect group jika user hanya memasukkan query parameter `type`.
     */
    public function test_level_3_auto_detects_group_when_only_type_passed(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/unit-support?type=EXCAVATOR');

        $response->assertStatus(200);
        $response->assertViewHas('level', 'units');
        $response->assertViewHas('selectedGroup', 'LOADER');
        $response->assertViewHas('selectedType', 'EXCAVATOR');
        $response->assertSee('EX-01');
        $response->assertSee('Power House');
    }

    /**
     * Admin QMA dapat mengedit data unit alat berat termasuk divisi penanggung jawab tanpa mereset atau menambah unit baru.
     */
    public function test_qma_admin_can_update_unit_support_division_and_details(): void
    {
        $unit = Vehicle::where('code', 'DT-01')->firstOrFail();

        $response = $this->actingAs($this->qmaAdmin)->post("/qma/vehicles/{$unit->id}", [
            'code' => 'DT-01',
            'category' => 'unit_support',
            'operational_group' => 'HAULING',
            'unit_type' => 'DUMP TRUCK',
            'division' => 'Civil & Dam Construction',
            'status' => 'Operation',
            'location' => 'Main Dam',
            'site_status' => 'inside_site',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $unit->refresh();
        $this->assertEquals('Civil & Dam Construction', $unit->division);
        $this->assertEquals('HAULING', $unit->operational_group);
        $this->assertEquals('DUMP TRUCK', $unit->unit_type);
    }
}
