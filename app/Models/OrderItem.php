<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_sku_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'error_message'
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

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductsSku::class, 'product_sku_id');
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

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'completed' => 'Concluído',
            'canceled' => 'Cancelado',
            default => ucfirst($this->status)
        };
    }

    // Métodos de cálculo
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function updateTotalPrice(): void
    {
        $this->total_price = $this->calculateTotalPrice();
    }

    // Boot method para auto-calcular total_price
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $orderItem->total_price = (string)$orderItem->calculateTotalPrice();
        });
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySale($query, $saleId)
    {
        return $query->where('sale_id', $saleId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed';
    }
}
