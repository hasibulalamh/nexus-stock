<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\StockService;
use App\Models\StockLevel;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGenerator;

class ProductController extends Controller
{
    /**
     * Display a list of products.
     */
    public function list(Request $request)
    {
        $perPage = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 10;

        $products = Product::with(['category', 'brand'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $query->where(function ($w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term);
                });
            })
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('brand_id'), fn($q) => $q->where('brand_id', $request->brand_id))
            ->when(in_array($request->status, ['active', 'inactive']), fn($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $brands = Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $units = config('inventory.units');

        return view('admin.product.list', compact('products', 'categories', 'brands', 'units'));
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $brands = Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $units = config('inventory.units');
        return view('admin.product.create', compact('categories', 'brands', 'units'));
    }

    /**
     * Store a new product.
     */
    public function store(ProductRequest $request)
    {
        $product = DB::transaction(fn () => $this->saveProduct($request));

        return redirect()
            ->route('admin.products.list')
            ->with('success', 'Product created successfully');
    }

    /**
     * Show the edit form.
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get(['id', 'name']);
        $brands = Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $units = config('inventory.units');
        return view('admin.product.edit', compact('product', 'categories', 'brands', 'units'));
    }

    /**
     * Update an existing product.
     */
    public function update(ProductRequest $request, Product $product)
    {
        DB::transaction(fn () => $this->saveProduct($request, $product));

        return redirect()
            ->route('admin.products.list')
            ->with('success', 'Product updated successfully');
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product)
    {
        // Remove image file if exists
        if ($product->image) {
            $imagePath = public_path('uploads/product/' . $product->image);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }

        $product->delete();

        return redirect()
            ->route('admin.products.list')
            ->with('success', 'Product deleted successfully');
    }

    /**
     * Save a product (used for both create and update).
     */
    private function saveProduct(ProductRequest $request, ?Product $product = null): Product
    {
        $uploadPath = public_path('uploads/product');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        // Process SKU - uppercase and trim
        $sku = strtoupper(trim($request->sku));

        $attributes = [
            'sku' => $sku,
            'barcode' => $request->filled('barcode') ? strtoupper(trim($request->barcode)) : $product?->barcode,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'unit' => $request->unit,
            'cost_price' => $request->cost_price,
            'selling_price' => $request->selling_price,
            'reorder_level' => $request->reorder_level,
            'track_stock' => $request->boolean('track_stock'),
            'status' => $request->status,
        ];

        // Handle image upload
        $oldImage = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move($uploadPath, $filename);

            $oldImage = $product?->image;
            $attributes['image'] = $filename;
        }

        if ($product) {
            $reorderLevelChanged = (string) $product->reorder_level !== (string) $request->reorder_level;
            $product->update($attributes);
            $result = $product;
            if ($reorderLevelChanged) {
                StockLevel::query()
                    ->where('product_id', $result->id)
                    ->where('location_type', 'branch')
                    ->whereNull('min_quantity')
                    ->get()
                    ->each(fn (StockLevel $level) => app(StockService::class)->syncAlertForLevel($level));
            }
        } else {
            $result = Product::create($attributes);
        }

        if (! $result->barcode) {
            $result->update(['barcode' => Product::internalBarcodeForId($result->id)]);
        }

        // Remove old image if replaced
        if ($oldImage && $oldImage !== $result->image) {
            $oldPath = public_path('uploads/product/' . $oldImage);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        return $result;
    }

    public function barcode(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'size' => ['nullable', 'in:50x30,40x25,a4'],
        ]);

        return view('admin.product.barcode', [
            'labels' => $this->barcodeLabels(collect([$product]), (int) ($validated['quantity'] ?? 1)),
            'sizes' => $this->barcodeSizes(),
            'selectedSize' => $validated['size'] ?? '50x30',
        ]);
    }

    public function bulkBarcode(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'quantities' => ['array'],
            'quantities.*' => ['integer', 'min:1', 'max:200'],
            'size' => ['nullable', 'in:50x30,40x25,a4'],
        ]);
        $products = Product::whereIn('id', $validated['product_ids'])->get();
        $labels = collect();
        foreach ($products as $product) {
            $quantity = (int) ($validated['quantities'][$product->id] ?? 1);
            $labels = $labels->concat($this->barcodeLabels(collect([$product]), $quantity));
        }

        return view('admin.product.barcode', [
            'labels' => $labels,
            'sizes' => $this->barcodeSizes(),
            'selectedSize' => $validated['size'] ?? '50x30',
        ]);
    }

    private function barcodeLabels($products, int $quantity)
    {
        $generator = new BarcodeGeneratorSVG();
        return $products->flatMap(fn (Product $product) => collect(range(1, $quantity))->map(fn () => [
            'product' => $product,
            'svg' => $generator->getBarcode($product->barcode, BarcodeGenerator::TYPE_EAN_13),
        ]));
    }

    private function barcodeSizes(): array
    {
        return ['50x30' => '50 x 30 mm', '40x25' => '40 x 25 mm', 'a4' => 'A4 sheet (3 x 8 grid)'];
    }
}
