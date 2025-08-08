<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $table = 'product_variants'; // Especificar explicitamente

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'cost_price',
        'sale_price',
        'store_id',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Métodos de domínio específicos para variantes
    public function hasStock(): bool
    {
        return $this->getCurrentStock() > 0;
    }

    public function getCurrentStock(): int
    {
        return $this->stockMovements()
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "in" THEN quantity WHEN type = "out" THEN -quantity END), 0) as total')
            ->value('total') ?? 0;
    }

    /**
     * 🚀 Método otimizado para alta demanda com cache
     */
    public function getCachedStock(): int
    {
        if (!config('sales.high_demand.cache.enabled', false)) {
            return $this->getCurrentStock();
        }

        $cacheKey = config('sales.high_demand.cache.prefix', 'stock:') . $this->id;
        $ttl = config('sales.high_demand.cache.ttl', 30);

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->getCurrentStock();
        });
    }

    /**
     * 🔄 Invalidar cache quando estoque muda
     */
    public function invalidateStockCache(): void
    {
        if (!config('sales.high_demand.cache.enabled', false)) {
            return;
        }

        $cacheKey = config('sales.high_demand.cache.prefix', 'stock:') . $this->id;
        Cache::forget($cacheKey);
    }

    public function getStockValue(): float
    {
        return $this->getCurrentStock() * $this->cost_price;
    }

    public function getFormattedCostPriceAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->cost_price, 2, ',', '.');
    }

    public function getFormattedSalePriceAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->sale_price, 2, ',', '.');
    }

    public function getMarginAttribute(): float
    {
        if ($this->cost_price > 0) {
            return (($this->sale_price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

    // Scopes
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('stockMovements', function($q) {
            $q->selectRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as stock')
              ->havingRaw('stock > 0');
        });
    }

    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->whereHas('stockMovements', function($q) use ($threshold) {
            $q->selectRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) as stock')
              ->havingRaw('stock <= ?', [$threshold])
              ->havingRaw('stock > 0');
        });
    }
}
