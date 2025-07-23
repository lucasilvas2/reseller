<?php

namespace App\Http\Controllers;

use App\Http\Traits\ServerPaginationTrait;
use App\Models\StockMovement;
use App\Models\Products;
use App\Models\ProductsSku;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    use ServerPaginationTrait;

    protected StockMovement $stockMovement;
    protected Products $products;
    protected ProductsSku $productsSku;

    public function __construct(StockMovement $stockMovement, Products $products, ProductsSku $productsSku)
    {
        $this->stockMovement = $stockMovement;
        $this->products = $products;
        $this->productsSku = $productsSku;
    }

    public function index(Request $request): Response
    {
        // Base query with relationships
        $query = $this->stockMovement->where('dealership_id', Auth::user()->dealership_id)
            ->with(['productSku.products', 'user']);

        // Apply additional filters specific to movements
        if ($request->get('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->get('product_sku_id')) {
            $query->where('product_sku_id', $request->get('product_sku_id'));
        }

        if ($request->get('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->get('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Define searchable and sortable fields
        $searchableFields = [
            'productSku.sku',
            'productSku.products.name',
            'type',
            'user.name'
        ];

        $sortableFields = [
            'id',
            'created_at',
            'type',
            'quantity',
            'product_name', // Custom field handled separately
            'user_name'     // Custom field handled separately
        ];

        // Apply server-side pagination
        $paginatedMovements = $this->applyServerPagination(
            $query,
            $request,
            $searchableFields,
            $sortableFields,
            'created_at',
            'desc'
        );

        // Transform data for frontend
        $movements = collect($paginatedMovements->items())->map(function ($movement) {
            return [
                'id' => $movement->id,
                'product_name' => $movement->productSku->products->name ?? 'N/A',
                'sku' => $movement->productSku->sku ?? 'N/A',
                'type' => $movement->type,
                'type_label' => ucfirst($movement->type),
                'quantity' => $movement->quantity,
                'user_name' => $movement->user->name ?? 'N/A',
                'created_at' => $movement->created_at,
                'created_at_formatted' => $movement->created_at->format('M d, Y H:i'),
                'cost_price' => $movement->productSku->cost_price ?? 0,
                'sale_price' => $movement->productSku->sale_price ?? 0,
                'total_value' => ($movement->productSku->cost_price ?? 0) * $movement->quantity,
                // Keep original data for actions
                '_original' => $movement
            ];
        });

        // Prepare response
        $response = $this->formatPaginationResponse($paginatedMovements, $request);
        $response['data'] = $movements;

        // Add custom filters to response
        $response['filters'] = array_merge($response['filters'], [
            'type' => $request->get('type'),
            'product_sku_id' => $request->get('product_sku_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ]);

        // Add additional data
        $response['products'] = $this->products->where('dealership_id', Auth::user()->dealership_id)
            ->select('id', 'name')
            ->get();

        $response['product_skus'] = $this->productsSku->where('dealership_id', Auth::user()->dealership_id)
            ->with('products:id,name')
            ->select('id', 'product_id', 'sku')
            ->get()
            ->map(function ($sku) {
                return [
                    'id' => $sku->id,
                    'sku' => $sku->sku,
                    'product_name' => $sku->products->name ?? 'N/A',
                    'display_name' => ($sku->products->name ?? 'N/A') . ' - ' . $sku->sku
                ];
            });

        return Inertia::render('App/Stocks/Movements/Index', $response);
    }

    public function create(): Response
    {
        $products = $this->products->where('dealership_id', Auth::user()->dealership_id)->get();
        return Inertia::render('App/Stocks/Movements/Create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:255',
            'type' => 'required|in:in,out',
        ]);

        // Find or create product SKU
        $productSku = $this->productsSku->firstOrCreate([
            'product_id' => $request->product_id,
            'sku' => $request->sku,
            'dealership_id' => Auth::user()->dealership_id,
        ], [
            'barcode' => $request->barcode,
            'cost_price' => $request->cost_price,
            'sale_price' => $request->sale_price,
        ]);

        // Create movement
        $this->stockMovement->create([
            'product_sku_id' => $productSku->id,
            'quantity' => $request->quantity,
            'type' => $request->type,
            'user_id' => Auth::id(),
            'dealership_id' => Auth::user()->dealership_id,
        ]);

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement created successfully!');
    }

    public function show($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', Auth::user()->dealership_id)
            ->with(['productSku.products', 'user'])
            ->firstOrFail();

        return Inertia::render('App/Stocks/Movements/Show', compact('movement'));
    }

    public function edit($id): Response
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', Auth::user()->dealership_id)
            ->with('productSku.products')
            ->firstOrFail();

        $products = $this->products->where('dealership_id', Auth::user()->dealership_id)->get();

        return Inertia::render('App/Stocks/Movements/Edit', compact('movement', 'products'));
    }

    public function update($id, Request $request): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', Auth::user()->dealership_id)
            ->with('productSku')
            ->firstOrFail();

        $request->validate([
            'quantity' => 'required|integer|min:0',
            'type' => 'required|in:in,out',
        ]);

        $movement->update([
            'quantity' => $request->quantity,
            'type' => $request->type,
        ]);

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement updated successfully!');
    }

    public function destroy($id): RedirectResponse
    {
        $movement = $this->stockMovement->where('id', $id)
            ->where('dealership_id', Auth::user()->dealership_id)
            ->firstOrFail();

        $movement->delete();

        return redirect()->route('stocks.movements.index')
            ->with('success', 'Movement deleted successfully!');
    }
}
