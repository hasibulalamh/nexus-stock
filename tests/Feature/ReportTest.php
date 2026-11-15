<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AdjustmentService;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use App\Services\StockService;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;
    private Branch $branch;
    private Product $product;
    private Product $lowProduct;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(string $name, string $cost = '10.00', string $reorder = '5.000', string $tracking = 'quantity'): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('REP-'), 'name' => $name, 'slug' => uniqid('report-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => $cost,
            'selling_price' => 20, 'reorder_level' => $reorder, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function user(array $permissions, array $warehouseIds = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Report user', 'email' => uniqid('rep-').'@example.com',
            'password' => Hash::make('password'), 'phone' => '01700000000', 'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')), 'role_id' => $role->id,
            'department_id' => Department::query()->value('id'), 'designation_id' => Designation::query()->value('id'),
            'join_date' => '2026-01-01', 'profile_photo' => '/uploads/users/default.png',
            'status' => 'active', 'access_all' => false,
        ]);
        $user->assignRole($role->name);
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        $user->warehouses()->attach($warehouseIds);

        return $user;
    }

    private function buildFixture(): void
    {
        $this->warehouse = Warehouse::create([
            'name' => 'Report warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 1000, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
        $this->branch = Branch::create([
            'name' => 'Report branch', 'code' => uniqid('BR-'), 'slug' => uniqid('br-'),
            'location_id' => 1, 'warehouse_id' => $this->warehouse->id, 'capacity' => 1000,
            'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
        $this->product = $this->product('Report product');
        $this->lowProduct = $this->product('Low stock product');
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $this->product, 'warehouse', $this->warehouse->id, '10.000');
        $stock->recordOpening($this->admin, $this->product, 'branch', $this->branch->id, '2.000');
        $stock->recordOpening($this->admin, $this->lowProduct, 'branch', $this->branch->id, '1.000');
        $serial = $this->product('Serial report product', '20.00', '0.000', 'serial');
        $stock->recordSerialOpening($this->admin, $serial, 'warehouse', $this->warehouse->id, ['RP-1', 'RP-2']);

        $transfers = app(TransferService::class);

        // Full transfer, resolves the alert.
        $first = $transfers->create($this->admin, [
            'type' => 'transfer', 'from_id' => $this->warehouse->id, 'to_id' => $this->branch->id,
            'lines' => [['product_id' => $this->product->id, 'requested_qty' => '4.000']],
        ]);
        $transfers->send($this->admin, $first->fresh());
        $transfers->receive($this->admin, $first->fresh());

        // Partial send leaves a backorder.
        $second = $transfers->create($this->admin, [
            'type' => 'transfer', 'from_id' => $this->warehouse->id, 'to_id' => $this->branch->id,
            'lines' => [['product_id' => $this->product->id, 'requested_qty' => '3.000']],
        ]);
        $secondItem = $second->items()->first();
        $transfers->send($this->admin, $second->fresh(), [$secondItem->id => ['sent_qty' => '1.000']]);

        // Partial receipt creates a discrepancy.
        $third = $transfers->create($this->admin, [
            'type' => 'transfer', 'from_id' => $this->warehouse->id, 'to_id' => $this->branch->id,
            'lines' => [['product_id' => $this->product->id, 'requested_qty' => '2.000']],
        ]);
        $transfers->send($this->admin, $third->fresh());
        $thirdItem = $third->fresh()->items()->first();
        $transfers->receiveWithQuantities($this->admin, $third->fresh(), [$thirdItem->id => ['received_qty' => '1.000']]);

        // Purchase and posted receipt with damage.
        $supplier = Supplier::create([
            'name' => 'Report supplier', 'code' => uniqid('SUP-'), 'status' => 'active',
        ]);
        $order = app(PurchaseOrderService::class)->create($this->admin, [
            'supplier_id' => $supplier->id, 'warehouse_id' => $this->warehouse->id,
            'lines' => [['product_id' => $this->product->id, 'ordered_qty' => '3.000', 'unit_cost' => '12.00']],
        ]);
        $receipt = app(GoodsReceiptService::class)->create($this->admin, [
            'purchase_order_id' => $order->id, 'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id, 'received_at' => now()->toDateTimeString(),
            'lines' => [['product_id' => $this->product->id, 'accepted_qty' => '2.000', 'damaged_qty' => '1.000', 'unit_cost' => '12.00']],
        ]);
        app(GoodsReceiptService::class)->post($this->admin, $receipt->fresh());

        // Approved adjustment.
        $adjustment = app(AdjustmentService::class)->submit($this->admin, [
            'location_type' => 'warehouse', 'location_id' => $this->warehouse->id, 'reason' => 'damaged',
            'lines' => [['product_id' => $this->product->id, 'delta' => '-1.000']],
        ]);
        app(AdjustmentService::class)->approve($this->admin, $adjustment->fresh());
    }

    public function test_every_report_renders_with_expected_numbers(): void
    {
        $this->buildFixture();

        $this->actingAs($this->admin)->get(route('admin.reports.valuation'))
            ->assertOk()->assertSee('Report product')->assertSee(\App\Support\Money::format(40));
        $this->actingAs($this->admin)->get(route('admin.reports.low-stock'))
            ->assertOk()->assertSee('Low stock product');
        $this->actingAs($this->admin)->get(route('admin.reports.movements'))
            ->assertOk()->assertSee('Purchase')->assertSee('Transfer in');
        $this->actingAs($this->admin)->get(route('admin.reports.transfers'))
            ->assertOk()->assertSee('TRF-')->assertSee('Lead time (hours)');
        $this->actingAs($this->admin)->get(route('admin.reports.discrepancies'))
            ->assertOk()->assertSee('Short');
        $this->actingAs($this->admin)->get(route('admin.reports.purchases'))
            ->assertOk()->assertSee('Report supplier')->assertSee(\App\Support\Money::format(36));
        $this->actingAs($this->admin)->get(route('admin.reports.adjustments'))
            ->assertOk()->assertSee('Damaged');
        $this->actingAs($this->admin)->get(route('admin.reports.backorders'))
            ->assertOk()->assertSee('2.000');
        $this->actingAs($this->admin)->get(route('admin.reports.stock-on-hand'))
            ->assertOk()->assertSee('Report product')->assertSee('Serial report product')->assertSee('Serial units in stock');
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Stock valuation by location');
    }

    public function test_kpi_dashboard_computes_expected_values(): void
    {
        $this->buildFixture();

        $this->actingAs($this->admin)->get(route('admin.stock.kpi'))
            ->assertOk()
            ->assertSee('Open stock alerts')
            ->assertSee('77.8%')
            ->assertSee('50.0%')
            ->assertSee('33.3%')
            ->assertSee('Not available')
            ->assertSee('Requires a Sales module');
    }

    public function test_reports_export_csv(): void
    {
        $this->buildFixture();

        $response = $this->actingAs($this->admin)->get(route('admin.reports.valuation', ['export' => 'csv']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Location', $response->streamedContent());
        $this->assertStringContainsString('Report product', $response->streamedContent());
    }

    public function test_reports_are_scoped_to_the_users_locations(): void
    {
        $this->buildFixture();
        $outsider = $this->user(['reports.view']);

        $this->actingAs($outsider)->get(route('admin.reports.valuation'))
            ->assertOk()->assertDontSee('Report product');
        $this->actingAs($outsider)->get(route('admin.reports.low-stock'))
            ->assertOk()->assertDontSee('Low stock product');
        $this->actingAs($outsider)->get(route('admin.reports.stock-on-hand'))
            ->assertOk()->assertDontSee('Report product');

        $this->actingAs($this->admin)->get(route('admin.reports.valuation'))
            ->assertOk()->assertSee('Report product');
    }

    public function test_report_and_kpi_routes_require_permissions(): void
    {
        $user = $this->user([]);
        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.stock.kpi'))->assertForbidden();

        $kpiOnly = $this->user(['stock.kpi.view']);
        $this->actingAs($kpiOnly)->get(route('admin.stock.kpi'))->assertOk();
        $this->actingAs($kpiOnly)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_all_report_routes_render_for_a_permitted_user(): void
    {
        $this->buildFixture();

        foreach ([
            route('admin.reports.index'),
            route('admin.reports.valuation'),
            route('admin.reports.low-stock'),
            route('admin.reports.movements'),
            route('admin.reports.transfers'),
            route('admin.reports.discrepancies'),
            route('admin.reports.purchases'),
            route('admin.reports.adjustments'),
            route('admin.reports.backorders'),
            route('admin.reports.stock-on-hand'),
            route('admin.stock.kpi'),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
