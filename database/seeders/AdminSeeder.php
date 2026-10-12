<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Create or get the superadmin role (without icon first)
        $role = Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'guard_name' => 'web',
                'dashboard_route' => 'admin.dashboard',
                 'icon_class' => 'fa-solid fa-crown',
                'status' => 'active'
            ]
        );


        // Step 3: Create or get Department
        $department = Department::firstOrCreate(
            ['name' => 'Administration'],
            [
                'code' => 'ADM',
                'status' => 'active'
            ]
        );

        // Step 4: Create or get Designation
        $designation = Designation::firstOrCreate(
            ['name' => 'Admin'],
            [
                'code' => 'ADM',
                'department_id' => $department->id,
                'status' => 'active'
            ]
        );

        // Step 5: Create Superadmin User
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@storify.com'],
            [
                'full_name' => 'Hasibul Alam',
                'password' => bcrypt('12345678'),
                'phone' => '01234567890',
                'gender' => 'male',
                'employee_id' => 'ADM-001',
                'role_id' => $role->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'join_date' => now(),
                'profile_photo' => '/uploads/users/default.png',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Step 6: Assign role
        if (!$superadmin->hasRole($role->name)) {
            $superadmin->assignRole($role->name);
        }
    }
}
