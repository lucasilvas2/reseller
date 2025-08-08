<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'user_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // relationships users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // relationships store
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // relationship sales
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Métodos utilitários
    public function getTotalSales(): int
    {
        return $this->sales()->count();
    }

    public function getTotalSpent(): float
    {
        return $this->sales()->sum('total_amount');
    }

    public function getFormattedTotalSpentAttribute(): string
    {
        return 'R$ ' . number_format($this->getTotalSpent(), 2, ',', '.');
    }

    // Scopes
    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}
