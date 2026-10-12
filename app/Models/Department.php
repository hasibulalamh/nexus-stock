<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    // A department can have many designations
    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    // A department can have many users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
