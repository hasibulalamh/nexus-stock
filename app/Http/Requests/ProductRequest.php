<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        return [
            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:products,sku',
                Rule::when($product, ['ignore' => $product]),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,barcode',
                Rule::when($product && $this->filled('barcode'), ['ignore' => $product]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit' => ['required', 'string', Rule::in(array_keys(config('inventory.units')))],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'track_stock' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) use ($product) {
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
