<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
    ];

    public function states()
    {
        return $this->hasMany(State::class);
    }  


    public function location()
    {
        return $this->hasMany(Location::class);
    }
}
