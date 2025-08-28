<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'store_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    // Relacionamentos
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get all failure records for this sale
     */
    public function failures(): HasMany
    {
        return $this->hasMany(SaleItemFailure::class, 'sale_id');
    }

    /**
     * Get stock movements related to this sale
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'sale_id');
    }

    // Scopes para filtros
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->total_amount, 2, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'canceled' => 'Cancelado',
            default => ucfirst($this->status)
        };
    }

    // Métodos utilitários
    public function calculateTotal(): float
    {
        return $this->orderItems()->sum(DB::raw('quantity * unit_price'));
    }

    public function getTotalItems(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function calculateProfitMargin(): float
    {
        $totalCost = $this->orderItems->sum(function($item) {
            return $item->quantity * $item->product->cost_price; // Agora via product direto
        });

        $totalProfit = $this->orderItems->sum(function($item) {
            return $item->quantity * ($item->product->sale_price - $item->product->cost_price);
        });

        return $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;
    }

    // ✅ ABORDAGEM HÍBRIDA: Status da venda + rastreamento detalhado de failures

    /**
     * Check if sale has any unresolved failures
     */
    public function hasUnresolvedFailures(): bool
    {
        return $this->failures()->unresolved()->exists();
    }

    /**
     * Get failed items via SaleItemFailure (híbrido)
     */
    public function getFailedItems()
    {
        return $this->failures()->unresolved()->with('orderItem')->get();
    }

    /**
     * Check if sale can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->hasUnresolvedFailures();
    }

    /**
     * Get summary of sale processing status
     */
    public function getProcessingSummary(): array
    {
        $failureCount = $this->failures()->unresolved()->count();
        $totalItems = $this->orderItems->count();

        return [
            'total_items' => $totalItems,
            'failed_items' => $failureCount,
            'success_items' => $totalItems - $failureCount,
            'success_rate' => $totalItems > 0 ? (($totalItems - $failureCount) / $totalItems) * 100 : 100,
            'has_failures' => $failureCount > 0
        ];
    }

    /**
     * Create failure record for this sale
     */
    public function createFailure(OrderItem $orderItem, string $type, string $message, array $context = []): SaleItemFailure
    {
        return $this->failures()->create([
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'failure_type' => $type,
            'error_message' => $message,
            'error_context' => $context,
            'attempted_at' => now(),
            'attempt_number' => 1,
            'store_id' => $this->store_id
        ]);
    }
}
