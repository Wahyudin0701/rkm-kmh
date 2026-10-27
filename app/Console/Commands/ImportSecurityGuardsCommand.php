<?php

namespace App\Console\Commands;

use App\Models\Gate;
use App\Models\SecurityGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportSecurityGuardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:import-plotingan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data personil Satpam lapangan dari berkas Plotingan Jaga Perpos ke database';

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

        $this->info("Ditemukan " . count($personnel) . " personil satpam dalam katalog.");

        $gateCache = [];
        $inserted = 0;
        $updated = 0;

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

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== SINKRONISASI SELESAI ===");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Personil Masuk', count($personnel)],
                ['Data Baru Ditambahkan', $inserted],
                ['Data Diperbarui', $updated],
                ['Regu 1 (R1)', $catalog['teams']['team_1'] ?? 0],
                ['Regu 2 (R2)', $catalog['teams']['team_2'] ?? 0],
                ['Regu 3 (R3)', $catalog['teams']['team_3'] ?? 0],
            ]
        );

        return Command::SUCCESS;
    }
}
