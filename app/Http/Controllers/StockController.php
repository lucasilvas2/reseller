<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementTypeEnum;
use App\Models\Products;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use Inertia\Inertia;

class StockController
{
    protected ProductsSku $productsSku;
    protected StockMovement $stockMovement;
    protected Products $products;

    public function __construct(ProductsSku $productsSku, StockMovement $stockMovement, Products $products)
    {
        $this->productsSku = $productsSku;
        $this->stockMovement = $stockMovement;
        $this->products = $products;
    }

    public function index(): \Inertia\Response
    {
        $movements = $this->stockMovement->where('dealership_id', auth()->user()->dealership_id)
            ->with('productSku.products')->get();
        return Inertia::render('App/Stocks/Products/Index', compact('movements'));
    }

    public function create(): \Inertia\Response
    {
        $products = $this->products->where('dealership_id', auth()->user()->dealership_id)->get();
        return Inertia::render('App/Stocks/Products/Create', compact('products'));
    }

    public function store(): \Illuminate\Http\RedirectResponse
    {
        request()->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
        ]);

        $productSku = $this->productsSku->create([
            'product_id' => request('product_id'),
            'sku' => request('sku'),
            'barcode' => request('barcode'),
            'cost_price' => request('cost_price'),
            'sale_price' => request('sale_price'),
            'dealership_id' => auth()->user()->dealership_id,
        ]);

        $this->stockMovement->create([
            'product_sku_id' => $productSku->id,
            'quantity' => request('quantity'),
            'type' => StockMovementTypeEnum::IN->value(),
            'user_id' => auth()->id(),
            'dealership_id' => auth()->user()->dealership_id,
        ]);

        return redirect()->route('stocks.products.index');
    }
}
