<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait ServerPaginationTrait
{
    /**
     * Apply server-side pagination with search, sorting and filtering
     *
     * @param Builder $query
     * @param Request $request
     * @param array $searchableFields - Fields that can be searched
     * @param array $sortableFields - Fields that can be sorted
     * @param string $defaultSort - Default sort field
     * @param string $defaultOrder - Default sort order (asc/desc)
     * @param int $defaultPerPage - Default items per page
     * @return LengthAwarePaginator
     */
    protected function applyServerPagination(
        Builder $query,
        Request $request,
        array $searchableFields = [],
        array $sortableFields = [],
        string $defaultSort = 'id',
        string $defaultOrder = 'desc',
        int $defaultPerPage = 25
    ): LengthAwarePaginator {

        // Get pagination parameters
        $perPage = $request->get('per_page', $defaultPerPage);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', $defaultSort);
        $sortOrder = $request->get('sort_order', $defaultOrder);

        // Validate sort parameters
        if (!in_array($sortBy, $sortableFields)) {
            $sortBy = $defaultSort;
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = $defaultOrder;
        }

        // Validate per page
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = $defaultPerPage;
        }

        // Apply search
        if ($search && !empty($searchableFields)) {
            $query->where(function($q) use ($searchableFields, $search) {
                foreach ($searchableFields as $field) {
                    if (str_contains($field, '.')) {
                        // Handle relationship fields (e.g., 'user.name')
                        $parts = explode('.', $field);
                        $relation = $parts[0];
                        $column = $parts[1];

                        $q->orWhereHas($relation, function($relationQuery) use ($column, $search) {
                            $relationQuery->where($column, 'like', "%{$search}%");
                        });
                    } else {
                        // Handle direct fields
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        // Apply sorting
        if (str_contains($sortBy, '.')) {
            // Handle relationship sorting (more complex, might need custom implementation)
            $query->orderBy($defaultSort, $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Get paginated results
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Format pagination response for Inertia
     *
     * @param LengthAwarePaginator $paginator
     * @param Request $request
     * @return array
     */
    protected function formatPaginationResponse(LengthAwarePaginator $paginator, Request $request): array
    {
        return [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => [
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by'),
                'sort_order' => $request->get('sort_order'),
                'per_page' => $request->get('per_page'),
                // Add any custom filters here
            ]
        ];
    }

    /**
     * Build search configuration for frontend
     *
     * @param array $searchableFields
     * @param array $sortableFields
     * @return array
     */
    protected function getSearchConfig(array $searchableFields, array $sortableFields): array
    {
        return [
            'searchable_fields' => $searchableFields,
            'sortable_fields' => $sortableFields,
        ];
    }
}
