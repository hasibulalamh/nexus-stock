<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SerialTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(string $name = 'Serial product'): Product
    {
        $category = Category::create(['name' => uniqid('Serial category '), 'slug' => uniqid('serial-category-'), 'status' => 'active']);
        return Product::create([
            'sku' => uniqid('SERIAL-'), 'name' => $name, 'slug' => uniqid('serial-'),
            'category_id' => $category->id, 'unit' => 'pcs', 'cost_price' => 1,
            'selling_price' => 2, 'track_stock' => true, 'tracking_type' => 'serial', 'status' => 'active',
        ]);
    }

    private function warehouse(string $name = 'Serial warehouse'): Warehouse
    {
        return Warehouse::create(['name' => $name, 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'), 'location_id' => 1, 'capacity' => 10, 'starting_date' => '2026-01-01', 'status' => 'active']);
    }

    private function branch(?int $warehouseId = null): Branch
    {
        return Branch::create(['name' => uniqid('Serial branch '), 'code' => uniqid('BR-'), 'slug' => uniqid('br-'), 'location_id' => 1, 'warehouse_id' => $warehouseId, 'capacity' => 10, 'starting_date' => '2026-01-01', 'status' => 'active']);
    }

    public function test_default_tracking_is_quantity_and_serial_opening_creates_units_and_pivots(): void
    {
        $quantity = Product::create([
            'sku' => uniqid('Q-'), 'name' => 'Quantity default', 'slug' => uniqid('quantity-'),
            'category_id' => $this->product()->category_id, 'unit' => 'pcs', 'track_stock' => true, 'status' => 'active',
        ]);
        $this->assertSame('quantity', $quantity->tracking_type);

        $product = $this->product();
        $warehouse = $this->warehouse();
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['IMEI-1', 'IMEI-2']);

        $this->assertCount(2, $result['units']);
        $this->assertSame('U'.str_pad((string) $result['units'][0]->id, 11, '0', STR_PAD_LEFT), $result['units'][0]->unit_code);
        $this->assertDatabaseCount('stock_movement_units', 2);
        $this->assertSame('2.000', StockLevel::firstOrFail()->quantity);
        $this->assertSame(2, StockUnit::where('status', 'in_stock')->count());
    }

    public function test_duplicate_serials_are_rejected_but_other_products_may_reuse_them(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $this->expectException(\InvalidArgumentException::class);
        $service->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['DUP', 'DUP']);
    }

    public function test_system_codes_only_opening_creates_null_serials_with_distinct_codes(): void
    {
        $result = app(StockService::class)->recordSerialOpening(
            $this->admin,
            $product = $this->product(),
            'warehouse',
            $this->warehouse()->id,
            [],
            3,
            true,
        );

        $this->assertCount(3, $result['units']);
        $this->assertSame(3, $result['units']->pluck('unit_code')->unique()->count());
        $this->assertSame(3, StockUnit::where('product_id', $product->id)->whereNull('serial_no')->count());
    }

    public function test_fractional_serial_quantities_are_rejected_for_opening_outflow_and_transfer(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['FRACTION']);

        foreach ([
            fn () => app(StockService::class)->recordSerialOpening($this->admin, $this->product(), 'warehouse', $this->warehouse()->id, [], 1.5, true),
            fn () => app(StockService::class)->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'sale', -0.5, null, null, null, null, [$result['units']->first()->id]),
            fn () => app(StockService::class)->transfer($this->admin, $product, 'warehouse', $warehouse->id, 'branch', $this->branch()->id, 0.5, null, null, [$result['units']->first()->id]),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('A fractional serial quantity was accepted.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function test_transfer_with_one_unit_not_at_source_is_fully_atomic(): void
    {
        $product = $this->product();
        $source = $this->warehouse('Atomic source');
        $destination = $this->branch();
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $source->id, ['ATOMIC-1', 'ATOMIC-2']);
        app(StockService::class)->recordMovement($this->admin, $product, 'warehouse', $source->id, 'sale', -1, null, null, null, null, [$result['units'][0]->id]);
        $beforeMovements = StockMovement::count();
        $beforeLevels = StockLevel::orderBy('id')->pluck('quantity', 'id')->all();
        $beforeLocations = StockUnit::orderBy('id')->pluck('location_id', 'id')->all();

        try {
            app(StockService::class)->transfer($this->admin, $product, 'warehouse', $source->id, 'branch', $destination->id, 2, null, null, [$result['units'][0]->id, $result['units'][1]->id]);
            $this->fail('A transfer with an unavailable unit was accepted.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('source', $exception->getMessage());
        }

        $this->assertSame($beforeMovements, StockMovement::count());
        $this->assertSame($beforeLevels, StockLevel::orderBy('id')->pluck('quantity', 'id')->all());
        $this->assertSame($beforeLocations, StockUnit::orderBy('id')->pluck('location_id', 'id')->all());
    }

    public function test_same_serial_is_allowed_for_a_different_product(): void
    {
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $service->recordSerialOpening($this->admin, $this->product('First'), 'warehouse', $warehouse->id, ['SHARED']);
        $service->recordSerialOpening($this->admin, $this->product('Second'), 'warehouse', $warehouse->id, ['SHARED']);
        $this->assertSame(2, StockUnit::where('serial_no', 'SHARED')->count());
    }

    public function test_serial_outflow_requires_units_at_source_and_preserves_count_invariant(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['ONE', 'TWO']);
        $unit = $result['units']->first();
        app(StockService::class)->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'sale', -1, null, null, null, null, [$unit->id]);
        $this->assertSame('1.000', StockLevel::firstOrFail()->quantity);
        $this->assertSame(1, StockUnit::where('status', 'in_stock')->count());

        $this->expectException(\InvalidArgumentException::class);
        app(StockService::class)->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'sale', -1, null, null, null, null, [$unit->id]);
    }

    public function test_serial_transfer_moves_exact_units_atomically(): void
    {
        $product = $this->product();
        $source = $this->warehouse('Source');
        $destination = $this->branch($source->id);
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $source->id, ['A', 'B']);
        $unitIds = $result['units']->pluck('id')->all();
        [$out, $in] = app(StockService::class)->transfer($this->admin, $product, 'warehouse', $source->id, 'branch', $destination->id, 2, null, null, $unitIds);

        $this->assertSame($out->batch_uuid, $in->batch_uuid);
        $this->assertSame(0, (int) StockLevel::where('location_type', 'warehouse')->value('quantity'));
        $this->assertSame('2.000', StockLevel::where('location_type', 'branch')->value('quantity'));
        $this->assertSame(2, StockUnit::where('location_type', 'branch')->where('location_id', $destination->id)->where('status', 'in_stock')->count());
    }

    public function test_tracking_type_is_locked_after_a_movement(): void
    {
        $product = Product::create(array_merge([
            'sku' => uniqid('LOCK-'), 'name' => 'Locked tracking', 'slug' => uniqid('locked-'),
            'category_id' => $this->product()->category_id, 'unit' => 'pcs', 'track_stock' => true, 'tracking_type' => 'quantity', 'status' => 'active',
        ]));
        $warehouse = $this->warehouse();
        app(StockService::class)->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'opening', 1);
        $payload = ['sku' => $product->sku, 'name' => $product->name, 'slug' => $product->slug, 'category_id' => $product->category_id, 'unit' => 'pcs', 'cost_price' => 1, 'selling_price' => 2, 'track_stock' => 1, 'tracking_type' => 'serial', 'status' => 'active'];
        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $payload)->assertSessionHasErrors('tracking_type');
    }

    public function test_unit_labels_are_distinct_and_lookup_is_scoped(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $branch = $this->branch();
        $result = app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['VISIBLE']);
        $unit = $result['units']->first();
        $this->actingAs($this->admin)->get(route('admin.stock.units.labels', ['unit_ids' => [$unit->id]]))->assertOk()->assertSee($unit->unit_code);
        $user = $this->admin->replicate();
        $user->email = uniqid('lookup-').'@example.com';
        $user->full_name = 'Scoped lookup';
        $user->role_id = Role::create(['name' => uniqid('lookup-role-'), 'guard_name' => 'web'])->id;
        $user->access_all = false;
        $user->save();
        $user->syncRoles([$user->role->name]);
        $user->givePermissionTo('stock.view');
        $user->branches()->attach($branch);
        $this->actingAs($user)->get(route('admin.stock.units.lookup', ['q' => $unit->unit_code]))->assertOk()->assertSee('Unit not found.');
    }

    public function test_unit_pages_require_stock_view_and_authentication(): void
    {
        $role = Role::create(['name' => uniqid('no-stock-view-'), 'guard_name' => 'web']);
        $user = $this->admin->replicate();
        $user->full_name = 'No stock view';
        $user->email = uniqid('no-stock-view-').'@example.com';
        $user->role_id = $role->id;
        $user->access_all = false;
        $user->save();

        $this->actingAs($user)->get(route('admin.stock.units.labels'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.stock.units.lookup'))->assertForbidden();
        Auth::logout();
        $this->get(route('admin.stock.units.labels'))->assertRedirect();
        $this->get(route('admin.stock.units.lookup'))->assertRedirect();
    }

    public function test_rebuild_reports_and_repairs_serial_unit_count_mismatch(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['REBUILD']);
        StockLevel::query()->update(['quantity' => '9.000']);
        $summary = app(StockService::class)->rebuildWithReport(true);
        $this->assertGreaterThan(0, $summary['mismatches']);
        app(StockService::class)->rebuildWithReport(false);
        $this->assertSame('1.000', StockLevel::firstOrFail()->fresh()->quantity);
    }
}
