<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'type',
        'user_id',
        'store_id',
        'description',
        'order_item_id',
        'sale_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Invalidar cache de estoque quando movimento é criado/atualizado
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($stockMovement) {
            $stockMovement->invalidateProductStockCache();
        });

        static::updated(function ($stockMovement) {
            $stockMovement->invalidateProductStockCache();
        });

        static::deleted(function ($stockMovement) {
            $stockMovement->invalidateProductStockCache();
        });
    }

    /**
     * Invalidar cache do produto relacionado
     */
    private function invalidateProductStockCache(): void
    {
        if ($this->productSku) {
            $this->productSku->invalidateStockCache();
        }
    }
}
