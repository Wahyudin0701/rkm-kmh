<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSoftDeleteAndProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_deleting_user_with_operational_history_soft_deletes_cleanly_without_500(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Buat user & employee sukon/karyawan
        $user = User::factory()->create([
            'name' => 'Supri Driver',
            'username' => '123456',
            'email' => 'supri@example.com',
            'role' => 'employee',
        ]);
        $user->assignRole('employee');

        $employee = Employee::create([
            'user_id' => $user->id,
            'nik' => '123456',
            'name' => 'Supri Driver',
            'employee_type' => 'Sukon',
            'division' => 'subkon',
            'position' => 'Driver',
            'is_operator' => true,
        ]);

        $gate = Gate::first() ?: Gate::create(['name' => 'Gate Utama', 'code' => 'GU-01']);
        $vehicle = Vehicle::first() ?: Vehicle::factory()->create();

        // Buat aktivitas riwayat operasional kendaraan
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-SUPRI-001',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $employee->id,
            'gate_id' => $gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Pengiriman Material',
            'recorded_at' => now(),
            'status' => 'approved',
        ]);

        // Super Admin menghapus akun user Supri dari menu Kelola Pengguna
        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $user->id));

        // Harus berhasil redirect tanpa error 500
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // Pastikan User & Employee berstatus Soft Deleted (ada di tong sampah)
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        // Pastikan riwayat aktivitas operasional tetap UTUH dan tidak hilang
        $this->assertDatabaseHas('vehicle_activities', [
            'id' => $activity->id,
            'driver_id' => $employee->id,
        ]);

        // Coba lakukan Hapus Permanen dari Tong Sampah -> Harus ditolak karena integritas audit
        $forceResponse = $this->actingAs($superAdmin)
            ->delete(route('admin.trash.force_delete', [
                'category' => 'users',
                'id' => $user->id,
            ]));

        $forceResponse->assertStatus(302);
        $forceResponse->assertSessionHas('error');

        // User tetap ada di basis data (tidak terhapus permanen)
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        // Pulihkan akun dari Tong Sampah -> User & Employee harus aktif kembali beserta perannya
        $restoreResponse = $this->actingAs($superAdmin)
            ->post(route('admin.trash.restore', [
                'category' => 'users',
                'id' => $user->id,
            ]));

        $restoreResponse->assertStatus(302);
        $restoreResponse->assertSessionHas('success');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertTrue($user->fresh()->hasRole('employee'));
    }
}
