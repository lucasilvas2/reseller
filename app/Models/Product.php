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
        'category',
        'image_url',
        'store_id',
        'sku',
        'barcode',
        'cost_price',
        'sale_price'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Get the store that owns the product.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Business Logic Methods

    /**
     * Get current stock for this product
     */
    public function getCurrentStock(): int
    {
        return $this->stockMovements()
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "in" THEN quantity WHEN type = "out" THEN -quantity END), 0) as total')
            ->value('total') ?? 0;
    }

    /**
     * Check if product has available stock
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->getCurrentStock() >= $quantity;
    }

    /**
     * Calculate profit margin percentage
     */
    public function getProfitMargin(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return (($this->sale_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Check if product is active and available for sale
     */
    public function isActive(): bool
    {
        return !empty($this->sku) && $this->sale_price > 0;
    }
}
