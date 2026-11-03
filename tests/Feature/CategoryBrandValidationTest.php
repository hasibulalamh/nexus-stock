<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryBrandValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    private function category(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Category '.uniqid(),
            'slug' => 'category-'.uniqid(),
            'status' => 'active',
            'sort_order' => 0,
        ], $attributes));
    }

    private function brand(array $attributes = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'Brand '.uniqid(),
            'slug' => 'brand-'.uniqid(),
            'status' => 'active',
        ], $attributes));
    }

    private function categoryPayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'description' => $category->description,
            'status' => $category->status,
            'sort_order' => $category->sort_order,
        ], $overrides);
    }

    private function brandPayload(Brand $brand, array $overrides = []): array
    {
        return array_merge([
            'name' => $brand->name,
            'slug' => $brand->slug,
            'website' => $brand->website,
            'description' => $brand->description,
            'status' => $brand->status,
        ], $overrides);
    }

    public function test_category_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'New category',
            'slug' => 'new-category',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.categories.list'));
        $this->assertDatabaseHas('categories', ['slug' => 'new-category']);
    }

    public function test_category_can_keep_its_own_name_and_slug_on_update(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)->put(route('admin.categories.update', $category), $this->categoryPayload($category))
            ->assertRedirect(route('admin.categories.list'));
    }

    public function test_category_rejects_another_category_slug(): void
    {
        $category = $this->category();
        $other = $this->category();

        $this->actingAs($this->admin)->from(route('admin.categories.edit', $category))
            ->put(route('admin.categories.update', $category), $this->categoryPayload($category, ['slug' => $other->slug]))
            ->assertSessionHasErrors('slug');
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin)->from(route('admin.categories.edit', $category))
            ->put(route('admin.categories.update', $category), $this->categoryPayload($category, ['parent_id' => $category->id]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_category_cannot_use_a_grandchild_as_parent(): void
    {
        $root = $this->category();
        $child = $this->category(['parent_id' => $root->id]);
        $grandchild = $this->category(['parent_id' => $child->id]);

        $this->actingAs($this->admin)->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'name' => 'Invalid depth',
                'slug' => 'invalid-depth',
                'parent_id' => $grandchild->id,
                'status' => 'active',
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_brand_can_be_created(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), [
            'name' => 'New brand',
            'slug' => 'new-brand',
            'status' => 'active',
        ])->assertRedirect(route('admin.brands.list'));

        $this->assertDatabaseHas('brands', ['slug' => 'new-brand']);
    }

    public function test_brand_can_keep_its_values_on_update(): void
    {
        $brand = $this->brand();

        $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), $this->brandPayload($brand))
            ->assertRedirect(route('admin.brands.list'));
    }

    public function test_brand_rejects_duplicate_name_and_slug(): void
    {
        $brand = $this->brand();
        $other = $this->brand();

        $this->actingAs($this->admin)->from(route('admin.brands.edit', $brand))
            ->put(route('admin.brands.update', $brand), $this->brandPayload($brand, [
                'name' => $other->name,
                'slug' => $other->slug,
            ]))->assertSessionHasErrors(['name', 'slug']);
    }
}
