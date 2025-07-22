<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementTypeEnum;
use App\Models\Products;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

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

    public function index(): Response
    {
        $movements = $this->stockMovement->where('dealership_id', auth()->user()->dealership_id)
            ->with('productSku.products')->get();
        return Inertia::render('App/Stocks/Movements/Index', compact('movements'));
    }

    public function create(): Response
    {
        $products = $this->products->where('dealership_id', auth()->user()->dealership_id)->get();
        return Inertia::render('App/Stocks/Movements/Create', compact('products'));
    }

    public function store(): RedirectResponse
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

        return redirect()->route('stocks.movements.index');
    }

    public function edit($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', auth()->user()->dealership_id)
            ->with('productSku.products')
            ->firstOrFail();

        $products = $this->products->where('dealership_id', auth()->user()->dealership_id)->get();

        return Inertia::render('App/Stocks/Movements/Edit', compact('movement', 'products'));
    }

    public function show($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', auth()->user()->dealership_id)
            ->with(['productSku.products', 'user'])
            ->firstOrFail();

        return Inertia::render('App/Stocks/Movements/Show', compact('movement'));
    }

    public function update($id): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', auth()->user()->dealership_id)
            ->with('productSku')
            ->firstOrFail();

        request()->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
        ]);

        $movement->productSku->update([
            'product_id' => request('product_id'),
            'sku' => request('sku'),
            'barcode' => request('barcode'),
            'cost_price' => request('cost_price'),
            'sale_price' => request('sale_price'),
        ]);

        $movement->update([
            'quantity' => request('quantity'),
        ]);

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', auth()->user()->dealership_id)
            ->with('productSku')
            ->firstOrFail();

        $movement->productSku->delete();
        $movement->delete();

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement deleted successfully!');
    }

    public function inventory(): Response
    {
        $inventory = $this->productsSku->where('dealership_id', auth()->user()->dealership_id)
            ->with(['products', 'stockMovements'])
            ->get()
            ->map(function ($productSku) {
                // Calcular o estoque atual baseado nos movimentos
                $totalIn = $productSku->stockMovements->where('type', 'in')->sum('quantity');
                $totalOut = $productSku->stockMovements->where('type', 'out')->sum('quantity');
                $currentStock = $totalIn - $totalOut;

                return [
                    'id' => $productSku->id,
                    'product_name' => $productSku->products->name ?? 'N/A',
                    'sku' => $productSku->sku,
                    'barcode' => $productSku->barcode,
                    'cost_price' => $productSku->cost_price,
                    'sale_price' => $productSku->sale_price,
                    'current_stock' => $currentStock,
                    'total_movements_in' => $totalIn,
                    'total_movements_out' => $totalOut,
                    'stock_value' => $currentStock * $productSku->cost_price,
                    'potential_revenue' => $currentStock * $productSku->sale_price,
                    'last_movement' => $productSku->stockMovements->sortByDesc('created_at')->first()?->created_at,
                ];
            })
            ->sortByDesc('current_stock');

        return Inertia::render('App/Stocks/Inventory/Index', compact('inventory'));
    }

    public function dashboard(): Response
    {
        $dealershipId = auth()->user()->dealership_id;

        // Calcular sumário
        $totalProducts = $this->products->where('dealership_id', $dealershipId)->count();
        $totalSkus = $this->productsSku->where('dealership_id', $dealershipId)->count();

        // Buscar todos os SKUs com movimentos para calcular estatísticas
        $inventory = $this->productsSku->where('dealership_id', $dealershipId)
            ->with(['products', 'stockMovements'])
            ->get()
            ->map(function ($productSku) {
                $totalIn = $productSku->stockMovements->where('type', 'in')->sum('quantity');
                $totalOut = $productSku->stockMovements->where('type', 'out')->sum('quantity');
                $currentStock = $totalIn - $totalOut;

                return [
                    'id' => $productSku->id,
                    'product_name' => $productSku->products->name ?? 'N/A',
                    'sku' => $productSku->sku,
                    'current_stock' => $currentStock,
                    'cost_price' => $productSku->cost_price,
                    'sale_price' => $productSku->sale_price,
                    'stock_value' => $currentStock * $productSku->cost_price,
                ];
            });

        $totalStock = $inventory->sum('current_stock');
        $totalValue = $inventory->sum('stock_value');

        // Produtos com estoque baixo (≤ 10)
        $lowStockProducts = $inventory->filter(function ($item) {
            return $item['current_stock'] > 0 && $item['current_stock'] <= 10;
        })->values();

        // Movimentos recentes (últimos 5)
        $recentMovements = $this->stockMovement->where('dealership_id', $dealershipId)
            ->with(['productSku.products', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $summary = [
            'totalProducts' => $totalProducts,
            'totalSkus' => $totalSkus,
            'totalStock' => $totalStock,
            'totalValue' => number_format($totalValue, 2),
        ];

        return Inertia::render('App/Stocks/Dashboard', compact('summary', 'recentMovements', 'lowStockProducts'));
    }
}
