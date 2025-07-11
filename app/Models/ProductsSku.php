<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsSku extends Model
{
    /** @use HasFactory<\Database\Factories\ProductsSkuFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'cost_price',
        'sale_price',
        'dealership_id',
    ];

    public function products()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
