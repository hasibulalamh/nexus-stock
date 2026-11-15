<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningStockRequestLine extends Model
{
    protected $fillable = [
        'request_id', 'product_id', 'quantity', 'serial_numbers', 'use_system_codes',
        'pending_location_type', 'pending_location_id', 'pending_marker',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'serial_numbers' => 'array',
        'use_system_codes' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(OpeningStockRequest::class, 'request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
