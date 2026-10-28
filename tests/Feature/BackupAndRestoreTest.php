<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
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
        $response->assertSee('Backup & Restore Sistem');
        $response->assertSee('Buat Backup Baru');
        $response->assertSee('Unggah File Backup');
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

    public function test_super_admin_can_create_database_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.store'), [
                'type' => 'database',
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $this->assertEquals('database', $backups[0]['type']);
        $this->assertStringEndsWith('.sql', $backups[0]['filename']);

        // Verifikasi log aktivitas
        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'create',
            'module' => 'Backup & Restore',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_download_backup_file(): void
    {
        $filename = $this->backupService->createDatabaseBackup('test_dl.sql');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.backups.download', $filename));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_super_admin_can_upload_backup_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('test_uploaded_backup.sql', '-- MySQL dump test\nSET FOREIGN_KEY_CHECKS=0;\n');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.upload'), [
                'backup_file' => $file,
            ]);

        $response->assertSessionHas('success');

        $targetPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'test_uploaded_backup.sql';
        $this->assertTrue(File::exists($targetPath));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'create',
            'module' => 'Backup & Restore',
            'target_name' => 'test_uploaded_backup.sql',
        ]);
    }

    public function test_restore_requires_valid_confirmation_or_password(): void
    {
        $filename = $this->backupService->createDatabaseBackup('test_for_restore.sql');

        // Percobaan tanpa konfirmasi yang benar
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.restore', $filename), [
                'confirmation' => 'SALAH',
                'password' => 'passwordsalah',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_super_admin_can_restore_from_backup_with_confirmation_string(): void
    {
        $filename = $this->backupService->createDatabaseBackup('test_real_restore.sql');

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

    public function test_super_admin_can_delete_backup_file(): void
    {
        $filename = $this->backupService->createDatabaseBackup('test_to_delete.sql');
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($fullPath));

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.backups.destroy', $filename));

        $response->assertSessionHas('success');
        $this->assertFalse(File::exists($fullPath));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'delete',
            'module' => 'Backup & Restore',
            'target_name' => $filename,
        ]);
    }

    public function test_super_admin_can_create_full_backup(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.store'), [
                'type' => 'full',
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $this->assertEquals('full', $backups[0]['type']);
        $this->assertStringEndsWith('.zip', $backups[0]['filename']);
    }

    public function test_super_admin_can_restore_from_backup_with_password(): void
    {
        $filename = $this->backupService->createDatabaseBackup('test_pwd_restore.sql');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.restore', $filename), [
                'password' => 'password123',
            ]);

        $response->assertSessionHas('success');
    }

    public function test_cli_artisan_backup_create_command(): void
    {
        $this->artisan('backup:create', [
            '--type' => 'database',
            '--filename' => 'test_cli_db.sql',
        ])->assertSuccessful();

        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . 'test_cli_db.sql';
        $this->assertTrue(File::exists($fullPath));
    }
}
