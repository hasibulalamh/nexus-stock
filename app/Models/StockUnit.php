<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockUnit extends Model
{
    protected $fillable = [
        'product_id',
        'serial_no',
        'unit_code',
        'status',
        'location_type',
        'location_id',
        'received_at',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movements()
    {
        return $this->belongsToMany(StockMovement::class, 'stock_movement_units');
    }

    public function scopeInStockAt($query, string $locationType, int $locationId)
    {
        return $query->where('status', 'in_stock')
            ->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    public static function unitCodeForId(int $id): string
    {
        return 'U'.str_pad((string) $id, 11, '0', STR_PAD_LEFT);
    }
}
