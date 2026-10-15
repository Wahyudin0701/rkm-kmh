<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Master Gates (Pos Gerbang Proyek PLTA Merangin)
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

        $createdGates = [];
        foreach ($gateList as $g) {
            $createdGates[$g['code']] = Gate::create([
                'code' => $g['code'],
                'name' => $g['name'],
                'is_active' => true,
            ]);
        }

        // 2. Seed Master Security Guards (Satpam PG Lengkap dengan Akun Login)
        $guardData = [
            ['badge_id' => 'SEC-001', 'name' => 'Ahmad Fauzi', 'team' => 1, 'gate' => 'BTUNGGAL'],
            ['badge_id' => 'SEC-002', 'name' => 'Budi Santoso', 'team' => 2, 'gate' => 'ADIT1'],
            ['badge_id' => 'SEC-003', 'name' => 'Hendra Pratama', 'team' => 3, 'gate' => 'REGWEIR'],
            ['badge_id' => 'SEC-004', 'name' => 'Rusli Efendi', 'team' => 1, 'gate' => 'ADIT2'],
            ['badge_id' => 'SEC-005', 'name' => 'Slamet Widodo', 'team' => 2, 'gate' => 'ADIT3'],
        ];

        foreach ($guardData as $g) {
            $gateModel = $createdGates[$g['gate']];
            $guardUser = User::create([
                'name' => $g['name'],
                'username' => $g['badge_id'],
                'email' => strtolower($g['badge_id']) . '@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'gate_officer',
                'gate_id' => $gateModel->id,
            ]);

            SecurityGuard::create([
                'badge_id' => $g['badge_id'],
                'name' => $g['name'],
                'team' => $g['team'],
                'gate_id' => $gateModel->id,
                'user_id' => $guardUser->id,
                'is_active' => true,
            ]);
        }

        // 3. Seed Users (Super Admin, Dept Admins & Shared Gate Accounts)
        // Super Admin [Nama : Super Administrator, Username : superadmin, Password : password]
        User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        // Admin HSE [Nama : Mima, Username : 1185, Password : password]
        User::create([
            'name' => 'Mima',
            'username' => '1185',
            'email' => '1185@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'hse_admin',
        ]);

        // Admin QMA [Nama : Pinto Yuliandra, Username : 1171, Password : password]
        User::create([
            'name' => 'Pinto Yuliandra',
            'username' => '1171',
            'email' => '1171@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'qma_admin',
        ]);

        // Admin PG [Nama : Admin PG, Username : AdminPG, Password : password]
        User::create([
            'name' => 'Admin PG',
            'username' => 'AdminPG',
            'email' => 'adminpg@kmh.co.id',
            'password' => Hash::make('password'),
            'role' => 'pg_admin',
        ]);

        // 4. Seed Employees (Master Data HSE & Akun Login Karyawan dengan Role 'employee')
        $employeesData = [
            [
                'nik' => 'KMH-2022-001',
                'name' => 'Eka Sufiatna',
                'unit_kerja' => 'Teknik',
                'division' => 'INFORMATION & TECHNOLOGY',
                'position' => 'IT Division Coordinator',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->addMonths(8), // SIMPER AKTIF
            ],
            [
                'nik' => 'KMH-2022-045',
                'name' => 'Wanturi',
                'unit_kerja' => 'Umum',
                'division' => 'GA',
                'position' => 'Driver Operasional',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->addMonths(4), // SIMPER AKTIF
            ],
            [
                'nik' => 'KMH-2023-012',
                'name' => 'Randi Kurniawan',
                'unit_kerja' => 'Pembangkit',
                'division' => 'Mechanical',
                'position' => 'Operator Lapangan',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->subDays(14), // SIMPER KEDALUWARSA (Untuk Test Validasi)
            ],
            [
                'nik' => 'KMH-2021-088',
                'name' => 'Siti Rahma',
                'unit_kerja' => 'Umum',
                'division' => 'Keuangan',
                'position' => 'Staff Finance',
                'is_operator' => false, // BUKAN DRIVER
                'simper_expired_at' => null,
            ],
        ];

        foreach ($employeesData as $emp) {
            $empUser = User::create([
                'name' => $emp['name'],
                'username' => $emp['nik'],
                'email' => strtolower(str_replace(['-', ' '], '', $emp['nik'])) . '@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);

            $emp['user_id'] = $empUser->id;
            Employee::create($emp);
        }

        // 5. Seed Vehicles (Master Data QMA: Kendaraan Ringan / LV)
        Vehicle::create([
            'code' => 'LV62',
            'category' => 'light_vehicle',
            'brand_type' => 'MITSUBISHI - L300 - PICK UP',
            'license_plate' => 'BH 8893 DI',
            'ownership' => 'EKA SUFIATNA',
            'division' => 'IT',
            'status' => 'Operation',
            'current_km' => 12500.00,
        ]);

        Vehicle::create([
            'code' => 'LV01',
            'category' => 'light_vehicle',
            'brand_type' => 'TOYOTA HILUX 4X4 DC',
            'license_plate' => 'BH 1024 AB',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'division' => 'HSE',
            'status' => 'Operation',
            'current_km' => 45200.00,
        ]);

        Vehicle::create([
            'code' => 'LV15',
            'category' => 'light_vehicle',
            'brand_type' => 'ISUZU D-MAX 4X4',
            'license_plate' => 'BH 9012 CD',
            'ownership' => 'Divisi Bendungan',
            'division' => 'Bendungan',
            'status' => 'Standby',
            'current_km' => 28350.00,
        ]);

        // 6. Seed Vehicles (Master Data QMA: Kendaraan Berat / Unit Support - HANYA Pencatatan Data)
        Vehicle::create([
            'code' => 'EX01',
            'category' => 'unit_support',
            'brand_type' => 'KOMATSU PC200-8',
            'unit_category' => 'Excavator',
            'unit_type' => 'Hydraulic Excavator',
            'brand' => 'Komatsu',
            'model_number' => 'PC200-8M0',
            'production_year' => 2021,
            'ownership' => 'PT KMH - Project',
            'division' => 'Civil & Inviro',
            'location' => 'Beringin Tunggal / Tunnel #2',
            'status' => 'Operation',
            'current_km' => 3450.00,
            'fuel_consumption' => 18.50,
            'silo_document' => 'SILO-KMH-2024/091',
            'stnk_document' => 'STNK-BH-EX01',
            'pajak_document' => 'PAJAK-2026/12',
            'kir_document' => 'KIR-2026/08',
        ]);

        Vehicle::create([
            'code' => 'CR01',
            'category' => 'unit_support',
            'brand_type' => 'KATO SR-250R',
            'unit_category' => 'Mobile Crane',
            'unit_type' => 'Rough Terrain Crane 25T',
            'brand' => 'Kato',
            'model_number' => 'SR-250R',
            'production_year' => 2019,
            'ownership' => 'PT Kerinci Merangin Hidro',
            'division' => 'Mechanical',
            'location' => 'CCR DAM / Spillway',
            'status' => 'Operation',
            'current_km' => 1820.00,
            'fuel_consumption' => 14.20,
            'silo_document' => 'SILO-KMH-2025/112',
            'kir_document' => 'KIR-2026/10',
        ]);

        Vehicle::create([
            'code' => 'DT02',
            'category' => 'unit_support',
            'brand_type' => 'MITSUBISHI FUSO FIGHTER FN62F',
            'license_plate' => 'BH 8412 AA',
            'unit_category' => 'Dump Truck',
            'unit_type' => 'Dump Truck 20 Ton',
            'brand' => 'Mitsubishi Fuso',
            'model_number' => 'FN62F HD',
            'production_year' => 2022,
            'ownership' => 'PT KMH - Project',
            'division' => 'Civil & Inviro',
            'location' => 'Quarry & Disposal Area',
            'status' => 'Standby',
            'current_km' => 14800.00,
            'fuel_consumption' => 28.00,
            'stnk_document' => 'STNK-BH-8412-AA',
            'kir_document' => 'KIR-2026/11',
        ]);

        // 7. Seed 28 Master Template P2H Unit Support
        $this->call(P2hTemplateSeeder::class);
    }
}
