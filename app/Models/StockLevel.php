<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsActivity;

class StockLevel extends Model
{
    use RecordsActivity;
    protected $fillable = [
        'product_id',
        'location_type',
        'location_id',
        'quantity',
        'min_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'min_quantity' => 'decimal:3',
    ];

    public function effectiveMinimum(): ?string
    {
        return $this->min_quantity ?? $this->product?->reorder_level;
    }

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
