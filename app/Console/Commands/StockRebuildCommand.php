<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class StockRebuildCommand extends Command
{
    protected $signature = 'stock:rebuild {--dry-run} {--repair-chain}';
    protected $description = 'Rebuild stock levels from the immutable movement ledger';

    public function handle(StockService $stockService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = $stockService->rebuildWithReport($dryRun, (bool) $this->option('repair-chain'));
        $this->line(($dryRun ? 'Dry run: ' : '').sprintf(
            'fixed=%d created=%d removed=%d mismatches=%d',
            $summary['fixed'], $summary['created'], $summary['removed'], $summary['mismatches']
        ));

        return ((! $this->option('repair-chain') && $summary['mismatches'] > 0)
            || ($dryRun && ($summary['fixed'] > 0 || $summary['created'] > 0 || $summary['removed'] > 0)))
            ? self::FAILURE : self::SUCCESS;
    }
}
