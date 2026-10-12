<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * HTTP-level tests that don't require database.
 * Use APP_KEY set to avoid encryption issues; use array session/cache from phpunit.xml.
 * Do NOT use RefreshDatabase because SQLite driver is unavailable.
 */
class HttpRoutesNoDbTest extends TestCase
{
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
     * TEST-062: GET "/" returns 404 because no route is defined
     */
    public function test_root_route_is_missing_404(): void
    {
        $response = $this->get('/');
        $response->assertStatus(404);
    }

    /**
     * TEST-063: GET "/admin/users/users/list" returns 404 AFTER FIX
     * (Confirms prefix duplication bug is resolved)
     */
    public function test_admin_old_double_prefix_route_no_longer_registered_404(): void
    {
        $oldWrong = $this->get('/admin/users/users/list');
        $oldWrong->assertStatus(
            404,
            'POST-FIX: double-segment URL /admin/users/users/list should now be 404 (removed duplication)'
        );
    }

    /**
     * TEST-064: GET "/admin/users/list" (SINGLE segment) returns auth redirect (200 through auth check)
     * This PROVES the prefix duplication bug is fixed - the clean URL now works.
     */
    public function test_clean_users_list_route_exists_and_requires_auth(): void
    {
        $response = $this->get('/admin/users/list');
        $response->assertRedirect('/login');
    }

    /**
     * TEST-065: branch list cleanup verification
     */
    public function test_branch_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/branch/branch/list');
        $oldWrong->assertStatus(404);

        $fixed = $this->get('/admin/branch/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-066: warehouse list cleanup verification
     */
    public function test_warehouse_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/warehouse/warehouse/list');
        $oldWrong->assertStatus(404);

        $fixed = $this->get('/admin/warehouse/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-067: designations list cleanup verification
     */
    public function test_designations_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/designations/designations/list');
        $oldWrong->assertStatus(404);

        $fixed = $this->get('/admin/designations/list');
        $fixed->assertRedirect('/login');
    }

    /**
     * TEST-068: locations list cleanup verification (also removes singular/plural mismatch)
     */
    public function test_locations_list_fixed_clean_url(): void
    {
        $oldWrong = $this->get('/admin/locations/location/list');
        $oldWrong->assertStatus(
            404,
            'POST-FIX: old locations/location/list URL should be gone'
        );

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
     * TEST-070: Login with password less than 8 chars triggers validation
     */
    public function test_login_post_short_password_triggers_min8_validation(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'any@example.com',
            'password' => 'short',
        ]);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');
    }
}
