<?php

namespace App\Console\Commands;

use App\Models\VehicleActivity;
use Illuminate\Console\Command;

class ExpirePendingActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:expire-pending {--hours=5 : Batas maksimal jam menunggu verifikasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan otomatis tiket perjalanan pending yang melebihi batas waktu verifikasi (default 5 jam)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        if ($hours <= 0) {
            $hours = VehicleActivity::PENDING_TIMEOUT_HOURS;
        }

        $this->info("Memeriksa tiket perjalanan pending yang berusia lebih dari {$hours} jam...");

        $affected = VehicleActivity::expireOverduePendingActivities($hours);

        $this->info("Selesai: Sebanyak {$affected} tiket perjalanan berhasil dibatalkan otomatis.");

        return Command::SUCCESS;
    }
}
