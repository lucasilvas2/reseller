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
            return ($item->productSku->cost_price ?? 0) * $item->quantity;
        });

        $totalProfit = $this->orderItems->sum(function($item) {
            $costPrice = $item->productSku->cost_price ?? 0;
            $salePrice = $item->unit_price ?? 0;
            return ($salePrice - $costPrice) * $item->quantity;
        });

        return $totalCost > 0 ? ($totalProfit / $totalCost) * 100 : 0;
    }

    /**
     * Atualizar status da venda baseado no status dos OrderItems
     * ✅ Otimizado com single query
     */
    public function updateStatusFromItems(): string
    {
        // ✅ Single query para buscar estatísticas
        $statusCounts = $this->orderItems()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalItems = array_sum($statusCounts);
        $completedItems = $statusCounts['completed'] ?? 0;
        $failedItems = $statusCounts['failed'] ?? 0;
        $processingItems = $statusCounts['processing'] ?? 0;

        // ✅ Lógica mais clara e performática
        $newStatus = match(true) {
            $totalItems === 0 => 'pending',                 // Nenhum item ainda
            $failedItems > 0 => 'failed',                   // Qualquer falha = venda falha
            $completedItems === $totalItems => 'completed', // Todos completos = sucesso
            $processingItems > 0 => 'processing',           // Algum item processando
            default => 'pending'                            // Padrão = pendente
        };

        $this->update(['status' => $newStatus]);
        return $newStatus;
    }

    public function getFailedItems()
    {
        return $this->orderItems()->failed()->get();
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->orderItems()->failed()->exists();
    }

    public function getItemsStatusSummary(): array
    {
        // ✅ Single query otimizada em vez de 5 queries separadas
        $statusCounts = $this->orderItems()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($statusCounts),
            'pending' => $statusCounts['pending'] ?? 0,
            'processing' => $statusCounts['processing'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'failed' => $statusCounts['failed'] ?? 0,
        ];
    }
}
