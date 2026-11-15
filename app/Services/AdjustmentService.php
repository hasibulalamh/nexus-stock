<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\AdjustmentChanged;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AdjustmentService
{
    public function submit(User $user, array $data): StockAdjustment
    {
        $this->requirePermission($user, 'adjustments.create');
        $type = (string) ($data['location_type'] ?? '');
        $locationId = (int) ($data['location_id'] ?? 0);
        $this->requireLocation($type, $locationId);
        $this->requireWrite($user, $type, $locationId);
        $reason = (string) ($data['reason'] ?? '');
        if (! in_array($reason, StockAdjustment::REASONS, true)) {
            throw new InvalidArgumentException('Select a valid adjustment reason.');
        }
        $lines = $this->normaliseLines($data['lines'] ?? []);
        if ($lines === []) {
            throw new InvalidArgumentException('At least one product line is required.');
        }

        $adjustment = DB::transaction(function () use ($user, $data, $type, $locationId, $reason, $lines): StockAdjustment {
            $adjustment = StockAdjustment::create([
                'adj_no' => DocumentNumber::next(config('stock.documents.adjustment_prefix', 'ADJ')),
                'location_type' => $type,
                'location_id' => $locationId,
                'reason' => $reason,
                'status' => 'pending',
                'requested_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);
            foreach ($lines as $line) {
                $adjustment->lines()->create($line);
            }

            return $adjustment->load(['lines.product', 'requester']);
        });

        activity()->causedBy($user)->performedOn($adjustment)->event('created')->log('Stock adjustment submitted');
        event(new AdjustmentChanged($adjustment, 'submitted', $user->id));

        return $adjustment;
    }

    public function approve(User $user, StockAdjustment $adjustment): StockAdjustment
    {
        $this->requirePermission($user, 'adjustments.approve');

        return $this->post($user, $adjustment);
    }

    /**
     * Approve the adjustment linked to a stock count using counts.approve.
     */
    public function approveForCount(User $user, StockAdjustment $adjustment): StockAdjustment
    {
        $this->requirePermission($user, 'counts.approve');

        return $this->post($user, $adjustment);
    }

    public function reject(User $user, StockAdjustment $adjustment, string $reason): StockAdjustment
    {
        $this->requirePermission($user, 'adjustments.approve');
        $this->requireWrite($user, $adjustment->location_type, (int) $adjustment->location_id);
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $rejected = DB::transaction(function () use ($user, $adjustment, $reason): StockAdjustment {
            $locked = StockAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending adjustments can be rejected.');
            }
            $locked->update([
                'status' => 'rejected', 'rejection_reason' => $reason,
                'reviewed_by' => $user->id, 'reviewed_at' => now(),
            ]);
            if ($locked->stock_count_id) {
                StockCount::query()->whereKey($locked->stock_count_id)->update(['status' => 'cancelled']);
            }

            return $locked->fresh(['lines.product', 'requester', 'reviewer']);
        });

        activity()->causedBy($user)->performedOn($rejected)->event('rejected')->log('Stock adjustment rejected');
        event(new AdjustmentChanged($rejected, 'rejected', $user->id));

        return $rejected;
    }

    public function cancel(User $user, StockAdjustment $adjustment): StockAdjustment
    {
        $this->requirePermission($user, 'adjustments.create');
        if ((int) $adjustment->requested_by !== (int) $user->id) {
            throw new RuntimeException('Only the requester can cancel this adjustment.');
        }
        $this->requireWrite($user, $adjustment->location_type, (int) $adjustment->location_id);

        $cancelled = DB::transaction(function () use ($adjustment): StockAdjustment {
            $locked = StockAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending adjustments can be cancelled.');
            }
            $locked->update(['status' => 'cancelled']);
            if ($locked->stock_count_id) {
                StockCount::query()->whereKey($locked->stock_count_id)->update(['status' => 'cancelled']);
            }

            return $locked;
        });

        activity()->causedBy($user)->performedOn($cancelled)->event('cancelled')->log('Stock adjustment cancelled');
        event(new AdjustmentChanged($cancelled, 'cancelled', $user->id));

        return $cancelled;
    }

    public function visibleTo(User $user): Builder
    {
        return StockAdjustment::query()->visibleTo($user);
    }

    private function post(User $user, StockAdjustment $adjustment): StockAdjustment
    {
        $this->requireWrite($user, $adjustment->location_type, (int) $adjustment->location_id);
        if (! config('stock.approval.allow_self_approval', true) && (int) $adjustment->requested_by === (int) $user->id) {
            throw new RuntimeException('You cannot approve your own adjustment.');
        }

        $approved = DB::transaction(function () use ($user, $adjustment): StockAdjustment {
            $locked = StockAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending adjustments can be approved.');
            }
            $locked->load('lines.product');
            $stock = app(StockService::class);
            $reference = ['type' => 'stock_adjustment', 'id' => $locked->id];

            foreach ($locked->lines as $line) {
                $product = Product::withTrashed()->findOrFail($line->product_id);
                if (! $product || $product->trashed() || ! $product->track_stock) {
                    throw new InvalidArgumentException('Every product must be stock-tracked.');
                }
                $delta = $stock->parseForRequest($line->delta);
                if ($delta === 0) {
                    throw new InvalidArgumentException('Adjustment quantities cannot be zero.');
                }

                if ($product->tracking_type === 'serial') {
                    if (abs($delta) % 1000 !== 0) {
                        throw new InvalidArgumentException('Serial adjustments must be whole units.');
                    }
                    if ($delta < 0) {
                        $unitIds = array_values(array_map('intval', $line->stock_unit_ids ?? []));
                        if (count($unitIds) !== intdiv(abs($delta), 1000)) {
                            throw new InvalidArgumentException('Select exactly the units being adjusted out.');
                        }
                        $unitStatus = in_array($locked->reason, ['damaged', 'expired'], true) ? 'damaged' : 'written_off';
                        $stock->removeSerialUnits(
                            $user, $product, $locked->location_type, (int) $locked->location_id,
                            $unitIds, $unitStatus, $reference, $locked->notes,
                        );
                    } else {
                        $serials = array_values(array_filter(array_map('trim', $line->serial_numbers ?? []), fn ($serial) => $serial !== ''));
                        if (count($serials) !== intdiv($delta, 1000)) {
                            throw new InvalidArgumentException('Provide exactly one serial number per added unit.');
                        }
                        $stock->recordSerialAdjustmentIn(
                            $user, $product, $locked->location_type, (int) $locked->location_id,
                            $serials, $reference, $locked->notes,
                        );
                    }
                } else {
                    $stock->recordMovement(
                        $user, $product, $locked->location_type, (int) $locked->location_id,
                        StockMovementType::ADJUSTMENT, $stock->formatQuantityValue($delta),
                        $reference, $locked->notes ?? ucfirst(str_replace('_', ' ', $locked->reason)),
                        (string) str()->uuid(),
                    );
                }
            }

            $locked->update(['status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now()]);
            if ($locked->stock_count_id) {
                StockCount::query()->whereKey($locked->stock_count_id)->update([
                    'status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now(),
                ]);
            }

            return $locked->fresh(['lines.product', 'requester', 'reviewer']);
        });

        activity()->causedBy($user)->performedOn($approved)->event('approved')->log('Stock adjustment approved');
        event(new AdjustmentChanged($approved, 'approved', $user->id));

        return $approved;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normaliseLines(array $lines): array
    {
        $stock = app(StockService::class);
        $normalised = [];
        $seen = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId < 1) {
                continue;
            }
            if (isset($seen[$productId])) {
                throw new InvalidArgumentException('A product may only appear once in an adjustment.');
            }
            $seen[$productId] = true;
            $product = Product::withTrashed()->find($productId);
            if (! $product || $product->trashed() || ! $product->track_stock) {
                throw new InvalidArgumentException('Every product must be stock-tracked.');
            }
            $delta = $stock->parseForRequest($line['delta'] ?? 0);
            if ($delta === 0) {
                throw new InvalidArgumentException('Adjustment quantities cannot be zero.');
            }
            $unitIds = array_values(array_filter((array) ($line['stock_unit_ids'] ?? []), fn ($id) => (int) $id > 0));
            $serials = array_values(array_filter(array_map('trim', (array) ($line['serial_numbers'] ?? [])), fn ($serial) => $serial !== ''));

            if ($product->tracking_type === 'serial') {
                if (abs($delta) % 1000 !== 0) {
                    throw new InvalidArgumentException('Serial adjustments must be whole units.');
                }
                if ($delta < 0 && count($unitIds) !== intdiv(abs($delta), 1000)) {
                    throw new InvalidArgumentException('Select one unit per unit being adjusted out.');
                }
                if ($delta > 0 && count($serials) !== intdiv($delta, 1000)) {
                    throw new InvalidArgumentException('Provide one serial number per added unit.');
                }
            } elseif ($unitIds !== [] || $serials !== []) {
                throw new InvalidArgumentException('Serial data is only valid for serial-tracked products.');
            }

            $normalised[] = [
                'product_id' => $productId,
                'delta' => $stock->formatQuantityValue($delta),
                'stock_unit_ids' => $unitIds === [] ? null : $unitIds,
                'serial_numbers' => $serials === [] ? null : $serials,
            ];
        }

        return $normalised;
    }

    private function requirePermission(User $user, string $permission): void
    {
        if (! $user->can($permission)) {
            throw new RuntimeException('You are not authorised to perform this action.');
        }
    }

    private function requireWrite(User $user, string $type, int $id): void
    {
        if (! StockAccess::canWrite($user, $type, $id)) {
            throw new RuntimeException('You cannot write stock at this location.');
        }
    }

    private function requireLocation(string $type, int $id): void
    {
        $exists = $type === 'branch'
            ? Branch::query()->whereKey($id)->exists()
            : ($type === 'warehouse' ? Warehouse::query()->whereKey($id)->exists() : false);
        if (! $exists) {
            throw new InvalidArgumentException('The selected stock location is not available.');
        }
    }
}
