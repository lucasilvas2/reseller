<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
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
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Alias for brand relationship to maintain compatibility
     */
    public function brands(): BelongsTo
    {
        return $this->brand();
    }

    /**
     * Get the product variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Alias for variants relationship (backward compatibility)
     */
    public function productSkus(): HasMany
    {
        return $this->variants();
    }

    /**
     * Alias for variants relationship
     */
    public function skus(): HasMany
    {
        return $this->variants();
    }

    /**
     * Get the store that owns the product.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
