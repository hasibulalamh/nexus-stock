<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'product_id',
        'branch_id',
        'warehouse_id',
        'quantity_at_alert',
        'minimum_at_alert',
        'status',
        'open_marker',
        'opened_at',
        'resolved_at',
        'last_notified_at',
    ];

    protected $casts = [
        'quantity_at_alert' => 'decimal:3',
        'minimum_at_alert' => 'decimal:3',
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
