<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        $brand = $this->route('brand') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:brands,slug',
                Rule::when($brand, ['ignore' => $brand]),
            ],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'website' => ['nullable', 'url'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) use ($brand) {
            // Ensure slug is lowercase and unique
            $slug = strtolower(trim($this->input('slug', '')));
            if ($slug !== $this->input('slug')) {
                $validator->errors()->add('slug', 'Slug must be lowercase.');
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
            'logo' => 'logo image',
        ];
    }
}
