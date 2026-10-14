<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Warehouse;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
       'full_name',
       'email',
       'password',
       'phone',
        'gender',
       'employee_id',
       'department_id',
       'designation_id',
       'role_id',
       'join_date',
       'profile_photo',
       'status',
       'access_all',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
   protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
];




    //many to many
   public function designation()
   {
       return $this->belongsTo(Designation::class);
   }

   //many to many
   public function department()
   {
       return $this->belongsTo(Department::class);
   }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * Branch/warehouse access rule:
     * - A superadmin, or any user with access_all = true, sees everything
     *   (these helpers then return null = unrestricted).
     * - Otherwise the assigned ids are returned (an empty array = no access).
     * - Warehouses do NOT belong to branches (there is no branch_id column on
     *   warehouses), so branch assignments never imply warehouse access;
     *   warehouses must be assigned directly.
     *
     * @return array<int>|null null means "all access"
     */
    public function hasAccessAll(): bool
    {
        return $this->hasRole('superadmin') || (bool) $this->access_all;
    }

    /** @return array<int>|null null means "all access" */
    public function accessibleBranchIds(): ?array
    {
        if ($this->hasAccessAll()) {
            return null;
        }

        return $this->branches()->pluck('branches.id')->all();
    }

    /** @return array<int>|null null means "all access" */
    public function accessibleWarehouseIds(): ?array
    {
        if ($this->hasAccessAll()) {
            return null;
        }

        return $this->warehouses()->pluck('warehouses.id')->all();
    }

    public function role(){
        return $this->belongsTo(Role::class);
    }


    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo && file_exists(public_path('uploads/user/' . $this->profile_photo))) {
            return asset('uploads/user/' . $this->profile_photo);
        }

        return asset('assets/img/avatars/avatar.jpg');
    }


}
