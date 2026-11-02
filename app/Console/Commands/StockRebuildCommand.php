<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class StockRebuildCommand extends Command
{
    protected $signature = 'stock:rebuild';
    protected $description = 'Rebuild stock levels from the immutable movement ledger';

    public function handle(StockService $stockService): int
    {
        $count = $stockService->rebuild();
        $this->info("Rebuilt {$count} stock levels.");

        return self::SUCCESS;
    }
}
