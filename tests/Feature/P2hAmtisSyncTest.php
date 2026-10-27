<?php

namespace Tests\Feature;

use App\Models\P2hTemplate;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\P2hAmtisSyncSeeder;
use Database\Seeders\P2hTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class P2hAmtisSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(P2hTemplateSeeder::class);
        $this->seed(P2hAmtisSyncSeeder::class);
    }

    public function test_excavator_template_has_3_k3_sessions_from_amtis(): void
    {
        $template = P2hTemplate::where('name', 'Excavator')->firstOrFail();
        $this->assertEquals(33, $template->items()->count());

        $sections = $template->items()->pluck('section')->unique()->values()->all();
        $this->assertContains('Sebelum Mesin Hidup', $sections);
        $this->assertContains('Persiapan Menghidupkan Mesin', $sections);
        $this->assertContains('Setelah Mesin Menyala', $sections);
        $this->assertCount(3, $sections);
    }

    public function test_dump_truck_template_has_3_k3_sessions_from_amtis(): void
    {
        $template = P2hTemplate::where('name', 'Dump Truck')->firstOrFail();
        $this->assertEquals(33, $template->items()->count());

        $sections = $template->items()->pluck('section')->unique()->values()->all();
        $this->assertContains('Sebelum Mesin Hidup', $sections);
        $this->assertContains('Persiapan Menghidupkan Mesin', $sections);
        $this->assertContains('Setelah Mesin Menyala', $sections);
        $this->assertCount(3, $sections);
    }

    public function test_crawler_crane_template_has_crane_component_sections_from_amtis(): void
    {
        $template = P2hTemplate::where('name', 'Crawler Crane')->firstOrFail();
        $this->assertGreaterThanOrEqual(20, $template->items()->count());

        $sections = $template->items()->pluck('section')->unique()->values()->all();
        $this->assertContains('Attachment', $sections);
        $this->assertContains('Oil', $sections);
        $this->assertContains('Elektrik', $sections);
    }

    public function test_operator_opens_p2h_form_with_3_sessions_rendered(): void
    {
        $user = User::create([
            'username' => 'OPR-TEST',
            'name' => 'Operator Test',
            'email' => 'oprtest@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        $template = P2hTemplate::where('name', 'Excavator')->firstOrFail();

        $vehicle = Vehicle::create([
            'category' => 'unit_support',
            'unit_category' => 'Excavator',
            'brand_type' => 'KOMATSU PC200',
            'code' => 'EX99',
            'status' => 'Operation',
            'current_km' => 1500,
            'p2h_template_id' => $template->id,
        ]);

        $response = $this->actingAs($user)->get(route('p2h.form', ['code' => $vehicle->code]));
        $response->assertStatus(200);
        $response->assertSee('Sebelum Mesin Hidup');
        $response->assertSee('Persiapan Menghidupkan Mesin');
        $response->assertSee('Setelah Mesin Menyala');
    }
}
