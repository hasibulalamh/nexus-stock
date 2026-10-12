<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'location_id',
        'capacity',
        'starting_date',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    

}
