<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'grn_no', 'purchase_order_id', 'supplier_id', 'warehouse_id', 'received_by',
        'received_at', 'posted_at', 'status', 'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->accessibleWarehouseIds();

        return $ids === null ? $query : $query->whereIn('warehouse_id', $ids);
    }
}
