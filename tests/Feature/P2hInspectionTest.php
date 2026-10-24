<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\P2hRecord;
use App\Models\P2hTemplate;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class P2hInspectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $qmaAdmin;
    protected User $operatorUser;
    protected Employee $operatorEmp;
    protected Vehicle $supportVehicle;
    protected P2hTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Akun Admin QMA
        $this->qmaAdmin = User::firstOrCreate(
            ['username' => '1171'],
            [
                'name' => 'Pinto Yuliandra',
                'email' => '1171@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'qma_admin',
            ]
        );

        // 2. Akun Operator Karyawan dengan SIMPER Aktif
        $this->operatorUser = User::firstOrCreate(
            ['username' => 'OPR-001'],
            [
                'name' => 'Bambang Operator',
                'email' => 'opr001@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]
        );

        $this->operatorEmp = Employee::firstOrCreate(
            ['nik' => 'OPR-001'],
            [
                'user_id' => $this->operatorUser->id,
                'name' => 'Bambang Operator',
                'unit_kerja' => 'Pembangkit',
                'division' => 'Operasional',
                'position' => 'Operator Excavator',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->addMonths(6),
            ]
        );

        // 3. Template & Unit Support
        $this->template = P2hTemplate::firstOrCreate(
            ['code' => 'EXD4RP'],
            [
                'name' => 'Excavator',
                'file_name' => 'P2H_Excavator_EXD4RP.md',
                'is_active' => true,
            ]
        );

        $this->supportVehicle = Vehicle::firstOrCreate(
            ['code' => 'EX99'],
            [
                'category' => 'unit_support',
                'p2h_template_id' => $this->template->id,
                'brand_type' => 'KOMATSU PC200 TEST',
                'unit_category' => 'Excavator',
                'status' => 'Operation',
                'current_km' => 1500.0,
            ]
        );
    }

    public function test_guest_cannot_access_p2h_catalog(): void
    {
        $response = $this->get(route('p2h.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_operator_can_view_p2h_catalog(): void
    {
        $response = $this->actingAs($this->operatorUser)->get(route('p2h.index'));
        $response->assertStatus(200);
        $response->assertSee('Katalog Pemeriksaan Pra-Harian (P2H)');
        $response->assertSee($this->supportVehicle->code);
    }

    public function test_all_28_p2h_categories_are_seeded_and_displayed(): void
    {
        $this->seed(\Database\Seeders\P2hTemplateSeeder::class);
        $this->assertEquals(28, P2hTemplate::count());
        $this->assertGreaterThanOrEqual(28, Vehicle::where('category', 'unit_support')->count());

        $response = $this->actingAs($this->operatorUser)->get(route('p2h.index'));
        $response->assertStatus(200);
        $response->assertSee('Boomlift');
        $response->assertSee('Excavator');
        $response->assertSee('Dump Truck');
        $response->assertSee('Wheel Loader');
        $response->assertSee('Water Truck');
        $response->assertSee('Vibro');
    }

    public function test_operator_can_open_p2h_form(): void
    {
        $response = $this->actingAs($this->operatorUser)->get(route('p2h.form', ['code' => $this->supportVehicle->code]));
        $response->assertStatus(200);
        $response->assertSee('Pemeriksaan Pra-Harian (P2H) Heavy Equipment');
        $response->assertSee($this->supportVehicle->code);
        $response->assertSee('Bambang Operator');
        $response->assertSee('SIMPER Aktif');
        $response->assertDontSee('<aside', false);
        $response->assertDontSee('userProfileDropdownBtn');
        $response->assertDontSee('openDrawerBtn');

        // Pastikan saat admin (seperti admin QMA) membuka form, sidebar dan topbar juga disembunyikan
        $admin = User::where('role', 'qma_admin')->first();
        $resAdmin = $this->actingAs($admin)->get(route('p2h.form', ['code' => $this->supportVehicle->code]));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Admin QMA');
        $resAdmin->assertDontSee('<aside', false);
        $resAdmin->assertDontSee('userProfileDropdownBtn');
        $resAdmin->assertDontSee('openDrawerBtn');
    }

    public function test_qma_admin_can_submit_p2h_directly(): void
    {
        $admin = User::where('role', 'qma_admin')->first();
        $item1 = $this->template->items()->create([
            'section' => 'Sebelum Mesin Hidup',
            'title' => 'Cek Oli Engine Admin Test',
            'question_type' => 'choice',
            'sequence' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('p2h.store', ['code' => $this->supportVehicle->code]), [
            'template_id' => $this->template->id,
            'start_hm' => 1500,
            'items' => [
                $item1->id => [
                    'condition' => 'baik',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_operator_can_submit_p2h_with_pending_status_for_qma(): void
    {
        // Pastikan ada beberapa item template
        $item1 = $this->template->items()->create([
            'section' => 'Sebelum Mesin Hidup',
            'title' => 'Periksa Level & Kondisi Oli Engine',
            'question_type' => 'choice',
            'sequence' => 1,
        ]);

        $item2 = $this->template->items()->create([
            'section' => 'Sebelum Mesin Hidup',
            'title' => 'Periksa Level Air Radiator',
            'question_type' => 'choice',
            'sequence' => 2,
        ]);

        $payload = [
            'template_id' => $this->template->id,
            'shift' => 'Shift 1 (Siang)',
            'start_hm' => 1520.5,
            'general_notes' => 'Unit bersih siap bekerja di disposal.',
            'items' => [
                $item1->id => [
                    'condition' => 'baik',
                ],
                $item2->id => [
                    'condition' => 'rusak',
                    'notes' => 'Air radiator sedikit bocor di sambungan klem',
                ],
            ],
        ];

        $response = $this->actingAs($this->operatorUser)->post(
            route('p2h.store', ['code' => $this->supportVehicle->code]),
            $payload
        );

        $response->assertRedirect(route('p2h.form', ['code' => $this->supportVehicle->code]));
        $response->assertSessionHas('success');

        // Cek record P2H tersimpan dengan status 'pending'
        $this->assertDatabaseHas('p2h_records', [
            'vehicle_id' => $this->supportVehicle->id,
            'employee_id' => $this->operatorEmp->id,
            'shift' => 'Shift 1 (Siang)',
            'start_hm' => 1520.5,
            'summary_condition' => 'has_defect',
            'defect_count' => 1,
            'validation_status' => 'pending',
        ]);

        // Cek HM kendaraan ter-update
        $this->supportVehicle->refresh();
        $this->assertEquals(1520.5, (float)$this->supportVehicle->current_km);
    }

    public function test_qma_admin_can_view_and_approve_p2h_record(): void
    {
        $record = P2hRecord::create([
            'transaction_no' => 'P2H-TEST-001',
            'vehicle_id' => $this->supportVehicle->id,
            'employee_id' => $this->operatorEmp->id,
            'template_id' => $this->template->id,
            'shift' => 'Shift 1 (Siang)',
            'start_hm' => 1530.0,
            'summary_condition' => 'good',
            'defect_count' => 0,
            'validation_status' => 'pending',
            'inspected_at' => Carbon::now(),
        ]);

        // QMA melihat index
        $resIndex = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('P2H-TEST-001');

        // QMA melihat detail
        $resShow = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.show', $record->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('P2H-TEST-001');

        // QMA menyetujui (Approve)
        $resApprove = $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.approve', $record->id),
            ['validation_notes' => 'P2H disetujui, unit laik operasi.']
        );

        $resApprove->assertSessionHas('success');
        $record->refresh();
        $this->assertEquals('approved', $record->validation_status);
        $this->assertEquals($this->qmaAdmin->id, $record->validated_by);
    }

    public function test_qma_admin_can_reject_p2h_record(): void
    {
        $record = P2hRecord::create([
            'transaction_no' => 'P2H-TEST-002',
            'vehicle_id' => $this->supportVehicle->id,
            'employee_id' => $this->operatorEmp->id,
            'template_id' => $this->template->id,
            'shift' => 'Shift 2 (Malam)',
            'start_hm' => 1540.0,
            'summary_condition' => 'has_defect',
            'defect_count' => 2,
            'validation_status' => 'pending',
            'inspected_at' => Carbon::now(),
        ]);

        // QMA menolak (Reject)
        $resReject = $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.reject', $record->id),
            ['validation_notes' => 'Unit dilarang beroperasi sebelum rem hidrolik diperbaiki mekanik.']
        );

        $resReject->assertSessionHas('success');
        $record->refresh();
        $this->assertEquals('rejected', $record->validation_status);
        $this->assertEquals($this->qmaAdmin->id, $record->validated_by);

        // Status unit otomatis menjadi Breakdown
        $this->supportVehicle->refresh();
        $this->assertEquals('Breakdown', $this->supportVehicle->status);
    }

    public function test_qma_admin_can_view_p2h_templates(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates'));
        $response->assertStatus(200);
        $response->assertSee('Master Template P2H Heavy Equipment');
        $response->assertSee('Excavator');
        $response->assertSee('EXD4RP');
    }

    public function test_adding_unit_support_category_automatically_creates_empty_p2h_template(): void
    {
        // 1. Admin QMA menambah kategori aset Unit Support baru
        $storeResponse = $this->actingAs($this->qmaAdmin)->post('/qma/vehicles', [
            'code' => 'TH01',
            'category' => 'unit_support',
            'brand_type' => 'Manitou MT-X 733',
            'unit_category' => 'Telehandler',
            'status' => 'Operation',
            'current_km' => 0,
        ]);

        $storeResponse->assertSessionHas('success');

        // 2. Pastikan Kategori Aset terbuat dan terhubung ke template
        $category = Vehicle::where('code', 'TH01')->first();
        $this->assertNotNull($category);
        $this->assertNotNull($category->p2h_template_id);

        // 3. Pastikan Master Template P2H otomatis terbuat dengan default kosong (0 butir pertanyaan)
        $template = P2hTemplate::where('code', 'TH01')->first();
        $this->assertNotNull($template);
        $this->assertEquals('Telehandler', $template->name);
        $this->assertEquals(0, $template->items()->count());
        $this->assertEquals($category->p2h_template_id, $template->id);

        // 4. Halaman Master Template P2H menampilkan card template baru tersebut
        $listResponse = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('TH01');
        $listResponse->assertSee('Telehandler');
        $listResponse->assertSee('Template Baru (Kosong)');
        $listResponse->assertSee('Kelola & Isi Pertanyaan');

        // 5. Update kategori menyinkronkan kode template P2H
        $updateResponse = $this->actingAs($this->qmaAdmin)->post('/qma/vehicles/' . $category->id, [
            'code' => 'TH99',
            'category' => 'unit_support',
            'brand_type' => 'Manitou MT-X 733 Heavy Duty',
            'unit_category' => 'Telehandler HD',
            'status' => 'Operation',
            'current_km' => 10,
        ]);
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('p2h_templates', [
            'id' => $template->id,
            'code' => 'TH99',
            'name' => 'Telehandler HD',
        ]);

        // 6. Hapus kategori aset juga menghapus master template yang belum ada transaksi
        $deleteResponse = $this->actingAs($this->qmaAdmin)->delete('/qma/vehicles/' . $category->id);
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('p2h_templates', ['id' => $template->id]);
    }

    public function test_qma_admin_can_edit_p2h_template_question(): void
    {
        $item = \App\Models\P2hTemplateItem::create([
            'template_id' => $this->template->id,
            'section' => 'Pemeriksaan Keliling',
            'title' => 'Level Oli Hidrolik',
            'question_type' => 'condition',
            'sequence' => 1,
        ]);

        // Akses halaman detail template
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates.show', $this->template->id));
        $response->assertStatus(200);
        $response->assertSee('Edit');
        $response->assertSee('Tambah Pertanyaan');
        $response->assertSee($item->title);

        // Edit butir pertanyaan
        $editResponse = $this->actingAs($this->qmaAdmin)->put(
            route('qma.p2h.templates.items.update', ['templateId' => $this->template->id, 'itemId' => $item->id]),
            [
                'title' => 'Level Oli Hidrolik dan Kebocoran Selang (Diperbarui)',
                'section' => 'Sistem Hidrolik',
                'question_type' => 'Multiple choice: only one answer',
                'sequence' => 1,
            ]
        );

        $editResponse->assertRedirect(route('qma.p2h.templates.show', $this->template->id));
        $editResponse->assertSessionHas('success');

        $this->assertDatabaseHas('p2h_template_items', [
            'id' => $item->id,
            'title' => 'Level Oli Hidrolik dan Kebocoran Selang (Diperbarui)',
            'section' => 'Sistem Hidrolik',
            'question_type' => 'Multiple choice: only one answer',
        ]);
    }

    public function test_qma_admin_can_view_two_column_section_layout_for_p2h_template_questions(): void
    {
        $this->template->items()->delete();

        $this->template->items()->createMany([
            [
                'section' => 'START',
                'title' => 'Start Engine',
                'question_type' => 'Datetime',
                'sequence' => 1,
            ],
            [
                'section' => 'START',
                'title' => 'Shift Operator',
                'question_type' => 'Multiple choice: only one answer',
                'sequence' => 2,
            ],
            [
                'section' => 'ATTACHMENT',
                'title' => 'Basket / Keranjang (enginee)',
                'question_type' => 'Multiple choice: only one answer',
                'sequence' => 3,
            ],
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates.show', $this->template->id));
        $response->assertStatus(200);
        $response->assertSee('sectionsContainer');
        $response->assertSee('section-card');
        $response->assertSee('section-content');
        $response->assertSee('START');
        $response->assertSee('ATTACHMENT');
        $response->assertSee('2 Pertanyaan');
        $response->assertSee('1 Pertanyaan');
        $response->assertSee('Basket / Keranjang (enginee)');
        $response->assertSee('addItemSectionContainer');
        $response->assertSee('addItemSectionBadge');
    }

    public function test_authenticated_operator_can_view_dashboard_p2h_and_timesheet_menus(): void
    {
        // Pastikan ada kendaraan ringan
        Vehicle::firstOrCreate(
            ['code' => 'LV99'],
            [
                'brand_type' => 'Toyota Hilux Double Cabin',
                'license_plate' => 'BH 9999 KM',
                'category' => 'light_vehicle',
                'current_km' => 5400,
                'ownership' => 'Operasional Site',
                'status' => 'Operation',
            ]
        );

        // 1. Login redirect ke dashboard karyawan
        $resLogin = $this->post('/login', [
            'username' => 'OPR-001',
            'password' => 'password',
        ]);
        $resLogin->assertRedirect(route('employee.dashboard'));

        // 2. Halaman Dashboard Karyawan menampilkan 2 card fitur utama
        $resDashboard = $this->actingAs($this->operatorUser)->get(route('employee.dashboard'));
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('Dashboard Karyawan');
        $resDashboard->assertSee('Buka Modul Pemeriksaan P2H');
        $resDashboard->assertSee('Scan Barcode Unit Mobil');
        $resDashboard->assertSee('Pemeriksaan P2H');
        $resDashboard->assertSee('Timesheet');

        // 3. Sidebar di halaman P2H memuat menu Dashboard, Pemeriksaan P2H, dan Timesheet
        $resCatalog = $this->actingAs($this->operatorUser)->get(route('p2h.index'));
        $resCatalog->assertStatus(200);
        $resCatalog->assertSee('Dashboard');
        $resCatalog->assertSee('Pemeriksaan P2H');
        $resCatalog->assertSee('Timesheet');
        $resCatalog->assertSee(route('employee.dashboard'));
        $resCatalog->assertSee(route('p2h.index'));
        $resCatalog->assertSee(route('timesheet.index'));

        // 4. Akses langsung halaman Timesheet
        $resTimesheet = $this->actingAs($this->operatorUser)->get(route('timesheet.index'));
        $resTimesheet->assertStatus(200);
        $resTimesheet->assertSee('Timesheet Kendaraan Ringan');
        $resTimesheet->assertSee('LV99');
        $resTimesheet->assertSee('BH 9999 KM');
        $resTimesheet->assertSee('Isi Form Mobilisasi LV');
    }

    public function test_qma_admin_can_add_section_with_multiple_questions_and_types_attached(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.items.store', $this->template->id),
            [
                'section' => 'Pemeriksaan Ruang Kemudi (Cabin)',
                'questions' => [
                    [
                        'title' => 'Kondisi kaca spion kanan & kiri',
                        'question_type' => 'Multiple choice: only one answer',
                    ],
                    [
                        'title' => 'Catatan kelainan instrumen dashboard',
                        'question_type' => 'Multiple Lines Text Box',
                    ],
                    [
                        'title' => 'Jumlah sabuk pengaman yang terpasang baik',
                        'question_type' => 'Numerical Value',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('qma.p2h.templates.show', $this->template->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('p2h_template_items', [
            'template_id' => $this->template->id,
            'section' => 'Pemeriksaan Ruang Kemudi (Cabin)',
            'title' => 'Kondisi kaca spion kanan & kiri',
            'question_type' => 'Multiple choice: only one answer',
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('p2h_template_items', [
            'template_id' => $this->template->id,
            'section' => 'Pemeriksaan Ruang Kemudi (Cabin)',
            'title' => 'Catatan kelainan instrumen dashboard',
            'question_type' => 'Multiple Lines Text Box',
            'sequence' => 2,
        ]);

        $this->assertDatabaseHas('p2h_template_items', [
            'template_id' => $this->template->id,
            'section' => 'Pemeriksaan Ruang Kemudi (Cabin)',
            'title' => 'Jumlah sabuk pengaman yang terpasang baik',
            'question_type' => 'Numerical Value',
            'sequence' => 3,
        ]);
    }


    public function test_qma_admin_template_detail_renders_add_section_modal(): void
    {
        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates.show', $this->template->id));
        $response->assertStatus(200);
        $response->assertSee('id="addSectionModal"', false);
        $response->assertSee('id="sectionQuestionsList"', false);
        $response->assertSee('openAddSectionModal', false);
        $response->assertSee('closeAddSectionModal', false);
        $response->assertSee('addQuestionToSection', false);
        $response->assertSee('removeQuestionFromSection', false);
    }

    public function test_qma_admin_can_add_section_before_target_section(): void
    {
        // 1. Buat Section A (sequence 1, 2) dan Section C (sequence 3, 4)
        $this->template->items()->createMany([
            ['section' => 'Section A', 'title' => 'Pertanyaan A1', 'question_type' => 'choice', 'sequence' => 1],
            ['section' => 'Section A', 'title' => 'Pertanyaan A2', 'question_type' => 'choice', 'sequence' => 2],
            ['section' => 'Section C', 'title' => 'Pertanyaan C1', 'question_type' => 'choice', 'sequence' => 3],
            ['section' => 'Section C', 'title' => 'Pertanyaan C2', 'question_type' => 'choice', 'sequence' => 4],
        ]);

        // 2. Tambah Section B dengan posisi 'before' Section C
        $response = $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.items.store', $this->template->id),
            [
                'section' => 'Section B',
                'position_type' => 'before',
                'target_section' => 'Section C',
                'questions' => [
                    [
                        'title' => 'Pertanyaan B1',
                        'question_type' => 'choice',
                    ],
                ],
            ]
        );

        $response->assertRedirect(route('qma.p2h.templates.show', $this->template->id));
        $response->assertSessionHas('success');

        // Sequence Section B harus berada sebelum Section C
        $itemB = $this->template->items()->where('section', 'Section B')->first();
        $itemC1 = $this->template->items()->where('title', 'Pertanyaan C1')->first();
        $this->assertNotNull($itemB);
        $this->assertLessThan($itemC1->sequence, $itemB->sequence);
        $this->assertEquals(3, $itemB->sequence);
        // Dan sequence C1 tergeser menjadi 4
        $this->assertEquals(4, $itemC1->sequence);
    }

    public function test_qma_admin_can_reorder_sections(): void
    {
        // 1. Buat 3 Section: Section 1 (seq 1,2), Section 2 (seq 3,4), Section 3 (seq 5,6)
        $this->template->items()->createMany([
            ['section' => 'Section 1', 'title' => 'Pertanyaan 1A', 'question_type' => 'choice', 'sequence' => 1],
            ['section' => 'Section 1', 'title' => 'Pertanyaan 1B', 'question_type' => 'choice', 'sequence' => 2],
            ['section' => 'Section 2', 'title' => 'Pertanyaan 2A', 'question_type' => 'choice', 'sequence' => 3],
            ['section' => 'Section 2', 'title' => 'Pertanyaan 2B', 'question_type' => 'choice', 'sequence' => 4],
            ['section' => 'Section 3', 'title' => 'Pertanyaan 3A', 'question_type' => 'choice', 'sequence' => 5],
            ['section' => 'Section 3', 'title' => 'Pertanyaan 3B', 'question_type' => 'choice', 'sequence' => 6],
        ]);

        // 2. Reorder menjadi: Section 3, Section 1, Section 2
        $response = $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.sections.reorder', $this->template->id),
            [
                'sections' => ['Section 3', 'Section 1', 'Section 2'],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh template items urut sequence
        $items = $this->template->items()->orderBy('sequence')->get();

        // 2 item pertama harus Section 3
        $this->assertEquals('Section 3', $items[0]->section);
        $this->assertEquals(1, $items[0]->sequence);
        $this->assertEquals('Section 3', $items[1]->section);
        $this->assertEquals(2, $items[1]->sequence);

        // 2 item berikutnya harus Section 1
        $this->assertEquals('Section 1', $items[2]->section);
        $this->assertEquals(3, $items[2]->sequence);
        $this->assertEquals('Section 1', $items[3]->section);
        $this->assertEquals(4, $items[3]->sequence);

        // 2 item terakhir harus Section 2
        $this->assertEquals('Section 2', $items[4]->section);
        $this->assertEquals(5, $items[4]->sequence);
        $this->assertEquals('Section 2', $items[5]->section);
        $this->assertEquals(6, $items[5]->sequence);
    }

    public function test_qma_admin_template_detail_renders_reorder_sections_modal_and_controls(): void
    {
        $this->template->items()->createMany([
            ['section' => 'Section A', 'title' => 'Tanya A', 'question_type' => 'choice', 'sequence' => 1],
            ['section' => 'Section B', 'title' => 'Tanya B', 'question_type' => 'choice', 'sequence' => 2],
        ]);

        $response = $this->actingAs($this->qmaAdmin)->get(route('qma.p2h.templates.show', $this->template->id));
        $response->assertStatus(200);
        $response->assertSee('id="reorderSectionsModal"', false);
        $response->assertSee('openReorderSectionsModal', false);
        $response->assertSee('closeReorderSectionsModal', false);
        $response->assertSee('moveSectionItemUp', false);
        $response->assertSee('moveSectionItemDown', false);
        $response->assertSee('Atur Urutan Section');
    }

    public function test_questions_are_automatically_and_contiguously_numbered_across_all_sections(): void
    {
        // 1. Buat Bagian Satu (2 pertanyaan) dan Bagian Dua (2 pertanyaan)
        $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.items.store', $this->template->id),
            [
                'section' => 'Bagian Satu',
                'questions' => [
                    ['title' => 'Pertanyaan 1.1', 'question_type' => 'choice'],
                    ['title' => 'Pertanyaan 1.2', 'question_type' => 'choice'],
                ],
            ]
        );

        $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.items.store', $this->template->id),
            [
                'section' => 'Bagian Dua',
                'questions' => [
                    ['title' => 'Pertanyaan 2.1', 'question_type' => 'choice'],
                    ['title' => 'Pertanyaan 2.2', 'question_type' => 'choice'],
                ],
            ]
        );

        // Pertanyaan harus berurutan 1, 2 di Bagian Satu, dan 3, 4 di Bagian Dua
        $items = $this->template->items()->orderBy('sequence')->get();
        $this->assertCount(4, $items);
        $this->assertEquals(['Pertanyaan 1.1', 'Pertanyaan 1.2', 'Pertanyaan 2.1', 'Pertanyaan 2.2'], $items->pluck('title')->all());
        $this->assertEquals([1, 2, 3, 4], $items->pluck('sequence')->all());

        // 2. Tambah satu pertanyaan baru ke Bagian Satu melalui form butir tunggal
        $this->actingAs($this->qmaAdmin)->post(
            route('qma.p2h.templates.items.store', $this->template->id),
            [
                'section' => 'Bagian Satu',
                'title' => 'Pertanyaan 1.3 Baru',
                'question_type' => 'choice',
            ]
        );

        // Pertanyaan baru harus berada di akhir Bagian Satu (nomor 3), dan Bagian Dua bergeser menjadi 4, 5
        $itemsAfter = $this->template->items()->orderBy('sequence')->get();
        $this->assertCount(5, $itemsAfter);
        $this->assertEquals(['Pertanyaan 1.1', 'Pertanyaan 1.2', 'Pertanyaan 1.3 Baru', 'Pertanyaan 2.1', 'Pertanyaan 2.2'], $itemsAfter->pluck('title')->all());
        $this->assertEquals([1, 2, 3, 4, 5], $itemsAfter->pluck('sequence')->all());

        // 3. Hapus item kedua (Pertanyaan 1.2)
        $itemToDelete = $itemsAfter->firstWhere('title', 'Pertanyaan 1.2');
        $this->actingAs($this->qmaAdmin)->delete(
            route('qma.p2h.templates.items.destroy', ['templateId' => $this->template->id, 'itemId' => $itemToDelete->id])
        );

        // Seluruh pertanyaan otomatis ternormalisasi kembali tanpa celah: 1, 2, 3, 4
        $itemsAfterDelete = $this->template->items()->orderBy('sequence')->get();
        $this->assertCount(4, $itemsAfterDelete);
        $this->assertEquals([1, 2, 3, 4], $itemsAfterDelete->pluck('sequence')->all());
    }
}


