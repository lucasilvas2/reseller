<?php

namespace App\Http\Controllers;

use App\Models\ProductsSku;
use App\Models\StockMovement;
use Inertia\Inertia;

class StockController
{
    protected ProductsSku $productsSku;
    protected StockMovement $stockMovement;

    public function __construct(ProductsSku $productsSku, StockMovement $stockMovement)
    {
        $this->productsSku = $productsSku;
        $this->stockMovement = $stockMovement;
    }

    public function index(): \Inertia\Response
    {
        $stocks = $this->productsSku->with('product')->get();
        return Inertia::render('Stocks/Index', compact('stocks'));
    }

    public function products(): \Inertia\Response
    {
        $products = $this->productsSku->with('product')->get();
        return Inertia::render('Stocks/Products', compact('products'));
    }

    public function movements(): \Inertia\Response
    {
        $movements = $this->stockMovement->with(['productSku', 'user'])->get();
        return Inertia::render('Stocks/Movements', compact('movements'));
    }
}
