<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\OpeningStockRequest;
use App\Models\Product;
use App\Models\State;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AreaImportService;
use App\Services\OpeningStockRequestService;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function product(string $sku = 'HARD-1'): Product
    {
        $category = Category::create(['name' => uniqid('Cat'), 'slug' => uniqid('cat-'), 'status' => 'active']);

        return Product::create([
            'sku' => uniqid($sku.'-'), 'name' => 'Hardening product', 'slug' => uniqid('hard-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 10,
            'selling_price' => 20, 'reorder_level' => 5, 'track_stock' => true,
            'tracking_type' => 'quantity', 'status' => 'active', 'barcode' => null,
        ]);
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Hardening warehouse', 'code' => uniqid('WH-'), 'slug' => uniqid('wh-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'name' => 'Hardening branch', 'code' => uniqid('BR-'), 'slug' => uniqid('br-'),
            'location_id' => 1, 'capacity' => 100, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    private function user(array $permissions = [], array $warehouses = []): User
    {
        $role = Role::create(['name' => uniqid('role-'), 'guard_name' => 'web']);
        $user = User::create([
            'full_name' => 'Hardening user', 'email' => uniqid('hard-').'@example.com',
            'password' => Hash::make('password'), 'phone' => '01700000000', 'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')), 'role_id' => $role->id,
            'department_id' => Department::query()->value('id'),
            'designation_id' => Designation::query()->value('id'),
            'join_date' => '2026-01-01', 'profile_photo' => '/uploads/users/default.png',
            'status' => 'active', 'access_all' => false,
        ]);
        $user->assignRole($role->name);
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        $user->warehouses()->attach($warehouses);

        return $user;
    }

    public function test_activity_log_requires_its_own_permission(): void
    {
        $without = $this->user(['roles.view']);
        $this->actingAs($without)->get(route('admin.activity-log'))->assertForbidden();

        $with = $this->user(['activitylog.view']);
        $this->actingAs($with)->get(route('admin.activity-log'))->assertOk()->assertSee('Activity log');
    }

    public function test_product_edit_shows_stock_by_location_only_with_stock_view(): void
    {
        $product = $this->product();
        $warehouse = $this->warehouse();
        app(StockService::class)->recordOpening($this->admin, $product, 'warehouse', $warehouse->id, '6.000');

        $viewer = $this->user(['products.view', 'products.edit', 'stock.view'], [$warehouse->id]);
        $this->actingAs($viewer)->get(route('admin.products.edit', $product))
            ->assertOk()->assertSee('Stock by location')->assertSee('Hardening warehouse')->assertSee('6.000');

        $withoutStock = $this->user(['products.view', 'products.edit'], [$warehouse->id]);
        $this->actingAs($withoutStock)->get(route('admin.products.edit', $product))
            ->assertOk()->assertDontSee('Stock by location');
    }

    public function test_barcode_page_generates_a_missing_barcode_instead_of_crashing(): void
    {
        $product = $this->product();
        $this->assertNull($product->barcode);

        $this->actingAs($this->admin)->get(route('admin.products.barcode', $product))->assertOk();

        $this->assertNotNull($product->fresh()->barcode);
        $this->assertSame(Product::internalBarcodeForId($product->id), $product->fresh()->barcode);
    }

    public function test_pending_markers_are_cleared_and_double_pending_lines_are_blocked_by_the_index(): void
    {
        $product = $this->product();
        $branch = $this->branch();
        $service = app(OpeningStockRequestService::class);

        $request = $service->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ]);
        $this->assertSame('pending', $request->lines()->first()->pending_marker);

        $other = OpeningStockRequest::create([
            'request_no' => 'OSR-test-'.uniqid(), 'location_type' => 'branch', 'location_id' => $branch->id,
            'occurred_at' => '2026-11-16', 'status' => 'pending', 'requested_by' => $this->admin->id,
            'requested_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $other->lines()->create([
            'product_id' => $product->id, 'quantity' => '1.000',
            'pending_location_type' => 'branch', 'pending_location_id' => $branch->id,
            'pending_marker' => 'pending',
        ]);
    }

    public function test_approval_clears_the_pending_marker(): void
    {
        $product = $this->product();
        $branch = $this->branch();
        $service = app(OpeningStockRequestService::class);
        $request = $service->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ]);
        $service->approve($this->admin, $request);

        $this->assertNull($request->lines()->first()->pending_marker);
    }

    public function test_access_all_users_receive_opening_request_notifications(): void
    {
        $accessAll = $this->user();
        $accessAll->update(['access_all' => true]);
        $branch = $this->branch();
        $product = $this->product();

        app(OpeningStockRequestService::class)->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '3.000']],
        ]);

        $this->assertSame(1, $accessAll->notifications()->count());
    }

    public function test_area_import_normalises_bangla_text_and_rejects_csv_duplicates(): void
    {
        [$city] = $this->city();
        $service = app(AreaImportService::class);

        $composed = 'মিরপুর';
        $decomposed = \Normalizer::normalize($composed, \Normalizer::FORM_D);

        $duplicate = $this->csv([
            ['', 'Mirpur', $composed, 'area', '', '23.8', '90.3', 'Dhaka', '', 'test', 'no', ''],
            ['', 'Mirpur 2', $decomposed, 'area', '', '23.8', '90.3', 'Dhaka', '', 'test', 'no', ''],
        ]);

        $preview = $service->analyse($duplicate);
        $this->assertNotEmpty($preview['errors']);
        $this->expectException(ValidationException::class);
        try {
            $service->apply($duplicate);
        } finally {
            unlink($duplicate);
        }
    }

    public function test_area_import_never_overwrites_an_osm_row_without_an_osm_id(): void
    {
        [$city] = $this->city();
        Area::create([
            'city_id' => $city->id, 'name' => 'Mirpur', 'name_bn' => 'মিরপুর',
            'osm_id' => 123456, 'status' => 'active',
        ]);

        $path = $this->csv([
            ['', 'Mirpur changed', 'মিরপুর', 'area', '', '23.8', '90.3', 'Dhaka', '', 'test', 'no', ''],
        ]);

        try {
            app(AreaImportService::class)->apply($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(1, Area::count());
        $this->assertSame('Mirpur', Area::firstOrFail()->name);
        $this->assertSame(123456, (int) Area::firstOrFail()->osm_id);
    }

    private function city(): array
    {
        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => 'bangladesh']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Dhaka', 'code' => 'DHK', 'slug' => 'dhaka']);
        $city = City::create(['state_id' => $state->id, 'name' => 'Dhaka', 'code' => 'DHK-C', 'slug' => 'dhaka-city']);

        return [$city];
    }

    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'areas').'.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, AreaImportService::HEADERS);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }
}
