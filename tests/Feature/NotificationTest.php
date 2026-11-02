<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NotificationTest extends TestCase
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
        $category = Category::create(['name' => uniqid('Category'), 'slug' => uniqid('category-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('NOTICE-'), 'name' => 'Notice product', 'slug' => uniqid('notice-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => '5.000', 'track_stock' => true, 'status' => 'active',
        ]);
    }

    private function warehouse(string $name): Warehouse
    {
        return Warehouse::create([
            'name' => $name, 'code' => uniqid('WH-'), 'slug' => uniqid('warehouse-'),
            'location_id' => 1, 'capacity' => '100', 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function branch(string $name, ?int $warehouseId = null): Branch
    {
        return Branch::create([
            'name' => $name, 'code' => uniqid('BR-'), 'slug' => uniqid('branch-'),
            'location_id' => 1, 'warehouse_id' => $warehouseId, 'capacity' => '100',
            'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function user(string $email): User
    {
        $user = $this->superadmin->replicate();
        $user->full_name = $email;
        $user->email = $email;
        $user->status = 'active';
        $user->save();

        return $user;
    }

    public function test_notifications_go_only_to_active_assigned_users_with_permission(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse('Assigned warehouse');
        $branch = $this->branch('Assigned branch', $warehouse->id);
        $recipient = $this->user('recipient@example.com');
        $recipient->givePermissionTo('stock.alerts.receive');
        $recipient->warehouses()->attach($warehouse);
        $withoutPermission = $this->user('without@example.com');
        $withoutPermission->warehouses()->attach($warehouse);
        $otherWarehouseUser = $this->user('other@example.com');
        $otherWarehouseUser->givePermissionTo('stock.alerts.receive');
        $otherWarehouseUser->warehouses()->attach($this->warehouse('Other warehouse'));
        $inactive = $this->user('inactive@example.com');
        $inactive->givePermissionTo('stock.alerts.receive');
        $inactive->warehouses()->attach($warehouse);
        $inactive->update(['status' => 'inactive']);

        app(StockService::class)->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $recipient->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $withoutPermission->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $otherWarehouseUser->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $inactive->id)->count());
        $this->assertNotNull(StockAlert::firstOrFail()->last_notified_at);
    }

    public function test_no_warehouse_alert_goes_to_view_all_users_only(): void
    {
        $product = $this->product();
        $branch = $this->branch('Unassigned branch');
        $recipient = $this->user('viewall@example.com');
        $recipient->givePermissionTo('stock.alerts.view_all');
        $other = $this->user('receive@example.com');
        $other->givePermissionTo('stock.alerts.receive');

        app(StockService::class)->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $recipient->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $other->id)->count());
    }

    public function test_open_alert_is_not_notified_again_on_dedupe_updates(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse('Dedupe warehouse');
        $branch = $this->branch('Dedupe branch', $warehouse->id);
        $recipient = $this->user('dedupe@example.com');
        $recipient->givePermissionTo('stock.alerts.receive');
        $recipient->warehouses()->attach($warehouse);
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'sale', -1);

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $recipient->id)->count());
    }

    public function test_notification_endpoints_are_scoped_to_the_authenticated_user(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other-owner@example.com');
        $owner->notify(new \App\Notifications\LowStockNotification(
            StockAlert::create([
                'product_id' => $this->product()->id, 'branch_id' => $this->branch('Endpoint branch')->id,
                'quantity_at_alert' => '1.000', 'minimum_at_alert' => '5.000', 'status' => 'open',
                'open_marker' => 'open', 'opened_at' => now(),
            ])
        ));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($owner)->getJson(route('notifications.index'))
            ->assertOk()->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'latest');
        $this->actingAs($other)->post(route('notifications.read', $notification->id))
            ->assertNotFound();
        $this->actingAs($owner)->post(route('notifications.read', $notification->id))
            ->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        $owner->notify(new \App\Notifications\LowStockNotification($notification->fresh()->data ? StockAlert::findOrFail($notification->data['alert_id']) : StockAlert::firstOrFail()));
        $this->actingAs($owner)->postJson(route('notifications.read-all'))
            ->assertOk()->assertJson(['unread_count' => 0]);
    }

    public function test_notification_routes_require_authentication(): void
    {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
    }
}
