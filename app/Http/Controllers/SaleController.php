<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Contracts\SaleProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SaleController extends Controller
{
    protected Sale $sale;
    protected Client $client;
    protected Product $product;
    protected StockMovement $stockMovement;
    protected SaleProcessor $saleProcessor;

    public function __construct(
        Sale $sale,
        Product $product,
        Client $client,
        StockMovement $stockMovement,
        SaleProcessor $saleProcessor
    ) {
        $this->sale = $sale;
        $this->product = $product;
        $this->client = $client;
        $this->stockMovement = $stockMovement;
        $this->saleProcessor = $saleProcessor;
    }

    public function index(Request $request)
    {
        $query = $this->sale->where('store_id', Auth::user()->store_id)
            ->with(['client', 'orderItems.product.brand']);

        if($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Apply sorting
        $sortKey = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if ($sortKey === 'client_name') {
            $query->join('clients', 'sales.client_id', '=', 'clients.id')
                  ->orderBy('clients.name', $sortOrder);
        } else {
            $query->orderBy($sortKey, $sortOrder);
        }

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $sales = $query->paginate($perPage);

        $transformedData = collect($sales->items())->map(function ($sale) {
            return [
                'id' => $sale->id,
                'client_name' => $sale->client->user->name ?? 'N/A',
                'total' => $sale->total_amount,
                'created_at' => $sale->created_at->format('Y-m-d H:i:s'),
                'status' => $sale->status,
                'products' => $sale->orderItems->map(function ($orderItem) {
                    return [
                        'id' => $orderItem->product->id,
                        'name' => $orderItem->product->name,
                        'quantity' => $orderItem->quantity,
                        'price' => $orderItem->unit_price,
                    ];
                }),
            ];
        });


        return Inertia('App/Sales/Index', [
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
                'links' => [],
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to' => $request->date_to ?? '',
                'sort' => $sortKey,
                'order' => $sortOrder,
            ],
        ]);
    }

    public function create()
    {
        $storeId = Auth::user()->store_id;

        // Buscar clientes da loja
        $clients = $this->client->where('store_id', $storeId)
            ->with('user:id,name,email,phone_number')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->user->name ?? 'N/A',
                    'email' => $client->user->email ?? 'N/A',
                    'phone' => $client->user->phone_number ?? 'N/A',
                    'total_sales' => $client->getTotalSales(),
                    'total_spent' => $client->getTotalSpent(),
                ];
            });

        // Buscar produtos com SKUs e estoque
        $products = $this->product->where('store_id', $storeId)
            ->with(['stockMovements' => function($query) use ($storeId) {
                $query->where('store_id', $storeId);
            }])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category ?? 'Sem categoria',
                    'brand_name' => $product->brand->name ?? 'Sem marca',
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'cost_price' => $product->cost_price,
                    'sale_price' => $product->sale_price,
                    'formatted_cost_price' => $product->formatted_cost_price,
                    'formatted_sale_price' => $product->formatted_sale_price,
                    'margin' => $product->margin,
                    'current_stock' => $product->getCurrentStock(),
                    'is_available' => $product->getCurrentStock() > 0,
                    'image_url' => $product->image_url ?? null,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $product->updated_at->format('Y-m-d H:i:s')
                ];
            })
            ->filter(function ($product) {
                // Filtrar produtos que têm pelo menos um SKU disponível
                return $product['is_available'] == true;
            });

        return Inertia::render('App/Sales/Create', [
            'clients' => $clients,
            'products' => $products,
            'store_id' => $storeId,
        ]);
    }

    public function store(StoreSaleRequest $request)
    {
        // Obter dados validados e processados
        $validated = $request->validatedWithProcessing();

        try {
            return DB::transaction(function () use ($validated) {
                $sale = $this->sale->create([
                    'user_id' => Auth::user()->id,
                    'client_id' => $validated['client_id'],
                    'store_id' => $validated['store_id'],
                    'status' => 'pending', // Status inicial sempre pending
                    'total_amount' => $validated['total_amount'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($validated['items'] as $item) {
                    OrderItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'status' => 'pending', // Status inicial sempre pending
                    ]);
                }

                $this->saleProcessor->process($sale);

                return redirect()->route('sales.show', $sale->id)
                    ->with('success', 'Venda criada com sucesso!');
            });

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Reprocessar uma venda que falhou
     */
    public function retry(Sale $sale)
    {
        if ($sale->store_id !== Auth::user()->store_id) {
            abort(403, 'Acesso negado.');
        }

        if (!$sale->canRetry()) {
            return back()->withErrors(['error' => 'Esta venda não pode ser reprocessada.']);
        }

        try {
            $this->saleProcessor->retry($sale->id);

            return redirect()->route('sales.show', $sale->id)
                ->with('success', 'Venda reprocessada com sucesso!');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Erro ao reprocessar venda: ' . $e->getMessage()]);
        }
    }

    public function show(Sale $sale)
    {
        if ($sale->store_id !== Auth::user()->store_id) {
            abort(403, 'Acesso negado.');
        }

        $sale->load([
            'client.user',
            'orderItems.product.brand',
            'store'
        ]);

        $saleData = [
            'id' => $sale->id,
            'status' => $sale->status,
            'status_label' => $sale->status_label,
            'total_amount' => $sale->total_amount,
            'formatted_total' => $sale->formatted_total,
            'notes' => $sale->notes,
            'created_at' => $sale->created_at,
            'updated_at' => $sale->updated_at,
            'client' => [
                'id' => $sale->client->id,
                'name' => $sale->client->user->name ?? 'N/A',
                'email' => $sale->client->user->email ?? 'N/A',
                'phone' => $sale->client->user->phone_number ?? 'N/A',
                'document' => $sale->client->document ?? 'N/A',
            ],
            'store' => [
                'id' => $sale->store->id ?? null,
                'name' => $sale->store->name ?? 'N/A',
                'address' => $sale->store->address ?? 'N/A',
                'phone' => $sale->store->phone ?? 'N/A',
            ],
            'order_items' => $sale->orderItems->map(function ($item) {
                // Verificar se os relacionamentos existem antes de acessá-los
                $product = $item->product;
                $brand = $product && $product->brand ? $product->brand : null;

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'product' => [
                        'id' => $product ? $product->id : null,
                        'name' => $product ? ($product->name ?? 'N/A') : 'N/A',
                        'description' => $product ? ($product->description ?? null) : null,
                        'sku' => $product ? ($product->sku ?? 'N/A') : 'N/A',
                        'barcode' => $product ? ($product->barcode ?? null) : null,
                        'cost_price' => $product ? ($product->cost_price ?? 0) : 0,
                        'sale_price' => $product ? ($product->sale_price ?? 0) : 0,
                        'brand' => [
                            'id' => $brand ? $brand->id : null,
                            'name' => $brand ? $brand->name : 'No Brand',
                        ]
                    ]
                ];
            }),
            'totals' => [
                'items_count' => $sale->orderItems->count(),
                'total_quantity' => $sale->getTotalItems(),
                'subtotal' => $sale->total_amount,
                'total' => $sale->total_amount,
                'formatted_total' => $sale->formatted_total,
            ],
            'financial_summary' => [
                'total_cost' => $sale->orderItems->sum(function($item) {
                    return ($item->product->cost_price ?? 0) * $item->quantity;
                }),
                'total_profit' => $sale->orderItems->sum(function($item) {
                    $costPrice = $item->product->cost_price ?? 0;
                    $salePrice = $item->unit_price ?? 0;
                    return ($salePrice - $costPrice) * $item->quantity;
                }),
                'profit_margin' => $sale->calculateProfitMargin(),
                'average_item_price' => $sale->orderItems->avg('unit_price'),
                'highest_item_value' => $sale->orderItems->max('total_price'),
                'most_profitable_item_value' => $sale->orderItems->max(function($item) {
                    $costPrice = $item->product->cost_price ?? 0;
                    $salePrice = $item->unit_price ?? 0;
                    return ($salePrice - $costPrice) * $item->quantity;
                }),
            ]
        ];

        return Inertia::render('App/Sales/Show', [
            'sale' => $saleData,
            'canRetry' => $sale->canRetry(),
        ]);
    }

    /**
     * API endpoint for sale status tracking (used for queue polling)
     */
    public function apiStatus($saleId)
    {
        $sale = Sale::where('store_id', Auth::user()->store_id)
            ->findOrFail($saleId);

        return response()->json([
            'status' => $sale->status,
            'message' => $this->getStatusMessage($sale->status),
            'updated_at' => $sale->updated_at->toISOString()
        ]);
    }

    /**
     * AJAX endpoint for sale status tracking (internal use by components)
     */
    public function ajaxStatus($saleId)
    {
        return $this->apiStatus($saleId);
    }

    /**
     * Get human-readable status message
     */
    private function getStatusMessage($status)
    {
        return match($status) {
            'pending' => 'Venda na fila de processamento',
            'processing' => 'Processando venda e atualizando estoque',
            'completed' => 'Venda processada com sucesso',
            'failed' => 'Falha no processamento da venda',
            'cancelled' => 'Venda cancelada',
            default => 'Status desconhecido'
        };
    }
}
