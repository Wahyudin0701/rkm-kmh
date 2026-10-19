<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_unauthorized_user_accessing_employee_dashboard_gets_403_forbidden_page(): void
    {
        // 1. Akun Admin QMA (misal Pinto Yuliandra)
        $qmaUser = User::factory()->create([
            'username' => '1171',
            'name' => 'Pinto Yuliandra',
            'role' => 'qma_admin',
        ]);
        $qmaUser->assignRole('qma_admin');

        // 2. Akses URL /karyawan/dashboard secara langsung
        $response = $this->actingAs($qmaUser)->get(route('employee.dashboard'));

        // 3. Harus dihadang dan menampilkan halaman 403 Forbidden branded KMH
        $response->assertStatus(403);
        $response->assertSee('Akses Tidak Diizinkan');
        $response->assertSee('Akses Ditolak: Anda tidak memiliki wewenang untuk membuka halaman operasional karyawan.');
        $response->assertSee('Buka Dashboard Saya');
        $response->assertSee('Ke Lobi Utama');
    }

    public function test_authorized_employee_can_access_employee_dashboard(): void
    {
        $employeeUser = User::factory()->create([
            'username' => 'EMP-001',
            'name' => 'Budi Operator',
            'role' => 'employee',
        ]);
        $employeeUser->assignRole('employee');

        Employee::create([
            'user_id' => $employeeUser->id,
            'nik' => 'EMP-001',
            'name' => 'Budi Operator',
            'unit_kerja' => 'Operasional',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addYear(),
        ]);

        $response = $this->actingAs($employeeUser)->get(route('employee.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard Karyawan');
    }

    public function test_super_admin_can_bypass_and_access_employee_dashboard(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->get(route('employee.dashboard'));
        $response->assertOk();
        $response->assertSee('Dashboard Karyawan');
    }

    public function test_unauthorized_user_accessing_admin_route_gets_branded_403_page(): void
    {
        $employeeUser = User::factory()->create([
            'username' => 'EMP-002',
            'name' => 'Siti Karyawan',
            'role' => 'employee',
        ]);
        $employeeUser->assignRole('employee');

        // Employee mencoba mengakses rute HSE Admin
        $response = $this->actingAs($employeeUser)->get(route('hse.employees.index'));

        // Harus mendapatkan HTTP 403 Forbidden dan merender view errors.403 yang branded
        $response->assertStatus(403);
        $response->assertSee('Akses Tidak Diizinkan');
        $response->assertSee('Ke Lobi Utama');
    }

    public function test_guest_accessing_employee_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get(route('employee.dashboard'));
        $response->assertRedirect(route('login'));
    }
}
