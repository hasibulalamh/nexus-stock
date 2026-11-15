<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    protected $fillable = [
        'goods_receipt_id', 'product_id', 'received_qty', 'accepted_qty', 'damaged_qty',
        'short_qty', 'returned_qty', 'unit_cost', 'reason', 'serial_numbers',
    ];

    protected $casts = [
        'received_qty' => 'decimal:3',
        'accepted_qty' => 'decimal:3',
        'damaged_qty' => 'decimal:3',
        'short_qty' => 'decimal:3',
        'returned_qty' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'serial_numbers' => 'array',
    ];

    public function receipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function returnableQty(): string
    {
        return number_format(max(0, (float) $this->damaged_qty - (float) $this->returned_qty), 3, '.', '');
    }
}
