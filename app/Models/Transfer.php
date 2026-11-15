<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use RecordsActivity;

    public const TRANSFER = 'transfer';

    public const RETURN = 'return';

    public const RETURN_REASONS = ['damaged', 'wrong_item', 'excess', 'customer_return', 'other'];

    protected $fillable = [
        'transfer_no', 'type', 'from_type', 'from_id', 'to_type', 'to_id',
        'status', 'reason', 'stock_alert_id', 'notes', 'batch_uuid',
        'requested_by', 'sent_by', 'received_by', 'sent_at', 'received_at', 'cancelled_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function stockAlert()
    {
        return $this->belongsTo(StockAlert::class);
    }

    public function discrepancies()
    {
        return $this->hasManyThrough(TransferDiscrepancy::class, TransferItem::class, 'transfer_id', 'transfer_item_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_id');
    }

    public function fromLocationName(): string
    {
        $model = $this->from_type === 'branch'
            ? ($this->relationLoaded('fromBranch') ? $this->fromBranch : $this->fromBranch()->first())
            : ($this->relationLoaded('fromWarehouse') ? $this->fromWarehouse : $this->fromWarehouse()->first());

        return $model?->name ?? ucfirst($this->from_type).' #'.$this->from_id;
    }

    public function toLocationName(): string
    {
        $model = $this->to_type === 'branch'
            ? ($this->relationLoaded('toBranch') ? $this->toBranch : $this->toBranch()->first())
            : ($this->relationLoaded('toWarehouse') ? $this->toWarehouse : $this->toWarehouse()->first());

        return $model?->name ?? ucfirst($this->to_type).' #'.$this->to_id;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $branchIds = StockAccess::readableLocationIds($user, 'branch');
        $warehouseIds = StockAccess::readableLocationIds($user, 'warehouse');
        if ($branchIds === null && $warehouseIds === null) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($branchIds, $warehouseIds): void {
            $scope->whereRaw('1 = 0');
            foreach ([['branch', $branchIds], ['warehouse', $warehouseIds]] as [$type, $ids]) {
                if ($ids === null) {
                    $scope->orWhere('from_type', $type)->orWhere('to_type', $type);
                    continue;
                }
                if ($ids === []) {
                    continue;
                }
                $scope->orWhere(fn (Builder $q) => $q->where('from_type', $type)->whereIn('from_id', $ids));
                $scope->orWhere(fn (Builder $q) => $q->where('to_type', $type)->whereIn('to_id', $ids));
            }
        });
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }
}
