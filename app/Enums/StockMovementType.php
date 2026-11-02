<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OPENING = 'opening';
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case ADJUSTMENT = 'adjustment';
    case RETURN_IN = 'return_in';
    case RETURN_OUT = 'return_out';

    public function requiresPositive(): bool
    {
        return in_array($this, [self::OPENING, self::PURCHASE, self::TRANSFER_IN, self::RETURN_IN], true);
    }

    public function requiresNegative(): bool
    {
        return in_array($this, [self::SALE, self::TRANSFER_OUT, self::RETURN_OUT], true);
    }
}
