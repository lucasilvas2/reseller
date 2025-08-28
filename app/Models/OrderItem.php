<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    // Relacionamentos
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get failure records for this order item
     */
    public function failures(): HasMany
    {
        return $this->hasMany(SaleItemFailure::class, 'order_item_id');
    }

    // Accessors & Mutators
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->unit_price, 2, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->total_price, 2, ',', '.');
    }

    // Métodos de cálculo
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function updateTotalPrice(): void
    {
        $this->setAttribute('total_price', $this->calculateTotalPrice());
    }

    // Boot method para auto-calcular total_price
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $orderItem->total_price = $orderItem->calculateTotalPrice();
        });
    }

    // Scopes simplificados (removidos os de status)
    public function scopeBySale($query, $saleId)
    {
        return $query->where('sale_id', $saleId);
    }
}
