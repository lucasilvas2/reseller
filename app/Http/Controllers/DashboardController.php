<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected ProductVariant $productVariant;
    protected StockMovement $stockMovement;
    protected Product $product;

    public function __construct(ProductVariant $productVariant, StockMovement $stockMovement, Product $product)
    {
        $this->productVariant = $productVariant;
        $this->stockMovement = $stockMovement;
        $this->product = $product;
    }

    public function index(): Response
    {
        $user = Auth::user();
        if (!$user->hasRole('reseller')) {
            return $this->buildGuestDashboard();
        }

        if (!$user->store_id) {
            return $this->buildResellerWithoutStoreDashboard();
        }

        $storeId = $user->store_id;
        $metrics = $this->calculateDashboardMetrics($storeId);
        $recentMovements = $this->getRecentMovements($storeId);
        $trendData = $this->getTrendData($storeId);
        $inventory = $this->getInventoryData($storeId);
        $stockDistribution = $this->getStockDistribution($inventory);
        $topProducts = $this->getTopProducts($inventory);

        return Inertia::render('Dashboard', array_merge($metrics, [
            'recentMovements' => $recentMovements,
            'trendData' => $trendData,
            'stockDistribution' => $stockDistribution,
            'topProducts' => $topProducts,
            'isReseller' => true,
        ]));
    }

    /**
     * Build dashboard for non-dealer users
     */
    private function buildGuestDashboard(): Response
    {
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
            'isReseller' => false,
        ]);
    }

    /**
     * Build dashboard for reseller users without store assigned
     */
    private function buildResellerWithoutStoreDashboard(): Response
    {
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
            'isReseller' => true,
            'needsStoreAssignment' => true,
        ]);
    }

    /**
     * Calculate main dashboard metrics
     */
    private function calculateDashboardMetrics(int $storeId): array
    {
        $totalProducts = $this->product->where('store_id', $storeId)->count();
        $totalMovements = $this->stockMovement->where('store_id', $storeId)->count();

        $inventory = $this->getInventoryData($storeId);
        $totalStock = $inventory->sum('current_stock');

        $lowStockProducts = $inventory->filter(function ($item) {
            return $item['current_stock'] > 0 && $item['current_stock'] <= 10;
        })->values();

        $outOfStockProducts = $inventory->filter(function ($item) {
            return $item['current_stock'] <= 0;
        })->values();

        // Nível de estoque geral
        $maxCapacity = 10000;

        return [
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockProducts->count(),
            'outOfStockCount' => $outOfStockProducts->count(),
            'totalMovements' => $totalMovements,
            'currentStockLevel' => $totalStock,
            'maxStockCapacity' => $maxCapacity,
        ];
    }

    /**
     * Get recent stock movements
     */
    private function getRecentMovements(int $storeId): \Illuminate\Support\Collection
    {
        return $this->stockMovement->where('store_id', $storeId)
            ->with(['productVariant.product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'product_name' => $movement->productVariant->product->name ?? 'N/A',
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'created_at' => $movement->created_at,
                ];
            });
    }

    /**
     * Get trend data for the last 30 days
     */
    private function getTrendData(int $storeId): \Illuminate\Support\Collection
    {
        return $this->stockMovement->where('store_id', $storeId)
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
    }

    /**
     * Get inventory data with stock calculations
     */
    private function getInventoryData(int $storeId): \Illuminate\Support\Collection
    {
        return $this->productVariant->where('store_id', $storeId)
            ->with(['product', 'stockMovements'])
            ->get()
            ->map(function ($productVariant) {
                $totalIn = $productVariant->stockMovements->where('type', 'in')->sum('quantity');
                $totalOut = $productVariant->stockMovements->where('type', 'out')->sum('quantity');
                $currentStock = $totalIn - $totalOut;

                return [
                    'id' => $productVariant->id,
                    'product_name' => $productVariant->product->name ?? 'N/A',
                    'sku' => $productVariant->sku,
                    'current_stock' => $currentStock,
                    'cost_price' => $productVariant->cost_price,
                    'sale_price' => $productVariant->sale_price,
                    'stock_value' => $currentStock * $productVariant->cost_price,
                    'category' => $productVariant->product->category ?? 'Uncategorized',
                ];
            });
    }

    /**
     * Get stock distribution by category
     */
    private function getStockDistribution(\Illuminate\Support\Collection $inventory): \Illuminate\Support\Collection
    {
        return $inventory->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'name' => $category,
                    'value' => $items->sum('current_stock'),
                ];
            })
            ->values();
    }

    /**
     * Get top products by stock quantity
     */
    private function getTopProducts(\Illuminate\Support\Collection $inventory): \Illuminate\Support\Collection
    {
        return $inventory->sortByDesc('current_stock')
            ->take(10)
            ->map(function ($item) {
                return [
                    'name' => $item['product_name'],
                    'value' => $item['current_stock'],
                ];
            })
            ->values();
    }
}
