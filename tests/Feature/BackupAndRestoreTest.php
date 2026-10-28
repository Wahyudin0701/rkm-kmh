<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackupAndRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private DatabaseBackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_backup_test',
            'role' => 'super_admin',
            'password' => bcrypt('password123'),
        ]);
        $this->superAdmin->assignRole($role);

        $this->backupService = new DatabaseBackupService();
    }

    protected function tearDown(): void
    {
        // Bersihkan seluruh file uji coba di storage/app/backups
        $dir = $this->backupService->getBackupDirectory();
        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $f) {
                if (str_starts_with($f->getFilename(), 'test_') || str_starts_with($f->getFilename(), 'backup_')) {
                    @unlink($f->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    public function test_super_admin_can_access_backup_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.backups.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.backups.index');
        $response->assertViewHas('backups');
        $response->assertViewHas('stats');
        $response->assertViewHas('tableGroups');
        $response->assertSee('Backup &amp; Restore Sistem', false);
        $response->assertSee('Buat Backup Baru');
        $response->assertSee('.kmh');
    }

    public function test_non_super_admin_cannot_access_backup_page(): void
    {
        $regularUser = User::factory()->create([
            'username' => 'regular_user_test',
            'role' => 'employee',
        ]);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $regularUser->assignRole($employeeRole);

        $response = $this->actingAs($regularUser)
            ->get(route('admin.backups.index'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_pilihan_backup_kmh(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.store'), [
                'backup_scope' => 'pilihan',
                'selected_tables' => ['users', 'roles'],
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $latest = $backups[0];

        $this->assertEquals('pilihan', $latest['type']);
        $this->assertTrue($latest['is_kmh']);
        $this->assertStringEndsWith('.kmh', $latest['filename']);

        // Verifikasi enkripsi: File harus diawali header magic KMH dan isinya terenkripsi
        $rawContent = file_get_contents($latest['path']);
        $this->assertStringStartsWith(DatabaseBackupService::KMH_MAGIC_HEADER, $rawContent);
        // Pastikan tidak ada plain text SQL di file mentah
        $this->assertStringNotContainsString('INSERT INTO `users`', $rawContent);

        // Verifikasi log aktivitas
        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'create',
            'module' => 'Backup & Restore',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_create_semua_backup_kmh(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.store'), [
                'backup_scope' => 'semua',
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $latest = $backups[0];

        $this->assertEquals('semua', $latest['type']);
        $this->assertTrue($latest['is_kmh']);
        $this->assertStringEndsWith('.kmh', $latest['filename']);
    }

    public function test_super_admin_can_download_kmh_backup_file(): void
    {
        $filename = $this->backupService->createKmhBackup(['users'], false, 'test_dl.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.backups.download', $filename));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_super_admin_can_upload_kmh_backup_file(): void
    {
        $validKmhContent = $this->backupService->encryptToKmh('dummy zip or sql content');
        $file = UploadedFile::fake()->createWithContent('test_uploaded.kmh', $validKmhContent);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.upload'), [
                'backup_file' => $file,
            ]);

        $response->assertSessionHas('success');

        $targetPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'test_uploaded.kmh';
        $this->assertTrue(File::exists($targetPath));
    }

    public function test_restore_requires_valid_confirmation_or_password(): void
    {
        $filename = $this->backupService->createKmhBackup(['users'], false, 'test_for_restore.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.restore', $filename), [
                'confirmation' => 'SALAH',
                'password' => 'passwordsalah',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_super_admin_can_restore_from_kmh_backup(): void
    {
        $filename = $this->backupService->createKmhBackup(['users'], false, 'test_real_restore.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.restore', $filename), [
                'confirmation' => 'PULIHKAN',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'update',
            'module' => 'Backup & Restore',
            'target_name' => $filename,
        ]);
    }

    public function test_restore_rejects_tampered_or_unauthorized_external_file(): void
    {
        $fakeKmh = DatabaseBackupService::KMH_MAGIC_HEADER . random_bytes(16) . random_bytes(32) . 'corrupted_ciphertext';
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'test_corrupt.kmh';
        file_put_contents($fullPath, $fakeKmh);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.restore', 'test_corrupt.kmh'), [
                'confirmation' => 'PULIHKAN',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_super_admin_can_delete_kmh_backup_file(): void
    {
        $filename = $this->backupService->createKmhBackup(['users'], false, 'test_to_delete.kmh');
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($fullPath));

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.backups.destroy', $filename));

        $response->assertSessionHas('success');
        $this->assertFalse(File::exists($fullPath));
    }

    public function test_cli_artisan_backup_create_command_creates_kmh(): void
    {
        $this->artisan('backup:create', [
            '--scope' => 'semua',
            '--filename' => 'test_cli_backup.kmh',
        ])->assertSuccessful();

        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'test_cli_backup.kmh';
        $this->assertTrue(File::exists($fullPath));

        $content = file_get_contents($fullPath);
        $this->assertStringStartsWith(DatabaseBackupService::KMH_MAGIC_HEADER, $content);
    }

    /**
     * Memastikan data yang berada di tong sampah (soft-deleted) TIDAK bocor ke dalam file backup .kmh.
     */
    public function test_backup_does_not_leak_trashed_records(): void
    {
        // 1. Buat user aktif dan user yang dihapus (masuk tong sampah)
        $activeUser = User::factory()->create([
            'username' => 'user_aktif_backup',
            'email' => 'aktif_backup@kmh.co.id',
        ]);

        $trashedUser = User::factory()->create([
            'username' => 'user_sampah_backup',
            'email' => 'sampah_backup@kmh.co.id',
        ]);
        $trashedUser->delete();

        $this->assertSoftDeleted('users', ['id' => $trashedUser->id]);

        // 2. Buat backup khusus tabel users
        $filename = $this->backupService->createKmhBackup(['users'], false, 'test_no_trash_leak.kmh');
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($fullPath));

        // 3. Dekripsi paket .kmh dan baca file database.sql di dalamnya
        $kmhContent = file_get_contents($fullPath);
        $zipContent = $this->backupService->decryptFromKmh($kmhContent);

        $tempZip = storage_path('app/temp_verify_leak_' . uniqid() . '.zip');
        file_put_contents($tempZip, $zipContent);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tempZip));
        $sqlContent = $zip->getFromName('database.sql');
        $zip->close();
        @unlink($tempZip);

        // 4. Pastikan data user aktif ada di file SQL, sedangkan data di tong sampah TIDAK ADA SAMA SEKALI
        $this->assertStringContainsString('aktif_backup@kmh.co.id', $sqlContent);
        $this->assertStringNotContainsString('sampah_backup@kmh.co.id', $sqlContent);
    }
}
