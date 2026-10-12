<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests that do NOT require any database connection.
 * These run safely with any environment.
 */
class NonDbStaticTests extends TestCase
{
    /**
     * TEST-044: Verify PSR-4 namespace declaration matches for ALL 8 Admin controllers
     */
    public function test_all_admin_controllers_use_correct_psr4_namespace(): void
    {
        $controllers = [
            app_path('Http/Controllers/Admin/DashboardController.php'),
            app_path('Http/Controllers/Admin/DepartmentController.php'),
            app_path('Http/Controllers/Admin/DesignationController.php'),
            app_path('Http/Controllers/Admin/LocationController.php'),
            app_path('Http/Controllers/Admin/LoginController.php'),
            app_path('Http/Controllers/Admin/PermissionController.php'),
            app_path('Http/Controllers/Admin/RoleController.php'),
            app_path('Http/Controllers/Admin/UserController.php'),
        ];

        foreach ($controllers as $path) {
            $contents = file_get_contents($path);
            $this->assertStringContainsString(
                'namespace App\Http\Controllers\Admin;',
                $contents,
                basename($path) . ' must use namespace App\Http\Controllers\Admin (uppercase A)'
            );
        }
    }

    /**
     * TEST-045: Verify PSR-4 namespace for Branch and Warehouse controllers
     */
    public function test_subnamespace_controllers_correct_namespace(): void
    {
        $this->assertStringContainsString(
            'namespace App\Http\Controllers\Branch;',
            file_get_contents(app_path('Http/Controllers/Branch/BranchController.php'))
        );
        $this->assertStringContainsString(
            'namespace App\Http\Controllers\Warehouse;',
            file_get_contents(app_path('Http/Controllers/Warehouse/WarehouseController.php'))
        );
    }

