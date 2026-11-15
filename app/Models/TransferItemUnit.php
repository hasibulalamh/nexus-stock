<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItemUnit extends Model
{
    protected $table = 'transfer_item_units';

    protected $fillable = ['transfer_item_id', 'stock_unit_id'];

    public function item()
    {
        return $this->belongsTo(TransferItem::class, 'transfer_item_id');
    }

    public function stockUnit()
    {
        return $this->belongsTo(StockUnit::class);
    }
}
