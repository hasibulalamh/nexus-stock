<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    protected $fillable = ['stock_adjustment_id', 'product_id', 'delta', 'stock_unit_ids', 'serial_numbers'];

    protected $casts = [
        'delta' => 'decimal:3',
        'stock_unit_ids' => 'array',
        'serial_numbers' => 'array',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
