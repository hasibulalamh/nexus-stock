<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use RecordsActivity, SoftDeletes;

    protected $fillable = [
        'city_id', 'upazila_id', 'name', 'name_bn', 'type', 'osm_id',
        'osm_place', 'lat', 'lng', 'thana_hint', 'source', 'verified',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name && $this->name_bn && $this->name !== $this->name_bn) {
            return "{$this->name} ({$this->name_bn})";
        }

        return $this->name ?: ($this->name_bn ?: '');
    }
}
