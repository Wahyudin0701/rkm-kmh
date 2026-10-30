<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackupConverterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private DatabaseBackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_conv_test',
            'role' => 'super_admin',
            'password' => bcrypt('password123'),
        ]);
        $this->superAdmin->assignRole($role);

        $this->backupService = new DatabaseBackupService();
    }

    protected function tearDown(): void
    {
        $dir = $this->backupService->getBackupDirectory();
        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $f) {
                if (str_starts_with($f->getFilename(), 'test_conv_')) {
                    @unlink($f->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    protected function createSampleKmhFile(string $name = 'test_conv_sample.kmh'): UploadedFile
    {
        $filename = $this->backupService->createKmhBackup(['users', 'roles'], false, $name);
        $fullPath = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $content = file_get_contents($fullPath);
        @unlink($fullPath);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_super_admin_can_inspect_uploaded_kmh_backup(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_inspect.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.backups.converter.inspect'), [
                'backup_file' => $uploadedFile,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'filename' => 'test_conv_inspect.kmh',
            ],
        ]);

        $this->assertContains('users', $response->json('data.tables'));
        $this->assertContains('roles', $response->json('data.tables'));
    }

    public function test_inspect_rejects_non_kmh_file(): void
    {
        $fakeFile = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.backups.converter.inspect'), [
                'backup_file' => $fakeFile,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Berkas yang dipilih harus berekstensi .kmh.',
        ]);
    }

    public function test_inspect_rejects_corrupted_kmh_content(): void
    {
        $corruptFile = UploadedFile::fake()->createWithContent('corrupted.kmh', "KMH_ENCRYPTED_BACKUP_V1\nCorruptedHMACandPayload12345");

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.backups.converter.inspect'), [
                'backup_file' => $corruptFile,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_super_admin_can_convert_single_table_to_xlsx(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_single.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users'],
                'format' => 'xlsx',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Tabel_users_', $disposition);
        $this->assertStringEndsWith('.xlsx', $disposition);
    }

    public function test_super_admin_can_convert_multiple_tables_to_xlsx_zip(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_multi_xlsx.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users', 'roles'],
                'format' => 'xlsx',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Arsip_KMH_Excel_', $disposition);
        $this->assertStringEndsWith('.zip', $disposition);
    }

    public function test_super_admin_can_convert_tables_to_csv_zip(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_csv.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users', 'roles'],
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Arsip_KMH_CSV_', $disposition);
        $this->assertStringEndsWith('.zip', $disposition);
    }

    public function test_super_admin_can_convert_tables_to_sql(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_sql.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users'],
                'format' => 'sql',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/sql');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Arsip_KMH_SQL_', $disposition);
        $this->assertStringEndsWith('.sql', $disposition);
    }

    public function test_convert_validates_required_fields(): void
    {
        $uploadedFile = $this->createSampleKmhFile('test_conv_val.kmh');

        // Tanpa tabel
        $response1 = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => [],
                'format' => 'xlsx',
            ]);
        $response1->assertStatus(422);

        // Format tidak valid
        $response2 = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users'],
                'format' => 'invalid_format',
            ]);
        $response2->assertStatus(422);
    }

    public function test_active_database_remains_unaffected_after_conversion(): void
    {
        $initialUserCount = User::count();
        $this->assertGreaterThan(0, $initialUserCount);

        $uploadedFile = $this->createSampleKmhFile('test_conv_safety.kmh');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.backups.converter.execute'), [
                'backup_file' => $uploadedFile,
                'tables' => ['users'],
                'format' => 'xlsx',
            ]);

        $response->assertStatus(200);

        // Pastikan jumlah user di database aktif tetap sama persis
        $this->assertEquals($initialUserCount, User::count());
    }
}
