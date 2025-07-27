<?php

namespace App\Repositories;

use App\Models\ProductsSku;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryRepository
{
    protected ProductsSku $productsSku;
    protected StockMovement $stockMovement;

    public function __construct(ProductsSku $productsSku, StockMovement $stockMovement)
    {
        $this->productsSku = $productsSku;
        $this->stockMovement = $stockMovement;
    }

    /**
     * Get base query for inventory items
     */
    public function getBaseQuery(): Builder
    {
        return $this->productsSku->where('store_id', Auth::user()->store_id)
            ->with(['products', 'stockMovements']);
    }

    /**
     * Get paginated inventory with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->getBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get all inventory items with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->getBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Find inventory item by ID
     */
    public function findById(int $id): ?ProductsSku
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find inventory item by ID or fail
     */
    public function findByIdOrFail(int $id): ProductsSku
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Apply filters to inventory query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('products', function($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereHas('products', function($productQuery) use ($filters) {
                $productQuery->where('category', $filters['category']);
            });
        }

        // Price range filters
        if (!empty($filters['min_cost_price'])) {
            $query->where('cost_price', '>=', $filters['min_cost_price']);
        }

        if (!empty($filters['max_cost_price'])) {
            $query->where('cost_price', '<=', $filters['max_cost_price']);
        }

        if (!empty($filters['min_sale_price'])) {
            $query->where('sale_price', '>=', $filters['min_sale_price']);
        }

        if (!empty($filters['max_sale_price'])) {
            $query->where('sale_price', '<=', $filters['max_sale_price']);
        }

        // Apply sorting (basic sorting, advanced sorting will be done in collection)
        if (!empty($filters['sort_by']) && !in_array($filters['sort_by'], ['current_stock', 'stock_value', 'last_movement'])) {
            $sortBy = $filters['sort_by'];
            $sortOrder = $filters['sort_order'] ?? 'desc';

            switch ($sortBy) {
                case 'product_name':
                    $query->join('products as p', 'products_skus.product_id', '=', 'p.id')
                          ->orderBy('p.name', $sortOrder)
                          ->select('products_skus.*');
                    break;
                case 'sku':
                    $query->orderBy('sku', $sortOrder);
                    break;
                case 'cost_price':
                    $query->orderBy('cost_price', $sortOrder);
                    break;
                case 'sale_price':
                    $query->orderBy('sale_price', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }
        }

        return $query;
    }

    /**
     * Calculate current stock for a product SKU
     */
    public function calculateCurrentStock(int $productSkuId): int
    {
        $totalIn = $this->stockMovement
            ->where('product_sku_id', $productSkuId)
            ->where('type', 'in')
            ->sum('quantity');

        $totalOut = $this->stockMovement
            ->where('product_sku_id', $productSkuId)
            ->where('type', 'out')
            ->sum('quantity');

        return $totalIn - $totalOut;
    }

    /**
     * Get inventory with calculated stock levels
     */
    public function getInventoryWithStock(array $filters = []): SupportCollection
    {
        $inventory = $this->getAll($filters);

        return $inventory->map(function ($productSku) {
            return $this->buildInventoryItem($productSku);
        });
    }

    /**
     * Build inventory item with calculations
     */
    public function buildInventoryItem(ProductsSku $productSku): array
    {
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
            'stock_value' => $currentStock * ($productSku->cost_price ?? 0),
            'potential_revenue' => $currentStock * ($productSku->sale_price ?? 0),
            'profit_margin' => ($productSku->sale_price ?? 0) - ($productSku->cost_price ?? 0),
            'profit_margin_percentage' => $this->calculateProfitMarginPercentage($productSku),
            'last_movement' => $productSku->stockMovements->sortByDesc('created_at')->first()?->created_at,
            'status' => $this->getStockStatus($currentStock),
            'needs_restock' => $currentStock <= 10,
            'category' => $productSku->products->category ?? 'Uncategorized',
        ];
    }

    /**
     * Calculate profit margin percentage
     */
    private function calculateProfitMarginPercentage(ProductsSku $productSku): float
    {
        if (($productSku->cost_price ?? 0) <= 0) {
            return 0;
        }

        $margin = ($productSku->sale_price ?? 0) - ($productSku->cost_price ?? 0);
        return ($margin / $productSku->cost_price) * 100;
    }

    /**
     * Get stock status
     */
    private function getStockStatus(int $currentStock): string
    {
        if ($currentStock <= 0) {
            return 'out-of-stock';
        } elseif ($currentStock <= 10) {
            return 'low-stock';
        } else {
            return 'in-stock';
        }
    }

    /**
     * Get items by stock status
     */
    public function getByStockStatus(string $status): Collection
    {
        $inventory = $this->getInventoryWithStock();

        return $inventory->filter(function ($item) use ($status) {
            switch ($status) {
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

    /**
     * Get inventory statistics
     */
    public function getStatistics(): array
    {
        $inventory = $this->getInventoryWithStock();

        $totalItems = $inventory->count();
        $totalStock = $inventory->sum('current_stock');
        $totalValue = $inventory->sum('stock_value');
        $totalPotentialRevenue = $inventory->sum('potential_revenue');

        $inStock = $inventory->where('status', 'in-stock')->count();
        $lowStock = $inventory->where('status', 'low-stock')->count();
        $outOfStock = $inventory->where('status', 'out-of-stock')->count();

        $averageProfitMargin = $inventory->where('cost_price', '>', 0)->avg('profit_margin_percentage');

        return [
            'total_items' => $totalItems,
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'total_potential_revenue' => $totalPotentialRevenue,
            'total_potential_profit' => $totalPotentialRevenue - $totalValue,
            'in_stock_count' => $inStock,
            'low_stock_count' => $lowStock,
            'out_of_stock_count' => $outOfStock,
            'average_profit_margin' => round($averageProfitMargin ?? 0, 2),
            'items_needing_restock' => $lowStock + $outOfStock,
        ];
    }

    /**
     * Get category distribution
     */
    public function getCategoryDistribution(): Collection
    {
        return $this->getBaseQuery()
            ->join('products', 'products_skus.product_id', '=', 'products.id')
            ->select('products.category', DB::raw('COUNT(*) as count'))
            ->groupBy('products.category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category ?? 'Uncategorized' => $item->count];
            });
    }

    /**
     * Get top products by stock value
     */
    public function getTopByStockValue(int $limit = 10): Collection
    {
        $inventory = $this->getInventoryWithStock();

        return $inventory->sortByDesc('stock_value')
            ->take($limit)
            ->values();
    }

    /**
     * Get top products by quantity
     */
    public function getTopByQuantity(int $limit = 10): Collection
    {
        $inventory = $this->getInventoryWithStock();

        return $inventory->sortByDesc('current_stock')
            ->take($limit)
            ->values();
    }

    /**
     * Get items that need restocking
     */
    public function getItemsNeedingRestock(): Collection
    {
        return $this->getByStockStatus('low-stock')
            ->merge($this->getByStockStatus('out-of-stock'))
            ->sortBy('current_stock')
            ->values();
    }

    /**
     * Search inventory items
     */
    public function search(string $term): SupportCollection
    {
        return $this->getInventoryWithStock(['search' => $term]);
    }

    /**
     * Get items by product category
     */
    public function getByCategory(string $category): SupportCollection
    {
        return $this->getInventoryWithStock(['category' => $category]);
    }
}
