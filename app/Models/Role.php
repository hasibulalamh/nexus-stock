<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'icon_class',
        'parent_id',
        'status',
        'dashboard_route',
    ];

    /**
     * Get the parent role
     */
    public function parent()
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    /**
     * Get the child roles
     */
    public function children()
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    //  Custom relation name to avoid conflict
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id');
    }

    //  Icon HTML Accessor
    public function getIconHtmlAttribute()
    {
        return $this->icon_class 
            ? '<i class="' . e($this->icon_class) . '"></i>' 
            : '';
    }}