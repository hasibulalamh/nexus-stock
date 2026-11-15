<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class OpeningStockRequest extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'request_no', 'location_type', 'location_id', 'occurred_at', 'notes',
        'status', 'requested_by', 'requested_at', 'reviewed_by', 'reviewed_at',
        'rejection_reason', 'resubmitted_from_id',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(OpeningStockRequestLine::class, 'request_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resubmittedFrom()
    {
        return $this->belongsTo(self::class, 'resubmitted_from_id');
    }

    public function location()
    {
        return $this->location_type === 'branch'
            ? $this->belongsTo(Branch::class, 'location_id')
            : $this->belongsTo(Warehouse::class, 'location_id');
    }
}
