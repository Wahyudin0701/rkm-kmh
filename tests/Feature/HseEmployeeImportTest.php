<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\SimpleXlsxExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HseEmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private User $hseAdmin;
    private User $regularOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions
        Permission::firstOrCreate(['name' => 'hse.employees.view']);
        Permission::firstOrCreate(['name' => 'hse.employees.create']);
        Permission::firstOrCreate(['name' => 'hse.employees.edit']);

        $hseRole = Role::firstOrCreate(['name' => 'hse_admin']);
        $hseRole->syncPermissions(['hse.employees.view', 'hse.employees.create', 'hse.employees.edit']);

        $gateRole = Role::firstOrCreate(['name' => 'gate_officer']);
        $gateRole->syncPermissions(['hse.employees.view']); // hanya bisa lihat, tidak bisa buat

        Role::firstOrCreate(['name' => 'employee']);

        $this->hseAdmin = User::factory()->create([
            'username' => 'hseadmin_test',
            'role' => 'hse_admin',
        ]);
        $this->hseAdmin->assignRole('hse_admin');

        $this->regularOfficer = User::factory()->create([
            'username' => 'officer_test',
            'role' => 'gate_officer',
        ]);
        $this->regularOfficer->assignRole('gate_officer');
    }

    public function test_authorized_user_can_download_import_template(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->get(route('hse.employees.import_template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }

    public function test_unauthorized_user_cannot_download_import_template(): void
    {
        $response = $this->actingAs($this->regularOfficer)
            ->get(route('hse.employees.import_template'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_import_employees_from_csv(): void
    {
        $csvContent = implode("\n", [
            'No,NIK,Nama Lengkap,Unit Kerja,Divisi,Jabatan,Operator Driver,Masa Berlaku SIMPER',
            '1,KMH-IMP-001,Budi Kurniawan,Pembangkit,Operasi,Operator Turbin,YA,2026-12-31',
            '2,KMH-IMP-002,Sari Agustin,Umum,Keuangan,Staff Akuntansi,TIDAK,',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->actingAs($this->hseAdmin)
            ->post(route('hse.employees.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-IMP-001',
            'name' => 'Budi Kurniawan',
            'unit_kerja' => 'Pembangkit',
            'is_operator' => 1,
            'simper_expired_at' => '2026-12-31 00:00:00',
        ]);

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-IMP-002',
            'name' => 'Sari Agustin',
            'unit_kerja' => 'Umum',
            'is_operator' => 0,
            'simper_expired_at' => null,
        ]);

        // Pastikan akun login User otomatis dibuat
        $this->assertDatabaseHas('users', [
            'username' => 'KMH-IMP-001',
            'name' => 'Budi Kurniawan',
        ]);
        $importedUser = User::where('username', 'KMH-IMP-001')->first();
        $this->assertNotNull($importedUser);
        $this->assertTrue(Hash::check('123456', $importedUser->password));
        $this->assertTrue($importedUser->hasRole('employee'));

        $this->assertDatabaseHas('users', [
            'username' => 'KMH-IMP-002',
            'name' => 'Sari Agustin',
        ]);
    }

    public function test_authorized_user_can_import_employees_from_xlsx(): void
    {
        $metaLines = ['TEMPLATE'];
        $headers = [
            'No' => 6,
            'NIK' => 18,
            'Nama Lengkap' => 28,
            'Unit Kerja' => 20,
            'Divisi' => 20,
            'Jabatan' => 24,
            'Operator Driver' => 18,
            'Masa Berlaku SIMPER' => 22,
        ];
        $rows = [
            [1, 'KMH-XLSX-101', 'Farhan Nugraha', 'Bendungan', 'Civil', 'Driver Heavy', 'YA', '2027-05-20'],
            [2, 'KMH-XLSX-102', 'Lina Marlina', 'Teknik', 'Listrik', 'Engineer', 'TIDAK', ''],
        ];

        $xlsxBinary = SimpleXlsxExporter::create('Test Import', $metaLines, $headers, $rows);
        $file = UploadedFile::fake()->createWithContent('employees.xlsx', $xlsxBinary);

        $response = $this->actingAs($this->hseAdmin)
            ->post(route('hse.employees.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-XLSX-101',
            'name' => 'Farhan Nugraha',
            'is_operator' => 1,
            'simper_expired_at' => '2027-05-20 00:00:00',
        ]);

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-XLSX-102',
            'name' => 'Lina Marlina',
            'is_operator' => 0,
        ]);
    }

    public function test_import_updates_existing_employee_when_nik_matches(): void
    {
        $existing = Employee::create([
            'nik' => 'KMH-UPD-999',
            'name' => 'Nama Lama',
            'unit_kerja' => 'Umum',
            'division' => 'GA',
            'position' => 'Staff',
            'is_operator' => false,
            'simper_expired_at' => null,
        ]);

        $csvContent = implode("\n", [
            'No,NIK,Nama Lengkap,Unit Kerja,Divisi,Jabatan,Operator Driver,Masa Berlaku SIMPER',
            '1,KMH-UPD-999,Nama Baru Terupdate,Pembangkit,Mekanik,Lead Driver,YA,2026-11-15',
        ]);

        $file = UploadedFile::fake()->createWithContent('update_employees.csv', $csvContent);

        $response = $this->actingAs($this->hseAdmin)
            ->post(route('hse.employees.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan tidak ada data ganda (tetap 1 record untuk NIK tersebut)
        $this->assertEquals(1, Employee::where('nik', 'KMH-UPD-999')->count());

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-UPD-999',
            'name' => 'Nama Baru Terupdate',
            'unit_kerja' => 'Pembangkit',
            'is_operator' => 1,
            'simper_expired_at' => '2026-11-15 00:00:00',
        ]);
    }

    public function test_unauthorized_user_cannot_import_employees(): void
    {
        $csvContent = "No,NIK,Nama Lengkap,Unit Kerja,Divisi,Jabatan,Operator Driver,Masa Berlaku SIMPER\n1,NIK-HACK,Hacker,Umum,IT,Staff,TIDAK,";
        $file = UploadedFile::fake()->createWithContent('hack.csv', $csvContent);

        $response = $this->actingAs($this->regularOfficer)
            ->post(route('hse.employees.import'), [
                'file' => $file,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('employees', ['nik' => 'NIK-HACK']);
    }

    public function test_authorized_user_can_create_employee_manually(): void
    {
        $response = $this->actingAs($this->hseAdmin)
            ->post(route('hse.employees.store'), [
                'nik' => 'KMH-MANUAL-001',
                'name' => 'Dimas Anggara',
                'unit_kerja' => 'Pembangkit',
                'division' => 'Operasi',
                'position' => 'Driver LV',
                'is_operator' => '1',
                'simper_expired_at' => '2027-01-15',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-MANUAL-001',
            'name' => 'Dimas Anggara',
            'is_operator' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'KMH-MANUAL-001',
            'name' => 'Dimas Anggara',
        ]);
        $manualUser = User::where('username', 'KMH-MANUAL-001')->first();
        $this->assertNotNull($manualUser);
        $this->assertTrue(Hash::check('123456', $manualUser->password));
        $this->assertTrue($manualUser->hasRole('employee'));
    }

    public function test_authorized_user_can_import_employees_with_only_basic_columns(): void
    {
        $csvContent = implode("\n", [
            'No,NIK,Nama Lengkap,Unit Kerja,Divisi,Jabatan',
            '1,KMH-2026-001,Ahmad Rizky Pratama,Pembangkit,Operasi,Operator Turbin',
            '2,KMH-2026-002,Dewi Lestari,Umum,Keuangan,Staff Finance',
            '3,KMH-2026-003,Hendra Saputra,Bendungan,Civil Maintenance,Driver Operasional',
        ]);

        $file = UploadedFile::fake()->createWithContent('karyawan_basic.csv', $csvContent);

        $response = $this->actingAs($this->hseAdmin)
            ->post(route('hse.employees.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-2026-001',
            'name' => 'Ahmad Rizky Pratama',
            'unit_kerja' => 'Pembangkit',
            'division' => 'Operasi',
            'position' => 'Operator Turbin',
            'is_operator' => 0,
            'simper_expired_at' => null,
        ]);

        $this->assertDatabaseHas('employees', [
            'nik' => 'KMH-2026-003',
            'name' => 'Hendra Saputra',
            'unit_kerja' => 'Bendungan',
            'division' => 'Civil Maintenance',
            'position' => 'Driver Operasional',
            'is_operator' => 0,
            'simper_expired_at' => null,
        ]);
    }
}
