<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
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
        StockMovementType|string $movementType,
        string|int|float $quantityDelta,
        ?array $reference = null,
        ?string $notes = null,
        ?string $batchUuid = null,
    ): StockMovement {
        $type = $this->movementType($movementType);
        $delta = $this->parseQuantity($quantityDelta);
        $product = $this->validatedProduct($product);
        $this->validateLocation($locationType, $locationId);
        $this->validateSign($type, $delta);

        if (! StockAccess::canWrite($user, $locationType, $locationId)) {
            throw new RuntimeException('You cannot write stock at this location.');
        }

        return DB::transaction(function () use ($user, $product, $locationType, $locationId, $type, $delta, $reference, $notes, $batchUuid) {
            $level = $this->lockedLevel($product->id, $locationType, $locationId);
            $available = $this->toThousandths($level->quantity);
            $after = $available + $delta;

            if ($after < 0 && ! config('stock.allow_negative', false)) {
                throw new InsufficientStockException($product->id, $locationType, $locationId, $available, abs($delta));
            }

            $level->quantity = $this->formatQuantity($after);
            $level->save();

            return StockMovement::create([
                'product_id' => $product->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'movement_type' => $type->value,
                'quantity_delta' => $this->formatQuantity($delta),
                'balance_after' => $this->formatQuantity($after),
                'reference_type' => $reference['type'] ?? null,
                'reference_id' => $reference['id'] ?? null,
                'batch_uuid' => $batchUuid,
                'notes' => $notes,
                'performed_by' => $user->id,
            ]);
        });
    }

    /**
     * Transfer stock atomically. The sender must write the source; the
     * destination only needs to exist because receipt is recorded on its behalf.
     */
    public function transfer(
        User $user,
        Product $product,
        string $fromType,
        int $fromId,
        string $toType,
        int $toId,
        string|int|float $quantity,
        ?array $reference = null,
        ?string $notes = null,
    ): array {
        $amount = $this->parseQuantity($quantity);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }
        $this->validateLocation($fromType, $fromId);
        $this->validateLocation($toType, $toId);
        if ($fromType === $toType && $fromId === $toId) {
            throw new InvalidArgumentException('A transfer source and destination must differ.');
        }
        if (! StockAccess::canWrite($user, $fromType, $fromId)) {
            throw new RuntimeException('You cannot write stock at the transfer source.');
        }

        return DB::transaction(function () use ($user, $product, $fromType, $fromId, $toType, $toId, $amount, $reference, $notes) {
            $batch = (string) str()->uuid();
            $out = $this->recordMovement($user, $product, $fromType, $fromId, StockMovementType::TRANSFER_OUT, $this->formatQuantity(-$amount), $reference, $notes, $batch);
            $in = $this->recordMovement($user, $product, $toType, $toId, StockMovementType::TRANSFER_IN, $this->formatQuantity($amount), $reference, $notes, $batch);

            return [$out, $in];
        });
    }

    /**
     * Rebuild levels without replacing unchanged rows.
     *
     * @return array{fixed:int,created:int,removed:int,mismatches:int}
     */
    public function rebuild(): int
    {
        $summary = $this->rebuildWithReport();

        return $summary['created'] + $summary['fixed'];
    }

    /**
     * @return array{fixed:int,created:int,removed:int,mismatches:int}
     */
    public function rebuildWithReport(bool $dryRun = false, bool $repairChain = false): array
    {
        $work = function () use ($dryRun, $repairChain): array {
            $expected = [];
            $mismatches = 0;
            $movements = StockMovement::query()->orderBy('product_id')->orderBy('location_type')
                ->orderBy('location_id')->orderBy('occurred_at')->orderBy('id')->get();

            foreach ($movements as $movement) {
                $key = "{$movement->product_id}:{$movement->location_type}:{$movement->location_id}";
                $expected[$key] = ($expected[$key] ?? 0) + $this->toThousandths($movement->quantity_delta);
                if ($this->toThousandths($movement->balance_after) !== $expected[$key]) {
                    $mismatches++;
                    if (! $dryRun && $repairChain) {
                        DB::table('stock_movements')->where('id', $movement->id)
                            ->update(['balance_after' => $this->formatQuantity($expected[$key])]);
                    }
                }
            }

            $existing = StockLevel::all()->keyBy(fn (StockLevel $level) => "{$level->product_id}:{$level->location_type}:{$level->location_id}");
            $fixed = $created = $removed = 0;
            foreach ($expected as $key => $quantity) {
                [$productId, $type, $locationId] = explode(':', $key);
                $level = $existing->get($key);
                if (! $level) {
                    if (! $dryRun) {
                        StockLevel::create(['product_id' => $productId, 'location_type' => $type, 'location_id' => $locationId, 'quantity' => $this->formatQuantity($quantity)]);
                    }
                    $created++;
                } elseif ($this->toThousandths($level->quantity) !== $quantity) {
                    if (! $dryRun) {
                        $level->update(['quantity' => $this->formatQuantity($quantity)]);
                    }
                    $fixed++;
                }
            }
            foreach ($existing as $key => $level) {
                if (! array_key_exists($key, $expected)) {
                    if (! $dryRun) {
                        $level->delete();
                    }
                    $removed++;
                }
            }

            return compact('fixed', 'created', 'removed', 'mismatches');
        };

        $summary = $dryRun ? $work() : DB::transaction($work);
        return $summary;
    }

    private function lockedLevel(int $productId, string $locationType, int $locationId): StockLevel
    {
        StockLevel::query()->insertOrIgnore([
            'product_id' => $productId,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'quantity' => '0.000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StockLevel::query()->where('product_id', $productId)
            ->where('location_type', $locationType)->where('location_id', $locationId)
            ->lockForUpdate()->firstOrFail();
    }

    private function validatedProduct(Product $product): Product
    {
        $fresh = Product::withTrashed()->find($product->id);
        if (! $fresh || $fresh->trashed()) {
            throw new InvalidArgumentException('Product does not exist or is deleted.');
        }
        if (! $fresh->track_stock) {
            throw new InvalidArgumentException('Stock is not tracked for this product.');
        }
        return $fresh;
    }

    private function validateLocation(string $type, int $id): void
    {
        $this->validateLocationType($type);
        $model = $type === 'branch' ? Branch::find($id) : Warehouse::find($id);
        if (! $model) {
            throw new InvalidArgumentException('Stock location does not exist or is deleted.');
        }
    }

    private function movementType(StockMovementType|string $type): StockMovementType
    {
        try {
            return $type instanceof StockMovementType ? $type : StockMovementType::from($type);
        } catch (\ValueError) {
            throw new InvalidArgumentException('Invalid stock movement type.');
        }
    }

    private function validateSign(StockMovementType $type, int $delta): void
    {
        if (($type->requiresPositive() && $delta <= 0) || ($type->requiresNegative() && $delta >= 0)) {
            throw new InvalidArgumentException('Movement quantity has the wrong sign for its type.');
        }
    }

    private function validateLocationType(string $locationType): void
    {
        if (! in_array($locationType, ['branch', 'warehouse'], true)) {
            throw new InvalidArgumentException('Stock location must be branch or warehouse.');
        }
    }

    private function parseQuantity(string|int|float $value): int
    {
        $text = trim((string) $value);
        if (! preg_match('/^[+-]?\d+(?:\.\d{1,3})?$/', $text)) {
            throw new InvalidArgumentException('Quantity must be numeric with at most 3 decimal places.');
        }
        $negative = str_starts_with($text, '-');
        $text = ltrim($text, '+-');
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        $thousandths = ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');
        return $negative ? -$thousandths : $thousandths;
    }

    private function toThousandths(string|int|float $value): int
    {
        return $this->parseQuantity((string) $value);
    }

    private function formatQuantity(int $thousandths): string
    {
        $sign = $thousandths < 0 ? '-' : '';
        $absolute = abs($thousandths);
        return sprintf('%s%d.%03d', $sign, intdiv($absolute, 1000), $absolute % 1000);
    }
}
