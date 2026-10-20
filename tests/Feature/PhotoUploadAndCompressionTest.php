<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadAndCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminQma;
    protected User $adminHse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->adminQma = User::factory()->create([
            'username' => 'admin_qma',
            'role' => 'qma_admin',
        ]);
        $this->adminQma->assignRole('qma_admin');

        $this->adminHse = User::factory()->create([
            'username' => 'admin_hse',
            'role' => 'hse_admin',
        ]);
        $this->adminHse->assignRole('hse_admin');
    }

    public function test_admin_qma_can_upload_and_compress_lv_photo(): void
    {
        Storage::fake('public');

        // Simulasi foto mentah resolusi tinggi dari kamera (1600x1200)
        $file = UploadedFile::fake()->image('hilux.png', 1600, 1200);

        $response = $this->actingAs($this->adminQma)->post(route('qma.vehicles.store'), [
            'code' => 'LV-99',
            'category' => 'light_vehicle',
            'brand_type' => 'Toyota Hilux 4x4 Double Cabin',
            'license_plate' => 'BH 9999 XX',
            'division' => 'HSE',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'status' => 'Operation',
            'current_km' => 1500,
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'code' => 'LV-99',
            'category' => 'light_vehicle',
        ]);

        $vehicle = Vehicle::where('code', 'LV-99')->firstOrFail();
        $this->assertNotNull($vehicle->photo_path);
        Storage::disk('public')->assertExists($vehicle->photo_path);

        // Verifikasi resolusi gambar telah dikompresi proporsional (max 800x800)
        $savedContent = Storage::disk('public')->get($vehicle->photo_path);
        $imageResource = @imagecreatefromstring($savedContent);
        $this->assertNotFalse($imageResource);
        $this->assertLessThanOrEqual(800, imagesx($imageResource));
        $this->assertLessThanOrEqual(800, imagesy($imageResource));
        imagedestroy($imageResource);
    }

    public function test_updating_lv_photo_replaces_and_deletes_old_photo(): void
    {
        Storage::fake('public');

        $oldFile = UploadedFile::fake()->image('old_car.png', 1000, 1000);
        $this->actingAs($this->adminQma)->post(route('qma.vehicles.store'), [
            'code' => 'LV-88',
            'category' => 'light_vehicle',
            'brand_type' => 'Mitsubishi Triton',
            'license_plate' => 'BH 8888 XX',
            'status' => 'Operation',
            'current_km' => 2000,
            'photo' => $oldFile,
        ]);

        $vehicle = Vehicle::where('code', 'LV-88')->firstOrFail();
        $oldPath = $vehicle->photo_path;
        Storage::disk('public')->assertExists($oldPath);

        // Update dengan foto baru
        $newFile = UploadedFile::fake()->image('new_car.png', 1200, 800);
        $updateResponse = $this->actingAs($this->adminQma)->post(route('qma.vehicles.update', $vehicle->id), [
            'code' => 'LV-88',
            'category' => 'light_vehicle',
            'brand_type' => 'Mitsubishi Triton Ultimate',
            'license_plate' => 'BH 8888 XX',
            'status' => 'Standby',
            'current_km' => 2500,
            'photo' => $newFile,
        ]);

        $updateResponse->assertRedirect();
        $vehicle->refresh();
        $newPath = $vehicle->photo_path;

        $this->assertNotEquals($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_deleting_vehicle_deletes_its_photo_from_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('car_to_delete.png', 800, 600);
        $this->actingAs($this->adminQma)->post(route('qma.vehicles.store'), [
            'code' => 'LV-DEL',
            'category' => 'light_vehicle',
            'brand_type' => 'Isuzu D-Max',
            'status' => 'Standby',
            'current_km' => 500,
            'photo' => $file,
        ]);

        $vehicle = Vehicle::where('code', 'LV-DEL')->firstOrFail();
        $photoPath = $vehicle->photo_path;
        Storage::disk('public')->assertExists($photoPath);

        $deleteResponse = $this->actingAs($this->adminQma)->delete(route('qma.vehicles.destroy', $vehicle->id));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_admin_qma_can_upload_photo_for_support_unit(): void
    {
        Storage::fake('public');

        // Buat kategori Unit Support terlebih dahulu
        $category = Vehicle::create([
            'code' => 'EX01',
            'category' => 'unit_support',
            'brand_type' => 'Komatsu PC200-8',
            'unit_category' => 'Excavator',
            'status' => 'Operation',
            'current_km' => 0,
        ]);

        $file = UploadedFile::fake()->image('excavator.png', 1500, 1000);

        $response = $this->actingAs($this->adminQma)->post(route('qma.vehicles.support.units.store', $category->id), [
            'unit_number' => '001',
            'serial_number' => 'SN-EXC-999',
            'status' => 'Operation',
            'current_km' => 120,
            'location' => 'Main Dam',
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $unit = Vehicle::where('code', 'EX01-001')->firstOrFail();
        $this->assertNotNull($unit->photo_path);
        Storage::disk('public')->assertExists($unit->photo_path);

        $savedContent = Storage::disk('public')->get($unit->photo_path);
        $imageResource = @imagecreatefromstring($savedContent);
        $this->assertNotFalse($imageResource);
        $this->assertLessThanOrEqual(800, imagesx($imageResource));
        $this->assertLessThanOrEqual(800, imagesy($imageResource));
        imagedestroy($imageResource);
    }

    public function test_admin_hse_can_upload_and_compress_employee_photo(): void
    {
        Storage::fake('public');

        // Simulasi foto avatar mentah ukuran 1200x1200
        $file = UploadedFile::fake()->image('profile.png', 1200, 1200);

        $response = $this->actingAs($this->adminHse)->post(route('hse.employees.store'), [
            'nik' => 'KMH-PHOTO-01',
            'name' => 'Budi Santoso',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Operasi',
            'position' => 'Driver Operasional',
            'is_operator' => '1',
            'simper_expired_at' => now()->addYear()->toDateString(),
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-PHOTO-01',
            'name' => 'Budi Santoso',
        ]);

        $employee = Employee::where('nik', 'KMH-PHOTO-01')->firstOrFail();
        $this->assertNotNull($employee->photo_path);
        Storage::disk('public')->assertExists($employee->photo_path);

        // Verifikasi resolusi avatar dikompresi proporsional (max 500x500 untuk avatar)
        $savedContent = Storage::disk('public')->get($employee->photo_path);
        $imageResource = @imagecreatefromstring($savedContent);
        $this->assertNotFalse($imageResource);
        $this->assertLessThanOrEqual(500, imagesx($imageResource));
        $this->assertLessThanOrEqual(500, imagesy($imageResource));
        imagedestroy($imageResource);
    }

    public function test_updating_employee_photo_replaces_and_deletes_old_photo(): void
    {
        Storage::fake('public');

        $oldFile = UploadedFile::fake()->image('old_avatar.png', 600, 600);
        $this->actingAs($this->adminHse)->post(route('hse.employees.store'), [
            'nik' => 'KMH-PHOTO-02',
            'name' => 'Dewi Lestari',
            'unit_kerja' => 'Umum',
            'division' => 'Keuangan',
            'position' => 'Staff Finance',
            'is_operator' => '0',
            'photo' => $oldFile,
        ]);

        $employee = Employee::where('nik', 'KMH-PHOTO-02')->firstOrFail();
        $oldPath = $employee->photo_path;
        Storage::disk('public')->assertExists($oldPath);

        // Update dengan avatar baru
        $newFile = UploadedFile::fake()->image('new_avatar.png', 800, 800);
        $updateResponse = $this->actingAs($this->adminHse)->post(route('hse.employees.update', $employee->id), [
            'nik' => 'KMH-PHOTO-02',
            'name' => 'Dewi Lestari, S.E.',
            'unit_kerja' => 'Umum',
            'division' => 'Keuangan',
            'position' => 'Senior Staff Finance',
            'is_operator' => '0',
            'photo' => $newFile,
        ]);

        $updateResponse->assertRedirect();
        $employee->refresh();
        $newPath = $employee->photo_path;

        $this->assertNotEquals($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_uploaded_employee_photo_syncs_to_user_and_displays_on_screens(): void
    {
        Storage::fake('public');

        $avatar = UploadedFile::fake()->image('ahmad_avatar.png', 400, 400);

        // HSE menambahkan karyawan Ahmad dengan foto
        $this->actingAs($this->adminHse)->post(route('hse.employees.store'), [
            'nik' => 'ahmad',
            'name' => 'Ahmad Sukaya',
            'unit_kerja' => 'Umum',
            'division' => 'Operasional',
            'position' => 'Driver Operasional',
            'is_operator' => '1',
            'simper_expired_at' => now()->addYear()->toDateString(),
            'photo' => $avatar,
        ]);

        $user = User::where('username', 'ahmad')->firstOrFail();
        $employee = Employee::where('nik', 'ahmad')->firstOrFail();

        // 1. Verifikasi sinkronisasi photo_url antara User dan Employee
        $this->assertNotNull($user->photo_url);
        $this->assertNotNull($employee->photo_url);
        $this->assertEquals($employee->photo_url, $user->photo_url);

        // 2. Login sebagai Ahmad dan buka Dashboard Karyawan
        $dashboardResponse = $this->actingAs($user)->get(route('employee.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee($user->photo_url, false);

        // 3. Buka Halaman Profil Saya
        $profileResponse = $this->actingAs($user)->get(route('profile.index'));
        $profileResponse->assertOk();
        $profileResponse->assertSee($user->photo_url, false);
    }

    public function test_superadmin_can_upload_photo_and_sync_with_employee(): void
    {
        Storage::fake('public');

        $superAdmin = User::factory()->create([
            'username' => 'superadmin_test',
            'role' => 'super_admin',
        ]);
        $superAdmin->assignRole('super_admin');

        $avatar = UploadedFile::fake()->image('user_avatar.png', 500, 500);

        // Super Admin membuat user baru dengan role employee dan menyertakan foto
        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Karyawan Baru',
            'username' => 'karyawan_baru',
            'email' => 'karyawan.baru@kmh.co.id',
            'password' => 'password123',
            'role' => 'employee',
            'photo' => $avatar,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $newUser = User::where('username', 'karyawan_baru')->firstOrFail();
        $this->assertNotNull($newUser->photo_path);
        Storage::disk('public')->assertExists($newUser->photo_path);

        // Verifikasi otomatis sinkron ke profil Employee
        $newEmployee = $newUser->employee;
        $this->assertNotNull($newEmployee);
        $this->assertEquals($newUser->photo_path, $newEmployee->photo_path);
        $this->assertEquals($newUser->photo_url, $newEmployee->photo_url);

        // Verifikasi foto muncul di tabel admin/users
        $indexResponse = $this->actingAs($superAdmin)->get(route('admin.users.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($newUser->photo_url, false);
    }
}
