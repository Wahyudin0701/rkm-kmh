<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Mengisi data master esensial sistem:
     * 1. Master Pos Gerbang (15 Pos)
     * 2. RBAC Roles & Permissions
     * 3. 4 Akun Utama Administrator (Super Admin, HSE, QMA, PG) - Kredensial via .env
     * 4. 28 Master Template Checksheet P2H & 282 Item Pertanyaan
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

        // 2. Seed RBAC Roles & Permissions Matrix
        $this->call(RolePermissionSeeder::class);

        // 3. Seed 4 Akun Utama Administrator Sistem (Murni dibaca dari config/auth.php & .env)
        $coreAdmins = [
            ['role' => 'super_admin', 'config' => config('auth.superadmin')],
            ['role' => 'hse_admin',   'config' => config('auth.hse_admin')],
            ['role' => 'qma_admin',   'config' => config('auth.qma_admin')],
            ['role' => 'pg_admin',    'config' => config('auth.pg_admin')],
        ];

        foreach ($coreAdmins as $adminData) {
            $conf = $adminData['config'];
            if (!$conf || empty($conf['username']) || empty($conf['password'])) {
                continue;
            }

            $user = User::firstOrCreate([
                'username' => $conf['username'],
            ], [
                'name' => $conf['name'] ?? ucwords(str_replace('_', ' ', $adminData['role'])),
                'email' => $conf['email'] ?? ($conf['username'] . '@kmh.co.id'),
                'password' => Hash::make($conf['password']),
                'role' => $adminData['role'],
            ]);

            if (!$user->hasRole($adminData['role'])) {
                $user->assignRole($adminData['role']);
            }
        }

        // 4. Seed 28 Master Template Checksheet P2H Unit Support & Pertanyaan Pemeriksaan
        $this->call(P2hTemplateSeeder::class);

        // 5. Seed Data Uji Coba Lapangan (Hanya dijalankan di testing atau jika SEED_SAMPLE_DATA=true di .env)
        $seedSamples = app()->environment('testing') || filter_var(env('SEED_SAMPLE_DATA', false), FILTER_VALIDATE_BOOLEAN);
        if ($seedSamples) {
            $this->call(SampleTestingDataSeeder::class);
        }
    }
}
