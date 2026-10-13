<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationLogFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminPg;
    protected VehicleActivity $testActivity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminPg = User::where('role', 'pg_admin')->first();
        $vehicle = Vehicle::first();
        $driver = Employee::first();
        $guard = SecurityGuard::first();

        $this->testActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-AUDIT-TEST-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'gate_id' => $guard->gate_id ?? Gate::first()->id,
            'security_guard_id' => $guard->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Uji Validasi Audit Gerbang',
            'validation_status' => 'approved',
            'recorded_at' => Carbon::now(),
            'validated_at' => Carbon::now(),
        ]);
    }

    /**
     * Memastikan halaman log verifikasi gerbang dapat diakses dengan layout terpisah dan card tertutup secara default.
     */
    public function test_admin_pg_can_view_verification_logs_with_collapsed_filter_card_by_default(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Log Riwayat Verifikasi Gerbang');
        $response->assertSee('verificationLogSearchInput');
        $response->assertSee('filterToggleBtn');
        $response->assertSee('id="collapsibleFilterCard" class="hidden bg-white', false);
        $response->assertDontSee('id="headerResetFilterBtn"');
    }

    /**
     * Memastikan card filter otomatis terbuka (auto-expanded / tanpa hidden) jika URL memiliki parameter filter lanjutan.
     */
    public function test_collapsible_filter_card_auto_expands_when_filter_params_present(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index', ['team' => 1]));

        $response->assertStatus(200);
        $response->assertSee('id="collapsibleFilterCard" class="bg-white', false);
        $response->assertDontSee('id="collapsibleFilterCard" class="hidden', false);
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan card filter tetap tertutup jika URL hanya memiliki parameter search saja.
     */
    public function test_collapsible_filter_card_remains_collapsed_when_only_search_param_present(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index', ['search' => 'TRX']));

        $response->assertStatus(200);
        $response->assertSee('id="collapsibleFilterCard" class="hidden bg-white', false);
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan pencarian teks menyaring log verifikasi dengan benar.
     */
    public function test_verification_logs_search_filtering(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index', ['search' => 'TRX-AUDIT-TEST-001']));

        $response->assertStatus(200);
        $response->assertSee('TRX-AUDIT-TEST-001');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan pesan pencarian kosong menampilkan format keterangan yang ditentukan saat data tidak ditemukan.
     */
    public function test_verification_logs_search_no_results_displays_message_and_reset_button(): void
    {
        $keyword = 'XYZNONEXISTENTKEYWORD123';
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index', ['search' => $keyword]));

        $response->assertStatus(200);
        $response->assertSee('🔍');
        $response->assertSeeText('Data "' . $keyword . '" tidak ditemukan di tabel', false);
        $response->assertSeeText('Pastikan ejaan nomor transaksi, plat kendaraan, nama pengemudi, atau petugas sudah benar.');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan filter multi-parameter (pos gerbang & arah) bekerja dengan benar.
     */
    public function test_verification_logs_filtered_by_gate_and_direction(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index', [
            'gate_id' => $this->testActivity->gate_id,
            'direction' => 'out',
        ]));

        $response->assertStatus(200);
        $response->assertSee('TRX-AUDIT-TEST-001');
        $response->assertSee('id="collapsibleFilterCard" class="bg-white', false);
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan filter rentang tanggal secara default diset ke hari ini.
     */
    public function test_verification_logs_defaults_date_filter_to_today(): void
    {
        $todayStr = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday();

        // Buat aktivitas kemarin
        $yesterdayActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-YESTERDAY-001',
            'vehicle_id' => $this->testActivity->vehicle_id,
            'driver_id' => $this->testActivity->driver_id,
            'gate_id' => $this->testActivity->gate_id,
            'security_guard_id' => $this->testActivity->security_guard_id,
            'direction' => 'in',
            'previous_km' => 900,
            'current_km' => 950,
            'distance' => 50,
            'purpose' => 'Aktivitas Hari Kemarin',
            'validation_status' => 'approved',
            'recorded_at' => $yesterday,
            'validated_at' => $yesterday,
        ]);

        $response = $this->actingAs($this->adminPg)->get(route('pg.logs.index'));

        $response->assertStatus(200);
        $this->assertEquals($todayStr, $response->viewData('startDate'));
        $this->assertEquals($todayStr, $response->viewData('endDate'));

        // Input value Mulai Tanggal dan Sampai Tanggal di-set hari ini
        $response->assertSee('name="start_date"', false);
        $response->assertSee('name="end_date"', false);
        $response->assertSee('value="' . $todayStr . '"', false);

        // Data hari ini tampil, data kemarin tidak tampil secara default
        $response->assertSee('TRX-AUDIT-TEST-001');
        $response->assertDontSee('TRX-YESTERDAY-001');

        // Saat rentang tanggal disetel ke kemarin, data kemarin muncul
        $customDateResponse = $this->actingAs($this->adminPg)->get(route('pg.logs.index', [
            'start_date' => $yesterday->toDateString(),
            'end_date' => $todayStr,
        ]));
        $customDateResponse->assertStatus(200);
        $customDateResponse->assertSee('TRX-YESTERDAY-001');
        $customDateResponse->assertSee('TRX-AUDIT-TEST-001');
    }
}

