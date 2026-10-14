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
