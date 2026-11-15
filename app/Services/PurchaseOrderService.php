<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PurchaseOrderService
{
    public function create(User $user, array $data): PurchaseOrder
    {
        $this->requirePermission($user, 'purchases.create');
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
            throw new InvalidArgumentException('At least one product line is required.');
        }
        $requireApproval = (bool) config('stock.purchases.require_approval', false);

        $order = DB::transaction(function () use ($user, $data, $warehouse, $supplier, $lines, $requireApproval): PurchaseOrder {
            $order = PurchaseOrder::create([
                'po_no' => DocumentNumber::next(config('stock.documents.purchase_prefix', 'PO')),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => $requireApproval ? 'draft' : 'ordered',
                'ordered_at' => $requireApproval ? null : now(),
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                $product = Product::withTrashed()->find($line['product_id']);
                if (! $product || $product->trashed() || $product->status !== 'active' || ! $product->track_stock) {
                    throw new InvalidArgumentException('Every product must be active and stock-tracked.');
                }
                $order->lines()->create([
                    'product_id' => $product->id,
                    'ordered_qty' => app(StockService::class)->formatQuantityValue($line['ordered_qty']),
                    'received_qty' => '0.000',
                    'unit_cost' => number_format((float) $line['unit_cost'], 2, '.', ''),
                ]);
            }

            return $order->load(['lines.product', 'supplier', 'warehouse', 'creator']);
        });

        activity()->causedBy($user)->performedOn($order)->event('created')->log('Purchase order created');

        return $order;
    }

    public function update(User $user, PurchaseOrder $order, array $data): PurchaseOrder
    {
        $this->requirePermission($user, 'purchases.edit');
        $this->requireWrite($user, 'warehouse', (int) $order->warehouse_id);
        $lines = $this->normaliseLines($data['lines'] ?? []);
        if ($lines === []) {
            throw new InvalidArgumentException('At least one product line is required.');
        }

        return DB::transaction(function () use ($order, $data, $lines): PurchaseOrder {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== 'draft') {
                throw new RuntimeException('Only draft purchase orders can be edited.');
            }
            $locked->update([
                'supplier_id' => $data['supplier_id'] ?? $locked->supplier_id,
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $locked->lines()->delete();
            foreach ($lines as $line) {
                $product = Product::withTrashed()->find($line['product_id']);
                if (! $product || $product->trashed() || $product->status !== 'active' || ! $product->track_stock) {
                    throw new InvalidArgumentException('Every product must be active and stock-tracked.');
                }
                $locked->lines()->create([
                    'product_id' => $product->id,
                    'ordered_qty' => app(StockService::class)->formatQuantityValue($line['ordered_qty']),
                    'received_qty' => '0.000',
                    'unit_cost' => number_format((float) $line['unit_cost'], 2, '.', ''),
                ]);
            }

            return $locked->fresh(['lines.product', 'supplier', 'warehouse']);
        });
    }

    public function approve(User $user, PurchaseOrder $order): PurchaseOrder
    {
        $this->requirePermission($user, 'purchases.approve');
        $this->requireWrite($user, 'warehouse', (int) $order->warehouse_id);

        $approved = DB::transaction(function () use ($user, $order): PurchaseOrder {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== 'draft') {
                throw new RuntimeException('Only draft purchase orders can be approved.');
            }
            if ($locked->lines()->count() === 0) {
                throw new RuntimeException('A purchase order needs at least one line.');
            }
            $locked->update([
                'status' => 'ordered', 'approved_by' => $user->id,
                'approved_at' => now(), 'ordered_at' => now(),
            ]);

            return $locked->fresh(['lines.product', 'supplier', 'warehouse', 'approver']);
        });

        activity()->causedBy($user)->performedOn($approved)->event('approved')->log('Purchase order approved');

        return $approved;
    }

    public function cancel(User $user, PurchaseOrder $order, ?string $reason = null): PurchaseOrder
    {
        $this->requirePermission($user, 'purchases.cancel');
        $this->requireWrite($user, 'warehouse', (int) $order->warehouse_id);

        $cancelled = DB::transaction(function () use ($order, $reason): PurchaseOrder {
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($locked->status, ['draft', 'ordered', 'partial'], true)) {
                throw new RuntimeException('This purchase order can no longer be cancelled.');
            }
            $locked->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
                'notes' => trim(($locked->notes ? $locked->notes."\n" : '').($reason ? 'Cancelled: '.$reason : '')),
            ]);

            return $locked;
        });

        activity()->causedBy($user)->performedOn($cancelled)->event('cancelled')->log('Purchase order cancelled');

        return $cancelled;
    }

    public function visibleTo(User $user): Builder
    {
        return PurchaseOrder::query()->visibleTo($user);
    }

    /**
     * @return array<int, array{product_id:int, ordered_qty:int, unit_cost:float}>
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
                throw new InvalidArgumentException('A product may only appear once in a purchase order.');
            }
            $seen[$productId] = true;
            $quantity = app(StockService::class)->parseForRequest($line['ordered_qty'] ?? 0);
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Ordered quantity must be greater than zero.');
            }
            $cost = (float) ($line['unit_cost'] ?? 0);
            if ($cost < 0) {
                throw new InvalidArgumentException('Unit cost cannot be negative.');
            }
            $normalised[] = ['product_id' => $productId, 'ordered_qty' => $quantity, 'unit_cost' => $cost];
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
