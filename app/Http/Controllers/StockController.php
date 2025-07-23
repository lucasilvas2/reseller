<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementTypeEnum;
use App\Models\Products;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function inventory(Request $request): Response
    {
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $stockStatus = $request->get('stock_status');
        $sortBy = $request->get('sort_by', 'current_stock');
        $sortOrder = $request->get('sort_order', 'desc');

        // Base query
        $query = $this->productsSku->where('dealership_id', auth()->user()->dealership_id)
            ->with(['products', 'stockMovements']);

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('products', function($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $productSkus = $query->paginate($perPage, ['*'], 'page', $page);
        $inventory = collect($productSkus->items())->map(function ($productSku) {
            // Calculate current stock based on movements
            $totalIn = $productSku->stockMovements->where('type', 'in')->sum('quantity');
            $totalOut = $productSku->stockMovements->where('type', 'out')->sum('quantity');
            $currentStock = $totalIn - $totalOut;

            return [
                'id' => $productSku->id,
                'product_id' => $productSku->product_id,
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
        });

        // Apply stock status filter after calculation (since it depends on calculated stock)
        if ($stockStatus) {
            $inventory = $inventory->filter(function ($item) use ($stockStatus) {
                switch ($stockStatus) {
                    case 'in-stock':
                        return $item['current_stock'] > 10;
                    case 'low-stock':
                        return $item['current_stock'] > 0 && $item['current_stock'] <= 10;
                    case 'out-of-stock':
                        return $item['current_stock'] <= 0;
                    default:
                        return true;
                }
            })->values();
        }

        // Apply sorting after calculation
        $inventory = $inventory->sortBy(function ($item) use ($sortBy) {
            switch ($sortBy) {
                case 'product_name':
                    return $item['product_name'];
                case 'stock_value':
                    return $item['stock_value'];
                case 'last_movement':
                    return $item['last_movement'] ? strtotime($item['last_movement']) : 0;
                default: // current_stock
                    return $item['current_stock'];
            }
        }, SORT_REGULAR, $sortOrder === 'desc');

        return Inertia::render('App/Stocks/Inventory/Index', [
            'inventory' => $inventory->values()->all(),
            'pagination' => [
                'current_page' => $productSkus->currentPage(),
                'last_page' => $productSkus->lastPage(),
                'per_page' => $productSkus->perPage(),
                'total' => $productSkus->total(),
                'from' => $productSkus->firstItem(),
                'to' => $productSkus->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'stock_status' => $stockStatus,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ]
        ]);
    }

    public function dashboard(): Response
    {
        $user = auth()->user();

        if (!$user->hasRole('dealer') || !$user->dealership_id) {
            return Inertia::render('Dashboard', [
                'totalProducts' => 0,
                'lowStockCount' => 0,
                'outOfStockCount' => 0,
                'totalMovements' => 0,
                'recentMovements' => [],
                'trendData' => [],
                'stockDistribution' => [],
                'topProducts' => [],
                'currentStockLevel' => 0,
                'maxStockCapacity' => 1000,
                'isDealer' => false,
            ]);
        }

        $dealershipId = $user->dealership_id;

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
                    'category' => $productSku->products->category ?? 'Uncategorized',
                ];
            });

        $totalStock = $inventory->sum('current_stock');
        $totalValue = $inventory->sum('stock_value');

        // Produtos com estoque baixo (≤ 10) e sem estoque
        $lowStockProducts = $inventory->filter(function ($item) {
            return $item['current_stock'] > 0 && $item['current_stock'] <= 10;
        })->values();

        $outOfStockProducts = $inventory->filter(function ($item) {
            return $item['current_stock'] <= 0;
        })->values();

        // Total de movimentos
        $totalMovements = $this->stockMovement->where('dealership_id', $dealershipId)->count();

        // Movimentos recentes (últimos 10 para exibir na tabela)
        $recentMovements = $this->stockMovement->where('dealership_id', $dealershipId)
            ->with(['productSku.products', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'product_name' => $movement->productSku->products->name ?? 'N/A',
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'created_at' => $movement->created_at,
                ];
            });

        // Dados para gráfico de tendência (últimos 30 dias)
        $trendData = $this->stockMovement->where('dealership_id', $dealershipId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, type, SUM(quantity) as total')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($movements, $date) {
                $in = $movements->where('type', 'in')->sum('total');
                $out = $movements->where('type', 'out')->sum('total');
                return [
                    'date' => $date,
                    'in' => $in,
                    'out' => $out,
                ];
            })
            ->values();

        // Dados para distribuição de estoque por categoria
        $stockDistribution = $inventory->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'name' => $category,
                    'value' => $items->sum('current_stock'),
                ];
            })
            ->values();

        // Top produtos por quantidade em estoque
        $topProducts = $inventory->sortByDesc('current_stock')
            ->take(10)
            ->map(function ($item) {
                return [
                    'name' => $item['product_name'],
                    'value' => $item['current_stock'],
                ];
            })
            ->values();

        // Nível de estoque geral (simulado - pode ser customizado)
        $maxCapacity = 10000; // Capacidade máxima estimada
        $currentStockLevel = $totalStock;

        return Inertia::render('Dashboard', [
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockProducts->count(),
            'outOfStockCount' => $outOfStockProducts->count(),
            'totalMovements' => $totalMovements,
            'recentMovements' => $recentMovements,
            'trendData' => $trendData,
            'stockDistribution' => $stockDistribution,
            'topProducts' => $topProducts,
            'currentStockLevel' => $currentStockLevel,
            'maxStockCapacity' => $maxCapacity,
            'isDealer' => true,
        ]);
    }
}
