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
        return $this->belongsToMany(User::class, 'warehouse_user');
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Restrict a query to the warehouses the given user may access.
     * Reuse this pattern for later models (stock, purchase, sale, transfer):
     * superadmin / access_all users keep the full query, everyone else is
     * limited to their assigned ids (an empty assignment yields no rows).
     */
    public function scopeAccessibleTo($query, User $user)
    {
        $ids = $user->accessibleWarehouseIds();

        return $ids === null ? $query : $query->whereIn('id', $ids);
    }

}
