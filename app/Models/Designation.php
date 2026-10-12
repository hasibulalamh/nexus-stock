<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = [
        'name',
        'code',
        'department_id',
        'status',
    ];

    // A designation belongs to one department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // A designation can have many users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
