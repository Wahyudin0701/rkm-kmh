<?php

namespace App\Console\Commands;

use App\Models\Gate;
use App\Models\SecurityGuard;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ImportSecurityGuardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:import-plotingan {--password=123456 : Password default untuk akun user login satpam}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data personil Satpam lapangan dari berkas Plotingan Jaga Perpos ke database beserta akun login User';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jsonPath = database_path('data/security_guards_plotingan.json');

        if (!File::exists($jsonPath)) {
            $this->error("File katalog data satpam tidak ditemukan di {$jsonPath}");
            return Command::FAILURE;
        }

        $catalog = json_decode(File::get($jsonPath), true);
        $personnel = $catalog['personnel'] ?? [];

        $defaultPassword = (string) $this->option('password');
        $defaultPasswordHash = Hash::make($defaultPassword);

        $this->info("Ditemukan " . count($personnel) . " personil satpam dalam katalog.");
        $this->info("Password akun login default: {$defaultPassword}");

        $gateOfficerRole = Role::firstOrCreate(['name' => 'gate_officer', 'guard_name' => 'web']);

        $gateCache = [];
        $insertedGuards = 0;
        $updatedGuards = 0;
        $usersCreated = 0;

        $bar = $this->output->createProgressBar(count($personnel));
        $bar->start();

        foreach ($personnel as $p) {
            $gateCode = $p['gate_code'];
            if (!isset($gateCache[$gateCode])) {
                $gate = Gate::firstOrCreate(
                    ['code' => $gateCode],
                    ['name' => $p['gate_name'], 'is_active' => true]
                );
                $gateCache[$gateCode] = $gate->id;
            }

            // 1. Buat / Hubungkan Akun User untuk login di pos jaga
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

            // 2. Simpan / Hubungkan Data Master Satpam
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

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== SINKRONISASI SELESAI ===");
        $this->table(
            ['Metrik', 'Keterangan'],
            [
                ['Total Personil', count($personnel)],
                ['Satpam Ditambahkan / Diupdate', "{$insertedGuards} baru / {$updatedGuards} diupdate"],
                ['Akun Login User Dibuat', $usersCreated],
                ['Password Default Akun', $defaultPassword],
                ['Role Akun Login', 'gate_officer (Petugas Pos Gerbang)'],
                ['Regu 1 (R1)', ($catalog['teams']['team_1'] ?? 0) . ' Personil (R1-001 s/d R1-029)'],
                ['Regu 2 (R2)', ($catalog['teams']['team_2'] ?? 0) . ' Personil (R2-001 s/d R2-029)'],
                ['Regu 3 (R3)', ($catalog['teams']['team_3'] ?? 0) . ' Personil (R3-001 s/d R3-029)'],
            ]
        );

        return Command::SUCCESS;
    }
}
