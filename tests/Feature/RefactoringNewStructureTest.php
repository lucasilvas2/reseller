<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Client;
use App\Models\SaleItemFailure;

class RefactoringNewStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Store $store;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base entities baseado na nova estrutura consolidada
        $this->store = Store::factory()->create();
        $this->user = User::factory()->create(['store_id' => $this->store->id]);

        // Product consolidado com campos de ProductVariant
        $this->product = Product::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'cost_price' => 10.00,
            'sale_price' => 15.00,
            'barcode' => '123456789'
        ]);
    }

    /**
     * Test sale creation with consolidated Product structure
     */
    public function test_sale_creation_with_consolidated_product_works()
    {
        $client = Client::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id
        ]);

        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        // OrderItem uses product_id (nova estrutura)
        $orderItem = OrderItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 15.00,
            'total_price' => 30.00
        ]);

        // Verify new relationships work
        $this->assertEquals($this->product->id, $orderItem->product_id);
        $this->assertEquals($this->product->sku, $orderItem->product->sku);
        $this->assertEquals($this->product->name, $orderItem->product->name);

        // Verify sale calculations work
        $this->assertEquals(30.00, $sale->calculateTotal());
        $this->assertEquals(2, $sale->getTotalItems());
    }

    /**
     * Test stock movement with consolidated Product
     */
    public function test_stock_movement_with_product_works()
    {
        // StockMovement with product_id
        $stockMovement = StockMovement::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'type' => 'in',
            'quantity' => 100,
            'description' => 'Initial stock'
        ]);

        // Verify new relationships work
        $this->assertEquals($this->product->id, $stockMovement->product_id);
        $this->assertEquals($this->product->sku, $stockMovement->product->sku);
        $this->assertEquals(100, $stockMovement->quantity);

        // Test consolidated stock calculation
        $this->assertEquals(100, $this->product->getCurrentStock());
        $this->assertTrue($this->product->hasStock());
        $this->assertTrue($this->product->hasStock(50));
        $this->assertFalse($this->product->hasStock(150));
    }

    /**
     * Test hybrid failure approach instead of granular status
     */
    public function test_sale_hybrid_failure_approach()
    {
        $client = Client::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id
        ]);

        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $orderItem = OrderItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 15.00,
            'total_price' => 30.00
        ]);

        // Create failure record using hybrid approach
        $failure = $sale->createFailure(
            $orderItem,
            'insufficient_stock',
            'Not enough stock available',
            ['required' => 2, 'available' => 1]
        );

        // Mark sale as failed to enable retry
        $sale->update(['status' => 'failed']);

        // Test hybrid approach
        $this->assertTrue($sale->hasUnresolvedFailures());
        $this->assertCount(1, $sale->getFailedItems());
        $this->assertTrue($sale->canRetry());

        $summary = $sale->getProcessingSummary();
        $this->assertEquals(1, $summary['total_items']);
        $this->assertEquals(1, $summary['failed_items']);
        $this->assertEquals(0, $summary['success_items']);
        $this->assertTrue($summary['has_failures']);

        // Test failure resolution
        $failure->markAsResolved('Stock replenished');
        $sale = $sale->fresh();

        $this->assertFalse($sale->hasUnresolvedFailures());
        $this->assertFalse($sale->canRetry());
    }

    /**
     * Test multi-store isolation with consolidated products
     */
    public function test_multi_store_isolation_works()
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create([
            'store_id' => $otherStore->id,
            'sku' => 'OTHER-STORE-SKU',
            'name' => 'Other Product'
        ]);

        // Verify isolation works
        $thisStoreProducts = Product::where('store_id', $this->store->id)->get();
        $otherStoreProducts = Product::where('store_id', $otherStore->id)->get();

        $this->assertCount(1, $thisStoreProducts);
        $this->assertCount(1, $otherStoreProducts);
        $this->assertEquals('TEST-SKU-001', $thisStoreProducts->first()->sku);
        $this->assertEquals('OTHER-STORE-SKU', $otherStoreProducts->first()->sku);
    }

    /**
     * Test profit margin calculation with consolidated product
     */
    public function test_sale_profit_margin_calculation()
    {
        $client = Client::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id
        ]);

        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'user_id' => $this->user->id
        ]);

        OrderItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 15.00,
            'total_price' => 30.00
        ]);

        // Test profit calculation (cost_price = 10.00, sale_price = 15.00)
        $profitMargin = $sale->calculateProfitMargin();

        // Expected: (15-10)/10 * 100 = 50% margin
        $this->assertEquals(50.0, $profitMargin);
    }
}
