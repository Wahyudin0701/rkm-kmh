<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('permissions') && \Spatie\Permission\Models\Permission::count() === 0) {
            $this->seed(RolePermissionSeeder::class);
        }
    }

    /**
     * Helper khusus test suite untuk menyiapkan sample testing mock data
     * (Pengganti seeder uji coba agar folder database/seeders tetap murni master data).
     */
    protected function seedSampleData(): void
    {
        // 1. Seed Master Gates
        if (Gate::count() === 0) {
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
                Gate::firstOrCreate(['code' => $g['code']], ['name' => $g['name'], 'is_active' => true]);
            }
        }

        // 2. Seed Mock Satpam Guards
        $createdGates = Gate::all()->keyBy('code');
        $guardData = [
            ['badge_id' => 'SEC-001', 'name' => 'Ahmad Fauzi', 'team' => 1, 'gate' => 'BTUNGGAL'],
            ['badge_id' => 'SEC-002', 'name' => 'Budi Santoso', 'team' => 2, 'gate' => 'ADIT1'],
            ['badge_id' => 'SEC-003', 'name' => 'Hendra Pratama', 'team' => 3, 'gate' => 'REGWEIR'],
            ['badge_id' => 'SEC-004', 'name' => 'Rusli Efendi', 'team' => 1, 'gate' => 'ADIT2'],
            ['badge_id' => 'SEC-005', 'name' => 'Slamet Widodo', 'team' => 2, 'gate' => 'ADIT3'],
        ];

        foreach ($guardData as $g) {
            $gateModel = $createdGates[$g['gate']] ?? Gate::first();
            $guardUser = User::firstOrCreate([
                'username' => $g['badge_id'],
            ], [
                'name' => $g['name'],
                'email' => strtolower($g['badge_id']) . '@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'gate_officer',
                'gate_id' => $gateModel?->id,
            ]);

            if (!$guardUser->hasRole('gate_officer')) {
                $guardUser->assignRole('gate_officer');
            }

            SecurityGuard::firstOrCreate([
                'badge_id' => $g['badge_id'],
            ], [
                'name' => $g['name'],
                'team' => $g['team'],
                'gate_id' => $gateModel?->id,
                'user_id' => $guardUser->id,
                'is_active' => true,
            ]);
        }

        // 3. Seed Mock Employees
        $employeesData = [
            [
                'nik' => 'KMH-2022-001',
                'name' => 'Eka Sufiatna',
                'unit_kerja' => 'Teknik',
                'division' => 'INFORMATION & TECHNOLOGY',
                'position' => 'IT Division Coordinator',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->addMonths(8),
            ],
            [
                'nik' => 'KMH-2022-045',
                'name' => 'Wanturi',
                'unit_kerja' => 'Umum',
                'division' => 'GA',
                'position' => 'Driver Operasional',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->addMonths(4),
            ],
            [
                'nik' => 'KMH-2023-012',
                'name' => 'Randi Kurniawan',
                'unit_kerja' => 'Pembangkit',
                'division' => 'Mechanical',
                'position' => 'Operator Lapangan',
                'is_operator' => true,
                'simper_expired_at' => Carbon::now()->subDays(14),
            ],
            [
                'nik' => 'KMH-2021-088',
                'name' => 'Siti Rahma',
                'unit_kerja' => 'Umum',
                'division' => 'Keuangan',
                'position' => 'Staff Finance',
                'is_operator' => false,
                'simper_expired_at' => null,
            ],
        ];

        foreach ($employeesData as $emp) {
            $empUser = User::firstOrCreate([
                'username' => $emp['nik'],
            ], [
                'name' => $emp['name'],
                'email' => strtolower(str_replace(['-', ' '], '', $emp['nik'])) . '@kmh.co.id',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]);

            if (!$empUser->hasRole('employee')) {
                $empUser->assignRole('employee');
            }

            $emp['user_id'] = $empUser->id;
            Employee::firstOrCreate(['nik' => $emp['nik']], $emp);
        }

        // 4. Seed Mock Vehicles
        Vehicle::firstOrCreate([
            'code' => 'LV62',
        ], [
            'category' => 'light_vehicle',
            'brand_type' => 'MITSUBISHI - L300 - PICK UP',
            'license_plate' => 'BH 8893 DI',
            'ownership' => 'EKA SUFIATNA',
            'division' => 'IT',
            'status' => 'Operation',
            'current_km' => 12500.00,
        ]);

        Vehicle::firstOrCreate([
            'code' => 'LV01',
        ], [
            'category' => 'light_vehicle',
            'brand_type' => 'TOYOTA HILUX 4X4 DC',
            'license_plate' => 'BH 1024 AB',
            'ownership' => 'PT Kerinci Merangin Hidro',
            'division' => 'HSE',
            'status' => 'Operation',
            'current_km' => 45200.00,
        ]);

        Vehicle::firstOrCreate([
            'code' => 'LV15',
        ], [
            'category' => 'light_vehicle',
            'brand_type' => 'ISUZU D-MAX 4X4',
            'license_plate' => 'BH 9012 CD',
            'ownership' => 'Divisi Bendungan',
            'division' => 'Bendungan',
            'status' => 'Standby',
            'current_km' => 28350.00,
        ]);

        Vehicle::firstOrCreate([
            'code' => 'EX01',
        ], [
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
    }
}
