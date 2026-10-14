<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $category = $this->route('category') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:categories,slug',
                Rule::when($category, ['ignore' => $category]),
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) use ($category) {
            // Check max depth (2 levels: root -> child, no grandchildren)
            $parentId = $this->input('parent_id');
            if ($parentId) {
                $parent = Category::find($parentId);
                if ($parent && $parent->parent_id !== null) {
                    $validator->errors()->add(
                        'parent_id',
                        'Cannot assign a grandchild as parent. Maximum depth is 2 levels.'
                    );
                }
            }

            // Prevent circular reference (set itself as parent)
            if ($category) {
                $categoryId = $category instanceof Category ? $category->id : $category;
                if ($this->input('parent_id') == $categoryId) {
                    $validator->errors()->add(
                        'parent_id',
                        'A category cannot be its own parent.'
                    );
                }
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
            'parent_id' => 'parent category',
        ];
    }
}
