<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'country_id',
        'state_id',
        'city_id',
        'thana_id',
        'union_id',
        'status',
    ];
    //Relationship
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
    public function thana()
    {
        return $this->belongsTo(Thana::class);
    }
    public function union()
    {
        return $this->belongsTo(Union::class);
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
