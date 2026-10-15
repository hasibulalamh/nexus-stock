<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Display a list of brands.
     */
    public function list(Request $request)
    {
        $perPage = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 10;

        $brands = Brand::withCount('products')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $query->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when(in_array($request->status, ['active', 'inactive']), fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.brand.list', compact('brands'));
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('admin.brand.create');
    }

    /**
     * Store a new brand.
     */
    public function store(BrandRequest $request)
    {
        $brand = $this->saveBrand($request);

        return redirect()
            ->route('admin.brands.list')
            ->with('success', 'Brand created successfully');
    }

    /**
     * Show the edit form.
     */
    public function edit(Brand $brand)
    {
        return view('admin.brand.edit', compact('brand'));
    }

    /**
     * Update an existing brand.
     */
    public function update(BrandRequest $request, Brand $brand)
    {
        $this->saveBrand($request, $brand);

        return redirect()
            ->route('admin.brands.list')
            ->with('success', 'Brand updated successfully');
    }

    /**
     * Delete a brand.
     */
    public function delete(Brand $brand)
    {
        // Cannot delete if has products
        if ($brand->products()->exists()) {
            return redirect()
                ->route('admin.brands.list')
                ->with('error', 'Cannot delete this brand because it has products.');
        }

        // Remove logo file if exists and is not default
        if ($brand->logo && $brand->logo !== 'placeholder.png') {
            $logoPath = public_path('uploads/brand/' . $brand->logo);
            if (File::exists($logoPath)) {
                File::delete($logoPath);
            }
        }

        $brand->delete();

        return redirect()
            ->route('admin.brands.list')
            ->with('success', 'Brand deleted successfully');
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
        while (Brand::where('slug', $slug)->withTrashed()->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return response()->json(['slug' => $slug]);
    }

    /**
     * Save a brand (used for both create and update).
     */
    private function saveBrand(BrandRequest $request, ?Brand $brand = null): Brand
    {
        $uploadPath = public_path('uploads/brand');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $attributes = [
            'name' => $request->name,
            'slug' => strtolower(trim($request->slug)),
            'website' => $request->website,
            'description' => $request->description,
            'status' => $request->status,
        ];

        // Handle logo upload
        $oldLogo = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = time() . '.' . $logo->getClientOriginalExtension();
            $logo->move($uploadPath, $filename);

            $oldLogo = $brand?->logo;
            $attributes['logo'] = $filename;
        }

        if ($brand) {
            $brand->update($attributes);
            $result = $brand;
        } else {
            $result = Brand::create($attributes);
        }

        // Remove old logo if replaced
        if ($oldLogo && $oldLogo !== 'placeholder.png' && $oldLogo !== $result->logo) {
            $oldPath = public_path('uploads/brand/' . $oldLogo);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        return $result;
    }
}
