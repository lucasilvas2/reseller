<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\StockMovementCollection;
use App\Http\Traits\ServerPaginationTrait;
use App\Repositories\StockMovementRepository;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    use ServerPaginationTrait;

    protected StockMovementRepository $stockMovementRepository;
    protected StockMovement $stockMovement;
    protected Product $product;

    public function __construct(
        StockMovementRepository $stockMovementRepository,
        StockMovement $stockMovement,
        Product $product,
    ) {
        $this->stockMovementRepository = $stockMovementRepository;
        $this->stockMovement = $stockMovement;
        $this->product = $product;
    }

    public function index(Request $request): Response
    {
        $query = $this->buildMovementsQuery($request);
        $paginatedMovements = $this->applyPagination($query, $request);
        $movements = $this->transformMovementsData($paginatedMovements);
        $response = $this->buildIndexResponse($paginatedMovements, $movements, $request);

        return Inertia::render('App/Stocks/Movements/Index', $response);
    }

    /**
     * Build base query with filters for movements
     */
    private function buildMovementsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $filters = [
            'type' => $request->get('type'),
            'product_id' => $request->get('product_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        return $this->stockMovementRepository->getFilteredQuery($filters);
    }

    /**
     * Apply pagination and sorting to movements query
     */
    private function applyPagination(\Illuminate\Database\Eloquent\Builder $query, Request $request)
    {
        $searchableFields = [
            'product.sku',
            'product.name',
            'type',
            'user.name'
        ];

        $sortableFields = [
            'id',
            'created_at',
            'type',
            'quantity',
            'product_name',
            'user_name'
        ];

        return $this->applyServerPagination(
            $query,
            $request,
            $searchableFields,
            $sortableFields,
            'created_at',
            'desc'
        );
    }

    /**
     * Transform movements data for frontend
     */
    private function transformMovementsData($paginatedMovements): \Illuminate\Support\Collection
    {
        return collect($paginatedMovements->items())->map(function ($movement) {
            return [
                'id' => $movement->id,
                'product_name' => $movement->product->name ?? 'N/A',
                'sku' => $movement->product->sku ?? 'N/A',
                'type' => $movement->type,
                'type_label' => ucfirst($movement->type),
                'quantity' => $movement->quantity,
                'user_name' => $movement->user->name ?? 'N/A',
                'created_at' => $movement->created_at,
                'created_at_formatted' => $movement->created_at->format('M d, Y H:i'),
                'cost_price' => $movement->product->cost_price ?? 0,
                'sale_price' => $movement->product->sale_price ?? 0,
                'total_value' => ($movement->product->cost_price ?? 0) * $movement->quantity,
                '_original' => $movement
            ];
        });
    }

    /**
     * Build complete response for index view
     */
    private function buildIndexResponse($paginatedMovements, \Illuminate\Support\Collection $movements, Request $request): array
    {
        $response = $this->formatPaginationResponse($paginatedMovements, $request);
        $response['data'] = $movements;

        // Add custom filters to response
        $response['filters'] = array_merge($response['filters'], [
            'type' => $request->get('type'),
            'product_id' => $request->get('product_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ]);

        // Add additional data
        $response['products'] = $this->getProductsForFilter();

        return $response;
    }

    /**
     * Get products list for filter dropdown
     */
    private function getProductsForFilter(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->product->where('store_id', Auth::user()->store_id)
            ->select('id', 'name')
            ->get();
    }

    public function create(): Response
    {
        $products = $this->product->where('store_id', Auth::user()->store_id)->get();
        return Inertia::render('App/Stocks/Movements/Create', compact('products'));
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $product = $this->product->where([
            'store_id' => Auth::user()->store_id,
            'id' => $request->get('product_id')
        ])->first();

        $this->stockMovement->create([
            'product_id' => $product->id,
            'cost_price' => $product->cost_price ?? 0,
            'quantity' => $request->quantity,
            'type' => $request->type,
            'user_id' => Auth::id(),
            'store_id' => Auth::user()->store_id,
        ]);

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement created successfully!');
    }

    public function show($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->with(['user'])
            ->firstOrFail();

        return Inertia::render('App/Stocks/Movements/Show', compact('movement'));
    }

    public function edit($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->with('product')
            ->firstOrFail();

        $products = $this->product->where('store_id', Auth::user()->store_id)->get();

        return Inertia::render('App/Stocks/Movements/Edit', compact('movement', 'products'));
    }

    public function update($id, UpdateStockMovementRequest $request): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->with('product')
            ->firstOrFail();

        $movement->update([
            'quantity' => $request->quantity,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->firstOrFail();

        $movement->delete();

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement deleted successfully!');
    }

    /**
     * Get movements data for API (JSON response)
     */
    public function apiIndex(Request $request): StockMovementCollection
    {
        $query = $this->buildMovementsQuery($request);
        $paginatedMovements = $this->applyPagination($query, $request);

        return new StockMovementCollection($paginatedMovements->items());
    }

    /**
     * Get single movement for API (JSON response)
     */
    public function apiShow($id): StockMovementResource
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->with(['product', 'user'])
            ->firstOrFail();

        return new StockMovementResource($movement);
    }

    /**
     * Create movement via API (JSON response)
     */
    public function apiStore(StoreStockMovementRequest $request): StockMovementResource
    {
        $product = $this->product->firstOrCreate([
            'id' => $request->id,
            'sku' => $request->sku,
            'store_id' => Auth::user()->store_id,
        ], [
            'barcode' => $request->barcode,
            'cost_price' => $request->cost_price,
            'sale_price' => $request->sale_price,
        ]);

        $movement = $this->stockMovement->create([
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'type' => $request->type,
            'description' => $request->description,
            'user_id' => Auth::id(),
            'store_id' => Auth::user()->store_id,
        ]);

        $movement->load(['product', 'user']);

        return new StockMovementResource($movement);
    }

    /**
     * Update movement via API (JSON response)
     */
    public function apiUpdate(UpdateStockMovementRequest $request, $id): StockMovementResource
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('store_id', Auth::user()->store_id)
            ->firstOrFail();

        $movement->update($request->validated());
        $movement->load(['product', 'user']);

        return new StockMovementResource($movement);
    }
}
