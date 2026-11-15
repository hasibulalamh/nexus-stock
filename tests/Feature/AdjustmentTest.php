<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AdjustmentService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdjustmentTest extends TestCase
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
            'sku' => uniqid('ADJ-'), 'name' => 'Adjustment product', 'slug' => uniqid('adjustment-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => 0, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Adjustment warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function user(array $permissions, array $warehouseIds = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Adjustment user', 'email' => uniqid('adj-').'@example.com',
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

    private function level(Product $product, int $warehouseId): string
    {
        return (string) (StockLevel::query()->where('product_id', $product->id)
            ->where('location_type', 'warehouse')->where('location_id', $warehouseId)->value('quantity') ?? '0.000');
    }

    private function submit(Product $product, Warehouse $warehouse, string $delta, string $reason = 'damaged', array $extra = []): StockAdjustment
    {
        return app(AdjustmentService::class)->submit($this->admin, [
            'location_type' => 'warehouse', 'location_id' => $warehouse->id,
            'reason' => $reason, 'notes' => 'Test adjustment',
            'lines' => [array_merge(['product_id' => $product->id, 'delta' => $delta], $extra)],
        ]);
    }

    public function test_approved_adjustment_posts_ledger_and_rejection_posts_nothing(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');

        $adjustment = $this->submit($product, $warehouse, '-3.000');
        $this->assertSame('pending', $adjustment->status);
        $this->assertMatchesRegularExpression('/^ADJ-\d{4}-\d{6}$/', $adjustment->adj_no);
        $this->assertSame('10.000', $this->level($product, $warehouse->id));

        app(AdjustmentService::class)->approve($this->admin, $adjustment->fresh());
        $this->assertSame('7.000', $this->level($product, $warehouse->id));
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment', 'quantity_delta' => '-3.000',
            'reference_type' => 'stock_adjustment', 'reference_id' => $adjustment->id,
        ]);

        $rejected = $this->submit($product, $warehouse, '2.000', 'found');
        app(AdjustmentService::class)->reject($this->admin, $rejected->fresh(), 'Wrong product');
        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSame('7.000', $this->level($product, $warehouse->id));
        $this->assertSame(1, StockMovement::where('reference_type', 'stock_adjustment')->count());
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_double_approval_is_rejected(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '5.000');
        $adjustment = $this->submit($product, $warehouse, '-1.000');
        $service = app(AdjustmentService::class);
        $service->approve($this->admin, $adjustment->fresh());

        try {
            $service->approve($this->admin, $adjustment->fresh());
            $this->fail('A second approval should be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('pending', $exception->getMessage());
        }
        $this->assertSame('4.000', $this->level($product, $warehouse->id));
        $this->assertSame(1, StockMovement::where('movement_type', 'adjustment')->count());
    }

    public function test_negative_adjustment_cannot_exceed_available_stock(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '1.000');
        $adjustment = $this->submit($product, $warehouse, '-5.000');

        try {
            app(AdjustmentService::class)->approve($this->admin, $adjustment->fresh());
            $this->fail('Negative stock must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('pending', $adjustment->fresh()->status);
        }
        $this->assertSame('1.000', $this->level($product, $warehouse->id));
    }

    public function test_serial_adjustments_remove_and_add_units(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product('serial');
        $stock = app(StockService::class);
        $stock->recordSerialOpening($this->admin, $product, 'warehouse', $warehouse->id, ['S-1', 'S-2', 'S-3']);
        $units = StockUnit::where('product_id', $product->id)->orderBy('id')->get();
        $service = app(AdjustmentService::class);

        $out = $this->submit($product, $warehouse, '-2.000', 'damaged', ['stock_unit_ids' => [$units[0]->id, $units[1]->id]]);
        $service->approve($this->admin, $out->fresh());
        $this->assertSame('damaged', $units[0]->fresh()->status);
        $this->assertSame('1.000', $this->level($product, $warehouse->id));

        $in = $this->submit($product, $warehouse, '1.000', 'found', ['serial_numbers' => ['S-FOUND']]);
        $service->approve($this->admin, $in->fresh());
        $this->assertSame('2.000', $this->level($product, $warehouse->id));
        $this->assertDatabaseHas('stock_units', ['product_id' => $product->id, 'serial_no' => 'S-FOUND', 'status' => 'in_stock']);
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_submission_notifies_approvers_and_not_the_actor(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '3.000');
        $approver = $this->user(['adjustments.view', 'adjustments.approve'], [$warehouse->id]);

        $this->submit($product, $warehouse, '-1.000');

        $this->assertSame(1, DatabaseNotification::where('notifiable_id', $approver->id)->count());
        $this->assertSame(0, DatabaseNotification::where('notifiable_id', $this->admin->id)->count());
    }

    public function test_adjustment_pages_render_and_are_scoped(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '3.000');
        $adjustment = $this->submit($product, $warehouse, '-1.000');

        foreach ([
            route('admin.adjustments.index'),
            route('admin.adjustments.create'),
            route('admin.adjustments.create', ['location_type' => 'warehouse', 'location_id' => $warehouse->id]),
            route('admin.adjustments.show', $adjustment),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $outsider = $this->user(['adjustments.view']);
        $this->actingAs($outsider)->get(route('admin.adjustments.index'))->assertOk()->assertDontSee($adjustment->adj_no);
        $this->actingAs($outsider)->get(route('admin.adjustments.show', $adjustment))->assertForbidden();

        $noPermissions = $this->user([]);
        $this->actingAs($noPermissions)->get(route('admin.adjustments.index'))->assertForbidden();
    }
}
