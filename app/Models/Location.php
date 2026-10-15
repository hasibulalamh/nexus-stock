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
        'status',
    ];    //Relationship
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

    public function thana()
    {
        return $this->upazila();
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
