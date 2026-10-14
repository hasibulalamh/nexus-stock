<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'location_id',
        'name',
        'code',
        'slug',
        'status',
        'capacity',
        'starting_date',
    ];
    
    //Relationship
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Restrict a query to the branches the given user may access.
     * Reuse this pattern for later models (stock, purchase, sale, transfer):
     * superadmin / access_all users keep the full query, everyone else is
     * limited to their assigned ids (an empty assignment yields no rows).
     */
    public function scopeAccessibleTo($query, User $user)
    {
        $ids = $user->accessibleBranchIds();

        return $ids === null ? $query : $query->whereIn('id', $ids);
    }
}
