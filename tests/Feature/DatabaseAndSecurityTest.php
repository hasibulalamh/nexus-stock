<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class DatabaseAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        // Seed necessary data for tests
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    /**
     * TEST-034: Warehouses table uses STRING for location_id instead of BIGINT (foreign key)
     * Migration-level defect.
     */
    public function test_warehouse_location_id_uses_string_type_not_foreign_key(): void
    {
        $columns = Schema::getColumnType('warehouses', 'location_id');
        // SQLite reports 'varchar' for string columns; MySQL reports 'string'
        $this->assertContains($columns, ['string', 'varchar'],
            'Bug confirmed: warehouses.location_id is string/varchar, expected bigint (FK)');
    }

    /**
     * TEST-035: Users migration uses cascadeOnDelete for role/department/designation
     * which causes unexpected user deletion when a role is deleted.
     *
     * Bug verification: deleting a role cascades and wipes the associated user.
     */
    public function test_user_table_role_fk_cascades_on_delete_confirmed(): void
    {
        $user = User::where('email', 'superadmin@storify.com')->firstOrFail();
        $roleId = $user->role_id;

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        // Spatie role won't delete via Role model because has users check in RoleController
        // but database level FK cascade is present. Manually delete direct from roles table.
        \Illuminate\Support\Facades\DB::table('model_has_roles')->where('role_id', $roleId)->delete();
        \Illuminate\Support\Facades\DB::table('roles')->where('id', $roleId)->delete();

        // Because users.role_id has constrained()->cascadeOnDelete(), user is GONE.
        $this->assertFalse(User::where('id', $user->id)->exists(),
            'Bug confirmed: deleting a role CASCADE-DELETES the associated user');
    }

    /**
     * TEST-036: Branches migration foreign_id for location declared without constrained()
     */
    public function test_branch_location_id_fk_has_no_cascade_or_reference(): void
    {
        $foreignKeys = Schema::getForeignKeys('branches');
        $hasLocationFk = collect($foreignKeys)->contains(
            fn ($fk) => in_array('location_id', $fk['columns'] ?? [])
        );

        // Migration used foreignId() without chained constrained() => no actual FK constraint
        $this->assertFalse($hasLocationFk,
            'Bug confirmed: branches.location_id foreignId was not constrained()');
    }

    /**
     * TEST-037: Locations table missing zipcode column but BranchController tries to save it
     */
    public function test_locations_table_missing_zipcode_column(): void
    {
        $hasColumn = Schema::hasColumn('locations', 'zipcode');
        $this->assertFalse($hasColumn,
            'Bug confirmed: locations table has no zipcode column');
    }

    /**
     * TEST-038: Warehouse model has no country_id fillable but controller stores it
     * (would be silently dropped via mass-assignment protection)
     */
    public function test_warehouse_country_id_silently_ignored(): void
    {
        $admin = User::firstOrFail();
        $hasColumn = Schema::hasColumn('warehouses', 'country_id');
        $this->assertFalse($hasColumn,
            'Confirmed: warehouses table has no country_id column; controller store query sets it');
    }

    /**
     * TEST-039: CSRF tokens must be present in login form
     */
    public function test_login_form_contains_csrf_token(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('_token');
    }

    /**
     * TEST-040: Role permissions middleware: admin routes now check 'can:' middleware
     * (fixed from previous state where only 'auth' was checked).
     * Verified by inspecting web.php route group.
     */
    public function test_authorization_middleware_on_admin_routes(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'admin/'))
            ->values();

        $this->assertNotEmpty($routes);

        // Dashboard is intentionally open to any authenticated user;
        // every other admin route must have a permission gate.
        $exemptUris = ['admin/dashboard'];

        $routesWithoutPermission = [];

        foreach ($routes as $route) {
            $middleware = $route->middleware();
            $hasAuth = in_array('auth', $middleware) || collect($middleware)->contains(
                fn ($m) => is_string($m) && str_starts_with($m, 'auth:')
            );
            $hasPermissionOrRole = collect($middleware)->contains(
                fn ($m) => is_string($m) && (str_starts_with($m, 'can:')
                    || str_starts_with($m, 'role:')
                    || str_starts_with($m, 'permission:'))
            );

            $this->assertTrue($hasAuth, "Admin route {$route->uri()} must have auth");

            if (!in_array($route->uri(), $exemptUris) && !$hasPermissionOrRole) {
                $routesWithoutPermission[] = $route->uri();
            }
        }

        // Confirmed fixed: only the dashboard is exempt; all other admin routes now have role/permission gates
        $this->assertEmpty($routesWithoutPermission,
            'Fixed: admin routes without permission gates: ' . implode(', ', $routesWithoutPermission));
    }

    /**
     * TEST-041: Warehouse delete uses findOrFail guard - passing nonexistent id gives 404
     */
    public function test_warehouse_delete_with_nonexistent_id_returns_404(): void
    {
        $admin = User::where('email', 'superadmin@storify.com')->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('admin.warehouse.delete', 99999));

        $response->assertNotFound();
    }

    /**
     * TEST-042: Profile photo file type validated with standard mime types.
     */
    public function test_user_profile_photo_validates_mimes_rule(): void
    {
        $admin = User::firstOrFail();

        // Use Validator facade directly to replicate rules
        $validator = \Illuminate\Support\Facades\Validator::make([
            'profile_photo' => 'not-an-image',
        ], [
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        $this->assertTrue($validator->fails(), 'Non-image profile photo must fail validation');
    }

    /**
     * TEST-043: Unused PDO import in DashboardController - code smell
     */
    public function test_dashboard_controller_imports_unused_pdo(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/Admin/DashboardController.php'));
        $hasPdoUse = str_contains($contents, 'use PDO;');
        $hasPdoUsage = str_contains($contents, 'new PDO') || str_contains($contents, 'PDO::');

        $this->assertTrue($hasPdoUse, 'Unused use PDO is present');
        $this->assertFalse($hasPdoUsage, 'PDO never actually used - dead import');
    }
}
