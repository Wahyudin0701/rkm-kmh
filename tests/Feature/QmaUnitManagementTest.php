<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use App\Models\VehicleLocationHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QmaUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected User $gateOfficer;
    protected Gate $gate;
    protected SecurityGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan permission dan role untuk testing
        Permission::firstOrCreate(['name' => 'qma.vehicles.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'gate.inspect', 'guard_name' => 'web']);

        $qmaRole = Role::firstOrCreate(['name' => 'qma_admin', 'guard_name' => 'web']);
        $qmaRole->givePermissionTo(['qma.vehicles.view', 'qma.vehicles.edit']);

        $gateRole = Role::firstOrCreate(['name' => 'gate_officer', 'guard_name' => 'web']);
        $gateRole->givePermissionTo(['gate.inspect']);

        $this->gate = Gate::firstOrCreate(
            ['code' => 'GATE-01'],
            ['name' => 'Pos Gerbang Beringin Tunggal', 'is_active' => true]
        );

        $this->guard = SecurityGuard::firstOrCreate(
            ['badge_id' => 'SEC-001'],
            ['name' => 'Budi Santoso', 'is_active' => true]
        );

        $this->qmaAdmin = User::firstOrCreate(
            ['username' => 'qma_admin_test'],
            [
                'name' => 'Admin QMA Test',
                'email' => 'qma_admin_test@kmh.co.id',
                'role' => 'qma_admin',
                'password' => bcrypt('password'),
            ]
        );
        $this->qmaAdmin->assignRole('qma_admin');

        $this->gateOfficer = User::firstOrCreate(
            ['username' => 'satpam_test'],
            [
                'name' => 'Petugas Gerbang Test',
                'email' => 'satpam_test@kmh.co.id',
                'role' => 'gate_officer',
                'gate_id' => $this->gate->id,
                'password' => bcrypt('password'),
            ]
        );
        $this->gateOfficer->assignRole('gate_officer');
    }

    /**
     * User yang belum login diarahkan ke halaman login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('qma.unit_management.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Admin QMA dapat membuka halaman Manajemen Unit dan melihat daftar unit serta KPI ringkasan.
     */
    public function test_qma_admin_can_view_unit_management_page(): void
    {
        $lv = Vehicle::create([
            'code' => 'LV-TEST-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'license_plate' => 'BH 1234 KM',
            'status' => 'Operation',
            'site_status' => 'inside_site',
            'location' => 'Main Dam',
            'current_km' => 15000,
        ]);

        $support = Vehicle::create([
            'code' => 'EX-TEST-01',
            'category' => 'unit_support',
            'brand_type' => 'Komatsu PC200',
            'status' => 'Operation',
            'site_status' => 'outside_site',
            'location' => 'Kota Bangko',
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_management.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Unit');
        $response->assertSee('LV-TEST-01');
        $response->assertSee('EX-TEST-01');
        $response->assertSee('Main Dam');
        $response->assertSee('Kota Bangko');
        $response->assertSee('Di Dalam Site');
        $response->assertSee('Di Luar Site');
    }

    /**
     * Filter kategori dan posisi site bekerja dengan benar.
     */
    public function test_unit_management_filtering_by_category_and_site_status(): void
    {
        Vehicle::create([
            'code' => 'LV-INSIDE',
            'category' => 'light_vehicle',
            'brand_type' => 'Hilux',
            'site_status' => 'inside_site',
            'location' => 'Powerhouse',
            'status' => 'Operation',
        ]);

        Vehicle::create([
            'code' => 'LV-OUTSIDE',
            'category' => 'light_vehicle',
            'brand_type' => 'Fortuner',
            'site_status' => 'outside_site',
            'location' => 'Kota Jambi',
            'status' => 'Operation',
        ]);

        // Filter inside_site
        $resInside = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.unit_management.index', ['site_status' => 'inside_site']));

        $resInside->assertStatus(200);
        $resInside->assertSee('LV-INSIDE');
        $resInside->assertDontSee('LV-OUTSIDE');

        // Filter outside_site
        $resOutside = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.unit_management.index', ['site_status' => 'outside_site']));

        $resOutside->assertStatus(200);
        $resOutside->assertSee('LV-OUTSIDE');
        $resOutside->assertDontSee('LV-INSIDE');
    }

    /**
     * Admin QMA dapat memperbarui posisi unit secara manual dan audit riwayat tersimpan.
     */
    public function test_manual_update_location_updates_vehicle_and_creates_history(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-UPDATE-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Triton 4x4',
            'site_status' => 'inside_site',
            'location' => 'Workshop GA',
            'status' => 'Operation',
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.unit_management.update_location', $vehicle->id), [
                'site_status' => 'outside_site',
                'location' => 'Bengkel Rekanan Bangko',
                'note' => 'Perbaikan radiator dan ganti oli',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi update master kendaraan
        $vehicle->refresh();
        $this->assertEquals('outside_site', $vehicle->site_status);
        $this->assertEquals('Bengkel Rekanan Bangko', $vehicle->location);
        $this->assertEquals('Admin QMA Test', $vehicle->location_updated_by);
        $this->assertEquals('Perbaikan radiator dan ganti oli', $vehicle->location_note);
        $this->assertNotNull($vehicle->location_updated_at);

        // Verifikasi pembuatan riwayat audit
        $this->assertDatabaseHas('vehicle_location_histories', [
            'vehicle_id' => $vehicle->id,
            'site_status' => 'outside_site',
            'location' => 'Bengkel Rekanan Bangko',
            'note' => 'Perbaikan radiator dan ganti oli',
            'source' => 'manual',
            'user_name' => 'Admin QMA Test',
        ]);
    }

    /**
     * Endpoint API riwayat mengembalikan format JSON lengkap dengan timeline logs.
     */
    public function test_unit_history_endpoint_returns_json_history(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-HIST-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Pajero Sport',
            'site_status' => 'outside_site',
            'location' => 'Mess Kumun',
            'status' => 'Operation',
        ]);

        VehicleLocationHistory::create([
            'vehicle_id' => $vehicle->id,
            'site_status' => 'inside_site',
            'location' => 'Adit 1',
            'note' => 'Inspeksi terowongan',
            'source' => 'manual',
            'user_id' => $this->qmaAdmin->id,
            'user_name' => 'Admin QMA Test',
            'recorded_at' => Carbon::now()->subHours(2),
        ]);

        VehicleLocationHistory::create([
            'vehicle_id' => $vehicle->id,
            'site_status' => 'outside_site',
            'location' => 'Mess Kumun',
            'note' => 'Istirahat driver',
            'source' => 'manual',
            'user_id' => $this->qmaAdmin->id,
            'user_name' => 'Admin QMA Test',
            'recorded_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.unit_management.history', $vehicle->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vehicle' => ['id', 'code', 'brand_type', 'site_status', 'location'],
            'count',
            'histories' => [
                '*' => ['id', 'site_status', 'site_status_label', 'location', 'note', 'source', 'source_label', 'updater', 'recorded_at']
            ],
        ]);

        $this->assertEquals(2, $response->json('count'));
    }

    /**
     * Integrasi otomatis: Saat aktivitas gerbang arah Masuk (IN) disetujui Satpam,
     * kendaraan otomatis berubah status menjadi inside_site dengan lokasi nama gerbang.
     */
    public function test_gate_inspection_approval_in_updates_vehicle_to_inside_site_and_records_history(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-GATE-IN',
            'category' => 'light_vehicle',
            'brand_type' => 'Hilux Double Cabin',
            'site_status' => 'outside_site',
            'location' => 'Luar Site - Kota Bangko',
            'status' => 'Operation',
            'current_km' => 20000,
        ]);

        $driver = Employee::firstOrCreate(
            ['nik' => 'KMH-DVR-001'],
            ['name' => 'Ahmad Driver', 'is_operator' => true, 'is_active' => true]
        );

        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-IN-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'in',
            'previous_km' => 20000,
            'current_km' => 20050,
            'distance' => 50,
            'purpose' => 'Kembali dari mobilisasi Bangko',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Satpam menyetujui aktivitas gerbang
        $response = $this->actingAs($this->gateOfficer)->post("/pg/gate-inspection/{$activity->id}/approve", [
            'security_guard_id' => $this->guard->id,
        ]);

        $response->assertRedirect();

        // Verifikasi posisi kendaraan berubah otomatis menjadi inside_site
        $vehicle->refresh();
        $this->assertEquals('inside_site', $vehicle->site_status);
        $this->assertEquals($this->gate->name, $vehicle->location);
        $this->assertEquals($this->guard->name, $vehicle->location_updated_by);
        $this->assertStringContainsString('TRX-IN-001', $vehicle->location_note);

        // Verifikasi riwayat tersimpan otomatis bersumber gate_crossing
        $this->assertDatabaseHas('vehicle_location_histories', [
            'vehicle_id' => $vehicle->id,
            'site_status' => 'inside_site',
            'location' => $this->gate->name,
            'source' => 'gate_crossing',
            'vehicle_activity_id' => $activity->id,
            'user_name' => $this->guard->name,
        ]);
    }

    /**
     * Integrasi otomatis: Saat aktivitas gerbang arah Keluar (OUT) disetujui Satpam,
     * kendaraan otomatis berubah status menjadi outside_site dengan lokasi Luar Site - [Tujuan].
     */
    public function test_gate_inspection_approval_out_updates_vehicle_to_outside_site_and_records_history(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-GATE-OUT',
            'category' => 'light_vehicle',
            'brand_type' => 'Fortuner 4x4',
            'site_status' => 'inside_site',
            'location' => 'Workshop GA',
            'status' => 'Operation',
            'current_km' => 30000,
        ]);

        $driver = Employee::firstOrCreate(
            ['nik' => 'KMH-DVR-002'],
            ['name' => 'Bambang Driver', 'is_operator' => true, 'is_active' => true]
        );

        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-OUT-002',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 30000,
            'current_km' => 30010,
            'distance' => 10,
            'purpose' => 'Antar Dokumen ke Kantor Jambi',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Satpam menyetujui aktivitas gerbang
        $response = $this->actingAs($this->gateOfficer)->post("/pg/gate-inspection/{$activity->id}/approve", [
            'security_guard_id' => $this->guard->id,
        ]);

        $response->assertRedirect();

        // Verifikasi posisi kendaraan berubah otomatis menjadi outside_site
        $vehicle->refresh();
        $this->assertEquals('outside_site', $vehicle->site_status);
        $this->assertEquals('Luar Site - Antar Dokumen ke Kantor Jambi', $vehicle->location);
        $this->assertEquals($this->guard->name, $vehicle->location_updated_by);

        // Verifikasi riwayat tersimpan otomatis
        $this->assertDatabaseHas('vehicle_location_histories', [
            'vehicle_id' => $vehicle->id,
            'site_status' => 'outside_site',
            'location' => 'Luar Site - Antar Dokumen ke Kantor Jambi',
            'source' => 'gate_crossing',
            'vehicle_activity_id' => $activity->id,
        ]);
    }

    /**
     * Admin QMA dapat mengekspor data posisi ke Excel (.xlsx).
     */
    public function test_export_excel_returns_valid_xlsx_stream(): void
    {
        Vehicle::create([
            'code' => 'LV-EXP-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Hilux',
            'site_status' => 'inside_site',
            'location' => 'Main Dam',
            'status' => 'Operation',
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_management.export_excel'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains(
            $response->headers->get('content-type') ?? '',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ));
    }

    /**
     * Admin QMA dapat mengekspor data posisi ke format PDF.
     */
    public function test_export_pdf_returns_valid_pdf_stream(): void
    {
        Vehicle::create([
            'code' => 'LV-EXP-02',
            'category' => 'light_vehicle',
            'brand_type' => 'Strada Triton',
            'site_status' => 'outside_site',
            'location' => 'Kota Bangko',
            'status' => 'Operation',
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_management.export_pdf'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains(
            $response->headers->get('content-type') ?? '',
            'application/pdf'
        ));
    }
}
