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

/**
 * PART B: branch / warehouse user scope.
 * NOTE: these tests need a database driver (phpunit.xml points at in-memory
 * SQLite, which is unavailable in this environment - pdo_sqlite missing).
 */
class AccessScopeTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Operations',
            'code' => 'OPS',
            'status' => 'active',
        ]);

        $this->designation = Designation::create([
            'name' => 'Officer',
            'code' => 'OFC',
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);
    }

    private function makeUser(string $roleName, array $attributes = [], array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['status' => 'active']
            );
        }
        $role->syncPermissions($permissions);

        $user = User::create(array_merge([
            'full_name' => 'Scoped User',
            'email' => uniqid('user') . '@example.com',
            'password' => 'secret-password',
            'phone' => '01700000000',
            'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')),
            'role_id' => $role->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => 'active',
            'access_all' => false,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    private function makeWarehouse(string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name),
            'location_id' => 1,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function makeBranch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name),
            'location_id' => 1,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    public function test_scoped_user_sees_only_assigned_warehouses(): void
    {
        $assigned = $this->makeWarehouse('Alpha Depot');
        $this->makeWarehouse('Beta Depot');
        $this->makeWarehouse('Gamma Depot');

        $user = $this->makeUser('staff', [], ['warehouses.view']);
        $user->warehouses()->attach($assigned->id);

        $response = $this->actingAs($user)->get(route('admin.warehouse.list'));

        $response->assertOk();
        $response->assertSee('Alpha Depot');
        $response->assertDontSee('Beta Depot');
        $response->assertDontSee('Gamma Depot');
    }

    public function test_direct_url_to_unassigned_warehouse_is_refused(): void
    {
        $this->makeWarehouse('Alpha Depot');
        $unassigned = $this->makeWarehouse('Beta Depot');

        $user = $this->makeUser('staff', [], ['warehouses.view']);

        $this->actingAs($user)
            ->get(route('admin.warehouse.edit', $unassigned->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('admin.warehouse.delete', $unassigned->id))
            ->assertNotFound();
    }

    public function test_access_all_user_sees_every_warehouse(): void
    {
        $this->makeWarehouse('Alpha Depot');
        $this->makeWarehouse('Beta Depot');

        $user = $this->makeUser('staff', ['access_all' => true], ['warehouses.view']);

        $response = $this->actingAs($user)->get(route('admin.warehouse.list'));

        $response->assertOk();
        $response->assertSee('Alpha Depot');
        $response->assertSee('Beta Depot');
    }

    public function test_superadmin_sees_every_warehouse_without_access_all_flag(): void
    {
        $this->makeWarehouse('Alpha Depot');
        $this->makeWarehouse('Beta Depot');

        $user = $this->makeUser('superadmin');

        $response = $this->actingAs($user)->get(route('admin.warehouse.list'));

        $response->assertOk();
        $response->assertSee('Alpha Depot');
        $response->assertSee('Beta Depot');
    }

    public function test_scoped_user_sees_only_assigned_branches(): void
    {
        $assigned = $this->makeBranch('Alpha Branch');
        $this->makeBranch('Beta Branch');

        $user = $this->makeUser('staff', [], ['branches.view']);
        $user->branches()->attach($assigned->id);

        $response = $this->actingAs($user)->get(route('admin.branch.list'));

        $response->assertOk();
        $response->assertSee('Alpha Branch');
        $response->assertDontSee('Beta Branch');
    }

    public function test_direct_url_to_unassigned_branch_is_refused(): void
    {
        $this->makeBranch('Alpha Branch');
        $unassigned = $this->makeBranch('Beta Branch');

        $user = $this->makeUser('staff', [], ['branches.view']);

        $this->actingAs($user)
            ->get(route('admin.branch.edit', $unassigned->id))
            ->assertNotFound();
    }

    public function test_user_without_assignments_sees_nothing(): void
    {
        $this->makeWarehouse('Alpha Depot');
        $this->makeBranch('Alpha Branch');

        $user = $this->makeUser('staff', [], ['warehouses.view', 'branches.view']);

        $this->actingAs($user)->get(route('admin.warehouse.list'))
            ->assertOk()
            ->assertDontSee('Alpha Depot');

        $this->actingAs($user)->get(route('admin.branch.list'))
            ->assertOk()
            ->assertDontSee('Alpha Branch');
    }
}
