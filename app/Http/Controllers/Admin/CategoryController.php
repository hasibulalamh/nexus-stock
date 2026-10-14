<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a list of categories.
     */
    public function list(Request $request)
    {
        $perPage = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 10;

        $categories = Category::with('parent')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $query->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when($request->filled('parent_id'), fn($q) => $q->where('parent_id', $request->parent_id))
            ->when(in_array($request->status, ['active', 'inactive']), fn($q) => $q->where('status', $request->status))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('admin.category.list', compact('categories', 'parents'));
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        return view('admin.category.create', compact('parents'));
    }

    /**
     * Store a new category.
     */
    public function store(CategoryRequest $request)
    {
        $category = $this->saveCategory($request);

        return redirect()
            ->route('admin.categories.list')
            ->with('success', 'Category created successfully');
    }

    /**
     * Show the edit form.
     */
    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        return view('admin.category.edit', compact('category', 'parents'));
    }

    /**
     * Update an existing category.
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $this->saveCategory($request, $category);

        return redirect()
            ->route('admin.categories.list')
            ->with('success', 'Category updated successfully');
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category)
    {
        // Cannot delete if has children
        if ($category->children()->exists()) {
            return redirect()
                ->route('admin.categories.list')
                ->with('error', 'Cannot delete this category because it has child categories.');
        }

        // Cannot delete if has products
        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.list')
                ->with('error', 'Cannot delete this category because it has products.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.list')
            ->with('success', 'Category deleted successfully');
    }

    /**
     * Generate a slug from the name.
     */
    public function generateSlug(Request $request)
    {
        $name = $request->input('name');
        if (!$name) {
            return response()->json(['slug' => '']);
        }

        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        // Ensure unique slug
        while (Category::where('slug', $slug)->withTrashed()->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Save a category (used for both create and update).
     */
    private function saveCategory(CategoryRequest $request, ?Category $category = null): Category
    {
        $attributes = [
            'name' => $request->name,
            'slug' => $request->slug,
            'parent_id' => $request->parent_id ?: null,
            'description' => $request->description,
            'status' => $request->status,
            'sort_order' => $request->sort_order ?? 0,
        ];

        if ($category) {
            $category->update($attributes);
            return $category;
        }

        return Category::create($attributes);
    }
}
