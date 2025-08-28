<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Store extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
    ];

    /**
     *  Get the users that belong to the store.
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the sales for the store.
     * @return HasMany
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the clients for the store.
     * @return HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the products for the store.
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Métodos utilitários
    public function getTotalSales(): int
    {
        return $this->sales()->count();
    }

    public function getTotalRevenue(): float
    {
        return $this->sales()->where('status', 'paid')->sum('total_amount');
    }

    public function getFormattedTotalRevenueAttribute(): string
    {
        return 'R$ ' . number_format($this->getTotalRevenue(), 2, ',', '.');
    }

    public function getTotalClients(): int
    {
        return $this->clients()->count();
    }

    public function getTotalProducts(): int
    {
        return $this->products()->count();
    }
}
