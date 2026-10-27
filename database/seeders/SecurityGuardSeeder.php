<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SecurityGuardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengimpor data personil satpam lapangan resmi dari Plotingan Jaga Perpos
     * sekaligus membuatkan akun login User (role: gate_officer) dengan password default 123456.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/security_guards_plotingan.json');

        if (!File::exists($jsonPath)) {
            $this->command?->error("File data katalog satpam tidak ditemukan: {$jsonPath}");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        $personnel = $data['personnel'] ?? [];

        $this->command?->info("Memulai sinkronisasi " . count($personnel) . " personil Satpam beserta akun login...");

        $gateOfficerRole = Role::firstOrCreate(['name' => 'gate_officer', 'guard_name' => 'web']);
        $defaultPasswordHash = Hash::make('123456');

        $gateCache = [];
        $insertedGuards = 0;
        $updatedGuards = 0;
        $usersCreated = 0;

        foreach ($personnel as $p) {
            $gateCode = $p['gate_code'];
            if (!isset($gateCache[$gateCode])) {
                $gate = Gate::firstOrCreate(
                    ['code' => $gateCode],
                    ['name' => $p['gate_name'], 'is_active' => true]
                );
                $gateCache[$gateCode] = $gate->id;
            }

            // 1. Buat / Ambil Akun Login User untuk Satpam
            $username = strtoupper(trim($p['badge_id']));
            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $p['name'],
                    'email' => strtolower($username) . '@kmh.co.id',
                    'password' => $defaultPasswordHash,
                    'role' => 'gate_officer',
                    'gate_id' => $gateCache[$gateCode],
                ]
            );

            if ($user->wasRecentlyCreated) {
                $usersCreated++;
                if (!$user->hasRole('gate_officer')) {
                    $user->assignRole($gateOfficerRole);
                }
            }

            // 2. Simpan Data Satpam
            $guard = SecurityGuard::updateOrCreate(
                ['badge_id' => $p['badge_id']],
                [
                    'name' => $p['name'],
                    'team' => $p['team'],
                    'gate_id' => $gateCache[$gateCode],
                    'user_id' => $user->id,
                    'is_active' => $p['is_active'] ?? true,
                ]
            );

            if ($guard->wasRecentlyCreated) {
                $insertedGuards++;
            } else {
                $updatedGuards++;
            }
        }

        $this->command?->info("Selesai! {$insertedGuards} satpam baru, {$updatedGuards} satpam diperbarui, {$usersCreated} akun login user dibuat (password default: 123456).");
    }
}
