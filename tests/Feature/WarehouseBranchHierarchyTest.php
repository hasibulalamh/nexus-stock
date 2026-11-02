<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Models\State;
use App\Models\Upazila;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseBranchHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $hierarchy;
    private int $locationId;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
        $this->hierarchy = $this->createHierarchy();
        $this->locationId = Location::create([
            'country_id' => $this->hierarchy['country']->id,
            'state_id' => $this->hierarchy['state']->id,
            'city_id' => $this->hierarchy['city']->id,
            'upazila_id' => null,
        ])->id;
    }

    private function createHierarchy(): array
    {
        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => uniqid('bd-')]);
        $state = State::create([
            'name' => 'Dhaka Division',
            'code' => uniqid('DHK-'),
            'slug' => uniqid('dhaka-'),
            'country_id' => $country->id,
        ]);
        $city = City::create([
            'name' => 'Dhaka',
            'code' => uniqid('CITY-'),
            'slug' => uniqid('city-'),
            'state_id' => $state->id,
        ]);
        $upazila = Upazila::create([
            'name' => 'Gulshan',
            'code' => uniqid('GUL-'),
            'slug' => uniqid('gulshan-'),
            'city_id' => $city->id,
            'postcode' => '1212',
        ]);

        return compact('country', 'state', 'city', 'upazila');
    }

    private function warehouse(string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name),
            'location_id' => $this->locationId,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function branch(string $name, ?int $warehouseId = null): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name),
            'location_id' => $this->locationId,
            'warehouse_id' => $warehouseId,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function branchData(string $name, ?int $warehouseId = null): array
    {
        return [
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name),
            'country_id' => $this->hierarchy['country']->id,
            'state_id' => $this->hierarchy['state']->id,
            'city_id' => $this->hierarchy['city']->id,
            'upazila_id' => null,
            'warehouse_id' => $warehouseId,
            'capacity' => 100,
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ];
    }

    private function scopedUser(string $name, array $permissions): User
    {
        $department = Department::firstOrCreate(['code' => 'OPS'], ['name' => 'Operations', 'status' => 'active']);
        $designation = Designation::firstOrCreate(
            ['code' => 'OFC'],
            ['name' => 'Officer', 'department_id' => $department->id, 'status' => 'active']
        );
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions(collect($permissions)->map(function (string $permission) {
            return Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['status' => 'active']
            );
        }));

        $user = User::create([
            'full_name' => $name,
            'email' => uniqid('hierarchy-') . '@example.com',
            'password' => 'secret-password',
            'phone' => '01700000000',
            'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')),
            'role_id' => $role->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => 'active',
            'access_all' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_branch_store_and_update_save_supplier_and_null_supplier(): void
    {
        $warehouse = $this->warehouse('Primary Depot');
        $response = $this->actingAs($this->admin)->post(
            route('admin.branch.store'),
            $this->branchData('Supplied Branch', $warehouse->id)
        );

        $response->assertRedirect(route('admin.branch.list'));
        $branch = Branch::where('code', 'SUPPLIED-BRANCH')->firstOrFail();
        $this->assertSame($warehouse->id, $branch->warehouse_id);

        $this->actingAs($this->admin)->put(
            route('admin.branch.update', $branch->id),
            $this->branchData('Unassigned Branch')
        )->assertRedirect(route('admin.branch.list'));

        $this->assertNull($branch->fresh()->warehouse_id);
    }

    public function test_invalid_supplier_is_rejected_without_creating_or_changing_branch(): void
    {
        $data = $this->branchData('Invalid Supplier Branch', 999999);
        $this->actingAs($this->admin)
            ->from(route('admin.branch.create'))
            ->post(route('admin.branch.store'), $data)
            ->assertRedirect(route('admin.branch.create'))
            ->assertSessionHasErrors('warehouse_id');
        $this->assertDatabaseMissing('branches', ['code' => 'INVALID-SUPPLIER-BRANCH']);

        $branch = $this->branch('Existing Branch');
        $this->actingAs($this->admin)
            ->from(route('admin.branch.edit', $branch->id))
            ->put(route('admin.branch.update', $branch->id), $this->branchData('Changed Branch', 999999))
            ->assertRedirect(route('admin.branch.edit', $branch->id))
            ->assertSessionHasErrors('warehouse_id');
        $this->assertSame('Existing Branch', $branch->fresh()->name);
        $this->assertNull($branch->fresh()->warehouse_id);
    }

    public function test_non_superadmin_cannot_assign_inaccessible_supplier(): void
    {
        $warehouse = $this->warehouse('Private Depot');
        $user = $this->scopedUser('Branch Creator', ['branches.create']);

        $this->actingAs($user)
            ->from(route('admin.branch.create'))
            ->post(route('admin.branch.store'), $this->branchData('Blocked Branch', $warehouse->id))
            ->assertRedirect(route('admin.branch.create'))
            ->assertSessionHasErrors('warehouse_id');
        $this->assertDatabaseMissing('branches', ['code' => 'BLOCKED-BRANCH']);
    }

    public function test_branch_forms_render_with_and_without_supplier(): void
    {
        $warehouse = $this->warehouse('Visible Depot');
        $assigned = $this->branch('Assigned Branch', $warehouse->id);
        $unassigned = $this->branch('Unassigned Branch');

        $this->actingAs($this->admin)->get(route('admin.branch.create'))
            ->assertOk()->assertSee('Supplying warehouse');
        $this->actingAs($this->admin)->get(route('admin.branch.edit', $assigned->id))
            ->assertOk()->assertSee('Visible Depot')->assertSee('selected', false);
        $this->actingAs($this->admin)->get(route('admin.branch.edit', $unassigned->id))
            ->assertOk()->assertSee('Supplying warehouse');
    }

    public function test_branch_list_displays_supplier_or_not_assigned(): void
    {
        $warehouse = $this->warehouse('Listed Depot');
        $this->branch('Supplied Branch', $warehouse->id);
        $this->branch('No Supplier Branch');

        $this->actingAs($this->admin)->get(route('admin.branch.list'))
            ->assertOk()
            ->assertSee('Listed Depot')
            ->assertSee('Not assigned');
    }

    public function test_warehouse_list_displays_supplied_branch_count_and_form_has_no_branch_select(): void
    {
        $warehouse = $this->warehouse('Counted Depot');
        $this->branch('Counted Branch', $warehouse->id);

        $this->actingAs($this->admin)->get(route('admin.warehouse.list'))
            ->assertOk()->assertSee('Branches supplied')->assertSee('>1<', false);
        $this->actingAs($this->admin)->get(route('admin.warehouse.create'))
            ->assertOk()->assertDontSee('name="branch_id"', false);
    }

    public function test_warehouse_user_reads_supplied_branch_ids_but_cannot_edit_supplied_branch(): void
    {
        $warehouse = $this->warehouse('Assigned Depot');
        $supplied = $this->branch('Supplied Branch', $warehouse->id);
        $user = $this->scopedUser('Warehouse Reader', ['branches.view', 'branches.edit', 'warehouses.view']);
        $user->warehouses()->attach($warehouse->id);

        $this->assertSame([$supplied->id], $user->readableSuppliedBranchIds());
        $this->actingAs($user)->get(route('admin.branch.edit', $supplied->id))->assertNotFound();
        $this->actingAs($user)->get(route('admin.warehouse.list'))->assertOk()->assertSee('Assigned Depot');
    }

    public function test_branch_with_no_supplier_remains_readable_and_superadmin_and_access_all_see_all(): void
    {
        $branch = $this->branch('No Supplier Branch');
        $this->actingAs($this->admin)->get(route('admin.branch.edit', $branch->id))->assertOk();

        $otherWarehouse = $this->warehouse('Other Depot');
        $user = $this->scopedUser('Access All', ['branches.view', 'warehouses.view']);
        $user->update(['access_all' => true]);
        $this->assertNull($user->fresh()->readableSuppliedBranchIds());
        $this->actingAs($user)->get(route('admin.warehouse.list'))
            ->assertOk()->assertSee($otherWarehouse->name);
    }

    public function test_branch_hierarchy_migration_backfills_only_unambiguous_legacy_links(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable();
        });

        try {
            $mappedBranch = $this->branch('Mapped Branch');
            $ambiguousBranch = $this->branch('Ambiguous Branch');
            $mappedWarehouse = $this->warehouse('Mapped Depot');
            $ambiguousOne = $this->warehouse('Ambiguous One');
            $ambiguousTwo = $this->warehouse('Ambiguous Two');
            \DB::table('warehouses')->where('id', $mappedWarehouse->id)->update(['branch_id' => $mappedBranch->id]);
            \DB::table('warehouses')->whereIn('id', [$ambiguousOne->id, $ambiguousTwo->id])
                ->update(['branch_id' => $ambiguousBranch->id]);

            $migration = require base_path('database/migrations/2026_11_02_000001_add_warehouse_id_to_branches_table.php');
            $migration->backfill();

            $this->assertSame($mappedWarehouse->id, $mappedBranch->fresh()->warehouse_id);
            $this->assertNull($ambiguousBranch->fresh()->warehouse_id);
        } finally {
            Schema::table('warehouses', fn (Blueprint $table) => $table->dropColumn('branch_id'));
        }
    }

    public function test_legacy_branch_column_migration_is_safe_when_repeated_on_sqlite(): void
    {
        $migration = require base_path('database/migrations/2026_11_02_000002_drop_branch_id_from_warehouses_table.php');

        $migration->up();
        $this->assertFalse(Schema::hasColumn('warehouses', 'branch_id'));

        $migration->down();
        $this->assertTrue(Schema::hasColumn('warehouses', 'branch_id'));

        $migration->up();
        $this->assertFalse(Schema::hasColumn('warehouses', 'branch_id'));
        $migration->up();
        $this->assertFalse(Schema::hasColumn('warehouses', 'branch_id'));
    }
}
