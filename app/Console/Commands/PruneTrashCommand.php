<?php

namespace App\Console\Commands;

use App\Services\TrashService;
use Illuminate\Console\Command;

class PruneTrashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trash:prune {--days= : Override periode retensi hari (default sesuai pengaturan sistem)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan data di tong sampah yang telah melewati periode retensi otomatis.';

    /**
     * Execute the console command.
     */
    public function handle(TrashService $trashService): int
    {
        $daysOption = $this->option('days');
        $days = $daysOption !== null ? (int) $daysOption : $trashService->getRetentionDays();

        if ($days <= 0) {
            $this->info('Retensi otomatis tong sampah dinonaktifkan (0 hari / mode manual). Tidak ada data yang dihapus.');
            return Command::SUCCESS;
        }

        $this->info("Menjalankan pembersihan tong sampah dengan retensi: {$days} hari...");

        $prunedCount = $trashService->pruneOldTrash($days);

        $this->info("Pembersihan selesai. {$prunedCount} record berhasil dihapus permanen dari sistem.");

        return Command::SUCCESS;
    }
}
