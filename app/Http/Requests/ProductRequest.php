<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Product;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product') ?? $this->route('id');
        $productId = is_object($product) ? $product->getKey() : $product;

        return [
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit' => ['required', 'string', Rule::in(array_keys(config('inventory.units')))],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'track_stock' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product') ?? $this->route('id');
        $productId = is_object($product) ? $product->getKey() : $product;
        $slug = trim((string) $this->input('slug'));

        if ($slug === '') {
            $existingSlug = is_object($product) ? $product->slug : null;
            $slug = $existingSlug ?: Str::slug((string) $this->input('name'));
        }

        $base = $slug ?: 'product';
        $candidate = $base;
        $suffix = 2;
        while (Product::query()
            ->where('slug', $candidate)
            ->when($productId, fn ($query) => $query->where('id', '!=', $productId))
            ->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        $this->merge(['slug' => $candidate]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // SKU must be uppercase and trimmed
            $sku = strtoupper(trim($this->input('sku', '')));
            if ($sku !== $this->input('sku')) {
                $this->merge(['sku' => $sku]);
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'barcode' => 'barcode',
            'unit' => 'unit of measure',
            'cost_price' => 'cost price',
            'selling_price' => 'selling price',
            'reorder_level' => 'reorder level',
            'track_stock' => 'track stock',
            'image' => 'image',
        ];
    }
}
