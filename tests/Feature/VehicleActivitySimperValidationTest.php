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
use Tests\TestCase;

class VehicleActivitySimperValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $driverUser;
    protected Employee $driverLvOnly;
    protected Employee $driverDtOnly;
    protected Vehicle $lightVehicle;
    protected Vehicle $heavyMobileUnit;
    protected Gate $gate;
    protected SimperType $simperLv;
    protected SimperType $simperDt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->simperLv = SimperType::where('code', 'SIMPER-LV')->first();
        $this->simperDt = SimperType::where('code', 'SIMPER-DT')->first();

        $this->gate = Gate::create([
            'code' => 'GATE-01',
            'name' => 'Pos Gerbang Utama KMH',
            'location' => 'Main Gate Site',
            'is_active' => true,
        ]);

        $this->lightVehicle = Vehicle::create([
            'code' => 'LV-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4 Double Cabin',
            'license_plate' => 'BH 1234 KM',
            'status' => 'Operation',
            'current_km' => 10000.00,
        ]);

        $this->heavyMobileUnit = Vehicle::create([
            'code' => 'DT-01',
            'category' => 'unit_support',
            'brand_type' => 'Hino 500 Dump Truck',
            'license_plate' => 'BH 5678 DT',
            'status' => 'Operation',
            'current_km' => 5000.00,
            'is_timesheet_mobile' => true,
            'required_simper_type_id' => $this->simperDt->id,
        ]);

        // Driver 1: Hanya punya SIMPER LV
        $this->driverLvOnly = Employee::create([
            'nik' => 'DRV-LV-01',
            'name' => 'Andi Driver LV',
            'position' => 'Driver Operasional',
            'division' => 'Logistik',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
        EmployeeSimper::create([
            'employee_id' => $this->driverLvOnly->id,
            'simper_type_id' => $this->simperLv->id,
            'simper_number' => 'SMP-LV-001',
            'expired_at' => Carbon::now()->addYear(),
            'is_active' => true,
        ]);

        // Driver 2: Sukon dengan SIMPER DT
        $this->driverDtOnly = Employee::create([
            'nik' => 'SKN-DT-01',
            'name' => 'Budi Sukon DT',
            'employee_type' => 'Sukon',
            'position' => 'Driver Dump Truck',
            'division' => 'Hauling',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);
        EmployeeSimper::create([
            'employee_id' => $this->driverDtOnly->id,
            'simper_type_id' => $this->simperDt->id,
            'simper_number' => 'SMP-DT-001',
            'expired_at' => Carbon::now()->addYear(),
            'is_active' => true,
        ]);

        // Akun login untuk pengujian form
        $this->driverUser = User::create([
            'name' => 'Andi Driver LV',
            'username' => 'driver_andi',
            'email' => 'andi@kmh.co.id',
            'role' => 'employee',
            'password' => bcrypt('password'),
        ]);
        $this->driverLvOnly->update(['user_id' => $this->driverUser->id]);
    }

    /**
     * Driver yang hanya memiliki SIMPER LV hanya melihat armada LV di katalog /timesheet.
     */
    public function test_driver_with_only_lv_simper_sees_only_lv_and_not_dump_truck(): void
    {
        $response = $this->actingAs($this->driverUser)->get(route('mobilisasi.index'));
        $response->assertStatus(200);
        $response->assertSee('LV-01');
        $response->assertDontSee('DT-01');
    }

    /**
     * Driver yang memiliki SIMPER Dump Truck hanya melihat unit Dump Truck yang sesuai izinnya.
     */
    public function test_driver_with_dt_simper_sees_dump_truck(): void
    {
        $driverDtUser = User::create([
            'name' => 'Budi Sukon DT',
            'username' => 'driver_dt',
            'email' => 'dt@kmh.co.id',
            'role' => 'employee',
            'password' => bcrypt('password'),
        ]);
        $this->driverDtOnly->update(['user_id' => $driverDtUser->id]);

        $response = $this->actingAs($driverDtUser)->get(route('mobilisasi.index'));
        $response->assertStatus(200);
        $response->assertSee('DT-01');
        $response->assertDontSee('Toyota Hilux');
    }

    /**
     * Karyawan yang belum memiliki SIMPER aktif melihat kartu peringatan khusus.
     */
    public function test_employee_without_active_simper_sees_warning_card(): void
    {
        $userNoSimper = User::create([
            'name' => 'Employee No Simper',
            'username' => 'no_simper_user',
            'email' => 'nosimper@kmh.co.id',
            'role' => 'employee',
            'password' => bcrypt('password'),
        ]);
        Employee::create([
            'user_id' => $userNoSimper->id,
            'nik' => 'EMP-NO-SMP',
            'name' => 'Employee No Simper',
            'is_operator' => false,
        ]);

        $response = $this->actingAs($userNoSimper)->get(route('mobilisasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Anda belum memiliki SIMPER aktif yang terdaftar di HSE untuk mengemudikan kendaraan.');
        $response->assertDontSee('LV-01');
        $response->assertDontSee('DT-01');
    }

    /**
     * Super Admin dapat memantau seluruh armada (LV dan Heavy Mobile) tanpa batasan.
     */
    public function test_super_admin_sees_both_lv_and_heavy_mobile(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::create([
            'name' => 'Super Admin Test',
            'username' => 'superadmin_test',
            'email' => 'super@kmh.co.id',
            'role' => 'super_admin',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get(route('mobilisasi.index'));
        $response->assertStatus(200);
        $response->assertSee('LV-01');
        $response->assertSee('DT-01');
    }

    /**
     * Driver LV dapat mengakses form mobilisasi LV.
     */
    public function test_driver_lv_can_access_lv_form(): void
    {
        $response = $this->actingAs($this->driverUser)
            ->get(route('vehicle.activity.form', ['code' => 'LV-01']));

        $response->assertStatus(200);
        $response->assertSee('LV-01');
        $response->assertDontSee('Izin SIMPER Tidak Sesuai');
    }

    /**
     * Driver yang hanya punya SIMPER LV ditolak saat mengakses unit Dump Truck (SIMPER-DT).
     */
    public function test_driver_lv_only_is_rejected_on_dump_truck(): void
    {
        $response = $this->actingAs($this->driverUser)
            ->get(route('vehicle.activity.form', ['code' => 'DT-01']));

        $response->assertStatus(200);
        $response->assertSee('Izin SIMPER Tidak Sesuai');
        $response->assertSee('SIMPER Dump Truck');
    }

    /**
     * Cek NIK AJAX menolak Driver LV pada unit Dump Truck.
     */
    public function test_ajax_check_nik_rejects_mismatched_simper(): void
    {
        $response = $this->actingAs($this->driverUser)
            ->postJson(route('vehicle.activity.check.nik', ['code' => 'DT-01']), [
                'nik' => $this->driverLvOnly->nik,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', 'expired');
        $response->assertJsonPath('title', 'Izin SIMPER Tidak Sesuai');
    }

    /**
     * Cek NIK AJAX menerima Driver Sukon DT pada unit Dump Truck.
     */
    public function test_ajax_check_nik_accepts_valid_dt_driver(): void
    {
        $response = $this->actingAs($this->driverUser)
            ->postJson(route('vehicle.activity.check.nik', ['code' => 'DT-01']), [
                'nik' => $this->driverDtOnly->nik,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'valid');
        $response->assertJsonPath('title', 'SIMPER Valid');
    }

    /**
     * Submit timesheet mobilisasi Dump Truck berhasil bila pengemudi memiliki SIMPER DT.
     */
    public function test_submit_activity_succeeds_for_authorized_dt_driver(): void
    {
        $postData = [
            'driver_id' => $this->driverDtOnly->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'current_km' => 5050.00,
            'purpose' => 'Mobilisasi angkut material agregat ke area powerhouse',
        ];

        $response = $this->actingAs($this->driverUser)
            ->post(route('vehicle.activity.store', ['code' => 'DT-01']), $postData);

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicle_activities', [
            'vehicle_id' => $this->heavyMobileUnit->id,
            'driver_id' => $this->driverDtOnly->id,
            'direction' => 'out',
            'current_km' => 5050.00,
        ]);
    }

    /**
     * Pencarian katalog timesheet tidak sensitif terhadap spasi dan tanda hubung,
     * serta tombol submit Cari tersedia untuk pengguna mobile.
     */
    public function test_timesheet_catalog_search_is_space_and_hyphen_insensitive(): void
    {
        // 1. Tombol submit 'Cari' tersedia di tampilan mobile/desktop
        $response = $this->actingAs($this->driverUser)->get(route('mobilisasi.index'));
        $response->assertStatus(200);
        $response->assertSee('id="lvSearchBtn"', false);
        $response->assertSee('Cari');

        // 2. Driver LV cari 'LV01' tanpa tanda hubung mencocokkan kode 'LV-01'
        $searchResponse1 = $this->actingAs($this->driverUser)->get(route('mobilisasi.index', ['search' => 'LV01']));
        $searchResponse1->assertStatus(200);
        $searchResponse1->assertSee('LV-01');

        // 3. Cari plat nomor tanpa spasi 'BH1234' mencocokkan 'BH 1234 KM'
        $searchResponse2 = $this->actingAs($this->driverUser)->get(route('mobilisasi.index', ['search' => 'BH1234']));
        $searchResponse2->assertStatus(200);
        $searchResponse2->assertSee('Toyota Hilux');
        $searchResponse2->assertSee('LV-01');

        // 4. Driver DT cari 'DT01' tanpa tanda hubung mencocokkan 'DT-01'
        $driverDtUser = User::firstOrCreate(
            ['username' => 'driver_dt_search'],
            [
                'name' => 'Budi Sukon DT Search',
                'email' => 'dt_search@kmh.co.id',
                'role' => 'employee',
                'password' => bcrypt('password'),
            ]
        );
        $this->driverDtOnly->update(['user_id' => $driverDtUser->id]);

        $searchResponse3 = $this->actingAs($driverDtUser)->get(route('mobilisasi.index', ['search' => 'DT01']));
        $searchResponse3->assertStatus(200);
        $searchResponse3->assertSee('DT-01');
        $searchResponse3->assertDontSee('Toyota Hilux');
    }
}
