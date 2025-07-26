<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
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
        $user = Auth::user();

        if (!$user->hasRole('dealer') || !$user->dealership_id) {
            return $this->buildGuestDashboard();
        }

        $dealershipId = $user->dealership_id;
        $metrics = $this->calculateDashboardMetrics($dealershipId);
        $recentMovements = $this->getRecentMovements($dealershipId);
        $trendData = $this->getTrendData($dealershipId);
        $inventory = $this->getInventoryData($dealershipId);
        $stockDistribution = $this->getStockDistribution($inventory);
        $topProducts = $this->getTopProducts($inventory);

        return Inertia::render('Dashboard', array_merge($metrics, [
            'recentMovements' => $recentMovements,
            'trendData' => $trendData,
            'stockDistribution' => $stockDistribution,
            'topProducts' => $topProducts,
            'isDealer' => true,
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
            'isDealer' => false,
        ]);
    }

    /**
     * Calculate main dashboard metrics
     */
    private function calculateDashboardMetrics(int $dealershipId): array
    {
        $totalProducts = $this->products->where('dealership_id', $dealershipId)->count();
        $totalMovements = $this->stockMovement->where('dealership_id', $dealershipId)->count();

        $inventory = $this->getInventoryData($dealershipId);
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
    private function getRecentMovements(int $dealershipId): \Illuminate\Support\Collection
    {
        return $this->stockMovement->where('dealership_id', $dealershipId)
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
    }

    /**
     * Get trend data for the last 30 days
     */
    private function getTrendData(int $dealershipId): \Illuminate\Support\Collection
    {
        return $this->stockMovement->where('dealership_id', $dealershipId)
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
    private function getInventoryData(int $dealershipId): \Illuminate\Support\Collection
    {
        return $this->productsSku->where('dealership_id', $dealershipId)
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
