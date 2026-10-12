<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // module => actions. Names must match the can:... middleware in routes/web.php
        $modules = [
            'roles'        => ['view', 'create', 'edit', 'delete', 'assign'],
            'permissions'  => ['view', 'edit'],
            'users'        => ['view', 'create', 'edit', 'delete'],
            'departments'  => ['view', 'create', 'edit', 'delete'],
            'designations' => ['view', 'create', 'edit', 'delete'],
            'locations'    => ['view', 'create', 'status'],
            'warehouses'   => ['view', 'create', 'edit', 'delete'],
            'branches'     => ['view', 'create', 'edit', 'delete'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$module}.{$action}", 'guard_name' => 'web'],
                    ['status' => 'active'] // status is NOT NULL in the table
                );
            }
        }

        // Superadmin already bypasses via Gate::before, but keep the DB in sync too
        $super = Role::where('name', 'superadmin')->first();
        $super?->syncPermissions(Permission::where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
