<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use RecordsActivity;

    public const STATUSES = ['draft', 'ordered', 'partial', 'received', 'closed', 'cancelled'];

    protected $fillable = [
        'po_no', 'supplier_id', 'warehouse_id', 'status', 'expected_date', 'notes',
        'created_by', 'approved_by', 'approved_at', 'ordered_at', 'cancelled_at',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'approved_at' => 'datetime',
        'ordered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['ordered', 'partial'], true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $ids = $user->accessibleWarehouseIds();

        return $ids === null ? $query : $query->whereIn('warehouse_id', $ids);
    }
}
