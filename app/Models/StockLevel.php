<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    protected $fillable = [
        'product_id',
        'location_type',
        'location_id',
        'quantity',
    ];

    protected $casts = ['quantity' => 'decimal:3'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->location_type === 'branch'
            ? $this->belongsTo(Branch::class, 'location_id')
            : $this->belongsTo(Warehouse::class, 'location_id');
    }
}
