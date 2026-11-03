<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\RecordsActivity;

class Product extends Model
{
    use SoftDeletes, RecordsActivity;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'category_id',
        'brand_id',
        'unit',
        'cost_price',
        'selling_price',
        'reorder_level',
        'track_stock',
        'tracking_type',
        'image',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'decimal:3',
        'track_stock' => 'boolean',
        'tracking_type' => 'string',
    ];

    /**
     * Get the category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function stockUnits()
    {
        return $this->hasMany(StockUnit::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function internalBarcodeForId(int $id): string
    {
        $body = '2'.str_pad((string) $id, 11, '0', STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($body) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $body.(($sum * 9) % 10);
    }

    /**
     * Get the image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && file_exists(public_path('uploads/product/' . $this->image))) {
            return asset('uploads/product/' . $this->image);
        }

        return asset('assets/img/product-placeholder.svg');
    }

}
