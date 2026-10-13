<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityGuardFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminPg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminPg = User::where('role', 'pg_admin')->first();
    }

    /**
     * Memastikan halaman data satpam berhasil diakses dengan elemen filter & search.
     */
    public function test_admin_pg_can_view_guards_page_with_filters(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Satpam');
        $response->assertSee('Cari ID Satpam atau nama petugas...');
        $response->assertSee('Semua Regu');
        $response->assertSee('Semua Penempatan');
        $response->assertSee('Ahmad Fauzi');
    }

    /**
     * Memastikan filter regu menyaring satpam sesuai regu yang dipilih.
     */
    public function test_guards_filtered_by_team(): void
    {
        // Regu 1: Ahmad Fauzi (SEC-001), Rusli Efendi (SEC-004)
        // Regu 2: Budi Santoso (SEC-002), Slamet Widodo (SEC-005)
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index', ['team' => 1]));

        $response->assertStatus(200);
        $guards = $response->viewData('guards');
        $this->assertTrue($guards->contains('name', 'Ahmad Fauzi'));
        $this->assertTrue($guards->contains('name', 'Rusli Efendi'));
        $this->assertFalse($guards->contains('name', 'Budi Santoso'));
        $this->assertFalse($guards->contains('name', 'Slamet Widodo'));

        $response->assertSee('SEC-001');
        $response->assertSee('SEC-004');
        $response->assertDontSee('SEC-002');
        $response->assertDontSee('SEC-005');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan filter penempatan pos menyaring satpam sesuai gerbang yang dipilih.
     */
    public function test_guards_filtered_by_gate(): void
    {
        $gateBeringin = Gate::where('code', 'BTUNGGAL')->first();

        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index', ['gate_id' => $gateBeringin->id]));

        $response->assertStatus(200);
        $guards = $response->viewData('guards');
        $this->assertTrue($guards->contains('name', 'Ahmad Fauzi'));
        $this->assertFalse($guards->contains('name', 'Budi Santoso'));

        $response->assertSee('SEC-001');
        $response->assertDontSee('SEC-002');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan pencarian kata kunci menyaring satpam sesuai input.
     */
    public function test_guards_filtered_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index', ['search' => 'Fauzi']));

        $response->assertStatus(200);
        $guards = $response->viewData('guards');
        $this->assertTrue($guards->contains('name', 'Ahmad Fauzi'));
        $this->assertFalse($guards->contains('name', 'Budi Santoso'));

        $response->assertSee('SEC-001');
        $response->assertDontSee('SEC-002');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan pencarian kata kunci yang tidak ditemukan menampilkan baris keterangan dan tombol reset.
     */
    public function test_empty_search_shows_not_found_message_and_reset_button(): void
    {
        $keyword = 'sgbsfgh';
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index', ['search' => $keyword]));

        $response->assertStatus(200);
        $response->assertSeeText('Data "' . $keyword . '" tidak ditemukan di tabel', false);
        $response->assertSeeText('Pastikan ejaan ID Satpam atau nama petugas yang dicari sudah benar.');
        $response->assertSee('Reset Filter');
    }

    /**
     * Memastikan modal konfirmasi toggle status dan pemanggilan JS tersedia pada halaman.
     */
    public function test_guards_page_renders_toggle_status_confirmation_modal(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index'));

        $response->assertStatus(200);
        $response->assertSee('toggleStatusModal');
        $response->assertSee('Konfirmasi Status Petugas');
        $response->assertSee('openToggleStatusModal');
    }

    /**
     * Memastikan admin PG dapat mengubah status aktif/nonaktif petugas.
     */
    public function test_admin_pg_can_toggle_guard_status(): void
    {
        $guard = SecurityGuard::where('badge_id', 'SEC-001')->first();
        $this->assertTrue($guard->is_active);

        // 1. Nonaktifkan petugas
        $response = $this->actingAs($this->adminPg)->post(route('pg.guards.toggle', $guard->id));
        $response->assertSessionHas('success');
        $this->assertFalse($guard->fresh()->is_active);

        // 2. Aktifkan kembali petugas
        $response = $this->actingAs($this->adminPg)->post(route('pg.guards.toggle', $guard->id));
        $response->assertSessionHas('success');
        $this->assertTrue($guard->fresh()->is_active);
    }

    /**
     * Memastikan modal konfirmasi hapus petugas satpam tersedia pada halaman.
     */
    public function test_guards_page_renders_delete_guard_modal(): void
    {
        $response = $this->actingAs($this->adminPg)->get(route('pg.guards.index'));

        $response->assertStatus(200);
        $response->assertSee('deleteGuardModal');
        $response->assertSee('Konfirmasi Hapus Petugas Satpam');
        $response->assertSee('openDeleteGuardModal');
    }

    /**
     * Memastikan admin PG dapat menghapus petugas Satpam beserta akun login terkait.
     */
    public function test_admin_pg_can_delete_guard_and_associated_user(): void
    {
        $guard = SecurityGuard::with('user')->where('badge_id', 'SEC-005')->first();
        $this->assertNotNull($guard);
        $guardId = $guard->id;
        $userId = $guard->user_id;

        $response = $this->actingAs($this->adminPg)->delete(route('pg.guards.destroy', $guardId));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('security_guards', ['id' => $guardId]);
        if ($userId) {
            $this->assertDatabaseMissing('users', ['id' => $userId]);
        }
    }
}
