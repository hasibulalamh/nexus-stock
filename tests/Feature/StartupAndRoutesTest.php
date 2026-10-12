<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartupAndRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate');
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
     * TEST-005: Route prefix duplication confirmed for users
     * Expected: The effective URL under admin prefix should not repeat segments
     */
    public function test_route_prefix_duplication_for_users(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.users.list')
            ?->uri();

        // admin/users group adds 'users' prefix, then the route path again adds 'users/list'
        $this->assertSame(
            'admin/users/users/list',
            $uri,
            'Confirmed prefix duplication bug: admin/users + users/list produces double segment'
        );
    }

    /**
     * TEST-006: Route prefix duplication confirmed for branch
     */
    public function test_route_prefix_duplication_for_branch(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.branch.list')
            ?->uri();

        $this->assertSame(
            'admin/branch/branch/list',
            $uri,
            'Confirmed prefix duplication for branch routes'
        );
    }

    /**
     * TEST-007: Route prefix duplication confirmed for warehouse
     */
    public function test_route_prefix_duplication_for_warehouse(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.warehouse.list')
            ?->uri();

        $this->assertSame(
            'admin/warehouse/warehouse/list',
            $uri,
            'Confirmed prefix duplication for warehouse routes'
        );
    }

    /**
     * TEST-008: Route prefix duplication confirmed for designations
     */
    public function test_route_prefix_duplication_for_designations(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.designations.list')
            ?->uri();

        $this->assertSame(
            'admin/designations/designations/list',
            $uri,
            'Confirmed prefix duplication for designations routes'
        );
    }

    /**
     * TEST-009: Route prefix duplication confirmed for locations
     */
    public function test_route_prefix_duplication_for_locations(): void
    {
        $uri = \Illuminate\Support\Facades\Route::getRoutes()
            ->getByName('admin.locations.list')
            ?->uri();

        $this->assertSame(
            'admin/locations/location/list',
            $uri,
            'Confirmed prefix duplication for locations routes (also location vs locations mismatch)'
        );
    }

    /**
     * TEST-010: Missing logout route
     */
    public function test_logout_route_does_not_exist(): void
    {
        $hasLogoutRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->contains(fn($r) => in_array(strtolower($r->uri()), ['logout', 'admin/logout'])
                || $r->getName() === 'logout' || $r->getName() === 'admin.logout');

        $this->assertFalse(
            $hasLogoutRoute,
            'Expected logout route NOT to be present - confirming missing logout'
        );
    }

    /**
     * TEST-011: Missing root "/" route
     */
    public function test_root_route_returns_404(): void
    {
        $response = $this->get('/');
        $response->assertStatus(404);
    }
}
