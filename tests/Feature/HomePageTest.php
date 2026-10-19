<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasicData(): void
    {
        Gate::create(['code' => 'ADIT1', 'name' => 'Adit 1', 'is_active' => true]);
        Gate::create(['code' => 'BTUNGGAL', 'name' => 'Beringin Tunggal', 'is_active' => true]);

        Employee::create([
            'nik' => '1001',
            'name' => 'Driver Budi',
            'unit_kerja' => 'Umum',
            'division' => 'GA',
            'position' => 'Driver',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addMonths(6),
        ]);

        Vehicle::create([
            'code' => 'LV62',
            'category' => 'light_vehicle',
            'brand_type' => 'MITSUBISHI L300',
            'license_plate' => 'BH 8893 DI',
            'status' => 'Operation',
            'current_km' => 12500,
        ]);

        Vehicle::create([
            'code' => 'LV01',
            'category' => 'light_vehicle',
            'brand_type' => 'TOYOTA HILUX',
            'license_plate' => 'BH 1024 AB',
            'status' => 'Operation',
            'current_km' => 45000,
        ]);
    }

    public function test_homepage_renders_successfully_for_guest(): void
    {
        $this->seedBasicData();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sistem RKM');
        $response->assertSee('PT Kerinci Merangin Hidro');
        $response->assertSee('PLTA Merangin 350 MW');
        $response->assertSee('Login');
        $response->assertSee('Masuk Ke Sistem RKM');
    }

    public function test_homepage_displays_system_summary_and_modules(): void
    {
        $this->seedBasicData();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Sistem');
        $response->assertSee('Modul 1: HSE');
        $response->assertSee('Modul 2: QMA');
        $response->assertSee('Modul 3: Private Guard (PG)');
        $response->assertSee('Bagaimana Aktivitas Kendaraan Lewat Gate Bekerja?');
    }

    public function test_gate_activity_index_is_removed_and_returns_404(): void
    {
        $this->seedBasicData();

        $response = $this->get('/gate-activity');

        $response->assertStatus(404);
    }

    public function test_homepage_does_not_display_recent_activities_or_quick_access_cards_for_privacy(): void
    {
        $this->seedBasicData();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Riwayat Aktivitas Kendaraan Lewat Gerbang');
        $response->assertDontSee('Riwayat Gerbang');
        $response->assertDontSee('Akses Cepat Pengguna');
        $response->assertDontSee('quickCodeInput');
    }

    public function test_gate_activity_form_via_qr_code_remains_accessible(): void
    {
        $this->seedBasicData();

        // Guest diarahkan login
        $guestResponse = $this->get('/gate-activity/LV62');
        $guestResponse->assertRedirect(route('login'));

        // Pengguna terautentikasi dapat mengakses formulir
        $user = User::factory()->create(['role' => 'employee']);
        $response = $this->actingAs($user)->get('/gate-activity/LV62');

        $response->assertStatus(200);
        $response->assertSee('Input Timesheet Unit');
        $response->assertSee('LV62');
        $response->assertDontSee('Kembali ke Beranda RKM');
    }

    public function test_homepage_displays_dashboard_link_when_authenticated(): void
    {
        $this->seedBasicData();

        $user = User::factory()->create([
            'name' => 'Pinto QMA',
            'role' => 'qma_admin',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Pinto QMA');
        $response->assertSee(route('qma.dashboard'));
    }

    public function test_login_page_renders_back_to_home_button(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Kembali ke Beranda RKM');
        $response->assertSee(route('home'));
    }
}
