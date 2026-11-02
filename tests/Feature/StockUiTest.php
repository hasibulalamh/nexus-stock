<?php

namespace Tests\Feature;

use App\Events\LowStockAlertOpened;
use App\Listeners\SendLowStockNotification;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockUiTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->superadmin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);
        return Product::create(['sku' => uniqid('UI-'), 'name' => 'UI product', 'slug' => uniqid('ui-'), 'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1, 'selling_price' => 2, 'reorder_level' => 5, 'track_stock' => true, 'status' => 'active']);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create(['name' => 'UI warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'), 'location_id' => 1, 'capacity' => 10, 'starting_date' => '2026-01-01', 'status' => 'active']);
    }

    private function branch(?int $warehouseId = null): Branch
    {
        return Branch::create(['name' => uniqid('Branch '), 'code' => uniqid('BR-'), 'slug' => uniqid('br-'), 'location_id' => 1, 'warehouse_id' => $warehouseId, 'capacity' => 10, 'starting_date' => '2026-01-01', 'status' => 'active']);
    }

    public function test_listener_failure_does_not_rollback_movement_or_alert(): void
    {
        Log::spy();
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $recipient = $this->superadmin->replicate();
        $recipient->email = uniqid('notify-').'@example.com';
        $recipient->full_name = 'Failing notifier';
        $recipient->status = 'active';
        $recipient->save();
        $recipient->givePermissionTo('stock.alerts.receive');
        $recipient->warehouses()->attach($warehouse);

        $listener = Mockery::mock(SendLowStockNotification::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $listener->shouldReceive('notifyUser')->andThrow(new \RuntimeException('notification failure'));
        app()->instance(SendLowStockNotification::class, $listener);

        app(StockService::class)->recordMovement($this->superadmin, $this->product(), 'branch', $branch->id, 'opening', 4);

        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('stock_alerts', 1);
        Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'Unable to send low-stock notification.'))->once();
    }

    public function test_stock_pages_require_permissions(): void
    {
        $user = $this->superadmin->replicate();
        $user->email = uniqid('user-').'@example.com';
        $user->full_name = 'No stock permissions';
        $user->save();

        foreach ([
            'admin.stock.levels' => [],
            'admin.stock.movements' => [],
            'admin.stock.opening' => [],
            'admin.stock.alerts' => [],
        ] as $route => $parameters) {
            $this->actingAs($user)->get(route($route, $parameters))->assertForbidden();
        }
    }

    public function test_opening_stock_is_recorded_once_and_second_opening_is_rejected(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        $user = $this->superadmin;
        $response = $this->actingAs($user)->post(route('admin.stock.opening.store'), [
            'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'product_id' => $product->id,
            'quantity' => '1.000', 'occurred_at' => '2026-11-03', 'notes' => 'Initial',
        ])->assertRedirect(route('admin.stock.levels'));
        $this->assertDatabaseCount('stock_movements', 1);

        $this->actingAs($user)->from(route('admin.stock.opening'))->post(route('admin.stock.opening.store'), [
            'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'product_id' => $product->id,
            'quantity' => '2.000', 'occurred_at' => '2026-11-03',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_opening_stock_rejects_invalid_quantity_and_unwritable_location(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        foreach (['0', '-1', '1.0001'] as $quantity) {
            $this->actingAs($this->superadmin)->post(route('admin.stock.opening.store'), [
                'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'product_id' => $product->id,
                'quantity' => $quantity, 'occurred_at' => '2026-11-03',
            ])->assertSessionHasErrors('quantity');
        }

        $user = $this->superadmin->replicate();
        $user->email = uniqid('limited-').'@example.com';
        $user->full_name = 'Limited stock user';
        $user->role_id = Role::create(['name' => 'limited-ui', 'guard_name' => 'web'])->id;
        $user->access_all = false;
        $user->status = 'active';
        $user->save();
        $user->syncRoles(['limited-ui']);
        $user->givePermissionTo('stock.opening');
        $response = $this->actingAs($user)->post(route('admin.stock.opening.store'), [
            'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'product_id' => $product->id,
            'quantity' => '1.000', 'occurred_at' => '2026-11-03',
        ]);
        $response->assertForbidden();
    }

    public function test_alert_url_is_filtered_to_the_alert(): void
    {
        $branch = $this->branch();
        $product = $this->product();
        $alert = StockAlert::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity_at_alert' => 1, 'minimum_at_alert' => 5, 'status' => 'open', 'open_marker' => 'open', 'opened_at' => now()]);

        $this->actingAs($this->superadmin)->get(route('admin.stock.alerts', ['alert_id' => $alert->id]))
            ->assertOk()->assertSee($product->name);
    }
}
