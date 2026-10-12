<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thana extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'city_id', 
    ];
    //Relationship
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function unions()
    {
        return $this->hasMany(Union::class);
        
    }
    public function location()
    {
        return $this->hasMany(Location::class);
    }
}