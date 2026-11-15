<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class StockArrivedAtLocation implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $productId,
        public readonly string $locationType,
        public readonly int $locationId,
        public readonly ?int $actorId = null,
    ) {
    }
}
