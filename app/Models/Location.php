<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'country_id',
        'state_id',
        'city_id',
        'upazila_id',
        'area_id',
        'area',
        'address_line',
        'status',
    ];    //Relationship

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->getAttribute('address_line'),
            $this->areaRecord?->display_name ?: $this->getAttribute('area'),
            $this->upazila?->name,
            $this->city?->name,
            $this->state?->name,
            $this->country?->name,
            $this->getAttribute('postcode') ?: $this->upazila?->postcode,
        ];

        return implode(', ', array_values(array_filter($parts, fn ($part) => filled($part))));
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }
    public function areaRecord()
    {
        return $this->belongsTo(Area::class);
    }
    public function warehouse()
    {
        return $this->hasMany(Warehouse::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function branch()
    {
        return $this->hasMany(Branch::class);
    }
}
