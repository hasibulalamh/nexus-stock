<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\TransferChanged;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockUnit;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TransferService
{
    public function create(User $user, array $data): Transfer
    {
        $this->requirePermission($user, 'transfers.create');
        $type = ($data['type'] ?? Transfer::TRANSFER) === Transfer::RETURN ? Transfer::RETURN : Transfer::TRANSFER;
        [$fromType, $fromId, $toType, $toId] = $this->resolveLocations($type, $data);
        $this->assertRoute($type, $fromType, $fromId, $toType, $toId);
        $this->requireWrite($user, $fromType, $fromId);

        $reason = $type === Transfer::RETURN ? ($data['reason'] ?? null) : null;
        if ($type === Transfer::RETURN && ! in_array($reason, Transfer::RETURN_REASONS, true)) {
            throw new InvalidArgumentException('A valid return reason is required.');
        }
        $lines = $this->normaliseLines($data['lines'] ?? []);
        if ($lines === []) {
            throw new InvalidArgumentException('At least one product line is required.');
        }

        $transfer = DB::transaction(function () use ($user, $data, $type, $fromType, $fromId, $toType, $toId, $reason, $lines): Transfer {
            $transfer = Transfer::create([
                'transfer_no' => DocumentNumber::next(config('stock.documents.transfer_prefix', 'TRF')),
                'type' => $type,
                'from_type' => $fromType,
                'from_id' => $fromId,
                'to_type' => $toType,
                'to_id' => $toId,
                'status' => 'draft',
                'reason' => $reason,
                'stock_alert_id' => $data['stock_alert_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'batch_uuid' => (string) str()->uuid(),
                'requested_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $product = Product::withTrashed()->find($line['product_id']);
                if (! $product || $product->trashed() || $product->status !== 'active' || ! $product->track_stock) {
                    throw new InvalidArgumentException('Every product must be active and stock-tracked.');
                }

                if ($product->tracking_type === 'serial') {
                    $unitIds = array_values(array_unique(array_map('intval', $line['unit_ids'] ?? [])));
                    if ($unitIds === []) {
                        throw new InvalidArgumentException('Select the serial units to transfer.');
                    }
                    $units = StockUnit::query()->where('product_id', $product->id)->whereIn('id', $unitIds)
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($units->count() !== count($unitIds) || $units->contains(fn (StockUnit $unit) => $unit->status !== 'in_stock' || $unit->location_type !== $fromType || (int) $unit->location_id !== $fromId)) {
                        throw new InvalidArgumentException('Every serial unit must be in stock at the source location.');
                    }
                    $reserved = DB::table('transfer_item_units')
                        ->join('transfer_items', 'transfer_items.id', '=', 'transfer_item_units.transfer_item_id')
                        ->join('transfers', 'transfers.id', '=', 'transfer_items.transfer_id')
                        ->whereIn('transfers.status', ['draft', 'sent'])
                        ->whereIn('transfer_item_units.stock_unit_id', $unitIds)
                        ->exists();
                    if ($reserved) {
                        throw new InvalidArgumentException('A selected unit already belongs to another open transfer.');
                    }
                    $item = $transfer->items()->create([
                        'product_id' => $product->id,
                        'requested_qty' => app(StockService::class)->formatQuantityValue(count($unitIds) * 1000),
                        'sent_qty' => '0.000',
                        'received_qty' => '0.000',
                    ]);
                    foreach ($units as $unit) {
                        $item->units()->create(['stock_unit_id' => $unit->id]);
                    }
                    continue;
                }

                if (($line['unit_ids'] ?? []) !== []) {
                    throw new InvalidArgumentException('Serial units are only valid for serial-tracked products.');
                }
                $quantity = app(StockService::class)->parseForRequest($line['requested_qty'] ?? 0);
                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Requested quantity must be greater than zero.');
                }
                $transfer->items()->create([
                    'product_id' => $product->id,
                    'requested_qty' => app(StockService::class)->formatQuantityValue($quantity),
                    'sent_qty' => '0.000',
                    'received_qty' => '0.000',
                ]);
            }

            return $transfer->load(['items.product', 'items.stockUnits']);
        });

        activity()->causedBy($user)->performedOn($transfer)->event('created')->log('Transfer created');

        return $transfer;
    }

    public function send(User $user, Transfer $transfer): Transfer
    {
        $this->requirePermission($user, 'transfers.send');
        $this->requireWrite($user, $transfer->from_type, (int) $transfer->from_id);

        $updated = DB::transaction(function () use ($user, $transfer): Transfer {
            $locked = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'draft') {
                throw new RuntimeException('Only draft transfers can be sent.');
            }
            $this->assertRoute($locked->type, $locked->from_type, (int) $locked->from_id, $locked->to_type, (int) $locked->to_id);
            $locked->load('items');

            foreach ($locked->items as $item) {
                $product = Product::withTrashed()->findOrFail($item->product_id);
                $outType = $locked->type === Transfer::RETURN ? StockMovementType::RETURN_OUT : StockMovementType::TRANSFER_OUT;
                $reference = ['type' => 'transfer', 'id' => $locked->id];

                if ($product->tracking_type === 'serial') {
                    app(StockService::class)->dispatchSerialUnits(
                        $user, $product, $locked->from_type, (int) $locked->from_id,
                        $item->units()->pluck('stock_unit_id')->all(),
                        $reference, $locked->notes, $locked->batch_uuid, $outType,
                    );
                } else {
                    $delta = app(StockService::class)->parseForRequest($item->requested_qty);
                    if ($delta <= 0) {
                        throw new InvalidArgumentException('Transfer quantities must be positive.');
                    }
                    app(StockService::class)->recordMovement(
                        $user, $product, $locked->from_type, (int) $locked->from_id, $outType,
                        app(StockService::class)->formatQuantityValue(-$delta),
                        $reference, $locked->notes, $locked->batch_uuid,
                    );
                }
                $item->update(['sent_qty' => $item->requested_qty]);
            }

            $locked->update(['status' => 'sent', 'sent_by' => $user->id, 'sent_at' => now()]);

            return $locked->fresh(['items.product', 'requester', 'sender']);
        });

        activity()->causedBy($user)->performedOn($updated)->event('sent')->log('Transfer sent');
        event(new TransferChanged($updated, 'sent', $user->id));

        return $updated;
    }

    public function receive(User $user, Transfer $transfer): Transfer
    {
        return $this->receiveWithQuantities($user, $transfer, []);
    }

    /**
     * Receive a transfer. An empty $received map means a full receipt.
     *
     * @param  array<int, array{received_qty?: string|int|float, unit_ids?: array<int>}>  $received  keyed by transfer item id
     */
    public function receiveWithQuantities(User $user, Transfer $transfer, array $received): Transfer
    {
        $this->requirePermission($user, 'transfers.receive');
        $this->requireWrite($user, $transfer->to_type, (int) $transfer->to_id);

        $updated = DB::transaction(function () use ($user, $transfer, $received): Transfer {
            $locked = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'sent') {
                throw new RuntimeException('Only sent transfers can be received.');
            }
            $locked->load(['items.product', 'items.stockUnits']);

            foreach ($locked->items as $item) {
                $input = $received[$item->id] ?? [];
                $sentQty = app(StockService::class)->parseForRequest($item->sent_qty);
                $receivedQty = array_key_exists('received_qty', $input)
                    ? app(StockService::class)->parseForRequest($input['received_qty'])
                    : $sentQty;

                if ($receivedQty < 0 || $receivedQty > $sentQty) {
                    throw new InvalidArgumentException('Received quantity cannot exceed the sent quantity.');
                }
                if ($item->product->tracking_type === 'serial' && $receivedQty % 1000 !== 0) {
                    throw new InvalidArgumentException('Serial receipts must be whole units.');
                }

                $unitIds = $this->receiptUnitIds($item, $input, $receivedQty);
                if ($item->product->tracking_type === 'serial' && $unitIds !== []) {
                    // The checked units are the source of truth for serial receipt.
                    $receivedQty = count($unitIds) * 1000;
                    if ($receivedQty > $sentQty) {
                        throw new InvalidArgumentException('Received quantity cannot exceed the sent quantity.');
                    }
                }

                $inType = $locked->type === Transfer::RETURN ? StockMovementType::RETURN_IN : StockMovementType::TRANSFER_IN;
                $reference = ['type' => 'transfer', 'id' => $locked->id];

                if ($receivedQty > 0) {
                    if ($item->product->tracking_type === 'serial') {
                        app(StockService::class)->receiveSerialUnits(
                            $user, $item->product, $locked->to_type, (int) $locked->to_id,
                            $unitIds, $reference, $locked->notes, $locked->batch_uuid, $inType,
                        );
                    } else {
                        app(StockService::class)->recordMovement(
                            $user, $item->product, $locked->to_type, (int) $locked->to_id, $inType,
                            app(StockService::class)->formatQuantityValue($receivedQty),
                            $reference, $locked->notes, $locked->batch_uuid,
                        );
                    }
                }

                $item->update(['received_qty' => app(StockService::class)->formatQuantityValue($receivedQty)]);

                $this->recordDiscrepancies($user, $locked, $item, $sentQty, $receivedQty, $input);
            }

            $locked->update(['status' => 'received', 'received_by' => $user->id, 'received_at' => now()]);

            return $locked->fresh(['items.product', 'items.stockUnits', 'requester', 'receiver']);
        });

        activity()->causedBy($user)->performedOn($updated)->event('received')->log('Transfer received');
        event(new TransferChanged($updated, 'received', $user->id));

        return $updated;
    }

    public function cancel(User $user, Transfer $transfer): Transfer
    {
        $this->requirePermission($user, 'transfers.cancel');
        $this->requireWrite($user, $transfer->from_type, (int) $transfer->from_id);

        $cancelled = DB::transaction(function () use ($transfer): Transfer {
            $locked = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'draft') {
                throw new RuntimeException('Only draft transfers can be cancelled.');
            }
            $locked->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return $locked->fresh(['items.product']);
        });

        activity()->causedBy($user)->performedOn($cancelled)->event('cancelled')->log('Transfer cancelled');
        event(new TransferChanged($cancelled, 'cancelled', $user->id));

        return $cancelled;
    }

    public function visibleTo(User $user): Builder
    {
        return Transfer::query()->visibleTo($user);
    }

    /**
     * Reserve/create discrepancy rows for a partial or damaged receipt.
     * Implemented fully in the receiving-exceptions phase; this hook keeps
     * the receive flow single-pass.
     */
    private function recordDiscrepancies(User $user, Transfer $transfer, TransferItem $item, int $sentQty, int $receivedQty, array $input): void
    {
        // Extended in Phase B (transfer_discrepancies).
    }

    /**
     * @return array<int>
     */
    private function receiptUnitIds(TransferItem $item, array $input, int $receivedQty): array
    {
        $supplied = array_values(array_unique(array_map('intval', $input['unit_ids'] ?? [])));
        if ($supplied !== []) {
            $allowed = $item->stockUnits->pluck('id')->all();
            foreach ($supplied as $id) {
                if (! in_array($id, $allowed, true)) {
                    throw new InvalidArgumentException('Every receipt unit must belong to this transfer line.');
                }
            }

            return $supplied;
        }

        return $item->stockUnits->take(intdiv($receivedQty, 1000))->pluck('id')->all();
    }

    /**
     * @return array{0:string,1:int,2:string,3:int}
     */
    private function resolveLocations(string $type, array $data): array
    {
        if ($type === Transfer::RETURN) {
            $branch = Branch::query()->find($data['from_id'] ?? null);
            if (! $branch) {
                throw new InvalidArgumentException('Select the branch the stock is returning from.');
            }
            if (! $branch->warehouse_id) {
                throw new InvalidArgumentException('The branch has no supplying warehouse.');
            }

            return ['branch', (int) $branch->id, 'warehouse', (int) $branch->warehouse_id];
        }

        $warehouseId = (int) ($data['from_id'] ?? $data['warehouse_id'] ?? 0);
        $branchId = (int) ($data['to_id'] ?? $data['branch_id'] ?? 0);

        return ['warehouse', $warehouseId, 'branch', $branchId];
    }

    private function assertRoute(string $type, string $fromType, int $fromId, string $toType, int $toId): void
    {
        if ($fromType === $toType && $fromId === $toId) {
            throw new InvalidArgumentException('A transfer source and destination must differ.');
        }

        if ($type === Transfer::RETURN) {
            $branch = Branch::query()->whereKey($fromId)->first();
            $warehouse = Warehouse::query()->whereKey($toId)->first();
            if (! $branch || ! $warehouse || (int) $branch->warehouse_id !== (int) $warehouse->id) {
                throw new InvalidArgumentException('A return must go to the warehouse that supplies the branch.');
            }

            return;
        }

        $warehouse = Warehouse::query()->whereKey($fromId)->first();
        $branch = Branch::query()->whereKey($toId)->first();
        if (! $warehouse || ! $branch) {
            throw new InvalidArgumentException('Select a valid warehouse and branch.');
        }
        if ((int) $branch->warehouse_id !== (int) $warehouse->id) {
            throw new InvalidArgumentException('The destination branch is not supplied by the selected warehouse.');
        }
    }

    /**
     * @return array<int, array{product_id:int, requested_qty:mixed, unit_ids:array<int>}>
     */
    private function normaliseLines(array $lines): array
    {
        $normalised = [];
        $seen = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId < 1) {
                continue;
            }
            if (isset($seen[$productId])) {
                throw new InvalidArgumentException('A product may only appear once in a transfer.');
            }
            $seen[$productId] = true;
            $normalised[] = [
                'product_id' => $productId,
                'requested_qty' => $line['requested_qty'] ?? 0,
                'unit_ids' => array_values(array_filter((array) ($line['unit_ids'] ?? []), fn ($id) => (int) $id > 0)),
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
}
