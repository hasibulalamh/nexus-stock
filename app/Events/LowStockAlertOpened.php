<?php

namespace App\Events;

use App\Models\StockAlert;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LowStockAlertOpened implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly StockAlert $alert)
    {
    }
}
