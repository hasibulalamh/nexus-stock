<?php

namespace App\Services;

use App\Events\OpeningStockRequestChanged;
use App\Models\Branch;
use App\Models\OpeningStockRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\User;
use App\Support\StockAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OpeningStockRequestService
{
    public function submit(User $user, array $data): OpeningStockRequest
    {
        $this->requirePermission($user, 'stock.opening');
        $type = $data['location_type'];
        $locationId = (int) $data['location_id'];
        $this->requireWrite($user, $type, $locationId);
        $this->requireLocation($type, $locationId);
        $lines = $this->normaliseLines($data['lines'] ?? []);

        $request = DB::transaction(function () use ($user, $data, $type, $locationId, $lines): OpeningStockRequest {
            $request = OpeningStockRequest::create([
                'request_no' => 'OSR-pending-'.str()->uuid(),
                'location_type' => $type,
                'location_id' => $locationId,
                'occurred_at' => $data['occurred_at'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'requested_by' => $user->id,
                'requested_at' => now(),
            ]);
            $request->update(['request_no' => sprintf('OSR-%s-%06d', Carbon::parse($data['occurred_at'])->format('Y'), $request->id)]);
            foreach ($lines as $line) {
                $this->validateLine($line, $type, $locationId, true);
                $request->lines()->create($line);
            }

            return $request->load('lines');
        });

        activity()->causedBy($user)->performedOn($request)->event('created')->log('Opening stock request submitted');
        event(new OpeningStockRequestChanged($request, 'submitted', $user->id));

        return $request;
    }

    public function approve(User $user, OpeningStockRequest $request): OpeningStockRequest
    {
        $this->requirePermission($user, 'stock.opening.approve');
        $this->requireWrite($user, $request->location_type, (int) $request->location_id);
        if (! config('stock.approval.allow_self_approval', true) && (int) $request->requested_by === $user->id) {
            throw new RuntimeException('You cannot approve your own opening stock request.');
        }

        $approved = DB::transaction(function () use ($user, $request): OpeningStockRequest {
            $locked = OpeningStockRequest::query()->with('lines.product')->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending opening stock requests can be approved.');
            }
            foreach ($locked->lines as $line) {
                $this->validateLine($line->toArray(), $locked->location_type, (int) $locked->location_id, false);
            }
            foreach ($locked->lines as $line) {
                $product = $line->product()->lockForUpdate()->firstOrFail();
                $reference = ['type' => 'opening_request', 'id' => $locked->id];
                if ($product->tracking_type === 'serial') {
                    app(StockService::class)->recordSerialOpening(
                        $user, $product, $locked->location_type, (int) $locked->location_id,
                        $line->serial_numbers ?? [], $line->quantity, $line->use_system_codes,
                        $locked->occurred_at, $locked->notes, $reference
                    );
                } else {
                    app(StockService::class)->recordOpening(
                        $user, $product, $locked->location_type, (int) $locked->location_id,
                        $line->quantity, $locked->occurred_at, $locked->notes, $reference
                    );
                }
            }
            $locked->update(['status' => 'approved', 'reviewed_by' => $user->id, 'reviewed_at' => now()]);

            return $locked->fresh(['lines', 'requester', 'reviewer', 'location']);
        });

        activity()->causedBy($user)->performedOn($approved)->event('approved')->log('Opening stock request approved');
        event(new OpeningStockRequestChanged($approved, 'approved', $user->id));

        return $approved;
    }

    public function reject(User $user, OpeningStockRequest $request, string $reason): OpeningStockRequest
    {
        $this->requirePermission($user, 'stock.opening.approve');
        $this->requireWrite($user, $request->location_type, (int) $request->location_id);
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }
        $updated = DB::transaction(function () use ($user, $request, $reason): OpeningStockRequest {
            $locked = OpeningStockRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending opening stock requests can be rejected.');
            }
            $locked->update(['status' => 'rejected', 'rejection_reason' => $reason, 'reviewed_by' => $user->id, 'reviewed_at' => now()]);
            return $locked->fresh(['lines', 'requester', 'reviewer', 'location']);
        });
        activity()->causedBy($user)->performedOn($updated)->event('rejected')->log('Opening stock request rejected');
        event(new OpeningStockRequestChanged($updated, 'rejected', $user->id));
        return $updated;
    }

    public function cancel(User $user, OpeningStockRequest $request): OpeningStockRequest
    {
        $this->requirePermission($user, 'stock.opening');
        $this->requireOwner($user, $request);
        $this->requireWrite($user, $request->location_type, (int) $request->location_id);
        $updated = DB::transaction(function () use ($request): OpeningStockRequest {
            $locked = OpeningStockRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Only pending opening stock requests can be cancelled.');
            }
            $locked->update(['status' => 'cancelled']);
            return $locked;
        });
        activity()->causedBy($user)->performedOn($updated)->event('cancelled')->log('Opening stock request cancelled');
        return $updated;
    }

    public function resubmit(User $user, OpeningStockRequest $request): OpeningStockRequest
    {
        $this->requirePermission($user, 'stock.opening');
        $this->requireOwner($user, $request);
        $this->requireWrite($user, $request->location_type, (int) $request->location_id);
        if ($request->status !== 'rejected') {
            throw new RuntimeException('Only rejected opening stock requests can be resubmitted.');
        }
        $newRequest = $this->submit($user, [
            'location_type' => $request->location_type, 'location_id' => $request->location_id,
            'occurred_at' => $request->occurred_at, 'notes' => $request->notes,
            'lines' => $request->lines->map(fn ($line) => [
                'product_id' => $line->product_id, 'quantity' => $line->quantity,
                'serial_numbers' => $line->serial_numbers, 'use_system_codes' => $line->use_system_codes,
            ])->all(),
        ]);
        $newRequest->update(['resubmitted_from_id' => $request->id]);
        activity()->causedBy($user)->performedOn($newRequest)->event('resubmitted')->log('Opening stock request resubmitted');
        return $newRequest->fresh('lines');
    }

    public function visibleTo(User $user): Builder
    {
        return OpeningStockRequest::query()->with(['lines.product', 'requester', 'reviewer', 'location'])
            ->where(function ($query) use ($user): void {
                $query->where('requested_by', $user->id);
                foreach (['branch', 'warehouse'] as $type) {
                    $ids = $user->can('stock.opening.approve')
                        ? ($type === 'branch' ? $user->accessibleBranchIds() : $user->accessibleWarehouseIds())
                        : StockAccess::readableLocationIds($user, $type);
                    $query->orWhere(fn ($q) => $q->where('location_type', $type)->when($ids !== null, fn ($q) => $q->whereIn('location_id', $ids)));
                }
            });
    }

    private function normaliseLines(array $lines): array
    {
        if ($lines === []) {
            throw new InvalidArgumentException('At least one product line is required.');
        }
        $seen = [];
        return array_map(function (array $line) use (&$seen): array {
            $productId = (int) ($line['product_id'] ?? 0);
            if (isset($seen[$productId])) {
                throw new InvalidArgumentException('A product may only appear once in a request.');
            }
            $seen[$productId] = true;
            $serials = array_values(array_filter(array_map('trim', $line['serial_numbers'] ?? []), fn ($value) => $value !== ''));
            return ['product_id' => $productId, 'quantity' => $line['quantity'] ?? 0, 'serial_numbers' => $serials, 'use_system_codes' => (bool) ($line['use_system_codes'] ?? false)];
        }, $lines);
    }

    private function validateLine(array $line, string $type, int $locationId, bool $checkPending): void
    {
        $product = Product::withTrashed()->find($line['product_id'] ?? 0);
        if (! $product || $product->trashed() || $product->status !== 'active' || ! $product->track_stock) {
            throw new InvalidArgumentException('Every product must be active and stock-tracked.');
        }
        if ($checkPending && OpeningStockRequest::query()->where('status', 'pending')->where('location_type', $type)->where('location_id', $locationId)->whereHas('lines', fn ($q) => $q->where('product_id', $product->id))->exists()) {
            throw new InvalidArgumentException('A pending opening stock request already exists for this product and location.');
        }
        if (StockMovement::query()->where('product_id', $product->id)->where('location_type', $type)->where('location_id', $locationId)->exists()) {
            throw new InvalidArgumentException('Opening stock already exists for this product and location.');
        }
        $quantity = app(StockService::class)->parseForRequest($line['quantity'] ?? 0);
        if ($quantity <= 0 || ($product->tracking_type === 'serial' && $quantity % 1000 !== 0)) {
            throw new InvalidArgumentException($product->tracking_type === 'serial' ? 'Serial quantities must be whole units.' : 'Quantity must be greater than zero with at most three decimals.');
        }
        $serials = $line['serial_numbers'] ?? [];
        if ($product->tracking_type === 'serial') {
            if (count($serials) !== count(array_unique($serials))) {
                throw new InvalidArgumentException('Duplicate serial numbers are not allowed.');
            }
            if ($serials === [] && (! $line['use_system_codes'] || $quantity < 1000)) {
                throw new InvalidArgumentException('Provide serial numbers or explicitly enable system codes with a quantity.');
            }
            if ($serials !== [] && count($serials) !== intdiv($quantity, 1000)) {
                throw new InvalidArgumentException('Serial count must equal the quantity.');
            }
            if (StockUnit::query()->where('product_id', $product->id)->whereIn('serial_no', $serials)->exists()) {
                throw new InvalidArgumentException('A serial number already exists for this product.');
            }
        } elseif ($serials !== [] || $line['use_system_codes']) {
            throw new InvalidArgumentException('Serial numbers are only valid for serial-tracked products.');
        }
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
            ? Branch::query()->whereKey($id)->where('status', 'active')->exists()
            : \App\Models\Warehouse::query()->whereKey($id)->where('status', 'active')->exists();
        if (! $exists) {
            throw new InvalidArgumentException('The selected stock location is not available.');
        }
    }

    private function requireOwner(User $user, OpeningStockRequest $request): void
    {
        if ($request->requested_by !== $user->id) {
            throw new RuntimeException('Only the requester can change this request.');
        }
    }
}
