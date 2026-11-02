<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Events\LowStockAlertOpened;
use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockAlertTest extends TestCase
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
            'sku' => uniqid('ALERT-'), 'name' => 'Alert product', 'slug' => uniqid('alert-'),
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

    public function test_alert_opens_dedupes_resolves_and_reopens(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $branch = $this->branch('Alert Branch');
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, StockMovementType::SALE, -1);
        $this->assertSame(1, StockAlert::where('status', 'open')->count());
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'purchase', 3);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, StockMovementType::SALE, -2);
        $this->assertSame(1, StockAlert::where('status', 'resolved')->count());
        $this->assertSame(1, StockAlert::where('status', 'open')->count());
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 2);
    }

    public function test_warehouse_movements_do_not_alert_and_unassigned_branch_is_flagged(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $warehouse = $this->warehouse('No Alert Warehouse');
        $branch = $this->branch('No Warehouse Branch');
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 1);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 1);
        $this->assertNull(StockAlert::firstOrFail()->warehouse_id);
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);
    }

    public function test_transfer_in_resolves_and_rollback_removes_alert(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $warehouse = $this->warehouse('Supplying Warehouse');
        $branch = $this->branch('Receiving Branch', $warehouse->id);
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);
        $this->assertSame($warehouse->id, StockAlert::firstOrFail()->warehouse_id);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 2);
        $service->transfer($this->superadmin, $product, 'warehouse', $warehouse->id, 'branch', $branch->id, 2);
        $this->assertSame(0, StockAlert::where('status', 'open')->count());

        try {
            DB::transaction(function () use ($service, $product, $branch): void {
                $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, StockMovementType::SALE, -5);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, StockAlert::where('status', 'open')->count());
    }

    public function test_alert_uses_override_default_and_no_threshold_correctly(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $branch = $this->branch('Override Branch');
        $level = \App\Models\StockLevel::create([
            'product_id' => $product->id,
            'location_type' => 'branch',
            'location_id' => $branch->id,
            'quantity' => '0.000',
            'min_quantity' => '2.000',
        ]);
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 1);
        $this->assertSame(1, StockAlert::count());
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'purchase', 2);
        $this->assertSame(1, StockAlert::where('status', 'resolved')->count());

        $product->update(['reorder_level' => null]);
        $level->update(['min_quantity' => null]);
        $other = $this->branch('No Threshold Branch');
        $service->recordMovement($this->superadmin, $product, 'branch', $other->id, 'opening', 0.001);
        $this->assertSame(1, StockAlert::count());
    }

    public function test_alert_opened_inside_rolled_back_outer_transaction_is_not_dispatched(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $branch = $this->branch('Rollback Branch');
        $service = app(StockService::class);

        try {
            DB::transaction(function () use ($service, $product, $branch): void {
                $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 1);
                throw new \RuntimeException('outer rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, StockAlert::count());
        Event::assertNotDispatched(LowStockAlertOpened::class);
    }

    public function test_committed_alert_dispatches_exactly_once_and_transfer_destination_does_too(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $branch = $this->branch('Committed Branch');
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 1);
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);

        Event::fake([LowStockAlertOpened::class]);
        $source = $this->warehouse('Transfer Source');
        $destination = $this->branch('Transfer Destination');
        $service->recordMovement($this->superadmin, $product, 'warehouse', $source->id, 'opening', 3);
        $service->transfer($this->superadmin, $product, 'warehouse', $source->id, 'branch', $destination->id, 3);

        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);
    }

    public function test_setting_minimum_re_evaluates_current_branch_quantity_without_movement(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $branch = $this->branch('Threshold Re-evaluation Branch');
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 4);
        $level = \App\Models\StockLevel::where('location_id', $branch->id)->firstOrFail();

        $this->actingAs($this->superadmin)
            ->post(route('admin.stock.levels.minimum', $level), ['min_quantity' => '5'])
            ->assertRedirect();
        $this->assertSame(1, StockAlert::where('status', 'open')->count());
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);

        $this->actingAs($this->superadmin)
            ->post(route('admin.stock.levels.minimum', $level), ['min_quantity' => '3'])
            ->assertRedirect();
        $this->assertSame(1, StockAlert::where('status', 'resolved')->count());

        $this->actingAs($this->superadmin)
            ->post(route('admin.stock.levels.minimum', $level), ['min_quantity' => null])
            ->assertRedirect();
        $this->assertSame(1, StockAlert::where('status', 'resolved')->count());
    }

    public function test_product_reorder_change_re_evaluates_only_levels_without_overrides(): void
    {
        Event::fake([LowStockAlertOpened::class]);
        $product = $this->product();
        $product->update(['reorder_level' => null]);
        $branch = $this->branch('Default Threshold Branch');
        $overridden = $this->branch('Override Threshold Branch');
        $defaultLevel = \App\Models\StockLevel::create([
            'product_id' => $product->id, 'location_type' => 'branch',
            'location_id' => $branch->id, 'quantity' => '4.000',
        ]);
        \App\Models\StockLevel::create([
            'product_id' => $product->id, 'location_type' => 'branch',
            'location_id' => $overridden->id, 'quantity' => '4.000', 'min_quantity' => '2.000',
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('admin.products.update', $product), [
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
            'category_id' => $product->category_id,
            'unit' => 'pcs',
            'cost_price' => $product->cost_price,
            'selling_price' => $product->selling_price,
            'reorder_level' => '5.000',
            'track_stock' => 1,
            'status' => 'active',
        ])->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('5.000', $product->fresh()->reorder_level);

        $this->assertSame(1, StockAlert::where('branch_id', $branch->id)->count());
        $this->assertSame(0, StockAlert::where('branch_id', $overridden->id)->count());
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);

        $this->actingAs($this->superadmin)->put(route('admin.products.update', $product), [
            'sku' => $product->sku, 'name' => $product->name, 'slug' => $product->slug,
            'category_id' => $product->category_id, 'unit' => 'pcs',
            'cost_price' => $product->cost_price, 'selling_price' => $product->selling_price,
            'reorder_level' => '5.000', 'track_stock' => 1, 'status' => 'active',
        ])->assertRedirect();
        Event::assertDispatchedTimes(LowStockAlertOpened::class, 1);
    }
}
