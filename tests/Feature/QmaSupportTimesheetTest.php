<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSimper;
use App\Models\SimperType;
use App\Models\UnitSupportTimesheet;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QmaSupportTimesheetTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected Vehicle $supportUnit;
    protected Employee $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'qma.logs.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.logs.edit_odometer', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.edit', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(['name' => 'qma_admin', 'guard_name' => 'web']);
        $role->givePermissionTo(['qma.logs.view', 'qma.logs.edit_odometer', 'qma.vehicles.view', 'qma.vehicles.edit']);

        $this->qmaAdmin = User::firstOrCreate(
            ['username' => 'qma_admin_tester'],
            [
                'name' => 'Admin QMA Tester',
                'email' => 'qma_admin_tester@kmh.co.id',
                'role' => 'qma_admin',
                'password' => bcrypt('password'),
            ]
        );
        $this->qmaAdmin->assignRole('qma_admin');

        $this->supportUnit = Vehicle::create([
            'code' => 'EX01',
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'brand_type' => 'Komatsu PC200-8 Excavator',
            'status' => 'Operation',
            'site_status' => 'inside_site',
            'location' => 'Adit 1',
            'current_km' => 1250.00,
        ]);

        $this->operator = Employee::create([
            'nik' => 'KMH-OPR-001',
            'name' => 'Joko Operator',
            'position' => 'Operator Excavator',
            'department' => 'Civil Construction',
            'is_operator' => true,
            'is_driver' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
    }

    /**
     * Tamu / user tanpa autentikasi diarahkan ke halaman login.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('qma.support_timesheet.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Admin QMA dapat mengakses halaman Timesheet Unit Support.
     */
    public function test_qma_admin_can_view_support_timesheet_page(): void
    {
        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-US-20261020-0001',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1258.50,
            'operating_hours' => 8.50,
            'location' => 'Main Dam',
            'activity_type' => 'Galian Material',
            'status' => 'Operation',
            'created_by_user_id' => $this->qmaAdmin->id,
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index'));

        $response->assertStatus(200);
        $response->assertSee('Timesheet Heavy Equipment');
        $response->assertSee('Catat Timesheet');
        $response->assertSee('TS-US-20261020-0001');
        $response->assertSee('EX01');
        $response->assertSee('Joko Operator');
        $response->assertSee('1.250,0 HM');
        $response->assertSee('Main Dam');
    }

    /**
     * Admin QMA dapat menginput timesheet unit support yang disederhanakan:
     * - Unit support diketik (vehicle_code)
     * - Shift default 'Shift Siang'
     * - Odometer hanya awal saja (start_meter) tanpa end_meter & fuel_liters
     * - Kategori pekerjaan teks bebas
     */
    public function test_store_streamlined_support_timesheet_with_typed_unit_and_start_meter_only(): void
    {
        $data = [
            'vehicle_code' => 'EX01', // Diketik langsung
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => '08:15',
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 1265.50, // Odometer awal saja
            'location' => 'Inlet Portal Adit 3',
            'activity_type' => 'Perapihan Dinding Batuan', // Ketik bebas
            'status' => 'Operation',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.support_timesheet.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('unit_support_timesheets', [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 1265.50,
            'end_meter' => null,
            'operating_hours' => null,
            'location' => 'Inlet Portal Adit 3',
            'activity_type' => 'Perapihan Dinding Batuan',
            'status' => 'Operation',
        ]);

        $saved = UnitSupportTimesheet::where('vehicle_id', $this->supportUnit->id)->first();
        $this->assertNotNull($saved);
        $this->assertStringStartsWith('08:15', (string) $saved->start_time);

        // Verifikasi odometer kendaraan terupdate ke start_meter terbaru
        $this->supportUnit->refresh();
        $this->assertEquals(1265.50, (float) $this->supportUnit->current_km);
        $this->assertEquals('Inlet Portal Adit 3', $this->supportUnit->location);
    }

    /**
     * Admin QMA dapat mengakses riwayat timesheet melalui rute Log Aktifitas (tanpa tombol penambahan).
     */
    public function test_qma_admin_can_view_support_timesheet_history_via_activity_log_route(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.activity.support_timesheet'));

        $response->assertStatus(200);
        $response->assertSee('History Timesheet Heavy Equipment');
        $response->assertDontSee('Catat Timesheet');
    }

    /**
     * Admin QMA dapat menyimpan timesheet baru, yang mengupdate meter unit dan posisi di Manajemen Unit.
     */
    public function test_qma_admin_can_store_support_timesheet_and_syncs_vehicle_and_location(): void
    {
        $data = [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1258.00,
            'standby_hours' => 1.0,
            'breakdown_hours' => 0,
            'location' => 'Powerhouse Area',
            'activity_type' => 'Loading Material',
            'task_description' => 'Loading batuan hasil peledakan ke dump truck',
            'fuel_liters' => 120.50,
            'status' => 'Operation',
            'notes' => 'Operasi lancar, cuaca cerah',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.support_timesheet.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi timesheet tersimpan
        $this->assertDatabaseHas('unit_support_timesheets', [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1258.00,
            'operating_hours' => 8.00,
            'location' => 'Powerhouse Area',
            'activity_type' => 'Loading Material',
            'fuel_liters' => 120.50,
            'status' => 'Operation',
        ]);

        // Verifikasi update master kendaraan
        $this->supportUnit->refresh();
        $this->assertEquals(1258.00, (float) $this->supportUnit->current_km);
        $this->assertEquals('Powerhouse Area', $this->supportUnit->location);
        $this->assertEquals('inside_site', $this->supportUnit->site_status);
        $this->assertEquals('Admin QMA Tester', $this->supportUnit->location_updated_by);

        // Verifikasi pembuatan riwayat audit posisi di Manajemen Unit
        $this->assertDatabaseHas('vehicle_location_histories', [
            'vehicle_id' => $this->supportUnit->id,
            'site_status' => 'inside_site',
            'location' => 'Powerhouse Area',
            'source' => 'manual',
            'user_name' => 'Admin QMA Tester',
        ]);
    }

    /**
     * Validasi gagal jika end_meter lebih kecil dari start_meter.
     */
    public function test_store_validates_end_meter_gte_start_meter(): void
    {
        $data = [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1240.00, // Invalid: lebih kecil dari start
            'location' => 'Main Dam',
            'activity_type' => 'Galian Material',
            'status' => 'Operation',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.support_timesheet.store'), $data);

        $response->assertSessionHasErrors('end_meter');
    }

    /**
     * Admin QMA dapat memperbarui entri timesheet.
     */
    public function test_qma_admin_can_update_support_timesheet(): void
    {
        $timesheet = UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-US-20261020-0002',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1256.00,
            'operating_hours' => 6.00,
            'location' => 'Adit 2',
            'activity_type' => 'Galian Material',
            'status' => 'Operation',
            'created_by_user_id' => $this->qmaAdmin->id,
        ]);

        $updateData = [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'start_time' => '07:45',
            'shift' => 'Shift 2 (Malam)',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1259.50, // diupdate
            'location' => 'Vertical Shaft',
            'activity_type' => 'Lifting / Erection',
            'status' => 'Standby',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->put(route('qma.support_timesheet.update', $timesheet->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $timesheet->refresh();
        $this->assertEquals(9.50, (float) $timesheet->operating_hours);
        $this->assertEquals('Shift 2 (Malam)', $timesheet->shift);
        $this->assertStringStartsWith('07:45', (string) $timesheet->start_time);
        $this->assertEquals('Vertical Shaft', $timesheet->location);
        $this->assertEquals('Standby', $timesheet->status);
    }

    /**
     * Admin QMA dapat menghapus data timesheet.
     */
    public function test_qma_admin_can_delete_support_timesheet(): void
    {
        $timesheet = UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-US-20261020-0003',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 100.00,
            'end_meter' => 105.00,
            'operating_hours' => 5.00,
            'location' => 'Main Dam',
            'activity_type' => 'Galian',
            'status' => 'Operation',
            'created_by_user_id' => $this->qmaAdmin->id,
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->delete(route('qma.support_timesheet.destroy', $timesheet->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('unit_support_timesheets', ['id' => $timesheet->id]);
    }

    /**
     * Filter berdasarkan tanggal dan unit berfungsi dengan tepat.
     */
    public function test_support_timesheet_filtering_by_date_and_vehicle(): void
    {
        $unit2 = Vehicle::create([
            'code' => 'CR01',
            'category' => 'unit_support',
            'brand_type' => 'Kato Crane 25 Ton',
            'status' => 'Operation',
        ]);

        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-FILTER-01',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 10,
            'end_meter' => 15,
            'operating_hours' => 5,
            'location' => 'Main Dam',
            'activity_type' => 'Galian',
            'status' => 'Operation',
        ]);

        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-FILTER-02',
            'vehicle_id' => $unit2->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 50,
            'end_meter' => 58,
            'operating_hours' => 8,
            'location' => 'Powerhouse',
            'activity_type' => 'Lifting',
            'status' => 'Operation',
        ]);

        $res = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index', ['vehicle_id' => $unit2->id]));

        $res->assertStatus(200);
        $res->assertSee('TS-FILTER-02');
        $res->assertDontSee('TS-FILTER-01');
    }

    /**
     * Ekspor Excel dan PDF menghasilkan response file valid.
     */
    public function test_export_excel_and_pdf(): void
    {
        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-EXPORT-01',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today(),
            'shift' => 'Shift 1 (Siang)',
            'meter_type' => 'HM',
            'start_meter' => 20,
            'end_meter' => 28,
            'operating_hours' => 8,
            'location' => 'Main Dam',
            'activity_type' => 'Galian',
            'status' => 'Operation',
        ]);

        // Excel
        $resExcel = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.export_excel'));
        $resExcel->assertStatus(200);
        $this->assertTrue(str_contains(
            $resExcel->headers->get('content-type') ?? '',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ));

        // PDF
        $resPdf = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.export_pdf'));
        $resPdf->assertStatus(200);
        $this->assertTrue(str_contains(
            $resPdf->headers->get('content-type') ?? '',
            'application/pdf'
        ));
    }

    /**
     * Halaman index memuat unitLocations dan merefleksikan posisi unit.
     */
    public function test_index_passes_unit_locations_for_location_first_workflow(): void
    {
        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index'));

        $response->assertStatus(200);
        $response->assertViewHas('unitLocations');
        $response->assertSee('Tentukan Lokasi Dulu');
        $response->assertSee('Adit 1 (1 Unit Ready)');
    }

    /**
     * Penyimpanan dengan lokasi custom atau normalisasi fallback.
     */
    public function test_store_handles_custom_location_input(): void
    {
        $data = [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 1300.00,
            'location' => '__CUSTOM__',
            'custom_location' => 'Quarry Batu KM 12',
            'activity_type' => 'Galian Quarry',
            'status' => 'Operation',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.support_timesheet.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('unit_support_timesheets', [
            'vehicle_id' => $this->supportUnit->id,
            'location' => 'Quarry Batu KM 12',
        ]);

        $this->supportUnit->refresh();
        $this->assertEquals('Quarry Batu KM 12', $this->supportUnit->location);
    }

    /**
     * Sinkronisasi data operator Sukon dari database ke dropdown QMA dan tabel timesheet.
     */
    public function test_qma_support_timesheet_syncs_and_filters_sukon_and_karyawan_operators(): void
    {
        $sukonOperator = Employee::create([
            'nik' => 'SKN-999',
            'name' => 'Budi Sukon Utama',
            'position' => 'Operator Subcon Dozer',
            'employee_type' => 'Sukon',
            'is_operator' => true,
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi Sukon Utama');
        $response->assertSee('data-type="Sukon"', false);
        $response->assertSee('data-type="Karyawan"', false);
    }

    /**
     * Filter berdasarkan jenis operator (Sukon vs Karyawan) di tabel timesheet.
     */
    public function test_qma_support_timesheet_filter_by_operator_type(): void
    {
        $sukonOperator = Employee::create([
            'nik' => 'SKN-888',
            'name' => 'Sukon Operator X',
            'position' => 'Operator Subcon Excavator',
            'employee_type' => 'Sukon',
            'is_operator' => true,
        ]);

        $tsKaryawan = UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-KARYAWAN-001',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $this->operator->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 100.0,
            'end_meter' => 108.0,
            'operating_hours' => 8.0,
            'location' => 'Adit 1',
            'activity_type' => 'Galian',
            'status' => 'Operation',
        ]);

        $tsSukon = UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-SUKON-001',
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $sukonOperator->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 110.0,
            'end_meter' => 118.0,
            'operating_hours' => 8.0,
            'location' => 'Adit 1',
            'activity_type' => 'Loading',
            'status' => 'Operation',
        ]);

        // Filter Sukon only
        $responseSukon = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index', ['operator_type' => 'Sukon']));

        $responseSukon->assertStatus(200);
        $responseSukon->assertSee('TS-SUKON-001');
        $responseSukon->assertDontSee('TS-KARYAWAN-001');

        // Filter Karyawan only
        $responseKaryawan = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index', ['operator_type' => 'Karyawan']));

        $responseKaryawan->assertStatus(200);
        $responseKaryawan->assertSee('TS-KARYAWAN-001');
        $responseKaryawan->assertDontSee('TS-SUKON-001');
    }

    /**
     * Index memuat peta kualifikasi operator per alat berat (unitOperatorsMap).
     */
    public function test_index_passes_unit_operators_map_matching_equipment_simper(): void
    {
        $simperEx = SimperType::create([
            'code' => 'SIMPER-EXCAVATOR',
            'name' => 'SIMPER Alat Excavator',
            'category_scope' => 'Excavator',
            'is_active' => true,
        ]);

        $simperDt = SimperType::create([
            'code' => 'SIMPER-DT-ONLY',
            'name' => 'SIMPER Khusus DT',
            'category_scope' => 'unit_support_mobile',
            'is_active' => true,
        ]);

        $opEx = Employee::create([
            'nik' => 'OP-EX-001',
            'name' => 'Operator Khusus Excavator',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
        EmployeeSimper::create([
            'employee_id' => $opEx->id,
            'simper_type_id' => $simperEx->id,
            'expired_at' => Carbon::now()->addYear(),
            'status' => 'active',
        ]);

        $opDt = Employee::create([
            'nik' => 'OP-DT-001',
            'name' => 'Driver Khusus Dump Truck',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
        EmployeeSimper::create([
            'employee_id' => $opDt->id,
            'simper_type_id' => $simperDt->id,
            'expired_at' => Carbon::now()->addYear(),
            'status' => 'active',
        ]);

        $unitDumpTruck = Vehicle::create([
            'code' => 'DT99',
            'category' => 'unit_support',
            'unit_category' => 'Dump Truck',
            'brand_type' => 'Mitsubishi Fuso 220PS',
            'is_timesheet_mobile' => true,
            'status' => 'Operation',
            'site_status' => 'inside_site',
            'location' => 'Adit 1',
            'current_km' => 500.0,
        ]);

        $response = $this->actingAs($this->qmaAdmin)
            ->get(route('qma.support_timesheet.index'));

        $response->assertStatus(200);
        $response->assertViewHas('unitOperatorsMap');

        $map = $response->viewData('unitOperatorsMap');
        $this->assertIsArray($map);

        // Unit EX01 harus memuat $opEx dan TIDAK memuat $opDt
        $this->assertContains($opEx->id, $map[$this->supportUnit->id]);
        $this->assertNotContains($opDt->id, $map[$this->supportUnit->id]);

        // Unit DT99 harus memuat $opDt dan TIDAK memuat $opEx
        $this->assertContains($opDt->id, $map[$unitDumpTruck->id]);
        $this->assertNotContains($opEx->id, $map[$unitDumpTruck->id]);
    }

    /**
     * Operator dengan SIMPER Dump Truck ditolak saat menginput timesheet alat berat Excavator.
     */
    public function test_store_fails_when_dump_truck_operator_attempts_excavator_timesheet(): void
    {
        $simperDt = SimperType::create([
            'code' => 'SIMPER-DT-ONLY2',
            'name' => 'SIMPER Khusus DT 2',
            'category_scope' => 'unit_support_mobile',
            'is_active' => true,
        ]);

        $opDt = Employee::create([
            'nik' => 'OP-DT-002',
            'name' => 'Driver DT Saja',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
        EmployeeSimper::create([
            'employee_id' => $opDt->id,
            'simper_type_id' => $simperDt->id,
            'expired_at' => Carbon::now()->addYear(),
            'status' => 'active',
        ]);

        $data = [
            'vehicle_id' => $this->supportUnit->id, // Excavator
            'operator_id' => $opDt->id,
            'date' => Carbon::today()->toDateString(),
            'shift' => 'Shift Siang',
            'meter_type' => 'HM',
            'start_meter' => 1250.00,
            'end_meter' => 1258.00,
            'location' => 'Main Dam',
            'activity_type' => 'Galian Material',
            'status' => 'Operation',
        ];

        $response = $this->actingAs($this->qmaAdmin)
            ->post(route('qma.support_timesheet.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('unit_support_timesheets', [
            'vehicle_id' => $this->supportUnit->id,
            'operator_id' => $opDt->id,
        ]);
    }
}
