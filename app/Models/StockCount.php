<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Support\StockAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    use RecordsActivity;

    public const STATUSES = ['open', 'counting', 'submitted', 'approved', 'cancelled'];

    protected $fillable = [
        'count_no', 'location_type', 'location_id', 'status', 'snapshot_at', 'notes',
        'requested_by', 'reviewed_by', 'reviewed_at', 'adjustment_id', 'drift_acknowledged',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'drift_acknowledged' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(StockCountLine::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function locationName(): string
    {
        $model = $this->location_type === 'branch'
            ? Branch::find($this->location_id)
            : Warehouse::find($this->location_id);

        return $model?->name ?? ucfirst($this->location_type).' #'.$this->location_id;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['open', 'counting'], true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $branchIds = StockAccess::readableLocationIds($user, 'branch');
        $warehouseIds = StockAccess::readableLocationIds($user, 'warehouse');
        if ($branchIds === null && $warehouseIds === null) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($branchIds, $warehouseIds): void {
            $scope->whereRaw('1 = 0');
            if ($branchIds !== null && $branchIds !== []) {
                $scope->orWhere(fn (Builder $q) => $q->where('location_type', 'branch')->whereIn('location_id', $branchIds));
            } elseif ($branchIds === null) {
                $scope->orWhere('location_type', 'branch');
            }
            if ($warehouseIds !== null && $warehouseIds !== []) {
                $scope->orWhere(fn (Builder $q) => $q->where('location_type', 'warehouse')->whereIn('location_id', $warehouseIds));
            } elseif ($warehouseIds === null) {
                $scope->orWhere('location_type', 'warehouse');
            }
        });
    }
}
