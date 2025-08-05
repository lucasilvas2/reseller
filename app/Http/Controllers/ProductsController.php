<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductsController extends Controller
{
    protected Products $productsModel;
    protected Brands $brandsModel;
    public function __construct()
    {
        $this->productsModel = new Products();
    }

    public function index(Request $request): \Inertia\Response
    {
        $query = $this->productsModel->where('store_id', Auth::user()->store_id)
            ->with('brands');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brands', function($brandQuery) use ($search) {
                      $brandQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply name filter
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // Apply brand filter
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Apply sorting
        $sortKey = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if ($sortKey === 'brand_name') {
            $query->join('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $sortOrder)
                  ->select('products.*');
        } else {
            $query->orderBy($sortKey, $sortOrder);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $products = $query->paginate($perPage);

        // Transform data for frontend
        $transformedData = collect($products->items())->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'brand_name' => $product->brands->name ?? 'N/A',
                'brand_id' => $product->brand_id,
                'created_at' => $product->created_at,
                'created_at_formatted' => $product->created_at->format('M d, Y H:i'),
            ];
        });

        // Get brands for filter dropdown
        $brands = Brands::where('store_id', Auth::user()->store_id)->get();

        return inertia('App/Products/Index', [
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
                'links' => [],
            ],
            'filters' => $request->only(['search', 'name', 'brand_id', 'date_from', 'date_to', 'sort', 'order']),
            'brands' => $brands
        ]);
    }

    public function create(): \Inertia\Response
    {
        $brands = Brands::where('store_id', Auth::user()->store_id)->get();
        return inertia('App/Products/Create', compact('brands'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $request->merge([
            'store_id' => Auth::user()->store_id,
        ]);

        $product = $this->productsModel->create($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 's3');
            $product->update(['image_url' => $path]);
        }

        return redirect()->route('products.index');
    }

    public function edit(int $id): \Inertia\Response
    {
        $product = $this->productsModel->findOrFail($id);
        $brands = Brands::where('store_id', Auth::user()->store_id)->get();
        return inertia('App/Products/Edit', compact('product', 'brands'));
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $product = $this->productsModel->findOrFail($id);
        $product->update($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 's3');
            $product->update(['image_url' => $path]);
        }

        return redirect()->route('products.index');
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $product = $this->productsModel
            ->where('store_id', Auth::user()->store_id)
            ->findOrFail($id);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * API endpoint for product search (used by ProductSelector component)
     */
    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Products::where('store_id', Auth::user()->store_id)
            ->with(['productSkus' => function($query) {
                $query->where('store_id', Auth::user()->store_id);
            }])
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->flatMap(function ($product) {
                return $product->productSkus->map(function ($sku) use ($product) {
                    return [
                        'id' => $sku->id,
                        'name' => $product->name,
                        'sku' => $sku->sku,
                        'barcode' => $sku->barcode,
                        'price' => $sku->sale_price,
                        'stock_quantity' => $sku->getCurrentStock(),
                        'product_id' => $product->id
                    ];
                });
            });

        return response()->json(['data' => $products]);
    }

    /**
     * AJAX endpoint for product search (internal use by components)
     */
    public function ajaxSearch(Request $request)
    {
        return $this->apiSearch($request);
    }
}
