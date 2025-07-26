<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryFilterRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryItemCollection;
use App\Repositories\InventoryRepository;
use App\Models\Products;
use App\Models\ProductsSku;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    protected InventoryRepository $inventoryRepository;
    protected ProductsSku $productsSku;
    protected Products $products;

    public function __construct(
        InventoryRepository $inventoryRepository,
        ProductsSku $productsSku,
        Products $products
    ) {
        $this->inventoryRepository = $inventoryRepository;
        $this->productsSku = $productsSku;
        $this->products = $products;
    }

    public function index(InventoryFilterRequest $request): Response
    {
        $filters = $this->extractFilters($request);
        $inventory = $this->inventoryRepository->getInventoryWithStock($filters);
        $pagination = $this->buildPagination($request);

        return Inertia::render('App/Stocks/Inventory/Index', [
            'inventory' => $inventory->values()->all(),
            'pagination' => $pagination,
            'filters' => $filters
        ]);
    }

    /**
     * Extract and validate filters from request
     */
    private function extractFilters(InventoryFilterRequest $request): array
    {
        return [
            'per_page' => $request->get('per_page', 25),
            'page' => $request->get('page', 1),
            'search' => $request->get('search'),
            'stock_status' => $request->get('stock_status'),
            'sort_by' => $request->get('sort_by', 'current_stock'),
            'sort_order' => $request->get('sort_order', 'desc'),
        ];
    }

    /**
     * Build inventory data with stock calculations (Legacy method - now using repository)
     */
    private function buildInventoryData(array $filters): \Illuminate\Support\Collection
    {
        $query = $this->buildBaseQuery($filters);
        $productSkus = $query->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

        $inventory = collect($productSkus->items())->map(function ($productSku) {
            return $this->buildInventoryItem($productSku);
        });

        $inventory = $this->applyStockStatusFilter($inventory, $filters['stock_status']);
        $inventory = $this->applySorting($inventory, $filters['sort_by'], $filters['sort_order']);

        return $inventory;
    }

    /**
     * Build base query with search filters
     */
    private function buildBaseQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->productsSku->where('dealership_id', Auth::user()->dealership_id)
            ->with(['products', 'stockMovements']);

        // Search filter
        if ($filters['search']) {
            $query->where(function($q) use ($filters) {
                $q->whereHas('products', function($productQuery) use ($filters) {
                    $productQuery->where('name', 'like', "%{$filters['search']}%");
                })
                ->orWhere('sku', 'like', "%{$filters['search']}%")
                ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }

    /**
     * Build individual inventory item with calculations
     */
    private function buildInventoryItem($productSku): array
    {
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
    }

    /**
     * Apply stock status filter to inventory collection
     */
    private function applyStockStatusFilter(\Illuminate\Support\Collection $inventory, ?string $stockStatus): \Illuminate\Support\Collection
    {
        if (!$stockStatus) {
            return $inventory;
        }

        return $inventory->filter(function ($item) use ($stockStatus) {
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

    /**
     * Apply sorting to inventory collection
     */
    private function applySorting(\Illuminate\Support\Collection $inventory, string $sortBy, string $sortOrder): \Illuminate\Support\Collection
    {
        return $inventory->sortBy(function ($item) use ($sortBy) {
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
    }

    /**
     * Build pagination data from request
     */
    private function buildPagination(InventoryFilterRequest $request): array
    {
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);

        // Note: This is simplified - in real implementation you'd need the actual paginator
        // For now, we're returning basic structure. This should be improved when implementing Services.
        return [
            'current_page' => $page,
            'last_page' => 1, // Will be properly calculated with actual data
            'per_page' => $perPage,
            'total' => 0, // Will be properly calculated with actual data
            'from' => 1,
            'to' => $perPage,
        ];
    }

    // ========================================
    // API METHODS (JSON RESPONSES)
    // ========================================

    /**
     * Get inventory data for API (JSON response)
     */
    public function apiIndex(InventoryFilterRequest $request): InventoryItemCollection
    {
        $filters = $this->extractFilters($request);
        $query = $this->buildBaseQuery($filters);

        $productSkus = $query->get(); // Get all for collection processing

        return new InventoryItemCollection($productSkus);
    }

    /**
     * Get single inventory item for API (JSON response)
     */
    public function apiShow($id): InventoryItemResource
    {
        $productSku = $this->productsSku->where('id', $id)
            ->where('dealership_id', Auth::user()->dealership_id)
            ->with(['products', 'stockMovements'])
            ->firstOrFail();

        return new InventoryItemResource($productSku);
    }
}
