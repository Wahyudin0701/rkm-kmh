<?php

namespace Database\Seeders;

use App\Models\Gate;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengisi data master esensial sistem (Pos Gerbang, RBAC, Super Admin, dan Template P2H).
     */
    public function run(): void
    {
        // 1. Seed Master Gates (15 Pos Gerbang Proyek PLTA Merangin 350 MW)
        $gateList = [
            ['code' => 'ADIT1', 'name' => 'Adit 1'],
            ['code' => 'ADIT2', 'name' => 'Adit 2'],
            ['code' => 'ADIT3', 'name' => 'Adit 3'],
            ['code' => 'BDG6', 'name' => 'Bedeng 6'],
            ['code' => 'BTUNGGAL', 'name' => 'Beringin Tunggal'],
            ['code' => 'CCRDAM', 'name' => 'CCR DAM'],
            ['code' => 'CRESTDAM', 'name' => 'Crest DAM'],
            ['code' => 'JAKON', 'name' => 'Dermaga Jakon'],
            ['code' => 'GUDANG', 'name' => 'Gudang Induk'],
            ['code' => 'KUMUN', 'name' => 'Mess Kumun'],
            ['code' => 'PHOFFICE', 'name' => 'PH - Office'],
            ['code' => 'PHUTAMA', 'name' => 'PH - Utama'],
            ['code' => 'REGWEIR', 'name' => 'Regulating Weir'],
            ['code' => 'VSHAFT', 'name' => 'Vertical Shaft'],
            ['code' => 'VSTPST', 'name' => 'VS - TPST'],
        ];

        foreach ($gateList as $g) {
            Gate::firstOrCreate([
                'code' => $g['code'],
            ], [
                'name' => $g['name'],
                'is_active' => true,
            ]);
        }

        // 2. Seed RBAC Roles, Permissions & Akun Super Admin Utama (Kredensial dari .env)
        $this->call(RolePermissionSeeder::class);

        // 3. Seed 28 Master Template Checksheet P2H Unit Support & Pertanyaan Pemeriksaan
        $this->call(P2hTemplateSeeder::class);

        // 4. Seed Data Uji Coba (Sample Satpam, Karyawan, Kendaraan LV & Alat Berat)
        // Dijalankan di mode testing atau jika SEED_SAMPLE_DATA=true di .env
        $seedSamples = app()->environment('testing') || filter_var(env('SEED_SAMPLE_DATA', false), FILTER_VALIDATE_BOOLEAN);
        if ($seedSamples) {
            $this->call(SampleTestingDataSeeder::class);
        }
    }
}
