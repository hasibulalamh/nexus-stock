<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    /**
     * TEST-001: root redirects unauthenticated users to login
     */
    public function test_root_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('login');
    }

    /**
     * TEST-001 alternate: authenticated user redirected to dashboard
     */
    public function test_root_redirects_authenticated_to_dashboard(): void
    {
        $user = User::where('email', 'superadmin@storify.com')->first();
        $this->assertNotNull($user, 'User must be seeded for authenticated test');
        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect(route('admin.dashboard'));
    }
}
