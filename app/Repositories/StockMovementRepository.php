<?php

namespace App\Repositories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class StockMovementRepository
{
    protected StockMovement $stockMovement;

    public function __construct(StockMovement $stockMovement)
    {
        $this->stockMovement = $stockMovement;
    }

    /**
     * Get base query for stock movements with store filter
     */
    public function getBaseQuery(): Builder
    {
        return $this->stockMovement->where('store_id', Auth::user()->store_id)
            ->with(['productVariant.product', 'user']);
    }

    /**
     * Find stock movement by ID within store
     */
    public function findById(int $id): ?StockMovement
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find stock movement by ID with relationships
     */
    public function findByIdWithRelations(int $id): ?StockMovement
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find stock movement by ID for store or fail
     */
    public function findByIdForStore(int $id): ?StockMovement
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find stock movement by ID or fail
     */
    public function findByIdOrFail(int $id): StockMovement
    {
        return $this->getBaseQuery()
            ->where('id', $id)
            ->firstOrFail();
    }

    /**
     * Get paginated stock movements with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->getBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get all stock movements with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = $this->getBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get filtered query builder for external use
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = $this->getBaseQuery();
        return $this->applyFilters($query, $filters);
    }

    /**
     * Apply filters to query
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by product SKU
        if (!empty($filters['product_variant_id'])) {
            $query->where('product_variant_id', $filters['product_variant_id']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Search in product name, SKU, or user name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('productVariant', function($productVariantQuery) use ($search) {
                    $productVariantQuery->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('products', function($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                })
                ->orWhereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Apply sorting
        if (!empty($filters['sort_by'])) {
            $sortBy = $filters['sort_by'];
            $sortOrder = $filters['sort_order'] ?? 'desc';

            switch ($sortBy) {
                case 'product_name':
                    $query->join('products_skus as ps', 'stock_movements.product_variant_id', '=', 'ps.id')
                          ->join('products as p', 'ps.product_id', '=', 'p.id')
                          ->orderBy('p.name', $sortOrder)
                          ->select('stock_movements.*');
                    break;
                case 'user_name':
                    $query->join('users as u', 'stock_movements.user_id', '=', 'u.id')
                          ->orderBy('u.name', $sortOrder)
                          ->select('stock_movements.*');
                    break;
                default:
                    $query->orderBy($sortBy, $sortOrder);
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Create new stock movement
     */
    public function create(array $data): StockMovement
    {
        $data['store_id'] = Auth::user()->store_id;
        $data['user_id'] = Auth::id();

        return $this->stockMovement->create($data);
    }

    /**
     * Update stock movement
     */
    public function update(int $id, array $data): StockMovement
    {
        $movement = $this->findByIdOrFail($id);
        $movement->update($data);

        return $movement->fresh(['productVariant.product', 'user']);
    }

    /**
     * Delete stock movement
     */
    public function delete(int $id): bool
    {
        $movement = $this->findByIdOrFail($id);
        return $movement->delete();
    }

    /**
     * Get movements by product SKU
     */
    public function getByproductVariant(int $productVariantId): Collection
    {
        return $this->getBaseQuery()
            ->where('product_variant_id', $productVariantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent movements
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->getBaseQuery()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get movements within date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->getBaseQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movement statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->getBaseQuery();
        $query = $this->applyFilters($query, $filters);

        $movements = $query->get();

        $totalIn = $movements->where('type', 'in')->sum('quantity');
        $totalOut = $movements->where('type', 'out')->sum('quantity');

        return [
            'total_movements' => $movements->count(),
            'total_in' => $movements->where('type', 'in')->count(),
            'total_out' => $movements->where('type', 'out')->count(),
            'quantity_in' => $totalIn,
            'quantity_out' => $totalOut,
            'net_quantity' => $totalIn - $totalOut,
        ];
    }

    /**
     * Get trend data for chart
     */
    public function getTrendData(int $days = 30): Collection
    {
        return $this->stockMovement
            ->where('store_id', Auth::user()->store_id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, type, SUM(quantity) as total')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type): Collection
    {
        return $this->getBaseQuery()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Bulk create movements
     */
    public function bulkCreate(array $movementsData): Collection
    {
        $movements = collect();

        foreach ($movementsData as $data) {
            $movements->push($this->create($data));
        }

        return $movements;
    }
}
