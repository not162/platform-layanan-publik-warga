<?php

namespace App\Console\Commands;

use App\Services\ResidentDueService;
use Illuminate\Console\Command;

class MarkOverdueDues extends Command
{
    protected $signature = 'app:mark-overdue-dues';

    protected $description = 'Menandai tagihan iuran UNPAID yang melewati jatuh tempo sebagai OVERDUE (dijadwalkan harian)';

    public function handle(ResidentDueService $service): int
    {
        $count = $service->markOverdueDues();

        $this->info("{$count} tagihan iuran ditandai terlambat (OVERDUE).");

        return self::SUCCESS;
    }
}
