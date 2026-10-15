<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upazila extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'city_id',
        'postcode',
    ];
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function location()
    {
        return $this->hasMany(Location::class);
    }
}
