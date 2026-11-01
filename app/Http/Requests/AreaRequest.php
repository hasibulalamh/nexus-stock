<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id' => ['required', 'exists:cities,id'],
            'upazila_id' => ['nullable', 'exists:upazilas,id'],
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'in:area,section,locality'],
            'osm_id' => ['nullable', 'integer', 'unique:areas,osm_id,'.$this->route('area')?->id],
            'osm_place' => ['nullable', 'string', 'max:30'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'thana_hint' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:120'],
            'verified' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
