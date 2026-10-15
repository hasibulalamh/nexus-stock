<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level route tests. Login POST requests query the users table, so the
 * in-memory test database is migrated for this class.
 */
class HttpRoutesNoDbTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST-061: GET /login returns 200 with login view (no DB needed for guest GET)
     */
    public function test_get_login_page_returns_ok(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * TEST-062: GET "/" redirects unauthenticated users to login
     */
    public function test_root_route_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('login');
    }

    /**
     * TEST-063: GET "/admin/users/users/list" returns 404 (confirmed fixed - no double prefix)
     */
    public function test_admin_old_double_prefix_route_returns_404(): void
    {
        $response = $this->get('/admin/users/users/list');
        $response->assertNotFound();
    }

    /**
     * TEST-064: GET "/admin/users/list" (SINGLE segment) returns auth redirect (proves bug fixed)
     */
    public function test_clean_users_list_route_exists_and_requires_auth(): void
    {
        $response = $this->get('/admin/users/list');
        $response->assertRedirect('/login');
    }

    /**
     * TEST-065: branch list - old double prefix returns 404, clean prefix works
     */
    public function test_branch_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/branch/branch/list');
        $oldWrong->assertNotFound();

        $fixed = $this->get('/admin/branch/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-066: warehouse list - old double prefix returns 404, clean prefix works
     */
    public function test_warehouse_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/warehouse/warehouse/list');
        $oldWrong->assertNotFound();

        $fixed = $this->get('/admin/warehouse/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-067: designations list - old double prefix returns 404, clean prefix works
     */
    public function test_designations_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/designations/designations/list');
        $oldWrong->assertNotFound();

        $fixed = $this->get('/admin/designations/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-068: locations list - old double prefix returns 404, clean prefix works
     */
    public function test_locations_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/locations/location/list');
        $oldWrong->assertNotFound();

        $fixed = $this->get('/admin/locations/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-069: POST login with invalid email format triggers validation error back
     */
    public function test_login_post_invalid_email_validation_redirects_back(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'anypass',
        ]);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    /**
     * TEST-070: Login with password less than 8 chars fails credentials check
     */
    public function test_login_post_short_password_fails_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'any@example.com',
            'password' => 'short',
        ]);
        $response->assertRedirect('/login');
        // Login controller uses required|string (no min:8), so short password
        // passes validation but fails the credentials check with an email error.
        $response->assertSessionHasErrors('email');
    }
}
