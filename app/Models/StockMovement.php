<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'product_sku_id',
        'quantity',
        'type',
        'user_id',
        'store_id',
        'description',
        'order_item_id',
        'sale_id',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Alias para compatibilidade (remover após refatoração completa)
    public function productSku()
    {
        return $this->productVariant();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * 🔄 Invalidar cache de estoque quando movimento é criado/atualizado
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
     * 🗑️ Invalidar cache do produto relacionado
     */
    private function invalidateProductStockCache(): void
    {
        if ($this->productSku) {
            $this->productSku->invalidateStockCache();
        }
    }
}
