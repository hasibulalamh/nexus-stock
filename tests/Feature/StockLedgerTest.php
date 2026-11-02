<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use LogicException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private int $departmentId;
    private int $designationId;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->superadmin = User::where('email', 'superadmin@storify.com')->firstOrFail();
        $department = Department::create(['name' => 'Operations', 'code' => uniqid('OPS-'), 'status' => 'active']);
        $designation = Designation::create([
            'name' => 'Officer',
            'code' => uniqid('OFC-'),
            'department_id' => $department->id,
            'status' => 'active',
        ]);
        $this->departmentId = $department->id;
        $this->designationId = $designation->id;
    }

    private function product(string $sku = 'SKU-1'): Product
    {
        $category = Category::create([
            'name' => 'General '.uniqid(),
            'slug' => 'general-'.uniqid(),
            'status' => 'active',
        ]);

        return Product::create([
            'sku' => $sku,
            'name' => 'Test product',
            'slug' => strtolower($sku),
            'category_id' => $category->id,
            'unit' => 'piece',
            'cost_price' => 10,
            'selling_price' => 12,
            'reorder_level' => 0,
            'track_stock' => true,
            'status' => 'active',
        ]);
    }

    private function warehouse(string $name = 'Depot'): Warehouse
    {
        return Warehouse::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name).uniqid(),
            'location_id' => 1,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function branch(string $name = 'Branch', ?int $warehouseId = null): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'slug' => str()->slug($name).uniqid(),
            'location_id' => 1,
            'warehouse_id' => $warehouseId,
            'capacity' => '100',
            'starting_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function userWithRole(string $name, array $permissions): User
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions(collect($permissions)->map(
            fn (string $permission) => Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['status' => 'active']
            )
        ));

        $user = User::create([
            'full_name' => $name,
            'email' => uniqid('stock-').'@example.com',
            'password' => Hash::make('password'),
            'phone' => '01700000000',
            'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')),
            'role_id' => $role->id,
            'department_id' => $this->departmentId,
            'designation_id' => $this->designationId,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_recording_movement_updates_level_and_writes_immutable_ledger(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $movement = app(StockService::class)->recordMovement(
            $this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', '12.500'
        );

        $this->assertSame('12.500', StockLevel::firstOrFail()->quantity);
        $this->assertSame('12.500', $movement->quantity_delta);
        $this->assertSame(1, StockMovement::count());

        $this->expectException(LogicException::class);
        $movement->update(['notes' => 'tampered']);
    }

    public function test_negative_movement_reduces_level_and_zero_is_rejected(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 10);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, -2.25);

        $this->assertSame('7.750', StockLevel::firstOrFail()->quantity);
        $this->expectException(\InvalidArgumentException::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'issue', 0);
    }

    public function test_branch_and_warehouse_levels_are_separate(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $branch = $this->branch('Supplied', $warehouse->id);
        $service = app(StockService::class);

        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 20);
        $service->recordMovement($this->superadmin, $product, 'branch', $branch->id, 'opening', 3);

        $this->assertCount(2, StockLevel::all());
        $this->assertSame(['branch', 'warehouse'], StockLevel::query()->orderBy('location_type')->pluck('location_type')->all());
    }

    public function test_branch_user_can_write_only_own_branch_and_warehouse_user_reads_supplied_branch(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $suppliedBranch = $this->branch('Supplied', $warehouse->id);
        $otherBranch = $this->branch('Other');
        $branchUser = $this->userWithRole('Branch stock user', ['stock.view', 'stock.opening']);
        $branchUser->branches()->attach($suppliedBranch->id);
        $warehouseUser = $this->userWithRole('Warehouse stock user', ['stock.view', 'stock.movements.view']);
        $warehouseUser->warehouses()->attach($warehouse->id);
        $service = app(StockService::class);

        $service->recordMovement($branchUser, $product, 'branch', $suppliedBranch->id, 'opening', 4);
        $this->expectException(RuntimeException::class);
        $service->recordMovement($branchUser, $product, 'branch', $otherBranch->id, 'opening', 4);
        $this->assertTrue(\App\Support\StockAccess::canRead($warehouseUser, 'branch', $suppliedBranch->id));
        $this->assertFalse(\App\Support\StockAccess::canRead($warehouseUser, 'branch', $otherBranch->id));
        $this->assertFalse(\App\Support\StockAccess::canWrite($warehouseUser, 'branch', $suppliedBranch->id));
    }

    public function test_rebuild_restores_levels_from_ledger_and_command_runs(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 8);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, -3);
        StockLevel::query()->update(['quantity' => 999]);

        $this->assertSame(1, $service->rebuild());
        $this->assertSame('5.000', StockLevel::firstOrFail()->quantity);
        $this->assertSame(0, Artisan::call('stock:rebuild'));
    }

    public function test_untracked_products_cannot_create_ledger_entries(): void
    {
        $product = $this->product();
        $product->update(['track_stock' => false]);

        $this->expectException(\InvalidArgumentException::class);
        app(StockService::class)->recordMovement($this->superadmin, $product, 'warehouse', $this->warehouse()->id, 'opening', 1);
    }

    public function test_integer_thousandths_math_balance_chain_and_precision_validation(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', '0.1');
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'purchase', '0.2');

        $this->assertSame('0.300', StockLevel::firstOrFail()->quantity);
        $this->assertSame(['0.100', '0.300'], StockMovement::orderBy('id')->pluck('balance_after')->all());

        $this->expectException(\InvalidArgumentException::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'purchase', '0.0001');
    }

    public function test_negative_stock_is_blocked_and_config_can_allow_it(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);

        try {
            $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, '-0.001');
            $this->fail('Expected insufficient stock exception.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame(0, $exception->available);
            $this->assertSame(1, $exception->requested);
        }

        config(['stock.allow_negative' => true]);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, '-0.001');
        $this->assertSame('-0.001', StockLevel::firstOrFail()->quantity);
    }

    public function test_movement_types_signs_and_locations_are_validated(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'unknown', 1);
    }

    public function test_movement_type_sign_rules_are_enforced(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);

        try {
            $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::PURCHASE, -1);
            $this->fail('Expected positive sign validation.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('wrong sign', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, 1);
    }

    public function test_superadmin_cannot_use_missing_location_or_deleted_product(): void
    {
        $product = $this->product();
        $service = app(StockService::class);
        try {
            $service->recordMovement($this->superadmin, $product, 'warehouse', 999999, 'opening', 1);
            $this->fail('Expected missing location exception.');
        } catch (\InvalidArgumentException) {
            $this->assertSame(0, StockMovement::count());
        }

        $product->delete();
        $this->expectException(\InvalidArgumentException::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $this->warehouse()->id, 'opening', 1);
    }

    public function test_transfer_is_atomic_and_shares_batch(): void
    {
        $product = $this->product();
        $from = $this->warehouse('Source');
        $to = $this->warehouse('Destination');
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $from->id, 'opening', 5);
        [$out, $in] = $service->transfer($this->superadmin, $product, 'warehouse', $from->id, 'warehouse', $to->id, '1.250');

        $this->assertSame($out->batch_uuid, $in->batch_uuid);
        $this->assertSame('3.750', StockLevel::where('location_id', $from->id)->firstOrFail()->quantity);
        $this->assertSame('1.250', StockLevel::where('location_id', $to->id)->firstOrFail()->quantity);
    }

    public function test_transfer_rolls_back_on_insufficient_stock_and_rejects_same_location(): void
    {
        $product = $this->product();
        $from = $this->warehouse('Source');
        $to = $this->warehouse('Destination');
        $service = app(StockService::class);

        try {
            $service->transfer($this->superadmin, $product, 'warehouse', $from->id, 'warehouse', $to->id, 1);
            $this->fail('Expected insufficient stock exception.');
        } catch (InsufficientStockException) {
            $this->assertSame(0, StockMovement::count());
        }

        $this->expectException(\InvalidArgumentException::class);
        $service->transfer($this->superadmin, $product, 'warehouse', $from->id, 'warehouse', $from->id, 1);
    }

    public function test_create_if_missing_path_is_idempotent_when_level_already_exists(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        StockLevel::create(['product_id' => $product->id, 'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'quantity' => '0.000']);

        app(StockService::class)->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', '1.000');
        $this->assertSame(1, StockLevel::where('product_id', $product->id)->count());
    }

    public function test_rebuild_dry_run_detects_tampering_and_normal_rebuild_fixes_levels_and_chain(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 2);
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, -1);
        StockLevel::query()->update(['quantity' => '99.000']);
        StockMovement::query()->where('id', 2)->update(['balance_after' => '99.000']);

        $this->assertSame(1, Artisan::call('stock:rebuild', ['--dry-run' => true]));
        $this->assertSame(0, Artisan::call('stock:rebuild'));
        $this->assertSame('1.000', StockLevel::firstOrFail()->quantity);
        $this->assertSame('1.000', StockMovement::find(2)->balance_after);
    }

    public function test_seeded_randomized_operations_keep_every_level_equal_to_ledger_sum(): void
    {
        mt_srand(20261103);
        $product = $this->product();
        $warehouse = $this->warehouse();
        $service = app(StockService::class);
        $expected = 0;
        $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'opening', 100);
        $expected = 100000;

        for ($i = 0; $i < 40; $i++) {
            $amount = mt_rand(1, 125) * 1000;
            if ($amount > $expected || mt_rand(0, 1) === 0) {
                $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, 'purchase', $this->formatTestQuantity($amount));
                $expected += $amount;
            } else {
                $service->recordMovement($this->superadmin, $product, 'warehouse', $warehouse->id, StockMovementType::SALE, $this->formatTestQuantity(-$amount));
                $expected -= $amount;
            }
        }

        $level = StockLevel::firstOrFail();
        $sum = StockMovement::sum('quantity_delta');
        $this->assertSame($this->formatTestQuantity($expected), $level->quantity);
        $this->assertSame($this->formatTestQuantity($expected), number_format((float) $sum, 3, '.', ''));
    }

    private function formatTestQuantity(int $thousandths): string
    {
        return sprintf('%d.%03d', intdiv($thousandths, 1000), $thousandths % 1000);
    }
}
