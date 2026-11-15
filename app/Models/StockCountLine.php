<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountLine extends Model
{
    protected $fillable = ['stock_count_id', 'product_id', 'system_qty', 'counted_qty', 'variance', 'drift_qty'];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'counted_qty' => 'decimal:3',
        'variance' => 'decimal:3',
        'drift_qty' => 'decimal:3',
    ];

    public function count()
    {
        return $this->belongsTo(StockCount::class, 'stock_count_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function currentSystemQty(): string
    {
        $quantity = StockLevel::query()->where('product_id', $this->product_id)
            ->where('location_type', $this->count->location_type)
            ->where('location_id', $this->count->location_id)
            ->value('quantity');

        return (string) ($quantity ?? '0.000');
    }

    public function currentDrift(): string
    {
        $stock = app(\App\Services\StockService::class);
        $drift = $stock->parseForRequest($this->currentSystemQty()) - $stock->parseForRequest($this->system_qty);

        return $stock->formatQuantityValue($drift);
    }
}
