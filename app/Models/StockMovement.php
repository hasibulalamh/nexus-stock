<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'location_type',
        'location_id',
        'movement_type',
        'quantity_delta',
        'balance_after',
        'reference_type',
        'reference_id',
        'batch_uuid',
        'occurred_at',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'movement_type' => StockMovementType::class,
        'quantity_delta' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function units()
    {
        return $this->belongsToMany(StockUnit::class, 'stock_movement_units');
    }

    public function location()
    {
        return $this->location_type === 'branch'
            ? $this->belongsTo(Branch::class, 'location_id')
            : $this->belongsTo(Warehouse::class, 'location_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stock movements are immutable.'));
        static::deleting(fn () => throw new LogicException('Stock movements are immutable.'));
    }
}
