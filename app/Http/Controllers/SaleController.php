<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Client;
use App\Models\Products;
use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\ProductsSku;
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
    protected Products $products;
    protected ProductsSku $productsSku;
    protected StockMovement $stockMovement;
    protected SaleProcessor $saleProcessor;

    public function __construct(
        Sale $sale,
        Products $products,
        Client $client,
        ProductsSku $productsSku,
        StockMovement $stockMovement,
        SaleProcessor $saleProcessor
    ) {
        $this->sale = $sale;
        $this->products = $products;
        $this->client = $client;
        $this->productsSku = $productsSku;
        $this->stockMovement = $stockMovement;
        $this->saleProcessor = $saleProcessor;
    }

    public function index(Request $request)
    {
        $query = $this->sale->where('store_id', Auth::user()->store_id)
            ->with(['client', 'orderItems.productSku.products']);

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
                        'id' => $orderItem->productSku->products->id,
                        'name' => $orderItem->productSku->products->name,
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
        $products = $this->products->where('store_id', $storeId)
            ->with(['productSkus' => function($query) use ($storeId) {
                $query->where('store_id', $storeId)
                      ->with('stockMovements');
            }])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category ?? 'Sem categoria',
                    'brand_name' => $product->brand->name ?? 'Sem marca',
                    'skus' => $product->productSkus->map(function ($sku) {
                        $currentStock = $sku->getCurrentStock();
                        return [
                            'id' => $sku->id,
                            'sku' => $sku->sku,
                            'barcode' => $sku->barcode,
                            'cost_price' => $sku->cost_price,
                            'sale_price' => $sku->sale_price,
                            'current_stock' => $currentStock,
                            'formatted_cost_price' => $sku->formatted_cost_price,
                            'formatted_sale_price' => $sku->formatted_sale_price,
                            'margin' => $sku->margin,
                            'is_available' => $currentStock > 0,
                        ];
                    })
                ];
            })
            ->filter(function ($product) {
                // Filtrar produtos que têm pelo menos um SKU disponível
                return $product['skus']->where('is_available', true)->count() > 0;
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
                // Criar a venda
                $sale = $this->sale->create([
                    'user_id' => Auth::user()->id,
                    'client_id' => $validated['client_id'],
                    'store_id' => $validated['store_id'],
                    'status' => 'pending', // Status inicial sempre pending
                    'total_amount' => $validated['total_amount'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Criar os itens da venda
                foreach ($validated['items'] as $item) {
                    OrderItem::create([
                        'sale_id' => $sale->id,
                        'product_sku_id' => $item['product_sku_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'status' => 'pending', // Status inicial sempre pending
                    ]);
                }

                // Processar a venda usando o SaleProcessor
                $this->saleProcessor->process($sale);

                // Retornar resposta de sucesso
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
        // Verificar se a venda pertence à loja do usuário
        if ($sale->store_id !== Auth::user()->store_id) {
            abort(403, 'Acesso negado.');
        }

        // Verificar se a venda pode ser reprocessada
        if (!$sale->canRetry()) {
            return back()->withErrors(['error' => 'Esta venda não pode ser reprocessada.']);
        }

        try {
            // Reprocessar usando o SaleProcessor
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
        // Verificar se a venda pertence à loja do usuário
        if ($sale->store_id !== Auth::user()->store_id) {
            abort(403, 'Acesso negado.');
        }

        // Carregar relacionamentos necessários com todos os dados
        $sale->load([
            'client.user',
            'orderItems.productSku.products.brand',
            'store'
        ]);

        // Transformar dados para o frontend
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
                $productSku = $item->productSku;
                $product = $productSku ? $productSku->products : null;
                $brand = $product && $product->brand ? $product->brand : null;

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'status' => $item->status ?? 'pending',
                    'product_sku' => [
                        'id' => $productSku ? $productSku->id : null,
                        'sku' => $productSku ? ($productSku->sku ?? 'N/A') : 'N/A',
                        'barcode' => $productSku ? ($productSku->barcode ?? null) : null,
                        'cost_price' => $productSku ? ($productSku->cost_price ?? 0) : 0,
                        'sale_price' => $productSku ? ($productSku->sale_price ?? 0) : 0,
                        'products' => [
                            'id' => $product ? $product->id : null,
                            'name' => $product ? ($product->name ?? 'N/A') : 'N/A',
                            'description' => $product ? ($product->description ?? null) : null,
                            'brand' => [
                                'id' => $brand ? $brand->id : null,
                                'name' => $brand ? $brand->name : 'No Brand',
                            ]
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
                    return ($item->productSku->cost_price ?? 0) * $item->quantity;
                }),
                'total_profit' => $sale->orderItems->sum(function($item) {
                    $costPrice = $item->productSku->cost_price ?? 0;
                    $salePrice = $item->unit_price ?? 0;
                    return ($salePrice - $costPrice) * $item->quantity;
                }),
                'profit_margin' => $sale->calculateProfitMargin(),
                'average_item_price' => $sale->orderItems->avg('unit_price'),
                'highest_item_value' => $sale->orderItems->max('total_price'),
                'most_profitable_item_value' => $sale->orderItems->max(function($item) {
                    $costPrice = $item->productSku->cost_price ?? 0;
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
