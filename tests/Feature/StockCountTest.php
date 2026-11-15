<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockCountService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Count warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function product(): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid('CNT-'), 'name' => 'Count product', 'slug' => uniqid('count-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => 0, 'track_stock' => true,
            'tracking_type' => 'quantity', 'status' => 'active',
        ]);
    }

    private function user(array $permissions, array $warehouseIds = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Count user', 'email' => uniqid('cnt-').'@example.com',
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

    private function startedCount(Product $product, Warehouse $warehouse): StockCount
    {
        return app(StockCountService::class)->start($this->admin, [
            'location_type' => 'warehouse', 'location_id' => $warehouse->id, 'notes' => 'Monthly count',
        ]);
    }

    public function test_variance_flow_posts_adjustment_only_on_approval(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');

        $service = app(StockCountService::class);
        $count = $this->startedCount($product, $warehouse);
        $this->assertSame('open', $count->status);
        $this->assertMatchesRegularExpression('/^CNT-\d{4}-\d{6}$/', $count->count_no);
        $this->assertSame('10.000', (string) $count->lines()->first()->system_qty);

        $line = $count->lines()->first();
        $service->submit($this->admin, $count->fresh(), [$line->id => '8.000']);
        $count->refresh();
        $this->assertSame('submitted', $count->status);
        $this->assertSame('-2.000', (string) $count->lines()->first()->variance);
        $this->assertSame('10.000', $this->level($product, $warehouse->id));

        $adjustment = StockAdjustment::findOrFail($count->adjustment_id);
        $this->assertSame('pending', $adjustment->status);
        $this->assertSame('-2.000', (string) $adjustment->lines()->first()->delta);

        $service->approve($this->admin, $count->fresh());
        $this->assertSame('approved', $count->fresh()->status);
        $this->assertSame('approved', $adjustment->fresh()->status);
        $this->assertSame('8.000', $this->level($product, $warehouse->id));
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment', 'quantity_delta' => '-2.000',
            'reference_type' => 'stock_adjustment', 'reference_id' => $adjustment->id,
        ]);
        $this->assertSame(0, app(StockService::class)->rebuildWithReport()['mismatches']);
        $this->artisan('stock:rebuild')->assertExitCode(0);
    }

    public function test_drift_after_snapshot_requires_acknowledgement_before_approval(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        $stock = app(StockService::class);
        $stock->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '10.000');

        $service = app(StockCountService::class);
        $count = $this->startedCount($product, $warehouse);
        $line = $count->lines()->first();
        $service->submit($this->admin, $count->fresh(), [$line->id => '9.000']);

        // Stock moved after the snapshot, so system qty no longer matches the sheet.
        $stock->recordMovement($this->admin, $product, 'warehouse', $warehouse->id, 'adjustment', 5);
        $this->assertSame('15.000', $this->level($product, $warehouse->id));

        try {
            $service->approve($this->admin, $count->fresh());
            $this->fail('Approval should be blocked by drift.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('moved after the snapshot', $exception->getMessage());
        }
        $this->assertSame('submitted', $count->fresh()->status);
        $this->assertSame('15.000', $this->level($product, $warehouse->id));

        $service->acknowledgeDrift($this->admin, $count->fresh());
        $this->assertTrue((bool) $count->fresh()->drift_acknowledged);
        $this->assertSame('5.000', (string) $count->lines()->first()->drift_qty);

        $service->approve($this->admin, $count->fresh());
        $this->assertSame('approved', $count->fresh()->status);
        $this->assertSame('14.000', $this->level($product, $warehouse->id));
        $this->assertSame(0, $stock->rebuildWithReport()['mismatches']);
    }

    public function test_only_one_open_count_per_location_and_double_approval_is_rejected(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '4.000');
        $service = app(StockCountService::class);
        $count = $this->startedCount($product, $warehouse);

        try {
            $this->startedCount($product, $warehouse);
            $this->fail('A second open count should be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }

        $line = $count->lines()->first();
        $service->submit($this->admin, $count->fresh(), [$line->id => '4.000']);
        $this->assertSame('approved', $count->fresh()->status, 'A count without variance is auto-approved.');

        try {
            $service->approve($this->admin, $count->fresh());
            $this->fail('Approving a non-submitted count should fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('submitted', $exception->getMessage());
        }
        $this->assertSame(1, StockMovement::where('movement_type', 'opening')->count());
    }

    public function test_count_pages_render_and_are_scoped(): void
    {
        $warehouse = $this->warehouse();
        $product = $this->product();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '4.000');
        $count = $this->startedCount($product, $warehouse);

        foreach ([
            route('admin.counts.index'),
            route('admin.counts.create'),
            route('admin.counts.show', $count),
            route('admin.counts.print', $count),
        ] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        $outsider = $this->user(['counts.view']);
        $this->actingAs($outsider)->get(route('admin.counts.index'))->assertOk()->assertDontSee($count->count_no);
        $this->actingAs($outsider)->get(route('admin.counts.show', $count))->assertForbidden();

        $noPermissions = $this->user([]);
        $this->actingAs($noPermissions)->get(route('admin.counts.index'))->assertForbidden();
    }
}
