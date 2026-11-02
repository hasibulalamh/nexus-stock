<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly string $locationType,
        public readonly int $locationId,
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct('Insufficient stock for this movement.');
    }
}
