<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $fillable = ['transfer_id', 'product_id', 'requested_qty', 'sent_qty', 'received_qty'];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'sent_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function units()
    {
        return $this->hasMany(TransferItemUnit::class);
    }

    public function stockUnits()
    {
        return $this->belongsToMany(StockUnit::class, 'transfer_item_units')->orderBy('stock_units.id');
    }

    public function discrepancies()
    {
        return $this->hasMany(TransferDiscrepancy::class);
    }

    public function backorderQty(): string
    {
        return number_format(max(0, (float) $this->requested_qty - (float) $this->sent_qty), 3, '.', '');
    }

    public function hasBackorder(): bool
    {
        return (float) $this->backorderQty() > 0;
    }
}
