<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\StockAccess;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    public function recordMovement(
        User $user,
        Product $product,
        string $locationType,
        int $locationId,
        string $movementType,
        string|int|float $quantityDelta,
        ?string $notes = null,
    ): StockMovement {
        $this->validateLocationType($locationType);
        $delta = $this->decimal($quantityDelta);

        if (! $product->track_stock) {
            throw new InvalidArgumentException('Stock is not tracked for this product.');
        }
        if ($delta == 0.0) {
            throw new InvalidArgumentException('Stock movement quantity must not be zero.');
        }
        if (! StockAccess::canWrite($user, $locationType, $locationId)) {
            throw new RuntimeException('You cannot write stock at this location.');
        }

        return DB::transaction(function () use ($user, $product, $locationType, $locationId, $movementType, $delta, $notes) {
            $level = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('location_type', $locationType)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (! $level) {
                $level = StockLevel::create([
                    'product_id' => $product->id,
                    'location_type' => $locationType,
                    'location_id' => $locationId,
                    'quantity' => 0,
                ]);
            }

            $level->quantity = (float) $level->quantity + $delta;
            $level->save();

            return StockMovement::create([
                'product_id' => $product->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'movement_type' => $movementType,
                'quantity_delta' => $delta,
                'notes' => $notes,
                'performed_by' => $user->id,
            ]);
        });
    }

    public function rebuild(): int
    {
        return DB::transaction(function (): int {
            StockLevel::query()->delete();

            $count = 0;
            StockMovement::query()
                ->select('product_id', 'location_type', 'location_id')
                ->selectRaw('SUM(quantity_delta) AS quantity')
                ->groupBy('product_id', 'location_type', 'location_id')
                ->havingRaw('SUM(quantity_delta) <> 0')
                ->get()
                ->each(function ($row) use (&$count): void {
                    StockLevel::create([
                        'product_id' => $row->product_id,
                        'location_type' => $row->location_type,
                        'location_id' => $row->location_id,
                        'quantity' => $row->quantity,
                    ]);
                    $count++;
                });

            return $count;
        });
    }

    private function validateLocationType(string $locationType): void
    {
        if (! in_array($locationType, ['branch', 'warehouse'], true)) {
            throw new InvalidArgumentException('Stock location must be branch or warehouse.');
        }
    }

    private function decimal(string|int|float $value): float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            throw new InvalidArgumentException('Stock movement quantity must be numeric.');
        }

        return (float) $value;
    }
}