    /**
     * TEST-046: UserController is MISSING methods edit, update, delete
     * even though routes are registered for them.
     */
    public function test_user_controller_missing_edit_update_delete_methods(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));

        $this->assertStringContainsString('public function list(', $code);
        $this->assertStringContainsString('public function create(', $code);
        $this->assertStringContainsString('public function store(', $code);

        // Confirming the missing methods
        $this->assertStringNotContainsString('public function edit(', $code,
            'Edit method unexpectedly EXISTS - check route mapping');
        $this->assertStringNotContainsString('public function update(', $code);
        $this->assertStringNotContainsString('public function delete(', $code);
    }

    /**
     * TEST-047: LocationController missing edit/update/delete methods
     */
    public function test_location_controller_missing_crud_methods(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/LocationController.php'));
        $this->assertStringContainsString('public function list(', $code);
        $this->assertStringContainsString('public function create(', $code);
        $this->assertStringContainsString('public function store(', $code);
        $this->assertStringContainsString('public function changeStatus(', $code);

        $this->assertStringNotContainsString('public function edit(', $code);
        $this->assertStringNotContainsString('public function update(', $code);
        $this->assertStringNotContainsString('public function delete(', $code);
    }

    /**
     * TEST-048: BranchController missing getManagers method
     */
    public function test_branch_controller_missing_getmanagers_method(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Branch/BranchController.php'));
        $this->assertStringNotContainsString('function getManagers(', $code,
            'Confirmed getManagers method missing even though route exists');
    }

    /**
     * TEST-049: LoginController missing logout method
     */
    public function test_login_controller_has_no_logout_method(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/LoginController.php'));
        $this->assertStringNotContainsString('function logout', $code);
    }

    /**
     * TEST-050: Warehouse store() passes country_id to create() even though:
     *  a) Model doesn't have country_id in $fillable
     *  b) DB table has no country_id column
     * This is a latent bug - the attribute is silently ignored.
     */
    public function test_warehouse_store_country_id_mass_assignment_mismatch(): void
    {
        $modelCode = file_get_contents(app_path('Models/Warehouse.php'));
        $controllerCode = file_get_contents(app_path('Http/Controllers/Warehouse/WarehouseController.php'));

        // Warehouse model has no country_id in $fillable
        $this->assertStringNotContainsString("'country_id'", $modelCode,
            'Warehouse $fillable should not include country_id because column does not exist');

        // But the store create() sets it
        $this->assertStringContainsString("'country_id' => \$request->country_id,", $controllerCode,
            'Confirmed: Warehouse store injects country_id which will be silently ignored');
    }

    /**
     * TEST-051: Login form validation password has min:8, AdminSeeder uses password '12345678'
     * which is exactly 8 chars. Matches.
     */
    public function test_login_password_min_length_consistent_with_seeder(): void
    {
        $login = file_get_contents(app_path('Http/Controllers/Admin/LoginController.php'));
        $seeder = file_get_contents(database_path('seeders/AdminSeeder.php'));
        $this->assertStringContainsString("'password' => 'required|min:8'", $login);
        $this->assertStringContainsString("bcrypt('12345678')", $seeder);
    }

    /**
     * TEST-052: Verify all Models have correct fillable for actual columns
     * (Static check - no DB needed)
     */
    public function test_user_model_profile_photo_fillable_but_migration_not_nullable(): void
    {
        // User controller stores default 'default.png' for profile_photo when no upload
        // but migration users.profile_photo is declared as string NOT nullable with no default.
        // So the model fillable line is REQUIRED to set it - check it exists:
        $userModel = file_get_contents(app_path('Models/User.php'));
        $this->assertStringContainsString("'profile_photo'", $userModel,
            'User model must be fillable with profile_photo');
    }

    /**
     * TEST-053: Route group middleware - static review of web.php
     * Only 'auth' middleware is applied. No role/permission middleware.
     */
    public function test_web_route_group_has_only_auth_middleware(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        // Admin group
        $this->assertMatchesRegularExpression(
            "/Route::group\(\['middleware'\s*=>\s*\['auth'\]/",
            $routes,
            'Admin route group has middleware [auth] only - no permission gates'
        );

        // No 'can:' or 'role:' middleware in the routes file
        $this->assertStringNotContainsString("'can:", $routes,
            'Confirmed: no Laravel authorization via can middleware in routes file');
        $this->assertStringNotContainsString("'role:", $routes,
            'Confirmed: no role middleware in routes file');
    }

    /**
     * TEST-054: AdminSeeder password profile_photo path mismatch
     * profile_photo set to '/uploads/users/default.png' (plural) but
     * UserController uploads to '/uploads/user/' (singular) and stores default as 'default.png' (no dir prefix)
     */
    public function test_seeder_profile_photo_path_mismatch_with_controller(): void
    {
        $seeder = file_get_contents(database_path('seeders/AdminSeeder.php'));
        $userController = file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));

        $this->assertStringContainsString("'profile_photo' => '/uploads/users/default.png'", $seeder,
            'Seeder writes /uploads/users/ (plural + directory)');
        $this->assertStringContainsString('$filename = \'default.png\';', $userController,
            'Controller stores default without directory prefix, different path convention');
    }

    /**
     * TEST-055: RoleController edit() uses find($id), not findOrFail()
     * Passing bad id yields null, and passing null to view may cause issues.
     */
    public function test_role_edit_uses_find_instead_of_findOrFail(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/RoleController.php'));
        // edit method at line
        $this->assertMatchesRegularExpression('/public function edit\(\$id\)\s*\{\s*\$role = Role::find\(\$id\);/',
            $code,
            'Confirmed: Role::find($id) - not findOrFail, 404 not guaranteed');
    }

    /**
     * TEST-056: Designation store() missing success flash message
     * (redirect route, no ->with('success', ...))
     */
    public function test_designation_store_and_update_missing_flash_success(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/DesignationController.php'));
        // store redirect
        $this->assertStringContainsString(
            "return redirect()->route('admin.designations.list');\n",
            $code,
            'Designation store redirects without success session message'
        );
    }

    /**
     * TEST-057: User store redirect route('admin.users.list') — but route URL
     * is admin/users/users/list (due to route prefix duplication). Bug is the URL,
     * not the route name resolution.
     */
    public function test_user_controller_redirects_to_valid_route_names(): void
    {
        $userController = file_get_contents(app_path('Http/Controllers/Admin/UserController.php'));
        // route name is used, not a hardcoded URL, so redirect goes to the right named route
        $this->assertStringContainsString("route('admin.users.list')", $userController);
    }

    /**
     * TEST-058: Warehouse and Branch delete() use find(nullable) then ->delete() without guard
     */
    public function test_warehouse_delete_missing_find_or_fail_guard(): void
    {
        $warehouse = file_get_contents(app_path('Http/Controllers/Warehouse/WarehouseController.php'));
        $branch = file_get_contents(app_path('Http/Controllers/Branch/BranchController.php'));

        $this->assertStringContainsString('$warehouse = Warehouse::find($id);', $warehouse);
        $this->assertStringContainsString('$branch = Branch::find($id);', $branch);
    }

    /**
     * TEST-059: Locations store imports CSV in a loop without DB transaction
     * partial writes can occur.
     */
    public function test_location_csv_import_without_transaction_boundary(): void
    {
        $code = file_get_contents(app_path('Http/Controllers/Admin/LocationController.php'));

        // Contains while (($data = fgetcsv...) loop
        $this->assertStringContainsString('while (($data = fgetcsv(', $code);
        // No DB::beginTransaction or DB::transaction found
        $this->assertStringNotContainsString('DB::beginTransaction', $code);
        $this->assertStringNotContainsString('DB::transaction(', $code);
    }

    /**
     * TEST-060: Welcome view exists but there is no route to serve "/"
     */
    public function test_welcome_view_exists_without_route(): void
    {
        $this->assertFileExists(resource_path('views/welcome.blade.php'));
        $routes = file_get_contents(base_path('routes/web.php'));
        $this->assertStringNotContainsString("Route::get('/'", $routes);
        $this->assertStringNotContainsString("view('welcome')", $routes);
    }
}
