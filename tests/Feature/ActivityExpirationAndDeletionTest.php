<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityExpirationAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $qmaUser;
    protected User $pgUser;
    protected User $driverUser;
    protected Employee $driver;
    protected Vehicle $vehicle;
    protected Gate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSampleData();

        $this->superAdmin = User::where('username', 'superadmin')->first();

        // Siapkan master kendaraan & gerbang
        $this->gate = Gate::first();
        $this->vehicle = Vehicle::create([
            'category' => 'light_vehicle',
            'code' => 'LV-TEST-01',
            'brand_type' => 'Toyota Hilux 2.4G',
            'license_plate' => 'BH 1234 XX',
            'division' => 'Operasional',
            'ownership' => 'PT KMH',
            'status' => 'Operation',
            'current_km' => 1000.0,
        ]);

        // Siapkan driver
        $this->driver = Employee::create([
            'name' => 'Bambang Supriadi',
            'nik' => 'DRV-999',
            'position' => 'Driver',
            'division' => 'Operasional',
            'is_operator' => true,
            'simper_expired_at' => Carbon::now()->addMonths(6),
        ]);
        $this->driverUser = User::factory()->create([
            'username' => 'DRV-999',
            'role' => 'employee',
        ]);
        $this->driver->update(['user_id' => $this->driverUser->id]);
        $this->driverUser->assignRole('employee');

        // Siapkan user QMA (tanpa permission delete)
        $this->qmaUser = User::factory()->create(['role' => 'qma_admin']);
        $this->qmaUser->assignRole('qma_admin');

        // Siapkan user PG (tanpa permission delete)
        $this->pgUser = User::factory()->create(['role' => 'pg_admin']);
        $this->pgUser->assignRole('pg_admin');
    }

    /**
     * 1. Tiket perjalanan pending yang sudah berusia > 5 jam otomatis dibatalkan sistem.
     */
    public function test_pending_activity_older_than_5_hours_is_automatically_cancelled(): void
    {
        // Buat tiket perjalanan 6 jam yang lalu
        $overdueActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-EXP-01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Mobilisasi Site',
            'recorded_at' => Carbon::now()->subHours(6),
            'validation_status' => 'pending',
        ]);

        // Buat tiket perjalanan 2 jam yang lalu (masih valid)
        $validActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-VAL-02',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'in',
            'previous_km' => 1050.0,
            'current_km' => 1100.0,
            'distance' => 50.0,
            'purpose' => 'Kembali ke Site',
            'recorded_at' => Carbon::now()->subHours(2),
            'validation_status' => 'pending',
        ]);

        // Jalankan fungsi auto expire
        $affected = VehicleActivity::expireOverduePendingActivities(5);

        $this->assertEquals(1, $affected);

        $overdueActivity->refresh();
        $this->assertEquals('cancelled', $overdueActivity->validation_status);
        $this->assertStringContainsString('Dibatalkan sistem: Melebihi batas waktu verifikasi (5 jam)', $overdueActivity->rejection_note);

        $validActivity->refresh();
        $this->assertEquals('pending', $validActivity->validation_status);
    }

    /**
     * 2. Perintah artisan activities:expire-pending bekerja membatalkan tiket kedaluwarsa.
     */
    public function test_artisan_command_expires_pending_activities(): void
    {
        VehicleActivity::create([
            'transaction_no' => 'TRX-CMD-01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Uji Command',
            'recorded_at' => Carbon::now()->subHours(7),
            'validation_status' => 'pending',
        ]);

        $this->artisan('activities:expire-pending')
            ->expectsOutputToContain('Selesai: Sebanyak 1 tiket perjalanan berhasil dibatalkan otomatis.')
            ->assertSuccessful();

        $activity = VehicleActivity::where('transaction_no', 'TRX-CMD-01')->first();
        $this->assertEquals('cancelled', $activity->validation_status);
    }

    /**
     * 3. Driver dashboard otomatis membatalkan tiket > 5 jam saat diakses dan kartu pending hilang.
     */
    public function test_driver_dashboard_auto_cancels_overdue_ticket_and_clears_pending_card(): void
    {
        VehicleActivity::create([
            'transaction_no' => 'TRX-DRV-01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Perjalanan Terlewat',
            'recorded_at' => Carbon::now()->subHours(5)->subMinutes(10),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($this->driverUser)->get(route('employee.dashboard'));
        $response->assertOk();

        // Card pending tidak boleh muncul karena tiket sudah kedaluwarsa
        $response->assertDontSee('Perjalanan Aktif');

        $activity = VehicleActivity::where('transaction_no', 'TRX-DRV-01')->first();
        $this->assertEquals('cancelled', $activity->validation_status);
        $this->assertStringContainsString('Dibatalkan sistem: Melebihi batas waktu verifikasi (5 jam)', $activity->rejection_note);
    }

    /**
     * 4. User tanpa permission qma.logs.delete tidak dapat menghapus aktivitas QMA (403).
     */
    public function test_unauthorized_user_cannot_delete_qma_activity(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-DEL-01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Testing Hapus',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        $response = $this->actingAs($this->qmaUser)->delete(route('qma.activity.destroy', $activity->id));
        $response->assertForbidden();

        $this->assertDatabaseHas('vehicle_activities', ['id' => $activity->id]);
    }

    /**
     * 5. User dengan permission qma.logs.delete atau Super Admin dapat menghapus aktivitas QMA.
     */
    public function test_authorized_user_and_superadmin_can_delete_qma_activity(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-DEL-02',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Testing Hapus QMA Berhasil',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'pending',
        ]);

        // Berikan izin qma.logs.delete ke qmaUser
        $this->qmaUser->givePermissionTo('qma.logs.delete');

        $response = $this->actingAs($this->qmaUser)->delete(route('qma.activity.destroy', $activity->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('vehicle_activities', ['id' => $activity->id]);

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'delete',
            'module' => 'Operasional Kendaraan (QMA)',
            'target_name' => "{$this->vehicle->code} (TRX-DEL-02)",
        ]);
    }

    /**
     * 6. User tanpa permission pg.logs.delete tidak dapat menghapus log verifikasi PG (403).
     */
    public function test_unauthorized_user_cannot_delete_pg_verification_log(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-PG-DEL-01',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Testing Hapus PG',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        $response = $this->actingAs($this->pgUser)->delete(route('pg.logs.destroy', $activity->id));
        $response->assertForbidden();

        $this->assertDatabaseHas('vehicle_activities', ['id' => $activity->id]);
    }

    /**
     * 7. User dengan permission pg.logs.delete atau Super Admin dapat menghapus log verifikasi PG.
     */
    public function test_authorized_user_and_superadmin_can_delete_pg_verification_log(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-PG-DEL-02',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000.0,
            'current_km' => 1050.0,
            'distance' => 50.0,
            'purpose' => 'Testing Hapus PG Berhasil',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        // Berikan izin pg.logs.delete ke pgUser
        $this->pgUser->givePermissionTo('pg.logs.delete');

        $response = $this->actingAs($this->pgUser)->delete(route('pg.logs.destroy', $activity->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('vehicle_activities', ['id' => $activity->id]);

        // Verifikasi audit log tercatat
        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'delete',
            'module' => 'Keamanan & Pos Satpam (PG)',
            'target_name' => "{$this->vehicle->code} (TRX-PG-DEL-02)",
        ]);
    }
}
