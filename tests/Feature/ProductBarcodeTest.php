<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductBarcodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function category(): Category
    {
        return Category::create(['name' => uniqid('Product category '), 'slug' => uniqid('product-category-'), 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'SKU-'.strtoupper(uniqid()),
            'name' => 'Sample product',
            'category_id' => $this->category()->id,
            'unit' => 'pcs',
            'cost_price' => '1.00',
            'selling_price' => '2.00',
            'track_stock' => 1,
            'status' => 'active',
        ], $overrides);
    }

    public function test_product_slug_and_internal_barcode_are_generated_when_omitted(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload());
        $response->assertRedirect(route('admin.products.list'));

        $product = Product::latest('id')->firstOrFail();
        $this->assertSame('sample-product', $product->slug);
        $this->assertMatchesRegularExpression('/^\d{13}$/', $product->barcode);
        $this->assertSame((int) substr($product->barcode, -1), $this->eanCheckDigit($product->barcode));
    }

    public function test_generated_slug_gets_a_suffix_on_collision(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload(['name' => 'Collision name']));
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload(['name' => 'Collision name']));

        $this->assertDatabaseHas('products', ['slug' => 'collision-name']);
        $this->assertDatabaseHas('products', ['slug' => 'collision-name-2']);
    }

    public function test_update_preserves_existing_slug_and_user_barcode(): void
    {
        $product = Product::create(array_merge($this->payload(), [
            'slug' => 'kept-slug',
            'barcode' => '5901234123457',
        ]));

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $this->payload([
            'sku' => $product->sku,
            'name' => 'Renamed product',
        ]))->assertRedirect(route('admin.products.list'));

        $this->assertSame('kept-slug', $product->fresh()->slug);
        $this->assertSame('5901234123457', $product->fresh()->barcode);
    }

    public function test_update_without_barcode_generates_one_and_duplicate_user_barcode_is_rejected(): void
    {
        $product = Product::create(array_merge($this->payload(), ['slug' => 'missing-barcode', 'barcode' => null]));
        $other = Product::create(array_merge($this->payload(), ['slug' => 'other-product', 'barcode' => '5901234123457']));

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $this->payload([
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
        ]))->assertRedirect();
        $this->assertNotNull($product->fresh()->barcode);

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $this->payload([
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
            'barcode' => $other->barcode,
        ]))->assertSessionHasErrors('barcode');
    }

    public function test_barcode_label_page_requires_view_permission_and_validates_quantity(): void
    {
        $product = Product::create(array_merge($this->payload(), ['slug' => 'label-product', 'barcode' => '5901234123457']));
        $user = $this->admin->replicate();
        $user->email = uniqid('label-user-').'@example.com';
        $user->full_name = 'Label user';
        $user->role_id = Role::create(['name' => 'label-user', 'guard_name' => 'web'])->id;
        $user->access_all = false;
        $user->save();
        $user->syncRoles(['label-user']);

        $this->actingAs($user)->get(route('admin.products.barcode', $product))->assertForbidden();
        $user->givePermissionTo('products.view');
        $this->actingAs($user)->get(route('admin.products.barcode', $product).'?quantity=0')->assertSessionHasErrors('quantity');
        $this->actingAs($user)->get(route('admin.products.barcode', $product))->assertOk()->assertSee('Sample product');
    }

    public function test_barcode_generation_dry_run_does_not_change_products(): void
    {
        $product = Product::create(array_merge($this->payload(), ['slug' => 'dry-run-product', 'barcode' => null]));

        $this->artisan('products:generate-barcodes', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($product->fresh()->barcode);
    }

    private function eanCheckDigit(string $barcode): int
    {
        $sum = 0;
        foreach (str_split(substr($barcode, 0, 12)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}
