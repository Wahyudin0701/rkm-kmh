<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\P2hRecord;
use App\Models\P2hRecordItem;
use App\Models\P2hTemplate;
use App\Models\UnitSupportTimesheet;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected Employee $empA;
    protected User $userB;
    protected Employee $empB;
    protected Vehicle $heavyEquipment;
    protected Vehicle $lightVehicle;
    protected Gate $gate;
    protected P2hTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Karyawan / Operator A
        $this->userA = User::create([
            'name' => 'Operator Alex Test',
            'username' => 'operator_alex',
            'email' => 'alex@kerinci.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);
        $this->userA->givePermissionTo(['employee.dashboard', 'employee.p2h', 'employee.timesheet']);
        $this->empA = $this->userA->getOrCreateEmployee();
        $this->empA->update(['is_operator' => true]);

        // Karyawan / Operator B
        $this->userB = User::create([
            'name' => 'Operator Budi Test',
            'username' => 'operator_budi',
            'email' => 'budi@kerinci.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);
        $this->userB->givePermissionTo(['employee.dashboard', 'employee.p2h', 'employee.timesheet']);
        $this->empB = $this->userB->getOrCreateEmployee();
        $this->empB->update(['is_operator' => true]);

        // Unit Heavy Equipment
        $this->heavyEquipment = Vehicle::create([
            'code' => 'EXC-HIST-01',
            'brand_type' => 'Komatsu PC200 History Test',
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'status' => 'Operation',
        ]);

        // Unit Light Vehicle
        $this->lightVehicle = Vehicle::create([
            'code' => 'LV-HIST-01',
            'brand_type' => 'Toyota Hilux 4x4',
            'category' => 'light_vehicle',
            'license_plate' => 'BH 1234 XY',
            'status' => 'Operation',
        ]);

        // Pos Gerbang
        $this->gate = Gate::create([
            'name' => 'Pos Gerbang Utama PLTA',
            'code' => 'PG-01',
            'status' => 'active',
        ]);

        // Template P2H
        $this->template = P2hTemplate::firstOrCreate(
            ['code' => 'EXD4RP'],
            [
                'name' => 'Excavator Checksheet Test',
                'is_active' => true,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('employee.history'));
        $response->assertRedirect(route('login'));
    }

    public function test_employee_can_access_history_page(): void
    {
        $response = $this->actingAs($this->userA)->get(route('employee.history'));
        $response->assertStatus(200);
        $response->assertSee('Riwayat Aktivitas Saya');
        $response->assertSee('P2H Heavy Equipment');
    }

    public function test_employee_only_sees_their_own_p2h_records(): void
    {
        // P2H milik A
        $recordA = P2hRecord::create([
            'transaction_no' => 'P2H-ALEX-001',
            'vehicle_id' => $this->heavyEquipment->id,
            'employee_id' => $this->empA->id,
            'template_id' => $this->template->id,
            'shift' => '1',
            'start_hm' => 1250.5,
            'summary_condition' => 'good',
            'defect_count' => 0,
            'validation_status' => 'approved',
            'inspected_at' => Carbon::now(),
        ]);

        // P2H milik B
        $recordB = P2hRecord::create([
            'transaction_no' => 'P2H-BUDI-999',
            'vehicle_id' => $this->heavyEquipment->id,
            'employee_id' => $this->empB->id,
            'template_id' => $this->template->id,
            'shift' => '2',
            'start_hm' => 2400.0,
            'summary_condition' => 'has_defect',
            'defect_count' => 1,
            'validation_status' => 'pending',
            'inspected_at' => Carbon::now(),
        ]);

        // Acting as User A
        $responseA = $this->actingAs($this->userA)->get(route('employee.history', ['tab' => 'p2h']));
        $responseA->assertStatus(200);
        $responseA->assertSee('P2H-ALEX-001');
        $responseA->assertDontSee('P2H-BUDI-999');

        // Acting as User B
        $responseB = $this->actingAs($this->userB)->get(route('employee.history', ['tab' => 'p2h']));
        $responseB->assertStatus(200);
        $responseB->assertSee('P2H-BUDI-999');
        $responseB->assertDontSee('P2H-ALEX-001');
    }

    public function test_employee_only_sees_their_own_timesheets(): void
    {
        // Timesheet milik Operator A
        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-ALEX-001',
            'vehicle_id' => $this->heavyEquipment->id,
            'operator_id' => $this->empA->id,
            'date' => Carbon::today(),
            'shift' => '1',
            'meter_type' => 'hm',
            'start_meter' => 100,
            'end_meter' => 108,
            'operating_hours' => 8.0,
            'activity_type' => 'Operation',
            'location' => 'Incline Shaft Section 1',
            'task_description' => 'Mucking and clearing rocks',
            'status' => 'completed',
        ]);

        // Timesheet milik Operator B
        UnitSupportTimesheet::create([
            'timesheet_no' => 'TS-BUDI-999',
            'vehicle_id' => $this->heavyEquipment->id,
            'operator_id' => $this->empB->id,
            'date' => Carbon::today(),
            'shift' => '2',
            'meter_type' => 'hm',
            'start_meter' => 200,
            'end_meter' => 206,
            'operating_hours' => 6.0,
            'activity_type' => 'Operation',
            'location' => 'Powerhouse Cavern',
            'task_description' => 'Excavation trench',
            'status' => 'completed',
        ]);

        // Acting as User A
        $responseA = $this->actingAs($this->userA)->get(route('employee.history', ['tab' => 'timesheet']));
        $responseA->assertStatus(200);
        $responseA->assertSee('TS-ALEX-001');
        $responseA->assertSee('Incline Shaft Section 1');
        $responseA->assertDontSee('TS-BUDI-999');
        $responseA->assertDontSee('Powerhouse Cavern');

        // Acting as User B
        $responseB = $this->actingAs($this->userB)->get(route('employee.history', ['tab' => 'timesheet']));
        $responseB->assertStatus(200);
        $responseB->assertSee('TS-BUDI-999');
        $responseB->assertSee('Powerhouse Cavern');
        $responseB->assertDontSee('TS-ALEX-001');
    }

    public function test_employee_only_sees_their_own_vehicle_activity_trips(): void
    {
        // Trip milik Driver A
        VehicleActivity::create([
            'transaction_no' => 'TRIP-ALEX-001',
            'vehicle_id' => $this->lightVehicle->id,
            'driver_id' => $this->empA->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 10000,
            'current_km' => 10035,
            'distance' => 35,
            'purpose' => 'Antar Logistik ke Camp Merangin Alex',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        // Trip milik Driver B
        VehicleActivity::create([
            'transaction_no' => 'TRIP-BUDI-999',
            'vehicle_id' => $this->lightVehicle->id,
            'driver_id' => $this->empB->id,
            'gate_id' => $this->gate->id,
            'direction' => 'in',
            'previous_km' => 20000,
            'current_km' => 20050,
            'distance' => 50,
            'purpose' => 'Patroli Jalur Pipa Budi',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        // Acting as User A
        $responseA = $this->actingAs($this->userA)->get(route('employee.history', ['tab' => 'trips']));
        $responseA->assertStatus(200);
        $responseA->assertSee('TRIP-ALEX-001');
        $responseA->assertSee('Antar Logistik ke Camp Merangin Alex');
        $responseA->assertDontSee('TRIP-BUDI-999');
        $responseA->assertDontSee('Patroli Jalur Pipa Budi');

        // Acting as User B
        $responseB = $this->actingAs($this->userB)->get(route('employee.history', ['tab' => 'trips']));
        $responseB->assertStatus(200);
        $responseB->assertSee('TRIP-BUDI-999');
        $responseB->assertSee('Patroli Jalur Pipa Budi');
        $responseB->assertDontSee('TRIP-ALEX-001');
    }

    public function test_tabs_adapt_according_to_employee_permissions_and_records(): void
    {
        // User C: Hanya punya akses Timesheet LV, tidak punya P2H dan bukan operator
        $userC = User::create([
            'name' => 'Driver Cici Test',
            'username' => 'driver_cici',
            'email' => 'cici@kerinci.local',
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);
        $userC->syncRoles([]);
        $userC->givePermissionTo(['employee.dashboard', 'employee.timesheet']);
        $empC = $userC->getOrCreateEmployee();
        $empC->update(['is_operator' => false]);

        $response = $this->actingAs($userC)->get(route('employee.history'));
        $response->assertStatus(200);
        $response->assertSee('Mobilisasi');
        $response->assertSee('Pos Gerbang');
        $response->assertDontSee('P2H Heavy Equipment');
        $response->assertDontSee('Timesheet Heavy Equipment');
    }

    public function test_employee_can_view_own_p2h_detail_and_pdf(): void
    {
        $recordA = P2hRecord::create([
            'transaction_no' => 'P2H-DETAIL-001',
            'vehicle_id' => $this->heavyEquipment->id,
            'employee_id' => $this->empA->id,
            'template_id' => $this->template->id,
            'shift' => '1',
            'start_hm' => 1500,
            'summary_condition' => 'good',
            'defect_count' => 0,
            'validation_status' => 'approved',
            'inspected_at' => Carbon::now(),
        ]);

        P2hRecordItem::create([
            'p2h_record_id' => $recordA->id,
            'section' => 'Cabin Inspection',
            'title' => 'Kaca Spion & Wiper',
            'question_type' => 'choice',
            'condition_status' => 'baik',
        ]);

        // Detail JSON
        $resJson = $this->actingAs($this->userA)->get(route('employee.history.p2h_detail', $recordA->id));
        $resJson->assertStatus(200);
        $resJson->assertJsonFragment([
            'transaction_no' => 'P2H-DETAIL-001',
            'item_name' => 'Kaca Spion & Wiper',
        ]);

        // PDF stream
        $resPdf = $this->actingAs($this->userA)->get(route('employee.history.p2h_pdf', $recordA->id));
        $resPdf->assertStatus(200);
        $resPdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_employee_cannot_view_or_download_pdf_of_other_employee_p2h(): void
    {
        // Record milik B
        $recordB = P2hRecord::create([
            'transaction_no' => 'P2H-PRIVATE-BUDI',
            'vehicle_id' => $this->heavyEquipment->id,
            'employee_id' => $this->empB->id,
            'template_id' => $this->template->id,
            'shift' => '1',
            'start_hm' => 2000,
            'summary_condition' => 'good',
            'defect_count' => 0,
            'validation_status' => 'approved',
            'inspected_at' => Carbon::now(),
        ]);

        // User A mencoba melihat detail B -> 403 Forbidden
        $resDetail = $this->actingAs($this->userA)->get(route('employee.history.p2h_detail', $recordB->id));
        $resDetail->assertStatus(403);

        // User A mencoba mengunduh PDF B -> 403 Forbidden
        $resPdf = $this->actingAs($this->userA)->get(route('employee.history.p2h_pdf', $recordB->id));
        $resPdf->assertStatus(403);
    }
}
