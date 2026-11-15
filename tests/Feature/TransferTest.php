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
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(string $tracking = 'quantity', string $reorder = '2.000'): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('TRF-'), 'name' => 'Transfer product', 'slug' => uniqid('transfer-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => $reorder, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Supply warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
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
            'full_name' => 'Scoped user', 'email' => uniqid('scoped-').'@example.com',
            'password' => Hash::make('password'), 'phone' => '01700000000', 'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')), 'role_id' => $role->id,
            'department_id' => \App\Models\Department::query()->value('id'),
            'designation_id' => \App\Models\Designation::query()->value('id'),
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

    private function level(Product $product, string $type, int $id): ?StockLevel
    {
        return StockLevel::query()->where('product_id', $product->id)
            ->where('location_type', $type)->where('location_id', $id)->first();
    }

    public function test_full_quantity_transfer_posts_ledger_and_resolves_the_alert(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');
        $stock->recordOpening($this->admin, $product, 'branch', $branch->id, '1.000');
        $this->assertDatabaseHas('stock_alerts', ['product_id' => $product->id, 'branch_id' => $branch->id, 'status' => 'open']);

        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '4.000']],
        ]);
        $this->assertSame('draft', $transfer->status);
        $this->assertMatchesRegularExpression('/^TRF-\d{4}-\d{6}$/', $transfer->transfer_no);

        $service->send($this->admin, $transfer->fresh());
        $this->assertSame('6.000', (string) $this->level($product, 'warehouse', $warehouse->id)->quantity);
        $this->assertSame('1.000', (string) $this->level($product, 'branch', $branch->id)->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'movement_type' => 'transfer_out',
            'reference_type' => 'transfer', 'reference_id' => $transfer->id, 'quantity_delta' => '-4.000',
        ]);

        $service->receive($this->admin, $transfer->fresh());
        $this->assertSame('6.000', (string) $this->level($product, 'warehouse', $warehouse->id)->quantity);
        $this->assertSame('5.000', (string) $this->level($product, 'branch', $branch->id)->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'movement_type' => 'transfer_in',
            'location_type' => 'branch', 'location_id' => $branch->id, 'quantity_delta' => '4.000',
        ]);
        $this->assertDatabaseHas('stock_alerts', ['product_id' => $product->id, 'branch_id' => $branch->id, 'status' => 'resolved']);
        $this->assertSame('received', $transfer->fresh()->status);
        $this->assertNotNull($transfer->fresh()->received_at);

        $this->assertSame(0, app(StockService::class)->rebuildWithReport()['mismatches']);
        $this->artisan('stock:rebuild')->assertExitCode(0);
    }

    public function test_serial_transfer_moves_units_in_transit_then_into_the_branch(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product('serial');
        app(StockService::class)->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['SN-A', 'SN-B', 'SN-C']);
        $units = StockUnit::where('product_id', $product->id)->orderBy('id')->get();

        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'unit_ids' => [$units[0]->id, $units[1]->id]]],
        ]);
        $this->assertSame('2.000', (string) $transfer->items()->first()->requested_qty);

        $service->send($this->admin, $transfer->fresh());
        $this->assertSame('in_transit', $units[0]->fresh()->status);
        $this->assertNull($units[0]->fresh()->location_id);
        $this->assertSame('1.000', (string) $this->level($product, 'warehouse', $warehouse->id)->quantity);

        $service->receive($this->admin, $transfer->fresh());
        $this->assertSame('in_stock', $units[0]->fresh()->status);
        $this->assertSame('branch', $units[0]->fresh()->location_type);
        $this->assertSame($branch->id, (int) $units[0]->fresh()->location_id);
        $this->assertSame('1.000', (string) $this->level($product, 'warehouse', $warehouse->id)->quantity);
        $this->assertSame('2.000', (string) $this->level($product, 'branch', $branch->id)->quantity);
        $this->assertSame(0, app(StockService::class)->rebuildWithReport()['mismatches']);
    }

    public function test_insufficient_stock_rejects_the_whole_send_and_changes_nothing(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '2.000');
        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '5.000']],
        ]);
        $movementCount = StockMovement::count();

        try {
            $service->send($this->admin, $transfer->fresh());
            $this->fail('Sending more than the available stock should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('stock', strtolower($exception->getMessage()));
        }

        $this->assertSame('draft', $transfer->fresh()->status);
        $this->assertSame($movementCount, StockMovement::count());
        $this->assertSame('2.000', (string) $this->level($product, 'warehouse', $warehouse->id)->quantity);
        $this->assertNull($this->level($product, 'branch', $branch->id));
        $this->assertSame('0.000', (string) $transfer->items()->first()->sent_qty);
    }

    public function test_double_send_and_double_receive_are_rejected(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '3.000');
        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '1.000']],
        ]);
        $service->send($this->admin, $transfer->fresh());

        try {
            $service->send($this->admin, $transfer->fresh());
            $this->fail('A sent transfer cannot be sent again.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('draft', $exception->getMessage());
        }

        $service->receive($this->admin, $transfer->fresh());
        try {
            $service->receive($this->admin, $transfer->fresh());
            $this->fail('A received transfer cannot be received again.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('sent', $exception->getMessage());
        }
        $this->assertSame(1, StockMovement::where('movement_type', 'transfer_in')->count());
    }

    public function test_transfer_requires_a_branch_supplied_by_the_source_warehouse(): void
    {
        $warehouse = $this->warehouse();
        $otherBranch = $this->branch($this->warehouse()->id);
        $product = $this->product();

        $this->expectException(\InvalidArgumentException::class);
        app(TransferService::class)->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $otherBranch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '1.000']],
        ]);
    }

    public function test_transfer_lists_and_details_are_scoped_to_the_users_locations(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $transfer = app(TransferService::class)->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $this->product()->id, 'requested_qty' => '1.000']],
        ]);

        $outsider = $this->user(['transfers.view']);
        $this->actingAs($outsider)->get(route('admin.transfers.index'))
            ->assertOk()->assertDontSee($transfer->transfer_no);
        $this->actingAs($outsider)->get(route('admin.transfers.show', $transfer))->assertForbidden();

        $branchUser = $this->user(['transfers.view'], [$branch->id]);
        $this->actingAs($branchUser)->get(route('admin.transfers.index'))
            ->assertOk()->assertSee($transfer->transfer_no);

        $warehouseUser = $this->user(['transfers.view'], [], [$warehouse->id]);
        $this->actingAs($warehouseUser)->get(route('admin.transfers.show', $transfer))->assertOk();
    }

    public function test_unwritable_user_cannot_store_a_transfer(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $user = $this->user(['transfers.create']);

        $this->actingAs($user)->from(route('admin.transfers.create'))->post(route('admin.transfers.store'), [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $this->product()->id, 'requested_qty' => '1.000']],
        ])->assertSessionHasErrors('transfer');

        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_pages_render_for_a_permitted_user(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '3.000');
        $transfer = app(TransferService::class)->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '1.000']],
        ]);

        foreach ([
            route('admin.transfers.index'),
            route('admin.transfers.create'),
            route('admin.transfers.create', ['type' => 'return']),
            route('admin.transfers.backorders'),
            route('admin.transfers.show', $transfer),
            route('admin.transfers.delivery-note', $transfer),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_send_notifies_destination_users_with_receive_permission_only(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '3.000');
        $receiver = $this->user(['transfers.view', 'transfers.receive'], [$branch->id]);
        $bystander = $this->user(['transfers.view'], [$branch->id]);

        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '1.000']],
        ]);
        $service->send($this->admin, $transfer->fresh());

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $receiver->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $bystander->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $this->admin->id)->count());
        $this->assertStringContainsString($transfer->transfer_no, DatabaseNotification::firstOrFail()->data['message']);
    }

    public function test_receive_rejects_a_user_without_write_access_to_the_branch(): void
    {
        $warehouse = $this->warehouse();
        $branch = $this->branch($warehouse->id);
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '2.000');
        $service = app(TransferService::class);
        $transfer = $service->create($this->admin, [
            'type' => 'transfer', 'from_id' => $warehouse->id, 'to_id' => $branch->id,
            'lines' => [['product_id' => $product->id, 'requested_qty' => '1.000']],
        ]);
        $service->send($this->admin, $transfer->fresh());
        $warehouseOnly = $this->user(['transfers.view', 'transfers.receive'], [], [$warehouse->id]);

        $this->expectException(\RuntimeException::class);
        $service->receive($warehouseOnly, $transfer->fresh());
    }
}
