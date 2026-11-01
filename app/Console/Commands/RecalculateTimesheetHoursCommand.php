<?php

namespace App\Console\Commands;

use App\Models\UnitSupportTimesheet;
use App\Models\Vehicle;
use Illuminate\Console\Command;

class RecalculateTimesheetHoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qma:recalculate-timesheet-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitung otomatis dan perbarui finish HM serta durasi jam operasional (1 HM = 1 Jam) dari entri kronologis berikutnya';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai rekalkulasi finish HM & jam operasi timesheet unit support...');

        $vehicles = Vehicle::whereHas('supportTimesheets')->get();
        $totalUpdated = 0;

        foreach ($vehicles as $veh) {
            $timesheets = UnitSupportTimesheet::where('vehicle_id', $veh->id)
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $count = $timesheets->count();
            for ($i = 0; $i < $count - 1; $i++) {
                $current = $timesheets[$i];
                $next = $timesheets[$i + 1];

                // Jika current belum memiliki end_meter atau bernilai 0
                if ($current->end_meter === null || (float) $current->end_meter == 0) {
                    if ($next->start_meter !== null && (float) $next->start_meter >= (float) $current->start_meter) {
                        $diff = (float) $next->start_meter - (float) $current->start_meter;
                        $current->update([
                            'end_meter' => $next->start_meter,
                            'operating_hours' => $diff,
                        ]);
                        $totalUpdated++;
                    }
                }
            }
        }

        $this->info("Rekalkulasi selesai. Sebanyak {$totalUpdated} entri timesheet berhasil diperbarui.");
        return self::SUCCESS;
    }
}
