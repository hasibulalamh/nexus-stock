<?php

namespace App\Events;

use App\Models\StockAdjustment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class AdjustmentChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly StockAdjustment $adjustment,
        public readonly string $action,
        public readonly ?int $actorId = null,
    ) {
    }
}
