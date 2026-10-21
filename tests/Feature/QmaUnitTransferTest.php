<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\UnitTransfer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QmaUnitTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected Vehicle $heavyEquipment;
    protected Vehicle $transporterTruck;
    protected Employee $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan permission dan role QMA
        Permission::firstOrCreate(['name' => 'qma.vehicles.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.edit', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'qma.vehicles.delete', 'guard_name' => 'web']);

        $qmaRole = Role::firstOrCreate(['name' => 'qma_admin', 'guard_name' => 'web']);
        $qmaRole->givePermissionTo([
            'qma.vehicles.view',
            'qma.vehicles.create',
            'qma.vehicles.edit',
            'qma.vehicles.delete',
        ]);

        $this->qmaAdmin = User::firstOrCreate(
            ['username' => 'qma_admin_transfer_test'],
            [
                'name' => 'Admin QMA Transfer Test',
                'email' => 'qma_transfer_test@kmh.co.id',
                'role' => 'qma_admin',
                'password' => bcrypt('password'),
            ]
        );
        $this->qmaAdmin->assignRole('qma_admin');

        // Kategori Support Unit Induk
        $category = Vehicle::create([
            'code' => 'CAT-EXCA',
            'category' => 'unit_support',
            'brand_type' => 'Excavator Heavy',
            'status' => 'Operation',
        ]);

        // Unit Fisik Alat Berat yang akan dipindahkan
        $this->heavyEquipment = Vehicle::create([
            'code' => 'EXCA-01',
            'category' => 'unit_support',
            'parent_id' => $category->id,
            'brand_type' => 'Komatsu PC200-8',
            'serial_number' => 'KM-EXCA-2026-001',
            'status' => 'Operation',
            'site_status' => 'inside_site',
            'location' => 'Main Dam',
            'location_updated_at' => Carbon::now()->subDays(2),
        ]);

        // Armada Truk Pengangkut (Transporter)
        $this->transporterTruck = Vehicle::create([
            'code' => 'DT-05',
            'category' => 'unit_support',
            'brand_type' => 'Hino 500 Heavy Dump/Flatbed',
            'license_plate' => 'BH 8899 KM',
            'status' => 'Operation',
            'site_status' => 'inside_site',
            'location' => 'Workshop GA',
        ]);

        // Supir / Pengemudi Pengangkut
        $this->driver = Employee::create([
            'nik' => 'DRV-001',
            'name' => 'Slamet Riyadi',
            'unit_kerja' => 'KMH Operation',
            'division' => 'Heavy Equipment',
            'position' => 'Senior Heavy Driver',
            'is_operator' => true,
        ]);
    }

    /**
     * User belum login diarahkan ke login.
     */
    public function test_unauthenticated_user_cannot_access_unit_transfers(): void
    {
        $response = $this->get(route('qma.unit_transfers.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Admin QMA dapat membuka halaman riwayat transfer unit alat berat.
     */
    public function test_qma_admin_can_view_unit_transfers_index(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.index'));

        $response->assertOk();
        $response->assertViewIs('qma.unit_transfers_index');
        $response->assertSee('Transfer Unit Alat Berat');
        $response->assertSee('Buat Transfer Unit');
    }

    /**
     * Admin QMA dapat membuka form pembuatan pemindahan unit alat berat.
     */
    public function test_qma_admin_can_view_create_transfer_form(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.create'));

        $response->assertOk();
        $response->assertViewIs('qma.unit_transfers_create');
        $response->assertSee($this->heavyEquipment->code);
        $response->assertSee($this->transporterTruck->code);
        $response->assertSee($this->driver->name);
        $response->assertSee('Nomor Surat / Berita Acara');
    }

    /**
     * Proses pemindahan alat berat berhasil menyimpan transaksi, mengupdate posisi unit,
     * serta mencatat riwayat audit trail.
     */
    public function test_qma_admin_can_store_unit_transfer_and_updates_vehicle_location(): void
    {
        Storage::fake('public');
        $dummyPdf = UploadedFile::fake()->create('surat_bast.pdf', 500, 'application/pdf');

        $payload = [
            'vehicle_id' => $this->heavyEquipment->id,
            'transfer_date' => Carbon::now()->format('Y-m-d'),
            'to_location' => 'Adit 2 Intake',
            'to_site_status' => 'inside_site',
            'description' => 'Relokasi untuk pengerjaan galian terowongan adit 2.',
            'reported_by' => 'Budi Santoso QMA',
            'transporter_vehicle_id' => $this->transporterTruck->id,
            'transporter_unit_name' => 'DT-05 (Hino 500 Flatbed)',
            'transporter_driver_id' => $this->driver->id,
            'transporter_driver_name' => $this->driver->name,
            'transporter_description' => 'Diikat rantai lashing 4 titik, pengawalan mobil patroli.',
            'document_number' => '015/BAST-AB/QMA-KMH/X/2026',
            'document_title' => 'Berita Acara Relokasi Excavator EXCA-01 ke Adit 2',
            'document_file' => $dummyPdf,
        ];

        $response = $this->actingAs($this->qmaAdmin)->post(route('qma.unit_transfers.store'), $payload);

        $transfer = UnitTransfer::where('document_number', '015/BAST-AB/QMA-KMH/X/2026')->first();
        $this->assertNotNull($transfer);

        $response->assertRedirect(route('qma.unit_transfers.show', $transfer->id));
        $response->assertSessionHas('success');

        // Pastikan relasi dan data tersimpan dengan tepat
        $this->assertEquals($this->heavyEquipment->id, $transfer->vehicle_id);
        $this->assertEquals('Main Dam', $transfer->from_location);
        $this->assertEquals('Adit 2 Intake', $transfer->to_location);
        $this->assertEquals('inside_site', $transfer->to_site_status);
        $this->assertEquals($this->driver->name, $transfer->transporter_driver_name);
        $this->assertNotNull($transfer->document_file_path);
        Storage::disk('public')->assertExists($transfer->document_file_path);

        // Pastikan posisi master unit alat berat terupdate otomatis
        $freshVehicle = $this->heavyEquipment->fresh();
        $this->assertEquals('Adit 2 Intake', $freshVehicle->location);
        $this->assertEquals('inside_site', $freshVehicle->site_status);
        $this->assertEquals('Budi Santoso QMA', $freshVehicle->location_updated_by);

        // Pastikan riwayat audit trail (VehicleLocationHistory) tercatat
        $history = VehicleLocationHistory::where('vehicle_id', $this->heavyEquipment->id)
            ->where('source', 'transfer')
            ->first();

        $this->assertNotNull($history);
        $this->assertEquals('Adit 2 Intake', $history->location);
        $this->assertEquals('Transfer Unit (BAST)', $history->source_label);
        $this->assertStringContainsString('015/BAST-AB/QMA-KMH/X/2026', $history->note);
    }

    /**
     * Validasi gagal jika field wajib tidak diisi.
     */
    public function test_validation_errors_when_required_fields_missing(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->post(route('qma.unit_transfers.store'), []);

        $response->assertSessionHasErrors([
            'vehicle_id',
            'transfer_date',
            'to_location',
            'to_site_status',
            'reported_by',
            'document_number',
            'document_title',
        ]);
    }

    /**
     * Admin QMA dapat melihat halaman detail Berita Acara Serah Terima (BAST).
     */
    public function test_qma_admin_can_view_transfer_detail_bast(): void
    {
        $transfer = UnitTransfer::create([
            'transfer_no' => 'TRF-20261021-0001',
            'vehicle_id' => $this->heavyEquipment->id,
            'transfer_date' => Carbon::now()->format('Y-m-d'),
            'from_location' => 'Main Dam',
            'from_site_status' => 'inside_site',
            'to_location' => 'Adit 1',
            'to_site_status' => 'inside_site',
            'description' => 'Galian tunnel',
            'reported_by' => 'Admin QMA',
            'document_number' => '001/BAST-TEST/2026',
            'document_title' => 'BAST Pemindahan Excavator',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.show', $transfer->id));

        $response->assertOk();
        $response->assertViewIs('qma.unit_transfers_show');
        $response->assertSee('001/BAST-TEST/2026');
        $response->assertSee('Adit 1');
        $response->assertSee('Cetak BAST');
    }

    /**
     * Admin QMA dapat mengekspor data transfer ke Excel dan PDF.
     */
    public function test_qma_admin_can_export_unit_transfers_excel_and_pdf(): void
    {
        UnitTransfer::create([
            'transfer_no' => 'TRF-20261021-0002',
            'vehicle_id' => $this->heavyEquipment->id,
            'transfer_date' => Carbon::now()->format('Y-m-d'),
            'from_location' => 'Main Dam',
            'to_location' => 'Powerhouse',
            'to_site_status' => 'inside_site',
            'reported_by' => 'Admin QMA',
            'document_number' => '002/BAST-TEST/2026',
            'document_title' => 'BAST Pemindahan Powerhouse',
            'status' => 'completed',
        ]);

        // Ekspor Excel
        $excelResponse = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.export_excel'));
        $excelResponse->assertOk();
        $excelResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Ekspor PDF
        $pdfResponse = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.export_pdf'));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Admin QMA dapat menghapus data transfer unit.
     */
    public function test_qma_admin_can_delete_unit_transfer(): void
    {
        $transfer = UnitTransfer::create([
            'transfer_no' => 'TRF-20261021-0003',
            'vehicle_id' => $this->heavyEquipment->id,
            'transfer_date' => Carbon::now()->format('Y-m-d'),
            'from_location' => 'Main Dam',
            'to_location' => 'Workshop GA',
            'to_site_status' => 'inside_site',
            'reported_by' => 'Admin QMA',
            'document_number' => '003/BAST-DELETE/2026',
            'document_title' => 'BAST Hapus',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->qmaAdmin)->delete(route('qma.unit_transfers.destroy', $transfer->id));

        $response->assertRedirect(route('qma.unit_transfers.index'));
        $this->assertDatabaseMissing('unit_transfers', ['id' => $transfer->id]);
    }

    /**
     * Form pembuatan transfer unit otomatis memilih alat berat jika parameter vehicle_id disertakan.
     */
    public function test_create_transfer_form_preselects_heavy_unit_when_vehicle_id_provided(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.unit_transfers.create', ['vehicle_id' => $this->heavyEquipment->id]));

        $response->assertOk();
        $response->assertSee('value="' . $this->heavyEquipment->id . '"', false);
        $response->assertSee('selected', false);
    }

    /**
     * Jika opsi update_transporter_location dicentang, armada pengangkut juga diperbarui posisinya
     * dan dicatat di riwayat mutasi lokasi.
     */
    public function test_store_transfer_updates_transporter_truck_location_when_checked(): void
    {
        Storage::fake('public');

        $payload = [
            'vehicle_id' => $this->heavyEquipment->id,
            'from_location' => 'Main Dam',
            'transfer_date' => Carbon::now()->format('Y-m-d'),
            'to_location' => 'Adit 2 Intake',
            'to_site_status' => 'inside_site',
            'reported_by' => 'Staff QMA Logistik',
            'transporter_vehicle_id' => $this->transporterTruck->id,
            'transporter_unit_name' => 'DT-05',
            'update_transporter_location' => '1',
            'document_number' => '020/BAST-AB/QMA-KMH/X/2026',
            'document_title' => 'BAST Pemindahan Unit & Mobilisasi Truk',
        ];

        $response = $this->actingAs($this->qmaAdmin)->post(route('qma.unit_transfers.store'), $payload);
        $response->assertSessionHas('success');

        // Posisi truk pengangkut harus terupdate ke Adit 2 Intake
        $freshTransporter = $this->transporterTruck->fresh();
        $this->assertEquals('Adit 2 Intake', $freshTransporter->location);

        // Riwayat lokasi truk pengangkut harus tercatat
        $transporterHistory = VehicleLocationHistory::where('vehicle_id', $this->transporterTruck->id)
            ->where('source', 'transfer')
            ->first();

        $this->assertNotNull($transporterHistory);
        $this->assertEquals('Adit 2 Intake', $transporterHistory->location);
        $this->assertStringContainsString('Mengantar EXCA-01', $transporterHistory->note);
    }
}
