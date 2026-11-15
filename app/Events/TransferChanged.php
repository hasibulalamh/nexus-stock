<?php

namespace App\Events;

use App\Models\Transfer;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class TransferChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly Transfer $transfer,
        public readonly string $action,
        public readonly ?int $actorId = null,
    ) {
    }
}
