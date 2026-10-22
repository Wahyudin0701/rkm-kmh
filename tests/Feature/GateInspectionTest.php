<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateInspectionTest extends TestCase
{
    use RefreshDatabase;

    private User $guardUser;
    private SecurityGuard $guard;
    private Gate $gate1;
    private Gate $gate2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gate1 = Gate::create([
            'name' => 'Pos Gerbang Utama',
            'code' => 'GATE-01',
            'location' => 'Main Entrance',
            'is_active' => true,
        ]);

        $this->gate2 = Gate::create([
            'name' => 'Pos Gerbang Bendungan',
            'code' => 'GATE-02',
            'location' => 'Dam Access',
            'is_active' => true,
        ]);

        // Satpam dibuat TANPA gate_id (fleksibel)
        $this->guardUser = User::factory()->create([
            'name' => 'Petugas Satpam Uji',
            'username' => 'SEC999',
            'role' => 'gate_officer',
            'gate_id' => null,
        ]);
        $this->guardUser->assignRole('gate_officer');

        $this->guard = SecurityGuard::create([
            'badge_id' => 'SEC999',
            'name' => 'Petugas Satpam Uji',
            'team' => 1,
            'gate_id' => null,
            'user_id' => $this->guardUser->id,
            'is_active' => true,
        ]);
    }

    /**
     * Saat satpam pertama kali login dan belum memilih gerbang di sesi,
     * flag needsGateSelection = true dan modal pemilihan pos gerbang ditampilkan.
     */
    public function test_guard_without_selected_gate_sees_selection_modal(): void
    {
        $response = $this->actingAs($this->guardUser)
            ->get(route('pg.gate.inspection'));

        $response->assertOk();
        $response->assertViewHas('needsGateSelection', true);
        $response->assertSee('Pilih Pos Gerbang Bertugas');
        $response->assertSee('Pos Gerbang Utama');
        $response->assertSee('Pos Gerbang Bendungan');
        $response->assertSee('Ganti Pos');
    }

    /**
     * Satpam dapat memilih pos gerbang via POST select-gate.
     * Sistem menyimpan ke session active_gate_id dan mengalihkan ke verifikasi gerbang terkait.
     */
    public function test_guard_can_select_gate_and_stores_in_session(): void
    {
        $response = $this->actingAs($this->guardUser)
            ->post(route('pg.gate.select_gate'), [
                'gate_id' => $this->gate2->id,
            ]);

        $response->assertRedirect(route('pg.gate.inspection', ['gate_id' => $this->gate2->id]));
        $response->assertSessionHas('active_gate_id', $this->gate2->id);

        $this->guardUser->refresh();
        $this->assertEquals($this->gate2->id, $this->guardUser->gate_id);

        // Setelah tersimpan di sesi, kunjungan berikutnya tidak memunculkan needsGateSelection = true
        $pageResponse = $this->actingAs($this->guardUser)
            ->withSession(['active_gate_id' => $this->gate2->id])
            ->get(route('pg.gate.inspection'));

        $pageResponse->assertOk();
        $pageResponse->assertViewHas('needsGateSelection', false);
        $pageResponse->assertSee('Pos Pos Gerbang Bendungan');
    }

    /**
     * Live queue mengambil data antrean sesuai gerbang yang sedang aktif.
     */
    public function test_live_queue_filters_by_active_gate(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'current_km' => 1000,
        ]);

        $driver = Employee::create([
            'name' => 'Driver Uji',
            'nik' => 'KMH-999',
        ]);

        // Buat aktivitas pending di Gate 2
        VehicleActivity::create([
            'transaction_no' => 'TRX-999-01',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $this->gate2->id,
            'direction' => 'in',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Mobilisasi Pekerja Project',
            'validation_status' => 'pending',
            'recorded_at' => now(),
        ]);

        // Request live queue dengan gate_id Gate 1 -> count = 0
        $resGate1 = $this->actingAs($this->guardUser)
            ->getJson(route('pg.gate.live_queue', ['gate_id' => $this->gate1->id]));

        $resGate1->assertOk();
        $resGate1->assertJsonPath('count', 0);

        // Request live queue dengan gate_id Gate 2 -> count = 1
        $resGate2 = $this->actingAs($this->guardUser)
            ->getJson(route('pg.gate.live_queue', ['gate_id' => $this->gate2->id]));

        $resGate2->assertOk();
        $resGate2->assertJsonPath('count', 1);
        $this->assertStringContainsString('LV-99', $resGate2->json('html'));
    }

    /**
     * Admin PG dapat mendaftarkan Satpam baru tanpa mengisi gate_id (penempatan fleksibel).
     */
    public function test_admin_pg_can_store_guard_without_gate_id(): void
    {
        $adminPg = User::factory()->create([
            'name' => 'Admin PG Tester',
            'username' => 'admin_pg_test',
            'role' => 'pg_admin',
        ]);
        $adminPg->assignRole('pg_admin');

        $data = [
            'badge_id' => 'SEC-NEW-01',
            'name' => 'Satpam Baru Fleksibel',
            'team' => 2,
            'password' => 'secret123',
            // gate_id dikosongkan
        ];

        $response = $this->actingAs($adminPg)
            ->post(route('pg.guards.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('security_guards', [
            'badge_id' => 'SEC-NEW-01',
            'name' => 'Satpam Baru Fleksibel',
            'team' => 2,
            'gate_id' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'SEC-NEW-01',
            'role' => 'gate_officer',
            'gate_id' => null,
        ]);
    }
}
