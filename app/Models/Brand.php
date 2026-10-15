<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'website',
        'description',
        'status',
    ];

    /**
     * Get the products for this brand.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && file_exists(public_path('uploads/brand/' . $this->logo))) {
            return asset('uploads/brand/' . $this->logo);
        }

        return asset('assets/img/brand-placeholder.svg');
    }
}
