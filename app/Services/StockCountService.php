<?php

namespace App\Services;

use App\Events\AdjustmentChanged;
use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockCountService
{
    public function start(User $user, array $data): StockCount
    {
        $this->requirePermission($user, 'counts.create');
        $type = (string) ($data['location_type'] ?? '');
        $locationId = (int) ($data['location_id'] ?? 0);
        $this->requireLocation($type, $locationId);
        $this->requireWrite($user, $type, $locationId);

        $count = DB::transaction(function () use ($user, $data, $type, $locationId): StockCount {
            $active = StockCount::query()->where('location_type', $type)->where('location_id', $locationId)
                ->whereIn('status', ['open', 'counting', 'submitted'])->lockForUpdate()->exists();
            if ($active) {
                throw new RuntimeException('An open stock count already exists for this location.');
            }

            $levels = StockLevel::query()->with('product')
                ->where('location_type', $type)->where('location_id', $locationId)
                ->orderBy('product_id')->get()
                ->filter(fn (StockLevel $level) => $level->product
                    && ! $level->product->trashed()
                    && $level->product->track_stock
                    && $level->product->tracking_type !== 'serial');
            if ($levels->isEmpty()) {
                throw new InvalidArgumentException('There are no quantity-tracked stock levels at this location to count.');
            }

            $count = StockCount::create([
                'count_no' => DocumentNumber::next(config('stock.documents.count_prefix', 'CNT')),
                'location_type' => $type,
                'location_id' => $locationId,
                'status' => 'open',
                'snapshot_at' => now(),
                'notes' => $data['notes'] ?? null,
                'requested_by' => $user->id,
            ]);
            foreach ($levels as $level) {
                $count->lines()->create([
                    'product_id' => $level->product_id,
                    'system_qty' => $level->quantity,
                ]);
            }

            return $count->load('lines.product');
        });

        activity()->causedBy($user)->performedOn($count)->event('created')->log('Stock count started');

        return $count;
    }

    /**
     * @param  array<int, string|int|float>  $counted  keyed by stock count line id
     */
    public function submit(User $user, StockCount $count, array $counted): StockCount
    {
        $this->requirePermission($user, 'counts.create');
        $this->requireWrite($user, $count->location_type, (int) $count->location_id);

        $submitted = DB::transaction(function () use ($user, $count, $counted): StockCount {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if (! in_array($locked->status, ['open', 'counting'], true)) {
                throw new RuntimeException('Only an open count can be submitted.');
            }
            $locked->load('lines.product');
            $stock = app(StockService::class);

            foreach ($locked->lines as $line) {
                if (! array_key_exists($line->id, $counted)) {
                    throw new InvalidArgumentException('Enter a counted quantity for every line.');
                }
                $countedQty = $stock->parseForRequest($counted[$line->id]);
                if ($countedQty < 0) {
                    throw new InvalidArgumentException('Counted quantity cannot be negative.');
                }
                $system = $stock->parseForRequest($line->system_qty);
                $line->update([
                    'counted_qty' => $stock->formatQuantityValue($countedQty),
                    'variance' => $stock->formatQuantityValue($countedQty - $system),
                    'drift_qty' => $stock->formatQuantityValue(
                        $stock->parseForRequest($line->currentSystemQty()) - $system,
                    ),
                ]);
            }

            $locked->update(['status' => 'submitted']);
            $variances = $locked->lines()->where('variance', '!=', 0)->with('product')->get();

            if ($variances->isEmpty()) {
                $locked->update(['status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now()]);
            } else {
                $adjustment = StockAdjustment::create([
                    'adj_no' => DocumentNumber::next(config('stock.documents.adjustment_prefix', 'ADJ')),
                    'location_type' => $locked->location_type,
                    'location_id' => $locked->location_id,
                    'reason' => 'count_correction',
                    'status' => 'pending',
                    'requested_by' => $user->id,
                    'notes' => 'Stock count '.$locked->count_no,
                    'stock_count_id' => $locked->id,
                ]);
                foreach ($variances as $line) {
                    $adjustment->lines()->create([
                        'product_id' => $line->product_id,
                        'delta' => $line->variance,
                    ]);
                }
                $locked->update(['adjustment_id' => $adjustment->id]);
                event(new AdjustmentChanged($adjustment->load('lines', 'requester'), 'submitted', $user->id));
            }

            return $locked->fresh(['lines.product', 'adjustment', 'requester', 'reviewer']);
        });

        activity()->causedBy($user)->performedOn($submitted)->event('submitted')->log('Stock count submitted');

        return $submitted;
    }

    public function approve(User $user, StockCount $count): StockCount
    {
        $this->requirePermission($user, 'counts.approve');
        $this->requireWrite($user, $count->location_type, (int) $count->location_id);

        $approved = DB::transaction(function () use ($user, $count): StockCount {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if ($locked->status !== 'submitted') {
                throw new RuntimeException('Only submitted counts can be approved.');
            }
            $locked->load('lines');
            $stock = app(StockService::class);
            $drift = 0;
            foreach ($locked->lines as $line) {
                $difference = $stock->parseForRequest($line->currentSystemQty()) - $stock->parseForRequest($line->system_qty);
                if ($difference !== 0) {
                    $drift++;
                }
            }
            if ($drift > 0 && ! $locked->drift_acknowledged && config('stock.counts.require_drift_ack', true)) {
                throw new InvalidArgumentException('Stock moved after the snapshot ('.$drift.' line(s)). Re-count or acknowledge the drift first.');
            }

            if ($locked->adjustment_id) {
                $adjustment = StockAdjustment::query()->lockForUpdate()->find($locked->adjustment_id);
                if (! $adjustment || $adjustment->status !== 'pending') {
                    throw new RuntimeException('The linked adjustment is no longer pending.');
                }
                app(AdjustmentService::class)->approveForCount($user, $adjustment);
            }

            $locked->update(['status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now()]);

            return $locked->fresh(['lines.product', 'adjustment', 'requester', 'reviewer']);
        });

        activity()->causedBy($user)->performedOn($approved)->event('approved')->log('Stock count approved');

        return $approved;
    }

    public function acknowledgeDrift(User $user, StockCount $count): StockCount
    {
        $this->requirePermission($user, 'counts.create');
        $this->requireWrite($user, $count->location_type, (int) $count->location_id);

        $acknowledged = DB::transaction(function () use ($count): StockCount {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if ($locked->status !== 'submitted') {
                throw new RuntimeException('Only submitted counts can acknowledge drift.');
            }
            $stock = app(StockService::class);
            foreach ($locked->lines()->with('product')->get() as $line) {
                $line->update([
                    'drift_qty' => $stock->formatQuantityValue(
                        $stock->parseForRequest($line->currentSystemQty()) - $stock->parseForRequest($line->system_qty),
                    ),
                ]);
            }
            $locked->update(['drift_acknowledged' => true]);

            return $locked->fresh(['lines.product']);
        });

        activity()->causedBy($user)->performedOn($acknowledged)->event('acknowledged')->log('Stock count drift acknowledged');

        return $acknowledged;
    }

    public function cancel(User $user, StockCount $count): StockCount
    {
        $this->requirePermission($user, 'counts.create');
        if ((int) $count->requested_by !== (int) $user->id) {
            throw new RuntimeException('Only the requester can cancel this count.');
        }
        $this->requireWrite($user, $count->location_type, (int) $count->location_id);

        $cancelled = DB::transaction(function () use ($count): StockCount {
            $locked = StockCount::query()->lockForUpdate()->findOrFail($count->id);
            if (! in_array($locked->status, ['open', 'counting', 'submitted'], true)) {
                throw new RuntimeException('This count can no longer be cancelled.');
            }
            if ($locked->adjustment_id) {
                StockAdjustment::query()->whereKey($locked->adjustment_id)
                    ->where('status', 'pending')->update(['status' => 'cancelled']);
            }
            $locked->update(['status' => 'cancelled']);

            return $locked;
        });

        activity()->causedBy($user)->performedOn($cancelled)->event('cancelled')->log('Stock count cancelled');

        return $cancelled;
    }

    public function visibleTo(User $user): Builder
    {
        return StockCount::query()->visibleTo($user);
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
