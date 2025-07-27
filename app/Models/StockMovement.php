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

    public function productSku()
    {
        return $this->belongsTo(ProductsSku::class, 'product_sku_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
