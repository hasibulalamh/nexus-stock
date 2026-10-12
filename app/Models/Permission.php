<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'status',
    ];

    //many to many
    public function role()
    {
        return $this->belongsToMany(Role::class);
    }
}
