<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Products extends Model
{
    /** @use HasFactory<\Database\Factories\ProductsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_id',
        'description',
        'image_url',
        'store_id'
    ];

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    /**
     * Alias for brand relationship to maintain compatibility
     */
    public function brands(): BelongsTo
    {
        return $this->brand();
    }

    /**
     * Get the product SKUs for the product.
     */
    public function productSkus(): HasMany
    {
        return $this->hasMany(ProductsSku::class, 'product_id');
    }

    /**
     * Alias for productSkus relationship
     */
    public function skus(): HasMany
    {
        return $this->productSkus();
    }

    /**
     * Get the store that owns the product.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
