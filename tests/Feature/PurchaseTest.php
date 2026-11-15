<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function supplier(string $code = 'SUP-1'): Supplier
    {
        return Supplier::create([
            'name' => 'Supplier '.$code, 'code' => $code, 'contact_person' => 'Rahim',
            'phone' => '01700000000', 'email' => strtolower($code).'@example.com',
            'address' => 'Dhaka', 'status' => 'active',
        ]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Purchase warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function product(string $tracking = 'quantity', string $cost = '5.00'): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('PUR-'), 'name' => 'Purchase product', 'slug' => uniqid('purchase-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => $cost,
            'selling_price' => 8, 'reorder_level' => 0, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function user(array $permissions, array $warehouseIds = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Purchasing user', 'email' => uniqid('pur-').'@example.com',
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

    private function order(PurchaseOrderService $service, Supplier $supplier, Warehouse $warehouse, Product $product, string $qty = '10.000', string $cost = '5.00'): PurchaseOrder
    {
        return $service->create($this->admin, [
            'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id,
            'expected_date' => '2026-11-30', 'notes' => 'Test order',
            'lines' => [['product_id' => $product->id, 'ordered_qty' => $qty, 'unit_cost' => $cost]],
        ]);
    }

    private function receipt(GoodsReceiptService $service, PurchaseOrder $order, array $lines): GoodsReceipt
    {
        return $service->create($this->admin, [
            'purchase_order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'warehouse_id' => $order->warehouse_id,
            'received_at' => '2026-11-17 10:00:00',
            'notes' => 'Delivered',
            'lines' => $lines,
        ]);
    }

    private function level(Product $product, int $warehouseId): string
    {
        return (string) (StockLevel::query()->where('product_id', $product->id)
            ->where('location_type', 'warehouse')->where('location_id', $warehouseId)->value('quantity') ?? '0.000');
    }

    public function test_supplier_crud_validation_and_delete_guard(): void
    {
        $this->actingAs($this->admin)->post(route('admin.suppliers.store'), [
            'name' => 'Acme Supply', 'code' => 'ACME', 'status' => 'active', 'email' => 'acme@example.com',
        ])->assertRedirect(route('admin.suppliers.index'));
        $supplier = Supplier::where('code', 'ACME')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.suppliers.store'), [
            'name' => 'Duplicate', 'code' => 'ACME', 'status' => 'active',
        ])->assertSessionHasErrors('code');

        $this->actingAs($this->admin)->put(route('admin.suppliers.update', $supplier), [
            'name' => 'Acme Supply Ltd', 'code' => 'ACME', 'status' => 'active',
        ])->assertRedirect(route('admin.suppliers.index'));
        $this->assertSame('Acme Supply Ltd', $supplier->fresh()->name);

        $order = $this->order(app(PurchaseOrderService::class), $supplier, $this->warehouse(), $this->product());
        $this->actingAs($this->admin)->delete(route('admin.suppliers.delete', $supplier))
            ->assertSessionHasErrors('supplier');
        $this->assertNull($supplier->fresh()->deleted_at);

        $order->update(['status' => 'cancelled']);
        $this->actingAs($this->admin)->delete(route('admin.suppliers.delete', $supplier));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_purchase_order_lifecycle_and_partial_then_full_receipt(): void
    {
        config(['stock.purchases.require_approval' => true]);
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $service = app(PurchaseOrderService::class);

        $order = $this->order($service, $supplier, $warehouse, $product);
        $this->assertSame('draft', $order->status);
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{6}$/', $order->po_no);

        $service->approve($this->admin, $order->fresh());
        $this->assertSame('ordered', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->approved_at);

        $receipts = app(GoodsReceiptService::class);
        $first = $this->receipt($receipts, $order->fresh(), [[
            'product_id' => $product->id, 'accepted_qty' => '4.000', 'unit_cost' => '5.00',
        ]]);
        $this->assertSame('draft', $first->status);
        $this->assertSame('0.000', $this->level($product, $warehouse->id));
        $this->assertDatabaseCount('stock_movements', 0);

        $receipts->post($this->admin, $first->fresh());
        $this->assertSame('4.000', $this->level($product, $warehouse->id));
        $this->assertSame('partial', $order->fresh()->status);
        $this->assertSame('4.000', (string) $order->fresh()->lines()->first()->received_qty);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'purchase', 'quantity_delta' => '4.000',
            'reference_type' => 'goods_receipt', 'reference_id' => $first->id,
        ]);
        $this->assertSame('posted', $first->fresh()->status);

        $second = $this->receipt($receipts, $order->fresh(), [[
            'product_id' => $product->id, 'accepted_qty' => '6.000', 'unit_cost' => '5.00',
        ]]);
        $receipts->post($this->admin, $second->fresh());
        $this->assertSame('10.000', $this->level($product, $warehouse->id));
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(0, app(StockService::class)->rebuildWithReport()['mismatches']);
        $this->artisan('stock:rebuild')->assertExitCode(0);
    }

    public function test_damaged_and_short_quantities_are_recorded_but_not_stocked(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '5.000');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [[
            'product_id' => $product->id, 'accepted_qty' => '3.000', 'damaged_qty' => '1.000',
            'short_qty' => '1.000', 'reason' => 'Two units rejected',
        ]]);
        $receipts->post($this->admin, $receipt->fresh());

        $this->assertSame('3.000', $this->level($product, $warehouse->id));
        $line = $receipt->fresh()->lines()->first();
        $this->assertSame('5.000', (string) $line->received_qty);
        $this->assertSame('3.000', (string) $line->accepted_qty);
        $this->assertSame('1.000', (string) $line->damaged_qty);
        $this->assertSame('1.000', (string) $line->short_qty);
        $this->assertSame('partial', $order->fresh()->status);
        $this->assertSame('3.000', (string) $order->fresh()->lines()->first()->received_qty);
    }

    public function test_serial_purchase_creates_units_with_their_serial_numbers(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product('serial');
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [[
            'product_id' => $product->id, 'accepted_qty' => '2.000',
            'serial_numbers' => ['SN-P1', 'SN-P2'], 'unit_cost' => '5.00',
        ]]);
        $receipts->post($this->admin, $receipt->fresh());

        $this->assertSame('2.000', $this->level($product, $warehouse->id));
        $this->assertSame(2, StockUnit::where('product_id', $product->id)->where('status', 'in_stock')->count());
        $this->assertDatabaseHas('stock_units', ['product_id' => $product->id, 'serial_no' => 'SN-P1', 'location_type' => 'warehouse']);
        $this->assertSame(0, app(StockService::class)->rebuildWithReport()['mismatches']);
    }

    public function test_over_receipt_is_rejected_unless_config_allows_it(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');
        $receipts = app(GoodsReceiptService::class);

        try {
            $this->receipt($receipts, $order, [['product_id' => $product->id, 'accepted_qty' => '3.000']]);
            $this->fail('Over-receipt should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('exceeds', $exception->getMessage());
        }
        $this->assertDatabaseCount('goods_receipts', 0);

        config(['stock.purchases.allow_over_receipt' => true]);
        $receipt = $this->receipt($receipts, $order, [['product_id' => $product->id, 'accepted_qty' => '3.000']]);
        $receipts->post($this->admin, $receipt->fresh());
        $this->assertSame('3.000', $this->level($product, $warehouse->id));
    }

    public function test_receipt_can_only_be_posted_once(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [['product_id' => $product->id, 'accepted_qty' => '2.000']]);
        $receipts->post($this->admin, $receipt->fresh());

        $this->expectException(\RuntimeException::class);
        try {
            $receipts->post($this->admin, $receipt->fresh());
        } finally {
            $this->assertSame(1, StockMovement::where('movement_type', 'purchase')->count());
            $this->assertSame('2.000', $this->level($product, $warehouse->id));
        }
    }

    public function test_supplier_return_posts_return_out_and_is_capped_by_damaged_quantity(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '4.000');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [[
            'product_id' => $product->id, 'accepted_qty' => '2.000', 'damaged_qty' => '2.000', 'reason' => 'Crushed',
        ]]);
        $receipts->post($this->admin, $receipt->fresh());
        $line = $receipt->fresh()->lines()->first();

        $receipts->supplierReturn($this->admin, $receipt->fresh(), $line->id, '1.000', 'Returned to supplier');

        $this->assertSame('1.000', $this->level($product, $warehouse->id));
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'return_out', 'quantity_delta' => '-1.000']);
        $this->assertSame('1.000', (string) $line->fresh()->returned_qty);

        $this->expectException(\InvalidArgumentException::class);
        $receipts->supplierReturn($this->admin, $receipt->fresh(), $line->id, '2.000', 'Too much');
    }

    public function test_cost_update_modes_are_configurable(): void
    {
        config(['stock.purchases.cost_update' => 'last']);
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product('quantity', '5.00');
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '1.000', '7.50');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [['product_id' => $product->id, 'accepted_qty' => '1.000', 'unit_cost' => '7.50']]);
        $receipts->post($this->admin, $receipt->fresh());

        $this->assertSame('7.50', (string) $product->fresh()->cost_price);
    }

    public function test_purchasing_documents_are_scoped_to_the_users_warehouses(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');
        $outsider = $this->user(['purchases.view', 'receiving.view', 'suppliers.view']);

        $this->actingAs($outsider)->get(route('admin.purchases.index'))->assertOk()->assertDontSee($order->po_no);
        $this->actingAs($outsider)->get(route('admin.purchases.show', $order))->assertForbidden();
        $this->actingAs($outsider)->get(route('admin.receiving.index'))->assertOk();

        $warehouseUser = $this->user(['purchases.view'], [$warehouse->id]);
        $this->actingAs($warehouseUser)->get(route('admin.purchases.show', $order))->assertOk();
    }

    public function test_purchasing_pages_require_permissions_and_render(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');
        $receipts = app(GoodsReceiptService::class);
        $receipt = $this->receipt($receipts, $order, [['product_id' => $product->id, 'accepted_qty' => '1.000']]);

        $stranger = $this->user([]);
        $this->actingAs($stranger)->get(route('admin.suppliers.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('admin.receiving.index'))->assertForbidden();

        foreach ([
            route('admin.suppliers.index'),
            route('admin.suppliers.create'),
            route('admin.suppliers.edit', $supplier),
            route('admin.purchases.index'),
            route('admin.purchases.create'),
            route('admin.purchases.show', $order),
            route('admin.purchases.print', $order),
            route('admin.receiving.index'),
            route('admin.receiving.create'),
            route('admin.receiving.create', ['purchase_order_id' => $order->id]),
            route('admin.receiving.show', $receipt),
            route('admin.receiving.print', $receipt),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_receipt_http_flow_creates_and_posts_via_routes(): void
    {
        $supplier = $this->supplier();
        $warehouse = $this->warehouse();
        $product = $this->product();
        $order = $this->order(app(PurchaseOrderService::class), $supplier, $warehouse, $product, '2.000');

        $this->actingAs($this->admin)->post(route('admin.receiving.store'), [
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'received_at' => '2026-11-17 10:00:00',
            'lines' => [[
                'product_id' => $product->id, 'accepted_qty' => '2.000', 'damaged_qty' => '0',
                'short_qty' => '0', 'unit_cost' => '5.00',
            ]],
        ])->assertRedirect();
        $receipt = GoodsReceipt::firstOrFail();
        $this->assertSame('draft', $receipt->status);

        $this->actingAs($this->admin)->post(route('admin.receiving.post', $receipt))->assertRedirect();
        $this->assertSame('posted', $receipt->fresh()->status);
        $this->assertSame('2.000', $this->level($product, $warehouse->id));

        $this->actingAs($this->admin)->post(route('admin.receiving.post', $receipt))
            ->assertSessionHasErrors('receipt');
        $this->assertSame(1, StockMovement::where('movement_type', 'purchase')->count());
    }
}
