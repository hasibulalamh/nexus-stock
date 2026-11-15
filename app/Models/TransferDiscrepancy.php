<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class TransferDiscrepancy extends Model
{
    use RecordsActivity;

    public const TYPES = ['short', 'damaged', 'extra'];

    public const RESOLUTIONS = ['lost', 'found', 'written_off', 'returned'];

    protected $fillable = [
        'transfer_item_id', 'type', 'qty', 'reason', 'stock_unit_ids', 'status',
        'resolution', 'resolution_note', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'stock_unit_ids' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(TransferItem::class, 'transfer_item_id');
    }

    public function transfer()
    {
        return $this->hasOneThrough(
            Transfer::class,
            TransferItem::class,
            'id',
            'id',
            'transfer_item_id',
            'transfer_id',
        );
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
