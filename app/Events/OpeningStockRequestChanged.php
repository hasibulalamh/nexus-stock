<?php

namespace App\Events;

use App\Models\OpeningStockRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class OpeningStockRequestChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly OpeningStockRequest $request,
        public readonly string $action,
        public readonly ?int $actorId = null,
    ) {
    }
}
