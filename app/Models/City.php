<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'country_id',
        'state_id',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function thanas()
    {
        return $this->hasMany(Thana::class);
    }
    public function location()
    {
        return $this->hasMany(Location::class);
    }
}
