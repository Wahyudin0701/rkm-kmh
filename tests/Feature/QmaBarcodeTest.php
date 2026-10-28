<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSimper;
use App\Models\Gate;
use App\Models\SimperType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QmaBarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected User $driverUser;
    protected Employee $driverEmployee;
    protected Vehicle $vehicleLv;
    protected Gate $gate;
    protected SimperType $simperLv;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'qma.vehicles.view']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'employee.p2h']);
        $roleQma = Role::firstOrCreate(['name' => 'admin_qma']);
        $roleQma->givePermissionTo('qma.vehicles.view');
        $roleEmp = Role::firstOrCreate(['name' => 'employee']);
        $roleEmp->givePermissionTo('employee.p2h');

        $this->qmaAdmin = User::factory()->create([
            'username' => 'qma_barcode_tester',
            'role' => 'admin_qma',
        ]);
        $this->qmaAdmin->assignRole('admin_qma');

        $this->driverUser = User::factory()->create([
            'username' => 'driver_barcode_tester',
            'role' => 'employee',
        ]);
        $this->driverUser->assignRole('employee');

        $this->driverEmployee = Employee::create([
            'user_id' => $this->driverUser->id,
            'nik' => 'DRV-BC-001',
            'name' => 'Driver Barcode Test',
            'employee_type' => 'Karyawan',
            'unit_kerja' => 'Umum',
            'division' => 'Operasional',
            'position' => 'Driver',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);

        $this->simperLv = SimperType::create([
            'code' => 'SIMPER-LV-BC',
            'name' => 'SIMPER Light Vehicle',
            'category_scope' => 'light_vehicle',
            'is_active' => true,
        ]);

        EmployeeSimper::create([
            'employee_id' => $this->driverEmployee->id,
            'simper_type_id' => $this->simperLv->id,
            'simper_number' => 'SMP-LV-BC-01',
            'expired_at' => Carbon::now()->addYear(),
            'status' => 'active',
        ]);

        $this->gate = Gate::create([
            'code' => 'GATE-BC-01',
            'name' => 'Gate Utama Pos A',
            'is_active' => true,
        ]);

        $this->vehicleLv = Vehicle::create([
            'code' => 'LV-BC-99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4 Double Cabin',
            'license_plate' => 'BH 9999 BC',
            'division' => 'Operasional',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
            'current_km' => 12500,
        ]);
    }

    /**
     * 1. Halaman QMA Light Vehicle merender barcode/QR code dan modal stiker dengan lengkap.
     */
    public function test_qma_lv_page_renders_working_barcode_and_qr_modal(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get('/qma/vehicles/light-vehicle');
        $response->assertOk();

        // 1. Kolom QR Code pada tabel
        $response->assertSee('QR CODE');
        $response->assertSee('LV-BC-99');

        // 2. Data attribute untuk modal dan generator SVG
        $expectedUrl = url('/gate-activity/' . $this->vehicleLv->code);
        $response->assertSee($this->vehicleLv->code);
        $response->assertSee(e($expectedUrl));
        $response->assertSee('data-qr-base64', false);

        // 3. Modal Stiker QR Code (Dual Orientation: Landscape & Portrait)
        $response->assertSee('qrVehicleModal');
        $response->assertSee('printableStickerAreaLandscape');
        $response->assertSee('printableStickerAreaPortrait');
        $response->assertSee('btnTabLandscape');
        $response->assertSee('btnTabPortrait');
        $response->assertSee('Landscape');
        $response->assertSee('Potrait');
        $response->assertSee('TIMESHEET KENDARAAN OPERASIONAL');
        $response->assertSee('LIGHT VEHICLE (LV) • RKM SYSTEM');
        $response->assertSee('Cetak Landscape');
        $response->assertSee('Cetak Potrait');
        $response->assertSee('Unduh Landscape (PNG)');
        $response->assertSee('Unduh Potrait (PNG)');
        $response->assertSee('Tes Form');
    }

    /**
     * 2. URL hasil scan barcode dapat dibuka langsung dan memuat formulir input pergerakan unit.
     */
    public function test_scanned_barcode_url_loads_gate_activity_form_successfully(): void
    {
        // Pengemudi scan barcode pada mobil (URL: /gate-activity/LV-BC-99)
        $response = $this->actingAs($this->driverUser)->get('/gate-activity/' . $this->vehicleLv->code);
        $response->assertOk();

        // Tampilan form memuat data mobil yang tepat sesuai barcode
        $response->assertSee('LV-BC-99');
        $response->assertSee('Toyota Hilux 4x4 Double Cabin');
        $response->assertSee('12500'); // Odometer terkini
        $response->assertSee('Gate Utama Pos A');
    }

    /**
     * 3. Pengemudi yang scan barcode dapat melakukan verifikasi NIK dan submit form timesheet.
     */
    public function test_driver_can_verify_nik_and_submit_activity_from_scanned_barcode(): void
    {
        // 1. AJAX Check NIK dari form hasil scan barcode
        $checkNikRes = $this->actingAs($this->driverUser)->postJson("/gate-activity/{$this->vehicleLv->code}/check-nik", [
            'nik' => $this->driverEmployee->nik,
        ]);
        $checkNikRes->assertOk();
        $checkNikRes->assertJsonPath('status', 'valid');
        $checkNikRes->assertJsonPath('employee.name', $this->driverEmployee->name);

        // 2. Submit form pergerakan unit (Keluar / out)
        $submitRes = $this->actingAs($this->driverUser)->post("/gate-activity/{$this->vehicleLv->code}", [
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'current_km' => 12550, // KM bertambah 50
            'purpose' => 'Perjalanan dinas operasional site via scan barcode',
        ]);

        $submitRes->assertRedirect();
        $submitRes->assertSessionHas('success');

        // Pastikan aktivitas tercatat di database dengan nomor transaksi
        $this->assertDatabaseHas('vehicle_activities', [
            'vehicle_id' => $this->vehicleLv->id,
            'driver_id' => $this->driverEmployee->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 12500,
            'current_km' => 12550,
            'distance' => 50,
            'validation_status' => 'pending',
        ]);
    }

    /**
     * 4. Halaman QMA Heavy Equipment Unit Support merender kolom QR CODE dan modal stiker.
     */
    public function test_qma_unit_support_page_renders_working_barcode_and_qr_modal(): void
    {
        // 1. Kategori Support (Dump Truck)
        $category = Vehicle::create([
            'code' => 'CAT-DT-01',
            'category' => 'unit_support',
            'unit_category' => 'Dump Truck',
            'brand_type' => 'Hino 500 FM 260 JD',
            'model_number' => 'FM260JD',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
        ]);

        // 2. Unit Fisik 1: Timesheet Mobile (Dump Truck operasional)
        $unitMobile = Vehicle::create([
            'parent_id' => $category->id,
            'code' => 'DT-BC-01',
            'category' => 'unit_support',
            'brand_type' => 'Hino 500 FM 260 JD',
            'license_plate' => 'BH 8888 DT',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
            'location' => 'Quarry Area A',
            'current_km' => 45000,
            'is_timesheet_mobile' => true,
        ]);

        // 3. Unit Fisik 2: Standard P2H Heavy Equipment
        $unitStandard = Vehicle::create([
            'parent_id' => $category->id,
            'code' => 'DT-BC-02',
            'category' => 'unit_support',
            'brand_type' => 'Hino 500 FM 260 JD',
            'license_plate' => 'BH 8889 DT',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
            'location' => 'Workshop Utama',
            'current_km' => 12000,
            'is_timesheet_mobile' => false,
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get("/qma/vehicles/unit-support/{$category->id}/units");
        $response->assertOk();

        // Verifikasi kolom QR CODE dan unit fisik
        $response->assertSee('QR CODE');
        $response->assertSee('DT-BC-01');
        $response->assertSee('DT-BC-02');
        $response->assertSee('data-qr-base64', false);

        // Unit mobile mengarah ke gate activity
        $expectedMobileUrl = url('/gate-activity/' . $unitMobile->code);
        $response->assertSee(e($expectedMobileUrl));

        // Unit standard P2H mengarah ke p2h form
        $expectedStandardUrl = url('/p2h/' . $unitStandard->code);
        $response->assertSee(e($expectedStandardUrl));

        // Verifikasi Modal Stiker QR Code Heavy Equipment (Dual Orientation: Landscape & Portrait)
        $response->assertSee('qrVehicleModal');
        $response->assertSee('printableStickerAreaLandscape');
        $response->assertSee('printableStickerAreaPortrait');
        $response->assertSee('btnTabLandscape');
        $response->assertSee('btnTabPortrait');
        $response->assertSee('Landscape');
        $response->assertSee('Potrait');
        $response->assertSee('Cetak Landscape');
        $response->assertSee('Cetak Potrait');
        $response->assertSee('Unduh Landscape (PNG)');
        $response->assertSee('Unduh Potrait (PNG)');
        $response->assertSee('Tes Form');
    }

    /**
     * 5. URL hasil scan barcode alat berat P2H (/p2h/{code}) dapat dimuat dengan checklist form.
     */
    public function test_scanned_barcode_for_heavy_equipment_p2h_loads_checklist_form_successfully(): void
    {
        $template = \App\Models\P2hTemplate::create([
            'code' => 'TPL-EX-01',
            'name' => 'Pemeriksaan Harian Excavator',
            'is_active' => true,
        ]);

        $category = Vehicle::create([
            'code' => 'CAT-EX-01',
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'brand_type' => 'Komatsu PC200-8',
            'status' => 'Operation',
            'p2h_template_id' => $template->id,
        ]);

        $unitExcavator = Vehicle::create([
            'parent_id' => $category->id,
            'p2h_template_id' => $template->id,
            'code' => 'EX-BC-01',
            'category' => 'unit_support',
            'brand_type' => 'Komatsu PC200-8',
            'status' => 'Operation',
            'location' => 'Powerhouse Area',
            'current_km' => 3200,
        ]);

        $response = $this->actingAs($this->driverUser)->get('/p2h/' . $unitExcavator->code);
        $response->assertOk();
        $response->assertSee('EX-BC-01');
        $response->assertSee('Komatsu PC200-8');
        $response->assertSee('Powerhouse Area');
    }
}
