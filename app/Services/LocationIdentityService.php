<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Location;

class LocationIdentityService
{
    public function findOrCreate(array $attributes): Location
    {
        $area = isset($attributes['area_id']) ? Area::find($attributes['area_id']) : null;
        if ($area) {
            $attributes['area'] = $area->display_name;
        }

        $query = Location::query();
        foreach ([
            'country_id', 'state_id', 'city_id', 'upazila_id', 'area_id',
            'area', 'address_line',
        ] as $column) {
            $value = $attributes[$column] ?? null;
            $value === null
                ? $query->whereNull($column)
                : $query->where($column, $value);
        }

        return $query->first() ?: Location::create($attributes);
    }
}
