<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartupAndRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate');
    }

    protected function afterRefreshingDatabase()
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    /**
     * TEST-001: Application bootstraps without errors
     */
    public function test_application_bootstraps(): void
    {
        $this->assertNotEmpty(config('app.name'));
        $this->assertEquals('testing', config('app.env'));
    }

    /**
     * TEST-002: Routes register successfully (no ReflectionException on controller classes)
     */
    public function test_routes_register_successfully(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->map(fn($r) => $r->uri())
            ->values()
            ->toArray();

        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes, 'Expected at least login route to be registered');
        $this->assertContains('login', $routes);
    }

    /**
     * TEST-003: Login page is reachable as guest
     */
    public function test_login_page_reachable_as_guest(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    /**
     * TEST-004: Protected dashboard route redirects guests to login
     */
    public function test_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * TEST-005: Route prefix confirmed for users (no duplication)
     * The admin.users.list route has clean URL without double segment
     */
    public function test_route_prefix_for_users(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.users.list')
            ?->uri();

        // Fixed: admin/users group + users/list = admin/users/list (no duplication)
        $this->assertSame(
            'admin/users/list',
            $uri,
            'Fixed: admin/users + users/list should produce single segment'
        );
    }

    /**
     * TEST-006: Route prefix confirmed for branch (no duplication)
     */
    public function test_route_prefix_for_branch(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.branch.list')
            ?->uri();

        // Fixed: admin/branch + list = admin/branch/list (no duplication)
        $this->assertSame(
            'admin/branch/list',
            $uri,
            'Fixed: admin/branch + list should produce single segment'
        );
    }

    /**
     * TEST-007: Route prefix confirmed for warehouse (no duplication)
     */
    public function test_route_prefix_for_warehouse(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.warehouse.list')
            ?->uri();

        // Fixed: admin/warehouse + list = admin/warehouse/list (no duplication)
        $this->assertSame(
            'admin/warehouse/list',
            $uri,
            'Fixed: admin/warehouse + list should produce single segment'
        );
    }

    /**
     * TEST-008: Route prefix confirmed for designations (no duplication)
     */
    public function test_route_prefix_for_designations(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.designations.list')
            ?->uri();

        // Fixed: admin/designations + list = admin/designations/list (no duplication)
        $this->assertSame(
            'admin/designations/list',
            $uri,
            'Fixed: admin/designations + list should produce single segment'
        );
    }

    /**
     * TEST-009: Route prefix confirmed for locations (no duplication)
     */
    public function test_route_prefix_for_locations(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.locations.list')
            ?->uri();

        // Fixed: admin/locations + list = admin/locations/list (no duplication, no singular/plural mismatch)
        $this->assertSame(
            'admin/locations/list',
            $uri,
            'Fixed: admin/locations + list should produce single segment'
        );
    }

    /**
     * TEST-010: Logout route exists and logs out the user
     */
    public function test_logout_route_exists_and_logs_out(): void
    {
        $admin = User::where('email', 'superadmin@storify.com')->first();
        $this->assertNotNull($admin, 'Admin user must be seeded');
        $response = $this->actingAs($admin)->post(route('logout'));
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * TEST-011: Root route redirects based on authentication status
     */
    public function test_root_redirects_based_on_auth(): void
    {
        // Unauthenticated user redirected to login
        $response = $this->get('/');
        $response->assertRedirect('login');

        // Authenticated user redirected to dashboard
        $admin = User::where('email', 'superadmin@storify.com')->first();
        $this->assertNotNull($admin, 'Admin user must be seeded');
        $response = $this->actingAs($admin)->get('/');
        $response->assertRedirect(route('admin.dashboard'));
    }
}
