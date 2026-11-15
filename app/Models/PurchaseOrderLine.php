<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    protected $fillable = ['purchase_order_id', 'product_id', 'ordered_qty', 'received_qty', 'unit_cost'];

    protected $casts = [
        'ordered_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function outstandingQty(): string
    {
        return number_format(max(0, (float) $this->ordered_qty - (float) $this->received_qty), 3, '.', '');
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->received_qty >= (float) $this->ordered_qty;
    }
}
