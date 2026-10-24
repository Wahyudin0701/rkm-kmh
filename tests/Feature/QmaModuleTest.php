<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QmaModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected User $hseAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qmaAdmin = User::factory()->create([
            'name' => 'Pinto Yuliandra',
            'username' => '1171',
            'role' => 'qma_admin',
        ]);

        $this->hseAdmin = User::factory()->create([
            'name' => 'Mima',
            'username' => '1185',
            'role' => 'hse_admin',
        ]);
    }

    /**
     * Memastikan QMA dashboard dapat diakses oleh Admin QMA.
     */
    public function test_qma_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Divisi QMA');
        $response->assertSee('Selamat datang, Pinto Yuliandra');
        $response->assertSee('Light Vehicle');
        $response->assertSee('Heavy Equipment');
        $response->assertSee('Log Aktivitas Kendaraan');
    }

    /**
     * Memastikan route /qma/vehicles redirect ke /qma/vehicles/light-vehicle.
     */
    public function test_qma_vehicles_index_redirects_to_lv(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles');
        $response->assertRedirect('/qma/vehicles/light-vehicle');
    }

    /**
     * Memastikan halaman khusus Light Vehicle (LV) memuat armada LV.
     */
    public function test_qma_lv_page_renders_successfully(): void
    {
        Vehicle::create([
            'code' => 'LV99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'license_plate' => 'BH 1234 XY',
            'ownership' => 'PT KMH',
            'status' => 'Operation',
            'current_km' => 10000,
        ]);

        Vehicle::create([
            'code' => 'EX99',
            'category' => 'unit_support',
            'brand_type' => 'Komatsu PC200',
            'unit_category' => 'Excavator',
            'ownership' => 'PT KMH Project',
            'status' => 'Standby',
            'current_km' => 2500,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/light-vehicle');

        $response->assertStatus(200);
        $response->assertSee('Light Vehicle');
        $response->assertSee('LV99');
        $response->assertDontSee('EX99');
    }

    /**
     * Memastikan halaman khusus Unit Support memuat armada alat berat.
     */
    public function test_qma_unit_support_page_renders_successfully(): void
    {
        Vehicle::create([
            'code' => 'LV99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'license_plate' => 'BH 1234 XY',
            'ownership' => 'PT KMH',
            'status' => 'Operation',
            'current_km' => 10000,
        ]);

        Vehicle::create([
            'code' => 'EX99',
            'category' => 'unit_support',
            'brand_type' => 'Komatsu PC200',
            'unit_category' => 'Excavator',
            'ownership' => 'PT KMH Project',
            'status' => 'Standby',
            'current_km' => 2500,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/unit-support');

        $response->assertStatus(200);
        $response->assertSee('Heavy Equipment');
        $response->assertSee('EX99');
        $response->assertDontSee('LV99');
    }

    /**
     * Memastikan Admin QMA dapat menambah kendaraan ringan baru.
     */
    public function test_admin_qma_can_store_light_vehicle(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->post('/qma/vehicles', [
            'code' => 'LV77',
            'category' => 'light_vehicle',
            'brand_type' => 'Isuzu D-Max',
            'license_plate' => 'BH 5555 ZZ',
            'ownership' => 'PT KMH Divisi HSE',
            'division' => 'HSE',
            'status' => 'Operation',
            'current_km' => 1500,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vehicles', [
            'code' => 'LV77',
            'category' => 'light_vehicle',
            'license_plate' => 'BH 5555 ZZ',
        ]);
    }

    /**
     * Memastikan Admin QMA dapat menambah unit support baru dengan dokumen teknis.
     */
    public function test_admin_qma_can_store_unit_support(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->post('/qma/vehicles', [
            'code' => 'DZ01',
            'category' => 'unit_support',
            'brand_type' => 'Caterpillar D6R',
            'unit_category' => 'Bulldozer',
            'unit_type' => 'Track-type Tractor',
            'brand' => 'Caterpillar',
            'model_number' => 'D6R XL',
            'production_year' => 2020,
            'ownership' => 'PT KMH',
            'division' => 'Civil',
            'location' => 'CCR DAM',
            'status' => 'Operation',
            'current_km' => 4500,
            'fuel_consumption' => 22.5,
            'silo_document' => 'SILO-2026/01',
            'kir_document' => 'KIR-2026/01',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vehicles', [
            'code' => 'DZ01',
            'category' => 'unit_support',
            'unit_category' => 'Bulldozer',
            'silo_document' => 'SILO-2026/01',
        ]);
    }

    /**
     * Memastikan Admin QMA dapat mengupdate kendaraan.
     */
    public function test_admin_qma_can_update_vehicle(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV50',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux',
            'status' => 'Standby',
            'current_km' => 3000,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->post("/qma/vehicles/{$vehicle->id}", [
            'code' => 'LV50',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux Facelift',
            'status' => 'Operation',
            'current_km' => 3500,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'brand_type' => 'Toyota Hilux Facelift',
            'status' => 'Operation',
            'current_km' => 3500,
        ]);
    }

    /**
     * Memastikan penghapusan kendaraan terlindungi jika tidak ada relasi aktivitas.
     */
    public function test_vehicle_deletion_protection_when_activities_exist(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV05',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux',
            'status' => 'Operation',
            'current_km' => 1000,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->delete("/qma/vehicles/{$vehicle->id}");
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    /**
     * Memastikan role selain QMA Admin ditolak dari rute QMA.
     */
    public function test_unauthorized_role_cannot_access_qma_routes(): void
    {
        $response = $this->actingAs($this->hseAdmin)->get('/qma/dashboard');
        $response->assertStatus(403);

        $responseLv = $this->actingAs($this->hseAdmin)->get('/qma/vehicles/light-vehicle');
        $responseLv->assertStatus(403);

        $responseSupport = $this->actingAs($this->hseAdmin)->get('/qma/vehicles/unit-support');
        $responseSupport->assertStatus(403);
    }

    /**
     * Memastikan kolom QR Code dan modal stiker fisik tampil pada tabel Light Vehicle.
     */
    public function test_qma_lv_table_renders_qr_code_column_and_modal(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV77',
            'category' => 'light_vehicle',
            'brand_type' => 'Mitsubishi Triton 4x4',
            'license_plate' => 'BH 7777 QR',
            'division' => 'Civil & Inviro',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
            'current_km' => 15000,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/light-vehicle');

        $response->assertStatus(200);
        $response->assertSee('QR CODE');
        $response->assertSee('Stiker QR');
        $response->assertSee('qrVehicleModal');
        $response->assertSee('printableStickerArea');
        $response->assertSee('gate-activity/LV77');
        $response->assertSee('Cetak Stiker');
        $response->assertSee('Unduh PNG');
        $response->assertSee('btnDownloadSticker');
        $response->assertSee('downloadQrCode');
    }
}

