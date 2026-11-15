<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
    }

    public function test_every_parameterless_admin_get_route_renders_without_a_server_error(): void
    {
        $admin = User::where('email', 'superadmin@storify.com')->firstOrFail();

        $routes = collect(Route::getRoutes())->filter(function ($route): bool {
            $name = (string) $route->getName();

            return in_array('GET', $route->methods(), true)
                && str_starts_with($name, 'admin.')
                && ! str_contains($route->uri(), '{');
        });

        $this->assertNotEmpty($routes);

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get('/'.$route->uri());
            $this->assertLessThan(
                500,
                $response->status(),
                "GET /{$route->uri()} ({$route->getName()}) returned {$response->status()}"
            );
        }

        $this->assertGreaterThan(40, $routes->count(), 'Expected a broad route sweep.');
    }
}
