<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\LowStockAlertOpened;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockAlert;
use App\Models\StockUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\StockAccess;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonInterface;
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
        ?CarbonInterface $occurredAt = null,
        ?array $unitIds = null,
    ): StockMovement {
        $type = $this->movementType($movementType);
        $delta = $this->parseQuantity($quantityDelta);
        $product = $this->validatedProduct($product);
        $this->validateLocation($locationType, $locationId);
        $this->validateSign($type, $delta);

        if (! StockAccess::canWrite($user, $locationType, $locationId)) {
            throw new RuntimeException('You cannot write stock at this location.');
        }

        if ($product->tracking_type === 'serial') {
            return $this->recordSerialMovement($user, $product, $locationType, $locationId, $type, $delta, $reference, $notes, $batchUuid, $occurredAt, $unitIds);
        }

        return DB::transaction(function () use ($user, $product, $locationType, $locationId, $type, $delta, $reference, $notes, $batchUuid, $occurredAt) {
            $level = $this->lockedLevel($product->id, $locationType, $locationId);
            $available = $this->toThousandths($level->quantity);
            $after = $available + $delta;

            if ($after < 0 && ! config('stock.allow_negative', false)) {
                throw new InsufficientStockException($product->id, $locationType, $locationId, $available, abs($delta));
            }

            $level->quantity = $this->formatQuantity($after);
            $level->save();
            $this->syncAlertForLevel($level);

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
                'occurred_at' => $occurredAt ?? now(),
            ]);
        });
    }

    public function recordOpening(
        User $user,
        Product $product,
        string $locationType,
        int $locationId,
        string|int|float $quantity,
        ?CarbonInterface $occurredAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->validateLocation($locationType, $locationId);
        $product = $this->validatedProduct($product);
        if ($product->tracking_type === 'serial') {
            throw new InvalidArgumentException('Serial products require serial numbers or explicit system-code opening.');
        }
        return DB::transaction(function () use ($user, $product, $locationType, $locationId, $quantity, $occurredAt, $notes): StockMovement {
            if (StockMovement::query()->where('product_id', $product->id)->where('location_type', $locationType)->where('location_id', $locationId)->exists()) {
                throw new InvalidArgumentException('Opening stock already exists. Use an adjustment for corrections.');
            }
            return $this->recordMovement($user, $product, $locationType, $locationId, StockMovementType::OPENING, $quantity, null, $notes, null, $occurredAt);
        });
    }

    /**
             * Open serial-tracked stock. System codes are only created without
             * manufacturer serials when explicitly enabled by the caller.
             *
             * @return array{movement: StockMovement, units: \Illuminate\Support\Collection<int, StockUnit>}
             */
    public function recordSerialOpening(
                User $user,
                Product $product,
                string $locationType,
                int $locationId,
                array $serialNumbers = [],
                int $quantity = 0,
                bool $allowSystemCodes = false,
                ?CarbonInterface $occurredAt = null,
                ?string $notes = null,
            ): array {
                $product = $this->validatedProduct($product);
                if ($product->tracking_type !== 'serial') {
                    throw new InvalidArgumentException('Serial opening is only available for serial-tracked products.');
                }
                if (! StockAccess::canWrite($user, $locationType, $locationId)) {
                    throw new RuntimeException('You cannot write stock at this location.');
                }
                $serialNumbers = array_values(array_filter(array_map('trim', $serialNumbers), fn ($serial) => $serial !== ''));
                if (count($serialNumbers) !== count(array_unique($serialNumbers))) {
                    throw new InvalidArgumentException('Duplicate serial numbers are not allowed.');
                }
                if ($serialNumbers === [] && (! $allowSystemCodes || $quantity < 1)) {
                    throw new InvalidArgumentException('Provide serial numbers or explicitly enable system codes with a quantity.');
                }
                $count = $serialNumbers === [] ? $quantity : count($serialNumbers);
                if ($count < 1) {
                    throw new InvalidArgumentException('At least one unit is required.');
                }

                return DB::transaction(function () use ($user, $product, $locationType, $locationId, $serialNumbers, $count, $occurredAt, $notes): array {
                    if (StockMovement::query()->where('product_id', $product->id)->where('location_type', $locationType)->where('location_id', $locationId)->exists()) {
                        throw new InvalidArgumentException('Opening stock already exists. Use an adjustment for corrections.');
                    }
                    $units = collect();
                    if ($serialNumbers !== []) {
                        $existing = StockUnit::query()->where('product_id', $product->id)->whereIn('serial_no', $serialNumbers)->exists();
                        if ($existing) {
                            throw new InvalidArgumentException('A serial number already exists for this product.');
                        }
                    }
                    foreach ($serialNumbers ?: array_fill(0, $count, null) as $serial) {
                        $units->push(StockUnit::create([
                            'product_id' => $product->id,
                            'serial_no' => $serial,
                            'unit_code' => 'pending-'.str()->uuid(),
                            'status' => 'in_stock',
                            'location_type' => $locationType,
                            'location_id' => $locationId,
                            'received_at' => $occurredAt ?? now(),
                        ]));
                    }
                    foreach ($units as $unit) {
                        $unit->update(['unit_code' => StockUnit::unitCodeForId($unit->id)]);
                    }
                    $movement = $this->createSerialMovement($user, $product, $locationType, $locationId, StockMovementType::OPENING, $count * 1000, $units, null, $notes, null, $occurredAt);
                    return ['movement' => $movement, 'units' => $units];
                });
            }

    private function recordSerialMovement(
                User $user,
                Product $product,
                string $locationType,
                int $locationId,
                StockMovementType $type,
                int $delta,
                ?array $reference,
                ?string $notes,
                ?string $batchUuid,
                ?CarbonInterface $occurredAt,
                ?array $unitIds,
            ): StockMovement {
                if ($unitIds === null || $unitIds === []) {
                    throw new InvalidArgumentException('Serial stock movements require exact unit ids.');
                }

                if ($delta % 1000 !== 0 || abs($delta) / 1000 !== count($unitIds)) {
                    throw new InvalidArgumentException('Serial movement quantity must equal the whole unit count.');
                }

                return DB::transaction(function () use ($user, $product, $locationType, $locationId, $type, $delta, $reference, $notes, $batchUuid, $occurredAt, $unitIds): StockMovement {
                    $units = StockUnit::query()->where('product_id', $product->id)->whereIn('id', $unitIds)->lockForUpdate()->get();
                    if ($units->count() !== count(array_unique($unitIds)) || $units->contains(fn (StockUnit $unit) => $unit->status !== 'in_stock' || $unit->location_type !== $locationType || (int) $unit->location_id !== $locationId)) {
                        throw new InvalidArgumentException('Every unit must be in stock at the source location.');
                    }
                    $movement = $this->createSerialMovement($user, $product, $locationType, $locationId, $type, $delta, $units, $reference, $notes, $batchUuid, $occurredAt);
                    $outflow = $delta < 0 || in_array($type, [StockMovementType::SALE, StockMovementType::TRANSFER_OUT, StockMovementType::RETURN_OUT], true);
                    $units->each(fn (StockUnit $unit) => $unit->update([
                        'status' => $outflow ? 'sold' : 'in_stock',
                        'location_type' => $outflow ? null : $locationType,
                        'location_id' => $outflow ? null : $locationId,
                    ]));
                    return $movement;
                });
            }

    private function createSerialMovement(
                User $user,
                Product $product,
                string $locationType,
                int $locationId,
                StockMovementType $type,
                int $delta,
                $units,
                ?array $reference,
                ?string $notes,
                ?string $batchUuid,
                ?CarbonInterface $occurredAt,
            ): StockMovement {
                $level = $this->lockedLevel($product->id, $locationType, $locationId);
                $count = StockUnit::query()->inStockAt($locationType, $locationId)->where('product_id', $product->id)->count();
                $after = $delta > 0 ? $count : $count + intdiv($delta, 1000);
                if ($after < 0) {
                    throw new InsufficientStockException($product->id, $locationType, $locationId, $count * 1000, abs($delta));
                }
                $level->quantity = $this->formatQuantity($after * 1000);
                $level->save();
                $this->syncAlertForLevel($level);
                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'location_type' => $locationType,
                    'location_id' => $locationId,
                    'movement_type' => $type->value,
                    'quantity_delta' => $this->formatQuantity($delta),
                    'balance_after' => $this->formatQuantity($after * 1000),
                    'reference_type' => $reference['type'] ?? null,
                    'reference_id' => $reference['id'] ?? null,
                    'batch_uuid' => $batchUuid,
                    'notes' => $notes,
                    'performed_by' => $user->id,
                    'occurred_at' => $occurredAt ?? now(),
                ]);
                $movement->units()->attach($units->pluck('id'));
                return $movement;
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
        ?array $unitIds = null,
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

        return DB::transaction(function () use ($user, $product, $fromType, $fromId, $toType, $toId, $amount, $reference, $notes, $unitIds) {
            $batch = (string) str()->uuid();
            if ($product->tracking_type === 'serial') {
                if ($unitIds === null || count($unitIds) !== (int) ($amount / 1000)) {
                    throw new InvalidArgumentException('Serial transfers require exact unit ids.');
                }
                $units = StockUnit::query()->where('product_id', $product->id)->whereIn('id', $unitIds)
                    ->lockForUpdate()->get();
                if ($units->count() !== count(array_unique($unitIds)) || $units->contains(fn (StockUnit $unit) => $unit->status !== 'in_stock' || $unit->location_type !== $fromType || (int) $unit->location_id !== $fromId)) {
                    throw new InvalidArgumentException('Every transfer unit must be in stock at the source location.');
                }
                $out = $this->createSerialMovement($user, $product, $fromType, $fromId, StockMovementType::TRANSFER_OUT, -$amount, $units, $reference, $notes, $batch, null);
                $units->each(fn (StockUnit $unit) => $unit->update(['status' => 'in_transit', 'location_type' => null, 'location_id' => null]));
                $units->each(fn (StockUnit $unit) => $unit->update(['status' => 'in_stock', 'location_type' => $toType, 'location_id' => $toId]));
                $in = $this->createSerialMovement($user, $product, $toType, $toId, StockMovementType::TRANSFER_IN, $amount, $units, $reference, $notes, $batch, null);
                return [$out, $in];
            }
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

            $serialProducts = Product::query()->where('tracking_type', 'serial')->pluck('id');
            foreach (StockUnit::query()->whereIn('product_id', $serialProducts)->where('status', 'in_stock')
                ->selectRaw('product_id, location_type, location_id, COUNT(*) as unit_count')
                ->groupBy('product_id', 'location_type', 'location_id')->get() as $unitGroup) {
                $key = "{$unitGroup->product_id}:{$unitGroup->location_type}:{$unitGroup->location_id}";
                $level = StockLevel::query()->where('product_id', $unitGroup->product_id)
                    ->where('location_type', $unitGroup->location_type)->where('location_id', $unitGroup->location_id)->first();
                if (! $level || $this->toThousandths($level->quantity) !== ((int) $unitGroup->unit_count * 1000)) {
                    $mismatches++;
                    if (! $dryRun) {
                        StockLevel::updateOrCreate(
                            ['product_id' => $unitGroup->product_id, 'location_type' => $unitGroup->location_type, 'location_id' => $unitGroup->location_id],
                            ['quantity' => $this->formatQuantity((int) $unitGroup->unit_count * 1000)],
                        );
                    }
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

    public function syncAlertForLevel(StockLevel $stockLevel): void
    {
        DB::transaction(function () use ($stockLevel): void {
            $level = StockLevel::query()
                ->whereKey($stockLevel->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($level->location_type !== 'branch') {
                return;
            }

            $product = $level->product()->firstOrFail();
            $quantity = $this->toThousandths($level->quantity);
            $minimum = $level->min_quantity ?? $product->reorder_level;
            $open = StockAlert::query()
                ->where('product_id', $product->id)
                ->where('branch_id', $level->location_id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($minimum === null) {
                if ($open) {
                    $open->update(['status' => 'resolved', 'open_marker' => null, 'resolved_at' => now()]);
                }
                return;
            }

            $minimumThousandths = $this->toThousandths($minimum);
            if ($quantity > $minimumThousandths) {
                if ($open) {
                    $open->update(['status' => 'resolved', 'open_marker' => null, 'resolved_at' => now()]);
                }
                return;
            }

            if ($open) {
                $open->update([
                    'quantity_at_alert' => $this->formatQuantity($quantity),
                    'minimum_at_alert' => $this->formatQuantity($minimumThousandths),
                ]);
                return;
            }

            $branch = Branch::findOrFail($level->location_id);
            $alert = StockAlert::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'warehouse_id' => $branch->warehouse_id,
                'quantity_at_alert' => $this->formatQuantity($quantity),
                'minimum_at_alert' => $this->formatQuantity($minimumThousandths),
                'status' => 'open',
                'open_marker' => 'open',
                'opened_at' => now(),
                'last_notified_at' => now(),
            ]);
            // ShouldDispatchAfterCommit defers this until the outermost transaction commits.
            event(new LowStockAlertOpened($alert));
        });
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
