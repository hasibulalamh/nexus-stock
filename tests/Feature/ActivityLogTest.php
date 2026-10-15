<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    public function test_creating_a_product_logs_the_causer_without_password_properties(): void
    {
        $category = Category::create(['name' => 'Activity category', 'slug' => 'activity-category', 'status' => 'active']);
        $brand = Brand::create(['name' => 'Activity brand', 'slug' => 'activity-brand', 'status' => 'active']);

        $this->actingAs($this->admin);
        $product = Product::create([
            'sku' => 'ACT-001',
            'name' => 'Activity product',
            'slug' => 'activity-product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit' => 'piece',
            'status' => 'active',
        ]);

        $activity = Activity::where('subject_type', Product::class)
            ->where('subject_id', $product->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('Created product', $activity->description);
        $this->assertStringNotContainsString('password', json_encode($activity->properties));
    }

    public function test_activity_log_page_is_paginated_and_can_filter_by_module(): void
    {
        Category::create(['name' => 'Filter category', 'slug' => 'filter-category', 'status' => 'active']);

        $this->actingAs($this->admin)
            ->get(route('admin.activity-log', ['module' => 'Category']))
            ->assertOk()
            ->assertSee('Activity log');
    }
}
