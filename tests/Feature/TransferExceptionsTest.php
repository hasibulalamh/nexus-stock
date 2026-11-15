<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\Transfer;
use App\Models\TransferDiscrepancy;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferExceptionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(string $tracking = 'quantity'): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('EXC-'), 'name' => 'Exception product', 'slug' => uniqid('exception-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => 0, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Exception warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function branch(?int $warehouseId = null): Branch
    {
        return Branch::create([
            'name' => uniqid('Branch '), 'code' => uniqid('BR-'), 'slug' => uniqid('br-'),
            'location_id' => 1, 'warehouse_id' => $warehouseId, 'capacity' => 100,
            'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function user(array $permissions, array $branchIds = [], array $warehouseIds = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Exception user', 'email' => uniqid('exc-').'@example.com',
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
        $user->branches()->attach($branchIds);
        $user->warehouses()->attach($warehouseIds);

        return $user;
    }

    private function level(Product $product, string $type, int $id): string
    {
        return (string) (StockLevel::query()->where('product_id', $product->id)
            ->where('location_type', $type)->where('location_id', $id)->value('quantity') ?? '0.000');
    }

    private function draft(TransferService $service, Warehouse $warehouse, Branch $branch, Product $product, string $qty = '4.000'): Transfer
    {
        return $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => $qty]],
        ]);
    }

    public function test_partial_receive_creates_a_shortage_and_only_good_quantity_enters_stock(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');
        $manager = $this->user(['transfers.create'], [], [$warehouse->id]);

        $service = app(TransferService::class);
        $transfer = $this->draft($service, $warehouse, $branch, $product);
        $service->send($this->admin, $transfer->fresh());
        $item = $transfer->fresh()->items()->first();

        $service->receiveWithQuantities($this->admin, $transfer->fresh(), [
            $item->id => ['received_qty' => '3.000', 'reason' => 'One carton missing'],
        ]);

        $this->assertSame('3.000', $this->level($product, 'branch', $branch->id));
        $this->assertSame('3.000', (string) $item->fresh()->received_qty);
        $this->assertDatabaseHas('transfer_discrepancies', [
            'transfer_item_id' => $item->id, 'type' => 'short', 'qty' => '1.000', 'status' => 'open',
        ]);
        $discrepancy = TransferDiscrepancy::firstOrFail();
        $this->assertSame('received', $transfer->fresh()->status);
        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $manager->id)->count());

        $service->resolveDiscrepancy($this->admin, $discrepancy, 'found', 'Carton located');
        $this->assertSame('4.000', $this->level($product, 'branch', $branch->id));
        $this->assertSame('resolved', $discrepancy->fresh()->status);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment', 'reference_type' => 'transfer_discrepancy',
            'reference_id' => $discrepancy->id, 'quantity_delta' => '1.000',
        ]);
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
        $this->artisan('stock:rebuild')->assertExitCode(0);
    }

    public function test_damaged_quantity_is_excluded_and_writing_it_off_does_not_change_stock(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');
        $service = app(TransferService::class);
        $transfer = $this->draft($service, $warehouse, $branch, $product);
        $service->send($this->admin, $transfer->fresh());
        $item = $transfer->fresh()->items()->first();

        $service->receiveWithQuantities($this->admin, $transfer->fresh(), [
            $item->id => ['received_qty' => '2.000', 'damaged_qty' => '1.000'],
        ]);

        $this->assertSame('2.000', $this->level($product, 'branch', $branch->id));
        $this->assertDatabaseHas('transfer_discrepancies', ['transfer_item_id' => $item->id, 'type' => 'damaged', 'qty' => '1.000']);
        $this->assertDatabaseHas('transfer_discrepancies', ['transfer_item_id' => $item->id, 'type' => 'short', 'qty' => '1.000']);

        $damaged = TransferDiscrepancy::where('type', 'damaged')->firstOrFail();
        $service->resolveDiscrepancy($this->admin, $damaged, 'written_off', 'Crushed in transit');

        $this->assertSame('2.000', $this->level($product, 'branch', $branch->id));
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment', 'reference_type' => 'transfer_discrepancy',
            'reference_id' => $damaged->id, 'quantity_delta' => '0.000', 'notes' => 'Crushed in transit',
        ]);
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_return_moves_branch_stock_back_to_the_supplying_warehouse(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'branch', $branch->id, '5.000');
        $service = app(TransferService::class);

        $return = $service->create($this->admin, [
            'type' => 'return', 'from_id' => $branch->id, 'reason' => 'damaged',
            'lines' => [['product_id' => $product->id, 'requested_qty' => '2.000']],
        ]);
        $this->assertSame('return', $return->type);
        $this->assertSame('warehouse', $return->to_type);
        $this->assertSame($warehouse->id, (int) $return->to_id);
        $this->assertSame('damaged', $return->fresh()->reason);

        $service->send($this->admin, $return->fresh());
        $this->assertSame('3.000', $this->level($product, 'branch', $branch->id));
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'return_out', 'quantity_delta' => '-2.000']);

        $service->receive($this->admin, $return->fresh());
        $this->assertSame('2.000', $this->level($product, 'warehouse', $warehouse->id));
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'return_in', 'quantity_delta' => '2.000']);
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_return_requires_a_valid_reason(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $this->expectException(\InvalidArgumentException::class);
        app(TransferService::class)->create($this->admin, [
            'type' => 'return', 'from_id' => $branch->id, 'reason' => 'made_up',
            'lines' => [['product_id' => $this->product()->id, 'requested_qty' => '1.000']],
        ]);
    }

    public function test_partial_send_leaves_a_backorder_and_arriving_stock_notifies_the_warehouse(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '5.000');
        $planner = $this->user(['transfers.view', 'transfers.create'], [], [$warehouse->id]);

        $service = app(TransferService::class);
        $transfer = $this->draft($service, $warehouse, $branch, $product, '5.000');
        $item = $transfer->items()->first();
        $service->send($this->admin, $transfer->fresh(), [$item->id => ['sent_qty' => '2.000']]);

        $this->assertSame('2.000', (string) $item->fresh()->sent_qty);
        $this->assertSame('3.000', $item->fresh()->backorderQty());
        $this->assertSame('3.000', $this->level($product, 'warehouse', $warehouse->id));
        $this->actingAs($this->admin)->get(route('admin.transfers.backorders'))->assertOk()->assertSee('3.000');

        $stock->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'adjustment', 3);

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $planner->id)->count());
        $this->assertStringContainsString('backorder', strtolower(DatabaseNotification::firstOrFail()->data['message']));
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $this->admin->id)->count());
    }

    public function test_serial_partial_receive_keeps_missing_units_in_transit_until_resolved(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product('serial');
        $stock = app(StockService::class);
        $stock->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['SN-1', 'SN-2', 'SN-3']);
        $units = StockUnit::where('product_id', $product->id)->orderBy('id')->get();

        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'unit_ids' => $units->pluck('id')->all()]],
        ]);
        $service->send($this->admin, $transfer->fresh());
        $item = $transfer->fresh()->items()->first();

        $service->receiveWithQuantities($this->admin, $transfer->fresh(), [
            $item->id => ['unit_ids' => [$units[0]->id, $units[1]->id]],
        ]);

        $this->assertSame('2.000', $this->level($product, 'branch', $branch->id));
        $this->assertSame('in_stock', $units[0]->fresh()->status);
        $this->assertSame('in_transit', $units[2]->fresh()->status);
        $discrepancy = TransferDiscrepancy::where('type', 'short')->firstOrFail();
        $this->assertSame('1.000', (string) $discrepancy->qty);
        $this->assertSame([$units[2]->id], $discrepancy->stock_unit_ids);

        $service->resolveDiscrepancy($this->admin, $discrepancy, 'found');
        $this->assertSame('in_stock', $units[2]->fresh()->status);
        $this->assertSame('branch', $units[2]->fresh()->location_type);
        $this->assertSame('3.000', $this->level($product, 'branch', $branch->id));
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_discrepancy_resolution_requires_permission_and_scope(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '4.000');
        $service = app(TransferService::class);
        $transfer = $this->draft($service, $warehouse, $branch, $product);
        $service->send($this->admin, $transfer->fresh());
        $item = $transfer->fresh()->items()->first();
        $service->receiveWithQuantities($this->admin, $transfer->fresh(), [$item->id => ['received_qty' => '2.000']]);
        $discrepancy = TransferDiscrepancy::firstOrFail();

        $noPermission = $this->user(['transfers.view'], [$branch->id]);
        $this->actingAs($noPermission)->post(route('admin.transfers.discrepancies.resolve', $discrepancy), [
            'resolution' => 'found',
        ])->assertForbidden();

        $noScope = $this->user(['transfers.view', 'stock.discrepancies.resolve']);
        $this->actingAs($noScope)->post(route('admin.transfers.discrepancies.resolve', $discrepancy), [
            'resolution' => 'found',
        ])->assertForbidden();

        $this->assertSame('open', $discrepancy->fresh()->status);
        $this->assertSame('2.000', $this->level($product, 'branch', $branch->id));
    }

    public function test_transfer_detail_renders_discrepancies_after_a_partial_receive(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '4.000');
        $service = app(TransferService::class);
        $transfer = $this->draft($service, $warehouse, $branch, $product);
        $service->send($this->admin, $transfer->fresh());
        $item = $transfer->fresh()->items()->first();
        $service->receiveWithQuantities($this->admin, $transfer->fresh(), [$item->id => ['received_qty' => '1.000']]);

        $this->actingAs($this->admin)->get(route('admin.transfers.show', $transfer))
            ->assertOk()->assertSee('Discrepancies')->assertSee('Open');
    }
}
