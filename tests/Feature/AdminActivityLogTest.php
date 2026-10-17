<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_view_activity_logs_page(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Create a dummy log
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Manajemen Pengguna',
            description: 'Menambahkan user baru: Test User',
            targetType: 'User',
            targetId: 999,
            targetName: 'Test User'
        );

        $response = $this->actingAs($superAdmin)->get(route('admin.activity_logs.index'));
        $response->assertOk();
        $response->assertSee('Log Riwayat Aktivitas Admin');
        $response->assertSee('Menambahkan user baru: Test User');
        $response->assertSee('Test User');
    }

    public function test_non_super_admin_cannot_access_activity_logs(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'hse_admin',
        ]);

        $response = $this->actingAs($regularUser)->get(route('admin.activity_logs.index'));
        $response->assertForbidden();
    }

    public function test_user_creation_is_logged(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Operator Test Log',
            'username' => 'operator.log',
            'email' => 'operator.log@kmh.co.id',
            'password' => 'password123',
            'role' => 'gate_officer',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'create',
            'module' => 'Manajemen Pengguna',
            'target_type' => 'User',
            'target_name' => 'Operator Test Log',
            'user_username' => 'superadmin',
        ]);

        $log = AdminActivityLog::where('target_name', 'Operator Test Log')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Operator Test Log', $log->description);
        $this->assertNotNull($log->new_values);
        $this->assertEquals('gate_officer', $log->new_values['role']);
    }

    public function test_user_update_is_logged_with_diff(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $user = User::factory()->create([
            'name' => 'Original Name',
            'username' => 'update.target',
            'email' => 'target@kmh.co.id',
            'role' => 'gate_officer',
        ]);
        $user->assignRole('gate_officer');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.update', $user->id), [
            'name' => 'Updated Name',
            'username' => 'update.target',
            'email' => 'target@kmh.co.id',
            'role' => 'hse_admin',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'update',
            'module' => 'Manajemen Pengguna',
            'target_type' => 'User',
            'target_name' => 'Updated Name',
        ]);

        $log = AdminActivityLog::where('target_name', 'Updated Name')->first();
        $this->assertNotNull($log);
        $this->assertEquals('Original Name', $log->old_values['name']);
        $this->assertEquals('Updated Name', $log->new_values['name']);
        $this->assertEquals('gate_officer', $log->old_values['role']);
        $this->assertEquals('hse_admin', $log->new_values['role']);
    }

    public function test_user_deletion_is_logged(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $user = User::factory()->create([
            'name' => 'Delete Me',
            'username' => 'delete.me',
            'email' => 'deleteme@kmh.co.id',
            'role' => 'gate_officer',
        ]);
        $user->assignRole('gate_officer');

        $response = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $user->id));
        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'delete',
            'module' => 'Manajemen Pengguna',
            'target_type' => 'User',
            'target_name' => 'Delete Me',
        ]);
    }

    public function test_role_creation_and_permission_update_are_logged(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // 1. Create Role
        $response = $this->actingAs($superAdmin)->post(route('admin.roles.store'), [
            'name' => 'auditor_custom',
        ]);
        $response->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'create',
            'module' => 'Role & Hak Akses',
            'target_name' => 'auditor_custom',
        ]);

        $createdRole = Role::findByName('auditor_custom');

        // 2. Update Permissions
        $response = $this->actingAs($superAdmin)->post(route('admin.roles.permissions.update', $createdRole->id), [
            'permissions' => ['hse.employees.view'],
        ]);
        $response->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'action_type' => 'permission',
            'module' => 'Role & Hak Akses',
            'target_name' => 'auditor_custom',
        ]);
    }

    public function test_activity_logs_filtering_works(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        // Create 2 different logs
        AdminActivityLog::record(
            actionType: 'create',
            module: 'Manajemen Pengguna',
            description: 'Membuat user alpha',
            targetType: 'User',
            targetName: 'user.alpha'
        );

        AdminActivityLog::record(
            actionType: 'delete',
            module: 'Manajemen Pengguna',
            description: 'Menghapus user beta',
            targetType: 'User',
            targetName: 'user.beta'
        );

        // Filter by action_type = delete
        $response = $this->actingAs($superAdmin)->get(route('admin.activity_logs.index', ['action_type' => 'delete']));
        $response->assertOk();
        $response->assertSee('Menghapus user beta');
        $response->assertDontSee('Membuat user alpha');

        // Filter by search = alpha
        $responseSearch = $this->actingAs($superAdmin)->get(route('admin.activity_logs.index', ['search' => 'alpha']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Membuat user alpha');
        $responseSearch->assertDontSee('Menghapus user beta');
    }

    public function test_dashboard_displays_recent_activity_widget(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        AdminActivityLog::record(
            actionType: 'create',
            module: 'Manajemen Pengguna',
            description: 'Aktivitas Khusus Dashboard Test',
            targetType: 'User',
            targetName: 'dash.test'
        );

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Aktivitas Administratif Terkini');
        $response->assertSee('Aktivitas Khusus Dashboard Test');
    }
}
