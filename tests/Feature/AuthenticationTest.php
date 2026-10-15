<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        // Only seed permission seeder + the roles/users the tests create.
        // BangladeshLocationSeeder is omitted to avoid UniqueConstraintViolationException.
        // Also seed the superadmin user so authentication tests can find it.
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    /**
     * TEST-012: User can view login form
     */
    public function test_login_view_returns_200(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * TEST-013: Successful login redirects based on role dashboard_route
     */
    public function test_successful_login_with_superadmin_redirects(): void
    {
        $user = User::where('email', 'superadmin@storify.com')->first();
        $this->assertNotNull($user, 'Seeded user must exist');

        $response = $this->post(route('login.submit'), [
            'email' => 'superadmin@storify.com',
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * TEST-014: Invalid password returns errors
     */
    public function test_login_with_wrong_password_returns_error(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'superadmin@storify.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * TEST-015: Invalid email format fails validation
     */
    public function test_login_invalid_email_fails_validation(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'not-an-email',
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    /**
     * TEST-016: Short password fails login (credentials check, not min:8 validation)
     */
    public function test_login_short_password_fails_validation(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'superadmin@storify.com',
            'password' => '1234567',
        ]);

        $response->assertRedirect(route('login'));
        // Login controller uses required|string (no min:8), so short password
        // passes validation but fails the credentials check with an email error.
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * TEST-017: All admin routes protected by auth middleware redirect guest to login
     */
    public function test_all_admin_routes_require_authentication(): void
    {
        $adminRouteNames = [
            'admin.dashboard',
            'admin.roles.list',
            'admin.roles.create',
            'admin.permissions.list',
            'admin.users.list',
            'admin.users.create',
            'admin.departments.list',
            'admin.departments.create',
            'admin.designations.list',
            'admin.designations.create',
            'admin.locations.list',
            'admin.locations.create',
            'admin.warehouse.list',
            'admin.warehouse.create',
            'admin.branch.list',
            'admin.branch.create',
        ];

        foreach ($adminRouteNames as $name) {
            $route = route($name);
            $response = $this->get($route);
            $response->assertRedirect(
                route('login'),
                "Route $name ($route) should redirect guest to login"
            );
        }
    }

    /**
     * TEST-018: Authenticated user can access dashboard
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'superadmin@storify.com')->first();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /**
     * TEST-019: Login with non-existent email fails
     */
    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'nobody@example.com',
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
