<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class GoodsReceiptService
{
    public function create(User $user, array $data): GoodsReceipt
    {
        $this->requirePermission($user, 'receiving.create');
        $warehouse = Warehouse::query()->find($data['warehouse_id'] ?? 0);
        if (! $warehouse) {
            throw new InvalidArgumentException('Select a valid warehouse.');
        }
        $this->requireWrite($user, 'warehouse', (int) $warehouse->id);
        $supplier = Supplier::query()->where('status', 'active')->find($data['supplier_id'] ?? 0);
        if (! $supplier) {
            throw new InvalidArgumentException('Select an active supplier.');
        }
        $lines = $this->normaliseLines($data['lines'] ?? []);
        if ($lines === []) {
            throw new InvalidArgumentException('At least one received product line is required.');
        }

        $receipt = DB::transaction(function () use ($user, $data, $warehouse, $supplier, $lines): GoodsReceipt {
            $order = null;
            if (! empty($data['purchase_order_id'])) {
                $order = PurchaseOrder::query()->lockForUpdate()->with('lines')->find($data['purchase_order_id']);
                if (! $order) {
                    throw new InvalidArgumentException('The purchase order does not exist.');
                }
                if (! in_array($order->status, ['ordered', 'partial'], true)) {
                    throw new InvalidArgumentException('Only ordered or partially received purchase orders can be received.');
                }
                if ((int) $order->warehouse_id !== (int) $warehouse->id || (int) $order->supplier_id !== (int) $supplier->id) {
                    throw new InvalidArgumentException('The receipt must match the purchase order warehouse and supplier.');
                }
            }

            $receipt = GoodsReceipt::create([
                'grn_no' => DocumentNumber::next(config('stock.documents.receipt_prefix', 'GRN')),
                'purchase_order_id' => $order?->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'received_by' => $user->id,
                'received_at' => $data['received_at'] ?? now(),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                if ($order) {
                    $orderLine = $order->lines->firstWhere('product_id', $line['product_id']);
                    if (! $orderLine) {
                        throw new InvalidArgumentException('Every received product must exist on the purchase order.');
                    }
                    if (! config('stock.purchases.allow_over_receipt', false)
                        && app(StockService::class)->parseForRequest($line['received_qty']) > app(StockService::class)->parseForRequest($orderLine->outstandingQty())) {
                        throw new InvalidArgumentException('Receipt quantity exceeds the outstanding purchase order quantity.');
                    }
                }
                $receipt->lines()->create($line);
            }

            return $receipt->load(['lines.product', 'supplier', 'warehouse']);
        });

        activity()->causedBy($user)->performedOn($receipt)->event('created')->log('Goods receipt drafted');

        return $receipt;
    }

    public function post(User $user, GoodsReceipt $receipt): GoodsReceipt
    {
        $this->requirePermission($user, 'receiving.create');
        $this->requireWrite($user, 'warehouse', (int) $receipt->warehouse_id);

        $posted = DB::transaction(function () use ($user, $receipt): GoodsReceipt {
            $locked = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== 'draft' || $locked->posted_at !== null) {
                throw new RuntimeException('Only draft receipts can be posted, and a receipt is posted once.');
            }
            $locked->load('lines.product');
            $order = $locked->purchase_order_id
                ? PurchaseOrder::query()->lockForUpdate()->with('lines')->find($locked->purchase_order_id)
                : null;
            $stock = app(StockService::class);
            $reference = ['type' => 'goods_receipt', 'id' => $locked->id];

            foreach ($locked->lines as $line) {
                $accepted = $stock->parseForRequest($line->accepted_qty);
                if ($accepted < 0 || $accepted > $stock->parseForRequest($line->received_qty)) {
                    throw new InvalidArgumentException('Accepted quantity cannot exceed the received quantity.');
                }
                if ($accepted === 0) {
                    continue;
                }
                $product = $line->product;
                if (! $product || $product->trashed() || ! $product->track_stock) {
                    throw new InvalidArgumentException('Every received product must be stock-tracked.');
                }
                $orderLine = null;
                if ($order) {
                    $orderLine = PurchaseOrderLine::query()->where('purchase_order_id', $order->id)
                        ->where('product_id', $product->id)->lockForUpdate()->first();
                    if (! $orderLine) {
                        throw new InvalidArgumentException('Every received product must exist on the purchase order.');
                    }
                    if (! config('stock.purchases.allow_over_receipt', false)
                        && $accepted > $stock->parseForRequest($orderLine->outstandingQty())) {
                        throw new InvalidArgumentException('Receipt quantity exceeds the outstanding purchase order quantity.');
                    }
                }

                if ($product->tracking_type === 'serial') {
                    $serials = array_values(array_filter((array) ($line->serial_numbers ?? []), fn ($serial) => trim((string) $serial) !== ''));
                    if (count($serials) * 1000 !== $accepted) {
                        throw new InvalidArgumentException('Provide exactly one serial number per accepted unit.');
                    }
                    $stock->recordSerialPurchase($user, $product, 'warehouse', (int) $locked->warehouse_id, $serials, $reference, 'Goods receipt '.$locked->grn_no, $locked->received_at);
                } else {
                    $stock->recordMovement(
                        $user, $product, 'warehouse', (int) $locked->warehouse_id,
                        StockMovementType::PURCHASE, $stock->formatQuantityValue($accepted),
                        $reference, 'Goods receipt '.$locked->grn_no.($line->reason ? ' — '.$line->reason : null),
                        (string) str()->uuid(), $locked->received_at,
                    );
                }

                $this->updateProductCost($product, $line, (int) $locked->warehouse_id);

                if ($orderLine) {
                    $orderLine->update([
                        'received_qty' => $stock->formatQuantityValue(
                            $stock->parseForRequest($orderLine->received_qty) + $accepted,
                        ),
                    ]);
                }
            }

            if ($order) {
                $this->refreshOrderStatus($order);
            }

            $locked->update(['status' => 'posted', 'posted_at' => now()]);

            return $locked->fresh(['lines.product', 'supplier', 'warehouse', 'purchaseOrder', 'receiver']);
        });

        activity()->causedBy($user)->performedOn($posted)->event('posted')->log('Goods receipt posted');

        return $posted;
    }

    public function supplierReturn(User $user, GoodsReceipt $receipt, int $lineId, string|int|float $quantity, string $reason): GoodsReceiptLine
    {
        $this->requirePermission($user, 'receiving.create');
        $this->requireWrite($user, 'warehouse', (int) $receipt->warehouse_id);
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A supplier return reason is required.');
        }

        $line = DB::transaction(function () use ($user, $receipt, $lineId, $quantity, $reason): GoodsReceiptLine {
            $locked = GoodsReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== 'posted') {
                throw new RuntimeException('Only posted receipts can be returned to the supplier.');
            }
            $line = GoodsReceiptLine::query()->where('goods_receipt_id', $locked->id)->lockForUpdate()->findOrFail($lineId);
            $amount = app(StockService::class)->parseForRequest($quantity);
            $returnable = app(StockService::class)->parseForRequest($line->returnableQty());
            if ($amount <= 0 || $amount > $returnable) {
                throw new InvalidArgumentException('Return quantity must be positive and within the damaged quantity.');
            }
            $product = Product::withTrashed()->findOrFail($line->product_id);
            if ($product->tracking_type === 'serial') {
                throw new InvalidArgumentException('Damaged serial units never entered stock; record a transfer discrepancy instead.');
            }
            app(StockService::class)->recordMovement(
                $user, $product, 'warehouse', (int) $locked->warehouse_id,
                StockMovementType::RETURN_OUT, app(StockService::class)->formatQuantityValue(-$amount),
                ['type' => 'goods_receipt', 'id' => $locked->id], 'Supplier return: '.$reason,
                (string) str()->uuid(),
            );
            $line->update([
                'returned_qty' => app(StockService::class)->formatQuantityValue(
                    app(StockService::class)->parseForRequest($line->returned_qty) + $amount,
                ),
            ]);

            return $line->fresh(['product']);
        });

        activity()->causedBy($user)->performedOn($line)->event('returned')->log('Supplier return recorded');

        return $line;
    }

    public function visibleTo(User $user): Builder
    {
        return GoodsReceipt::query()->visibleTo($user);
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
                throw new InvalidArgumentException('A product may only appear once in a receipt.');
            }
            $seen[$productId] = true;
            $accepted = $stock->parseForRequest($line['accepted_qty'] ?? 0);
            $damaged = $stock->parseForRequest($line['damaged_qty'] ?? 0);
            $short = $stock->parseForRequest($line['short_qty'] ?? 0);
            if ($accepted < 0 || $damaged < 0 || $short < 0 || ($accepted + $damaged + $short) <= 0) {
                throw new InvalidArgumentException('Enter a received, damaged or short quantity for every line.');
            }
            $serials = array_values(array_filter(array_map('trim', (array) ($line['serial_numbers'] ?? [])), fn ($serial) => $serial !== ''));
            if (count($serials) !== count(array_unique($serials))) {
                throw new InvalidArgumentException('Duplicate serial numbers are not allowed.');
            }
            $normalised[] = [
                'product_id' => $productId,
                'received_qty' => $stock->formatQuantityValue($accepted + $damaged + $short),
                'accepted_qty' => $stock->formatQuantityValue($accepted),
                'damaged_qty' => $stock->formatQuantityValue($damaged),
                'short_qty' => $stock->formatQuantityValue($short),
                'returned_qty' => '0.000',
                'unit_cost' => number_format((float) ($line['unit_cost'] ?? 0), 2, '.', ''),
                'reason' => $line['reason'] ?? null,
                'serial_numbers' => $serials === [] ? null : $serials,
            ];
        }

        return $normalised;
    }

    private function updateProductCost(Product $product, GoodsReceiptLine $line, int $warehouseId): void
    {
        $mode = (string) config('stock.purchases.cost_update', 'off');
        $unitCost = (float) $line->unit_cost;
        if ($mode === 'off' || $unitCost <= 0) {
            return;
        }
        if ($mode === 'last') {
            $product->update(['cost_price' => $line->unit_cost]);

            return;
        }
        if ($mode === 'weighted_average') {
            $level = StockLevel::query()->where('product_id', $product->id)
                ->where('location_type', 'warehouse')->where('location_id', $warehouseId)->first();
            $onHand = max(0, (float) ($level?->quantity ?? 0) - (float) $line->accepted_qty);
            $accepted = (float) $line->accepted_qty;
            $total = $onHand + $accepted;
            $average = $total > 0
                ? (($onHand * (float) $product->cost_price) + ($accepted * $unitCost)) / $total
                : $unitCost;
            $product->update(['cost_price' => round($average, 2)]);
        }
    }

    private function refreshOrderStatus(PurchaseOrder $order): void
    {
        $order->load('lines');
        $status = $order->lines->isNotEmpty() && $order->lines->every(fn (PurchaseOrderLine $line) => $line->isFullyReceived())
            ? 'received'
            : 'partial';
        $order->update(['status' => $status]);
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
