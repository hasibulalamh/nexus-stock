<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Events\TransferChanged;
use App\Events\TransferDiscrepancyOpened;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockUnit;
use App\Models\Transfer;
use App\Models\TransferDiscrepancy;
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

    /**
     * Send (all or part) of a draft transfer.
     *
     * @param  array<int, array{sent_qty?: string|int|float, unit_ids?: array<int>}>  $sent  keyed by transfer item id
     */
    public function send(User $user, Transfer $transfer, array $sent = []): Transfer
    {
        $this->requirePermission($user, 'transfers.send');
        $this->requireWrite($user, $transfer->from_type, (int) $transfer->from_id);

        $updated = DB::transaction(function () use ($user, $transfer, $sent): Transfer {
            $locked = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== 'draft') {
                throw new RuntimeException('Only draft transfers can be sent.');
            }
            $this->assertRoute($locked->type, $locked->from_type, (int) $locked->from_id, $locked->to_type, (int) $locked->to_id);
            $locked->load('items.product');
            $totalSent = 0;

            foreach ($locked->items as $item) {
                $requested = app(StockService::class)->parseForRequest($item->requested_qty);
                $input = $sent[$item->id] ?? [];
                $sendQty = array_key_exists('sent_qty', $input)
                    ? app(StockService::class)->parseForRequest($input['sent_qty'])
                    : $requested;
                if ($sendQty < 0 || $sendQty > $requested) {
                    throw new InvalidArgumentException('Sent quantity cannot exceed the requested quantity.');
                }

                $product = Product::withTrashed()->findOrFail($item->product_id);
                $outType = $locked->type === Transfer::RETURN ? StockMovementType::RETURN_OUT : StockMovementType::TRANSFER_OUT;
                $reference = ['type' => 'transfer', 'id' => $locked->id];

                if ($product->tracking_type === 'serial') {
                    if ($sendQty % 1000 !== 0) {
                        throw new InvalidArgumentException('Serial quantities must be whole units.');
                    }
                    $candidateIds = $item->units()->pluck('stock_unit_id')->all();
                    $candidates = StockUnit::query()->where('product_id', $product->id)->whereIn('id', $candidateIds)
                        ->orderBy('id')->lockForUpdate()->get()
                        ->filter(fn (StockUnit $unit) => $unit->status === 'in_stock'
                            && $unit->location_type === $locked->from_type
                            && (int) $unit->location_id === (int) $locked->from_id)
                        ->values();
                    $selected = array_values(array_unique(array_map('intval', $input['unit_ids'] ?? [])));
                    if ($selected === []) {
                        $selected = $candidates->take(intdiv($sendQty, 1000))->pluck('id')->all();
                    } else {
                        // Checked units are the source of truth for a partial serial send.
                        $sendQty = count($selected) * 1000;
                        if ($sendQty > $requested) {
                            throw new InvalidArgumentException('Sent quantity cannot exceed the requested quantity.');
                        }
                    }
                    if (count($selected) !== intdiv($sendQty, 1000)) {
                        throw new InvalidArgumentException('Select exactly the units being sent.');
                    }
                    if ($selected !== []) {
                        app(StockService::class)->dispatchSerialUnits(
                            $user, $product, $locked->from_type, (int) $locked->from_id,
                            $selected, $reference, $locked->notes, $locked->batch_uuid, $outType,
                        );
                    }
                } elseif ($sendQty > 0) {
                    app(StockService::class)->recordMovement(
                        $user, $product, $locked->from_type, (int) $locked->from_id, $outType,
                        app(StockService::class)->formatQuantityValue(-$sendQty),
                        $reference, $locked->notes, $locked->batch_uuid,
                    );
                }

                $item->update(['sent_qty' => app(StockService::class)->formatQuantityValue($sendQty)]);
                $totalSent += $sendQty;
            }

            if ($totalSent <= 0) {
                throw new InvalidArgumentException('Send at least one unit.');
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
     * @param  array<int, array{received_qty?: string|int|float, damaged_qty?: string|int|float, unit_ids?: array<int>, damaged_unit_ids?: array<int>, reason?: string}>  $received  keyed by transfer item id
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
            $locked->load(['items.product']);
            $inType = $locked->type === Transfer::RETURN ? StockMovementType::RETURN_IN : StockMovementType::TRANSFER_IN;
            $reference = ['type' => 'transfer', 'id' => $locked->id];

            foreach ($locked->items as $item) {
                $input = $received[$item->id] ?? [];
                $product = Product::withTrashed()->findOrFail($item->product_id);
                $sentQty = app(StockService::class)->parseForRequest($item->sent_qty);
                $candidates = $this->dispatchedUnits($item);
                $damagedIds = [];

                if ($product->tracking_type === 'serial') {
                    $arrivedIds = array_values(array_unique(array_map('intval', $input['unit_ids'] ?? [])));
                    $damagedIds = array_values(array_unique(array_map('intval', $input['damaged_unit_ids'] ?? [])));
                    $allowedIds = $candidates->pluck('id')->all();
                    foreach (array_merge($arrivedIds, $damagedIds) as $unitId) {
                        if (! in_array($unitId, $allowedIds, true)) {
                            throw new InvalidArgumentException('Every receipt unit must be an in-transit unit of this transfer line.');
                        }
                    }
                    if (array_intersect($arrivedIds, $damagedIds) !== []) {
                        throw new InvalidArgumentException('A unit cannot be both received and damaged.');
                    }
                    if ($arrivedIds === [] && $damagedIds === []) {
                        $arrivedIds = $allowedIds;
                    }
                    $receivedQty = count($arrivedIds) * 1000;
                    $damagedQty = count($damagedIds) * 1000;
                    $shortQty = $sentQty - $receivedQty - $damagedQty;
                    $shortUnitIds = array_values(array_diff($allowedIds, $arrivedIds, $damagedIds));
                } else {
                    $receivedQty = array_key_exists('received_qty', $input)
                        ? app(StockService::class)->parseForRequest($input['received_qty'])
                        : $sentQty;
                    $damagedQty = array_key_exists('damaged_qty', $input)
                        ? app(StockService::class)->parseForRequest($input['damaged_qty'])
                        : 0;
                    $shortUnitIds = [];
                }

                if ($receivedQty < 0 || $damagedQty < 0 || ($receivedQty + $damagedQty) > $sentQty) {
                    throw new InvalidArgumentException('Received and damaged quantities cannot exceed the sent quantity.');
                }
                $shortQty = $sentQty - $receivedQty - $damagedQty;

                if ($receivedQty > 0) {
                    if ($product->tracking_type === 'serial') {
                        app(StockService::class)->receiveSerialUnits(
                            $user, $product, $locked->to_type, (int) $locked->to_id,
                            $arrivedIds, $reference, $locked->notes, $locked->batch_uuid, $inType,
                        );
                    } else {
                        app(StockService::class)->recordMovement(
                            $user, $product, $locked->to_type, (int) $locked->to_id, $inType,
                            app(StockService::class)->formatQuantityValue($receivedQty),
                            $reference, $locked->notes, $locked->batch_uuid,
                        );
                    }
                }

                $item->update(['received_qty' => app(StockService::class)->formatQuantityValue($receivedQty)]);

                $this->openDiscrepancy($user, $locked, $item, 'damaged', $damagedQty, $damagedIds, $input['reason'] ?? null);
                $this->openDiscrepancy($user, $locked, $item, 'short', $shortQty, $shortUnitIds, $input['reason'] ?? null);
            }

            $locked->update(['status' => 'received', 'received_by' => $user->id, 'received_at' => now()]);

            return $locked->fresh(['items.product', 'items.stockUnits', 'items.discrepancies', 'requester', 'receiver']);
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

    /**
     * Resolve an open discrepancy. Stock effects:
     * - found:    the missing/damaged quantity enters the destination location
     * - returned: the quantity returns to the source location
     * - written_off: quantity products get an audited zero-delta adjustment
     *   (the quantity never entered stock); serial units are marked written_off
     * - lost:     acknowledged, no stock movement
     */
    public function resolveDiscrepancy(User $user, TransferDiscrepancy $discrepancy, string $resolution, ?string $note = null): TransferDiscrepancy
    {
        $this->requirePermission($user, 'stock.discrepancies.resolve');
        if (! in_array($resolution, TransferDiscrepancy::RESOLUTIONS, true)) {
            throw new InvalidArgumentException('Select a valid resolution.');
        }
        $discrepancy->loadMissing(['item.product', 'item.transfer']);
        $transfer = $discrepancy->item?->transfer;
        $product = $discrepancy->item?->product;
        if (! $transfer || ! $product) {
            throw new InvalidArgumentException('The discrepancy is not linked to a transfer.');
        }
        $this->requireWrite($user, $transfer->to_type, (int) $transfer->to_id);
        if ($resolution === 'returned') {
            $this->requireWrite($user, $transfer->from_type, (int) $transfer->from_id);
        }

        $resolved = DB::transaction(function () use ($user, $discrepancy, $resolution, $note, $transfer, $product): TransferDiscrepancy {
            $locked = TransferDiscrepancy::query()->lockForUpdate()->findOrFail($discrepancy->id);
            if ($locked->status !== 'open') {
                throw new RuntimeException('Only open discrepancies can be resolved.');
            }
            $quantity = app(StockService::class)->parseForRequest($locked->qty);
            $unitIds = array_values(array_map('intval', $locked->stock_unit_ids ?? []));
            $reference = ['type' => 'transfer_discrepancy', 'id' => $locked->id];
            $note = $note ?: $locked->reason;

            if ($resolution === 'found' || $resolution === 'returned') {
                $locationType = $resolution === 'found' ? $transfer->to_type : $transfer->from_type;
                $locationId = $resolution === 'found' ? (int) $transfer->to_id : (int) $transfer->from_id;
                if ($product->tracking_type === 'serial') {
                    app(StockService::class)->receiveSerialUnits(
                        $user, $product, $locationType, $locationId, $unitIds,
                        $reference, $note, (string) str()->uuid(), StockMovementType::ADJUSTMENT,
                    );
                } else {
                    app(StockService::class)->recordMovement(
                        $user, $product, $locationType, $locationId, StockMovementType::ADJUSTMENT,
                        app(StockService::class)->formatQuantityValue($quantity),
                        $reference, $note, (string) str()->uuid(),
                    );
                }
            } elseif ($resolution === 'written_off') {
                if ($product->tracking_type === 'serial') {
                    app(StockService::class)->writeOffInTransitUnits(
                        $user, $product, $transfer->to_type, (int) $transfer->to_id,
                        $unitIds, $note ?? 'In-transit shortage written off', $reference,
                    );
                } else {
                    app(StockService::class)->recordMovement(
                        $user, $product, $transfer->to_type, (int) $transfer->to_id, StockMovementType::ADJUSTMENT,
                        '0.000', $reference, $note ?? 'In-transit shortage written off', (string) str()->uuid(),
                    );
                }
            }

            $locked->update([
                'status' => 'resolved', 'resolution' => $resolution, 'resolution_note' => $note,
                'resolved_by' => $user->id, 'resolved_at' => now(),
            ]);

            return $locked->fresh(['item.product', 'resolver']);
        });

        activity()->causedBy($user)->performedOn($resolved)->event('resolved')->log('Transfer discrepancy resolved: '.$resolution);

        return $resolved;
    }

    public function visibleTo(User $user): Builder
    {
        return Transfer::query()->visibleTo($user);
    }

    /**
     * Serial units currently in transit for this transfer line.
     */
    private function dispatchedUnits(TransferItem $item)
    {
        $candidateIds = $item->units()->pluck('stock_unit_id')->all();
        if ($candidateIds === []) {
            return collect();
        }

        return StockUnit::query()->where('product_id', $item->product_id)->whereIn('id', $candidateIds)
            ->where('status', 'in_transit')->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  array<int>  $unitIds
     */
    private function openDiscrepancy(User $user, Transfer $transfer, TransferItem $item, string $type, int $quantity, array $unitIds, ?string $reason): void
    {
        if ($quantity <= 0) {
            return;
        }

        $discrepancy = $item->discrepancies()->create([
            'type' => $type,
            'qty' => app(StockService::class)->formatQuantityValue($quantity),
            'reason' => $reason,
            'stock_unit_ids' => $unitIds === [] ? null : array_values($unitIds),
            'status' => 'open',
        ]);

        event(new TransferDiscrepancyOpened($discrepancy, $user->id));
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
