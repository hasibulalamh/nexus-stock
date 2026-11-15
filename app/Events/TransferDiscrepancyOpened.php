<?php

namespace App\Events;

use App\Models\TransferDiscrepancy;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class TransferDiscrepancyOpened implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly TransferDiscrepancy $discrepancy,
        public readonly ?int $actorId = null,
    ) {
    }
}
