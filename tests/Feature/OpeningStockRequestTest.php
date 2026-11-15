<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Models\StockUnit;
use App\Models\User;
use App\Services\OpeningStockRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpeningStockRequestTest extends TestCase
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
            'sku' => uniqid('OSR-'), 'name' => 'Opening product', 'slug' => uniqid('opening-'),
            'category_id' => $category->id, 'unit' => 'piece', 'cost_price' => 1,
            'selling_price' => 2, 'reorder_level' => 5, 'track_stock' => true,
            'tracking_type' => $tracking, 'status' => 'active',
        ]);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'name' => uniqid('Branch '), 'code' => uniqid('BR-'), 'slug' => uniqid('branch-'),
            'location_id' => 1, 'capacity' => 10, 'starting_date' => '2026-01-01', 'status' => 'active',
        ]);
    }

    public function test_submit_changes_no_stock_and_approve_records_approver_reference_and_alert(): void
    {
        $product = $this->product();
        $branch = $this->branch();
        $request = app(OpeningStockRequestService::class)->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '4.000']],
        ]);

        $this->assertSame('pending', $request->status);
        $this->assertDatabaseCount('stock_movements', 0);
        app(OpeningStockRequestService::class)->approve($this->admin, $request);

        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => 'opening_request', 'reference_id' => $request->id,
            'performed_by' => $this->admin->id, 'quantity_delta' => '4.000',
        ]);
        $this->assertDatabaseHas('stock_alerts', ['branch_id' => $branch->id, 'status' => 'open']);
    }

    public function test_double_approval_and_revalidation_failure_create_nothing(): void
    {
        $product = $this->product();
        $branch = $this->branch();
        $service = app(OpeningStockRequestService::class);
        $request = $service->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '2.000']],
        ]);
        app(\App\Services\StockService::class)->recordMovement($this->admin, $product, 'branch', $branch->id, 'opening', 1);
        $this->expectException(\InvalidArgumentException::class);
        $service->approve($this->admin, $request);
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_multiline_approval_is_atomic_and_reject_requires_reason(): void
    {
        $first = $this->product();
        $second = $this->product();
        $branch = $this->branch();
        $service = app(OpeningStockRequestService::class);
        $request = $service->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [
                ['product_id' => $first->id, 'quantity' => '2.000'],
                ['product_id' => $second->id, 'quantity' => '3.000'],
            ],
        ]);
        app(\App\Services\StockService::class)->recordMovement($this->admin, $second, 'branch', $branch->id, 'opening', 1);
        try {
            $service->approve($this->admin, $request);
            $this->fail('Approval should fail.');
        } catch (\InvalidArgumentException) {
            $this->assertDatabaseCount('stock_movements', 1);
            $this->assertDatabaseMissing('stock_movements', ['product_id' => $first->id, 'reference_id' => $request->id]);
        }
        $this->expectException(\InvalidArgumentException::class);
        $service->reject($this->admin, $request, '');
    }

    public function test_serial_approval_creates_units_and_resubmit_copies_lines(): void
    {
        $product = $this->product('serial');
        $branch = $this->branch();
        $service = app(OpeningStockRequestService::class);
        $request = $service->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $product->id, 'quantity' => '2', 'serial_numbers' => ['SN-1', 'SN-2']]],
        ]);
        $service->reject($this->admin, $request, 'Please verify the count.');
        $newRequest = $service->resubmit($this->admin, $request->fresh('lines'));
        $this->assertSame('pending', $newRequest->status);
        $this->assertSame($request->id, $newRequest->resubmitted_from_id);
        $service->approve($this->admin, $newRequest);
        $this->assertCount(2, StockUnit::where('product_id', $product->id)->get());
    }

    public function test_self_approval_can_be_disabled(): void
    {
        config(['stock.approval.allow_self_approval' => false]);
        $branch = $this->branch();
        $request = app(OpeningStockRequestService::class)->submit($this->admin, [
            'location_type' => 'branch', 'location_id' => $branch->id, 'occurred_at' => '2026-11-16',
            'lines' => [['product_id' => $this->product()->id, 'quantity' => '1.000']],
        ]);
        $this->expectException(\RuntimeException::class);
        app(OpeningStockRequestService::class)->approve($this->admin, $request);
    }
}
