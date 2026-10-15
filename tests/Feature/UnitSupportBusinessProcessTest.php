<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitSupportBusinessProcessTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qmaAdmin = User::firstOrCreate(
            ['username' => 'qma_test_admin'],
            [
                'name' => 'QMA Tester',
                'email' => 'qma_test_admin@kmh.co.id',
                'role' => 'qma_admin',
                'password' => bcrypt('password'),
            ]
        );
    }

    /**
     * Pastikan halaman daftar kategori unit support menampilkan kategori dengan units_count.
     */
    public function test_qma_admin_can_view_support_categories(): void
    {
        $category = Vehicle::firstOrCreate(
            ['code' => 'TEST-CAT'],
            [
                'category' => 'unit_support',
                'brand_type' => 'Test Excavator X',
                'unit_category' => 'Excavator',
                'status' => 'Operation',
                'current_km' => 100,
            ]
        );

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support'));

        $response->assertStatus(200);
        $response->assertSee('TEST-CAT');
        $response->assertSee('Tambah Kategori Unit');
    }

    /**
     * Admin dapat melihat halaman daftar unit fisik di bawah kategori tertentu.
     */
    public function test_qma_admin_can_view_physical_units_page(): void
    {
        $category = Vehicle::firstOrCreate(
            ['code' => 'TEST-BL'],
            [
                'category' => 'unit_support',
                'brand_type' => 'Test Boomlift',
                'unit_category' => 'Boomlift',
                'status' => 'Operation',
                'current_km' => 50,
            ]
        );

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.units', $category->id));

        $response->assertStatus(200);
        $response->assertSee('Tambah Unit Baru');
        $response->assertSee('TEST-BL');
    }

    /**
     * Pencarian unit fisik menampilkan hasil cocok atau pesan seragam jika tidak ditemukan.
     */
    public function test_qma_admin_can_search_physical_units_and_renders_empty_state_when_not_found(): void
    {
        $category = Vehicle::firstOrCreate(
            ['code' => 'TEST-SEARCH'],
            [
                'category' => 'unit_support',
                'brand_type' => 'Test Forklift',
                'unit_category' => 'Forklift',
                'status' => 'Operation',
                'current_km' => 50,
            ]
        );

        $category->units()->create([
            'code' => 'TEST-SEARCH-001',
            'category' => 'unit_support',
            'brand_type' => 'Test Forklift',
            'status' => 'Operation',
            'current_km' => 120,
            'location' => 'Workshop Central',
            'silo_document' => 'SILO-FL-2026',
        ]);

        // 1. Pencarian dengan kata kunci yang cocok
        $resFound = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.units', ['id' => $category->id, 'search' => 'Workshop']));
        $resFound->assertStatus(200);
        $resFound->assertSee('TEST-SEARCH-001');
        $resFound->assertSee('Workshop Central');
        $resFound->assertSee('unitSearchInput');
        $resFound->assertSee('name="search"', false);
        $resFound->assertSee('value="Workshop"', false);

        // 2. Pencarian dengan kata kunci yang tidak ditemukan
        $resNotFound = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.support.units', ['id' => $category->id, 'search' => 'nonexistentkeyword']));
        $resNotFound->assertStatus(200);
        $resNotFound->assertDontSee('TEST-SEARCH-001');
        $resNotFound->assertSee('Data "<span class="text-rose-600 font-bold">nonexistentkeyword</span>" tidak ditemukan di tabel', false);
        $resNotFound->assertSee('Pastikan kata kunci yang dicari sudah benar.');
        $resNotFound->assertSee('Hapus Pencarian');
    }

    /**
     * Admin dapat menambahkan unit fisik baru dan kode digenerate format 3-digit.
     */
    public function test_qma_admin_can_create_physical_units_with_sequential_code(): void
    {
        $category = Vehicle::firstOrCreate(
            ['code' => 'TEST-CR'],
            [
                'category' => 'unit_support',
                'brand_type' => 'Test Crane',
                'unit_category' => 'Crane',
                'status' => 'Operation',
                'current_km' => 0,
            ]
        );

        // Tambah unit fisik pertama
        $response1 = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.vehicles.support.units.store', $category->id), [
                'unit_number' => '001',
                'serial_number' => 'SN-CRANE-9988',
                'license_plate' => 'BH 5544 XX',
                'production_year' => 2022,
                'status' => 'Operation',
                'current_km' => 10,
                'location' => 'Inlet Dam',
            ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'code' => 'TEST-CR-001',
            'parent_id' => $category->id,
            'category' => 'unit_support',
            'serial_number' => 'SN-CRANE-9988',
            'license_plate' => 'BH 5544 XX',
            'production_year' => 2022,
        ]);

        // Tambah unit fisik kedua dengan auto-number
        $response2 = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.vehicles.support.units.store', $category->id), [
                'unit_number' => '',
                'status' => 'Standby',
                'current_km' => 0,
            ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'code' => 'TEST-CR-002',
            'parent_id' => $category->id,
            'category' => 'unit_support',
        ]);
    }

    /**
     * Ketika kode kategori diubah, kode unit fisik di bawahnya otomatis tersinkronisasi.
     */
    public function test_updating_category_code_cascades_to_physical_units(): void
    {
        $category = Vehicle::firstOrCreate(
            ['code' => 'OLD01'],
            [
                'category' => 'unit_support',
                'brand_type' => 'Old Category',
                'unit_category' => 'Bulldozer',
                'status' => 'Operation',
                'current_km' => 0,
            ]
        );

        // Buat unit fisik
        $unit1 = Vehicle::firstOrCreate(
            ['code' => 'OLD01-001'],
            [
                'parent_id' => $category->id,
                'category' => 'unit_support',
                'brand_type' => 'Old Category',
                'status' => 'Operation',
                'current_km' => 0,
            ]
        );

        $unit2 = Vehicle::firstOrCreate(
            ['code' => 'OLD01-002'],
            [
                'parent_id' => $category->id,
                'category' => 'unit_support',
                'brand_type' => 'Old Category',
                'status' => 'Operation',
                'current_km' => 0,
            ]
        );

        // Update kategori dari OLD01 menjadi NEW01
        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.vehicles.update', $category->id), [
                'code' => 'NEW01',
                'category' => 'unit_support',
                'brand_type' => 'Old Category Renamed',
                'status' => 'Operation',
                'current_km' => 0,
            ]);

        $response->assertRedirect();

        // Kategori terupdate
        $this->assertDatabaseHas('vehicles', [
            'id' => $category->id,
            'code' => 'NEW01',
        ]);

        // Seluruh anak fisik otomatis tersinkronisasi
        $this->assertDatabaseHas('vehicles', [
            'id' => $unit1->id,
            'code' => 'NEW01-001',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'id' => $unit2->id,
            'code' => 'NEW01-002',
        ]);

        // Kode lama tidak boleh ada lagi
        $this->assertDatabaseMissing('vehicles', ['code' => 'OLD01-001']);
        $this->assertDatabaseMissing('vehicles', ['code' => 'OLD01-002']);
    }

    /**
     * Pastikan modul Light Vehicle (LV) tidak terpengaruh sama sekali.
     */
    public function test_light_vehicle_module_remains_unaffected(): void
    {
        $lv = Vehicle::firstOrCreate(
            ['code' => 'LV-TEST-99'],
            [
                'category' => 'light_vehicle',
                'brand_type' => 'Toyota Hilux Test',
                'license_plate' => 'BH 9999 XX',
                'status' => 'Operation',
                'current_km' => 12000,
            ]
        );

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.vehicles.lv'));

        $response->assertStatus(200);
        $response->assertSee('LV-TEST-99');
        $this->assertNull($lv->parent_id);
    }
}
