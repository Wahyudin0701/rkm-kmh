<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\SecurityGuard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SecurityGuardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengimpor data personil satpam lapangan resmi dari Plotingan Jaga Perpos.
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

        $this->command?->info("Memulai sinkronisasi " . count($personnel) . " personil Satpam...");

        $gateCache = [];
        $inserted = 0;
        $updated = 0;

        foreach ($personnel as $p) {
            $gateCode = $p['gate_code'];
            if (!isset($gateCache[$gateCode])) {
                $gate = Gate::firstOrCreate(
                    ['code' => $gateCode],
                    ['name' => $p['gate_name'], 'is_active' => true]
                );
                $gateCache[$gateCode] = $gate->id;
            }

            $guard = SecurityGuard::updateOrCreate(
                ['badge_id' => $p['badge_id']],
                [
                    'name' => $p['name'],
                    'team' => $p['team'],
                    'gate_id' => $gateCache[$gateCode],
                    'is_active' => $p['is_active'] ?? true,
                ]
            );

            if ($guard->wasRecentlyCreated) {
                $inserted++;
            } else {
                $updated++;
            }
        }

        $this->command?->info("Selesai! {$inserted} personil baru ditambahkan, {$updated} personil diperbarui.");
    }
}
