<?php

namespace App\Models;

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
        'notes',
        'performed_by',
    ];

    protected $casts = ['quantity_delta' => 'decimal:3'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
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
