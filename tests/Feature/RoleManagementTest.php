<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    public function test_role_permission_assignment_is_persisted_and_cache_is_reset(): void
    {
        $role = Role::create([
            'name' => 'catalog manager',
            'guard_name' => 'web',
            'icon_class' => 'fa-solid fa-box',
            'status' => 'active',
            'dashboard_route' => 'admin.dashboard',
        ]);
        $permission = Permission::where('name', 'products.view')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.roles.permissions.assign.store', $role->id), [
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('admin.roles.list'));

        $this->assertTrue($role->fresh()->hasPermissionTo('products.view'));
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $role = Role::create([
            'name' => 'assigned role',
            'guard_name' => 'web',
            'icon_class' => 'fa-solid fa-user',
            'status' => 'active',
            'dashboard_route' => 'admin.dashboard',
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.roles.list'))
            ->delete(route('admin.roles.delete', $role->id))
            ->assertRedirect(route('admin.roles.list'))
            ->assertSessionHas('error', 'Cannot delete this role because it is assigned to users.');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_role_name_is_unique_case_insensitively(): void
    {
        Role::create([
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
            'icon_class' => 'fa-solid fa-user',
            'status' => 'active',
            'dashboard_route' => 'admin.dashboard',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.roles.create'))
            ->post(route('admin.roles.store'), [
                'name' => 'inventory staff',
                'icon_class' => 'fa-solid fa-user',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.roles.create'))
            ->assertSessionHasErrors('name');
    }

    public function test_superadmin_role_cannot_be_edited(): void
    {
        $role = Role::where('name', 'superadmin')->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.roles.edit', $role->id))
            ->put(route('admin.roles.update', $role->id), [
                'name' => 'superadmin',
                'icon_class' => 'fa-solid fa-crown',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.roles.edit', $role->id))
            ->assertSessionHas('error', 'The superadmin role cannot be edited.');

        $this->assertSame('active', $role->fresh()->status);
    }
}
