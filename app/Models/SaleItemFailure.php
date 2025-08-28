<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemFailure extends Model
{
    /** @use HasFactory<\Database\Factories\SaleItemFailureFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'order_item_id',
        'product_id',
        'failure_type',
        'error_message',
        'error_context',
        'attempted_at',
        'attempt_number',
        'is_retry',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
        'store_id'
    ];

    protected $casts = [
        'error_context' => 'array',
        'attempted_at' => 'datetime',
        'attempt_number' => 'integer',
        'is_retry' => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Relacionamentos
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Accessors & Mutators
    public function getFailureTypeLabelAttribute(): string
    {
        return match($this->failure_type) {
            'insufficient_stock' => 'Estoque Insuficiente',
            'payment_error' => 'Erro de Pagamento',
            'validation_error' => 'Erro de Validação',
            'processing_error' => 'Erro de Processamento',
            'network_error' => 'Erro de Rede',
            default => ucfirst(str_replace('_', ' ', $this->failure_type))
        };
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeByFailureType($query, $type)
    {
        return $query->where('failure_type', $type);
    }

    public function scopeBySale($query, $saleId)
    {
        return $query->where('sale_id', $saleId);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Business Logic Methods
    public function markAsResolved(string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    public function canRetry(): bool
    {
        return !$this->is_resolved && in_array($this->failure_type, [
            'insufficient_stock',
            'payment_error',
            'network_error'
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt_number');
        $this->update([
            'attempted_at' => now(),
            'is_retry' => true
        ]);
    }
}
