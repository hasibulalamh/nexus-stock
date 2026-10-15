<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;
    protected Designation $designation;
    protected User $superadmin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->superadmin = User::where('email', 'superadmin@storify.com')->firstOrFail();
        $this->department = Department::where('name', 'Administration')->firstOrFail();
        $this->designation = Designation::where('name', 'Admin')->firstOrFail();
    }

    private function makeUser(string $name, array $permissions): User
    {
        $role = Role::create([
            'name' => strtolower($name).'-role',
            'guard_name' => 'web',
            'icon_class' => 'fa-solid fa-user',
            'status' => 'active',
            'dashboard_route' => 'admin.dashboard',
        ]);
        foreach ($permissions as $permission) {
            $permissionModel = Permission::where('name', $permission)->firstOrFail();
            $role->givePermissionTo($permissionModel);
        }

        $user = User::create([
            'full_name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'password123',
            'phone' => '01712345678',
            'gender' => 'male',
            'employee_id' => strtoupper($name).'-001',
            'role_id' => $role->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_branch_view_user_sees_only_branch_link_and_lands_on_branches(): void
    {
        $user = $this->makeUser('Branch Staff', ['branches.view']);

        $this->actingAs($user)->get(route('admin.branch.list'))
            ->assertOk()
            ->assertSee('Branches')
            ->assertDontSee('Users')
            ->assertDontSee('Dashboard');
        $this->actingAs($user)->get(route('admin.users.list'))->assertForbidden();

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('admin.branch.list'));
    }

    public function test_superadmin_sees_dashboard_and_all_module_links(): void
    {
        $this->actingAs($this->superadmin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Users')
            ->assertSee('Branches')
            ->assertSee('Warehouses');
    }

    public function test_scoped_dashboard_counts_allocated_branches_and_warehouses(): void
    {
        $assignedBranch = Branch::create([
            'name' => 'Scoped Branch',
            'code' => 'SCOPED-BRANCH',
            'slug' => 'scoped-branch',
            'location_id' => 1,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
        Branch::create([
            'name' => 'Hidden Branch',
            'code' => 'HIDDEN-BRANCH',
            'slug' => 'hidden-branch',
            'location_id' => 1,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
        $assignedWarehouse = Warehouse::create([
            'name' => 'Scoped Warehouse',
            'code' => 'SCOPED-WAREHOUSE',
            'slug' => 'scoped-warehouse',
            'location_id' => '1',
            'branch_id' => $assignedBranch->id,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
        Warehouse::create([
            'name' => 'Hidden Warehouse',
            'code' => 'HIDDEN-WAREHOUSE',
            'slug' => 'hidden-warehouse',
            'location_id' => '1',
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $user = $this->makeUser('Scoped Dashboard', ['dashboard.view', 'branches.view', 'warehouses.view']);
        $user->branches()->attach($assignedBranch->id);
        $user->warehouses()->attach($assignedWarehouse->id);

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('1')
            ->assertSee('Scoped Dashboard');
    }

    public function test_user_without_permissions_gets_no_access_landing(): void
    {
        $user = $this->makeUser('No Access', []);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertSee('No access assigned');
    }
}
