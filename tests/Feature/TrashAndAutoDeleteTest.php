<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleActivity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrashAndAutoDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Vehicle $vehicle;
    protected Employee $driver;
    protected Gate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'employee']);

        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_test',
            'role' => 'super_admin',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->regularUser = User::factory()->create([
            'username' => 'employee_test',
            'role' => 'employee',
        ]);
        $this->regularUser->assignRole('employee');

        $this->gate = Gate::create([
            'code' => 'GATE-TRASH-01',
            'name' => 'Gate Utama Trash',
            'is_active' => true,
        ]);

        $this->vehicle = Vehicle::create([
            'code' => 'LV-TRASH-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4',
            'license_plate' => 'B 1111 TRH',
            'status' => 'Operation',
            'current_km' => 1000,
        ]);

        $this->driver = Employee::create([
            'nik' => 'DRV-TRASH-01',
            'name' => 'Driver Trash Test',
            'unit_kerja' => 'Umum',
            'division' => 'Operasional',
            'position' => 'Driver',
            'is_operator' => false,
        ]);
    }

    /**
     * 1. Super admin dapat mengakses halaman tong sampah, non-super admin diblokir.
     */
    public function test_super_admin_can_access_trash_page_and_others_forbidden(): void
    {
        $responseSuper = $this->actingAs($this->superAdmin)->get(route('admin.trash.index'));
        $responseSuper->assertOk();
        $responseSuper->assertSee('Tong Sampah Sistem (Recycle Bin)');

        $responseRegular = $this->actingAs($this->regularUser)->get(route('admin.trash.index'));
        $responseRegular->assertForbidden();
    }

    /**
     * 2. Data yang dihapus masuk ke tong sampah (Soft Delete) dan tampil di UI.
     */
    public function test_deleted_records_are_soft_deleted_and_appear_in_trash_ui(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-TRASH-001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1000,
            'current_km' => 1050,
            'distance' => 50,
            'purpose' => 'Pengantaran Sampel Uji',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        // Hapus activity
        $activity->delete();

        // Pastikan soft deleted
        $this->assertSoftDeleted('vehicle_activities', ['id' => $activity->id]);

        // Cek halaman index tong sampah
        $response = $this->actingAs($this->superAdmin)->get(route('admin.trash.index'));
        $response->assertOk();
        $response->assertSee('TRX-TRASH-001');
        $response->assertSee('LV-TRASH-01');

        // Cek filter kategori
        $responseCat = $this->actingAs($this->superAdmin)->get(route('admin.trash.index', ['category' => 'activities']));
        $responseCat->assertOk();
        $responseCat->assertSee('TRX-TRASH-001');
    }

    /**
     * 3. Super admin dapat memulihkan (restore) data dari tong sampah.
     */
    public function test_super_admin_can_restore_trashed_item(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-RESTORE-001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'in',
            'previous_km' => 1050,
            'current_km' => 1100,
            'distance' => 50,
            'purpose' => 'Kepulangan Unit',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        $activity->delete();
        $this->assertSoftDeleted('vehicle_activities', ['id' => $activity->id]);

        // Request restore
        $response = $this->actingAs($this->superAdmin)->post(route('admin.trash.restore', [
            'category' => 'activities',
            'id' => $activity->id,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Data tidak lagi soft-deleted (aktif kembali)
        $this->assertDatabaseHas('vehicle_activities', [
            'id' => $activity->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * 4. Super admin dapat menghapus data secara permanen (force delete).
     */
    public function test_super_admin_can_force_delete_trashed_item(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-FORCE-001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1100,
            'current_km' => 1150,
            'distance' => 50,
            'purpose' => 'Operasional Test',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);

        $activity->delete();
        $this->assertSoftDeleted('vehicle_activities', ['id' => $activity->id]);

        // Request force delete
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.trash.force_delete', [
            'category' => 'activities',
            'id' => $activity->id,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Data terhapus permanen dari basis data
        $this->assertDatabaseMissing('vehicle_activities', [
            'id' => $activity->id,
        ]);
    }

    /**
     * 5. Super admin dapat mengosongkan seluruh tong sampah dengan konfirmasi.
     */
    public function test_super_admin_can_empty_trash_with_confirmation(): void
    {
        $activity = VehicleActivity::create([
            'transaction_no' => 'TRX-EMPTY-001',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1150,
            'current_km' => 1200,
            'distance' => 50,
            'purpose' => 'Test Empty',
            'recorded_at' => Carbon::now(),
            'validation_status' => 'approved',
        ]);
        $activity->delete();

        // 1. Tanpa konfirmasi yang tepat harus ditolak
        $responseFailed = $this->actingAs($this->superAdmin)->post(route('admin.trash.empty'), [
            'confirm_text' => 'SALAH',
        ]);
        $responseFailed->assertSessionHasErrors('confirm_text');

        // 2. Dengan konfirmasi KOSONGKAN
        $responseSuccess = $this->actingAs($this->superAdmin)->post(route('admin.trash.empty'), [
            'confirm_text' => 'KOSONGKAN',
        ]);
        $responseSuccess->assertRedirect();
        $responseSuccess->assertSessionHas('success');

        $this->assertDatabaseMissing('vehicle_activities', [
            'id' => $activity->id,
        ]);
    }

    /**
     * 6. Super admin dapat mengubah durasi retensi otomatis (30, 60, 90, 0 hari).
     */
    public function test_super_admin_can_update_retention_days(): void
    {
        // Set ke 60 hari
        $response = $this->actingAs($this->superAdmin)->post(route('admin.trash.retention'), [
            'retention_days' => 60,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('60', SystemSetting::get('trash_retention_days'));

        // Coba nilai tidak valid
        $responseInvalid = $this->actingAs($this->superAdmin)->post(route('admin.trash.retention'), [
            'retention_days' => 45,
        ]);
        $responseInvalid->assertSessionHasErrors('retention_days');
    }

    /**
     * 7. Artisan command trash:prune menghapus data yang telah melewati retensi.
     */
    public function test_artisan_prune_trash_command_removes_expired_records(): void
    {
        // Item yang kadaluarsa (dihapus 40 hari yang lalu)
        $oldActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-OLD-PRUNE',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1200,
            'current_km' => 1250,
            'distance' => 50,
            'purpose' => 'Aktivitas Kadaluarsa',
            'recorded_at' => Carbon::now()->subDays(40),
            'validation_status' => 'approved',
        ]);
        $oldActivity->delete();
        // Update deleted_at secara eksplisit ke 40 hari lalu
        VehicleActivity::withTrashed()->where('id', $oldActivity->id)->update([
            'deleted_at' => Carbon::now()->subDays(40),
        ]);

        // Item yang masih baru (dihapus 5 hari yang lalu)
        $freshActivity = VehicleActivity::create([
            'transaction_no' => 'TRX-FRESH-PRUNE',
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'gate_id' => $this->gate->id,
            'direction' => 'out',
            'previous_km' => 1250,
            'current_km' => 1300,
            'distance' => 50,
            'purpose' => 'Aktivitas Baru',
            'recorded_at' => Carbon::now()->subDays(5),
            'validation_status' => 'approved',
        ]);
        $freshActivity->delete();
        VehicleActivity::withTrashed()->where('id', $freshActivity->id)->update([
            'deleted_at' => Carbon::now()->subDays(5),
        ]);

        // Jalankan artisan trash:prune dengan retensi 30 hari
        $this->artisan('trash:prune', ['--days' => 30])
            ->expectsOutputToContain('Pembersihan selesai')
            ->assertExitCode(0);

        // Record yang 40 hari lalu harus terhapus permanen
        $this->assertDatabaseMissing('vehicle_activities', [
            'id' => $oldActivity->id,
        ]);

        // Record yang 5 hari lalu harus tetap ada di tong sampah
        $this->assertSoftDeleted('vehicle_activities', [
            'id' => $freshActivity->id,
        ]);
    }

    public function test_vehicle_photo_preserved_on_soft_delete_and_restored(): void
    {
        $vehicle = Vehicle::create([
            'code' => 'LV-PHOTO-01',
            'category' => 'light_vehicle',
            'brand_type' => 'Hilux',
            'license_plate' => 'BH 9999 AA',
            'status' => 'Operation',
            'current_km' => 500,
            'photo_path' => 'vehicles/dummy_photo.jpg',
        ]);

        // Simulasikan penghapusan kendaraan
        $this->actingAs($this->superAdmin)
            ->delete(route('qma.vehicles.destroy', $vehicle->id));

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);

        // Verifikasi photo_path masih tersimpan di record
        $trashed = Vehicle::onlyTrashed()->find($vehicle->id);
        $this->assertNotNull($trashed);
        $this->assertEquals('vehicles/dummy_photo.jpg', $trashed->photo_path);

        // Pulihkan dari tong sampah
        $this->actingAs($this->superAdmin)
            ->post(route('admin.trash.restore', ['category' => 'vehicles', 'id' => $vehicle->id]));

        $restored = Vehicle::find($vehicle->id);
        $this->assertNotNull($restored);
        $this->assertEquals('vehicles/dummy_photo.jpg', $restored->photo_path);
    }
}
