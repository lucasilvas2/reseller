<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Sale;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_product_variant_can_be_created(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
        ]);

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertEquals($product->id, $variant->product_id);
        $this->assertEquals($store->id, $variant->store_id);
    }

    public function test_product_variant_belongs_to_product(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        $this->assertInstanceOf(Product::class, $variant->product);
        $this->assertEquals($product->name, $variant->product->name);
    }

    public function test_product_variant_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        $this->assertInstanceOf(Store::class, $variant->store);
        $this->assertEquals($store->id, $variant->store->id);
    }

    public function test_product_variant_has_many_stock_movements(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        StockMovement::factory()
            ->count(2)
            ->create([
                'product_variant_id' => $variant->id,
                'store_id' => $store->id,
                'user_id' => $user->id
            ]);

        $this->assertCount(2, $variant->stockMovements);
        $this->assertInstanceOf(StockMovement::class, $variant->stockMovements->first());
    }

    public function test_product_variant_has_many_order_items(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        // Create client directly with user_id
        $client = Client::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id
        ]);

        $sale = Sale::factory()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'client_id' => $client->id
        ]);

        OrderItem::factory()
            ->count(2)
            ->create([
                'product_variant_id' => $variant->id,
                'sale_id' => $sale->id
            ]);

        $this->assertCount(2, $variant->orderItems);
        $this->assertInstanceOf(OrderItem::class, $variant->orderItems->first());
    }

    public function test_product_variant_get_current_stock(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        // Create stock in movements
        StockMovement::factory()->create([
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 50
        ]);

        // Create stock out movements
        StockMovement::factory()->create([
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => 20
        ]);

        $currentStock = $variant->getCurrentStock();
        $this->assertEquals(30, $currentStock);
    }

    public function test_product_variant_has_stock(): void
    {
        $store = Store::factory()->create();
        $user = User::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        // Initially should not have stock
        $this->assertFalse($variant->hasStock());

        // Add stock
        StockMovement::factory()->create([
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 10
        ]);

        // Refresh the model to get updated stock
        $variant = $variant->fresh();
        $this->assertTrue($variant->hasStock());
    }

    public function test_product_variant_factory_creates_valid_data(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id
        ]);

        $this->assertNotEmpty($variant->sku);
        $this->assertIsNumeric($variant->cost_price);
        $this->assertIsNumeric($variant->sale_price);
        $this->assertTrue($variant->sale_price >= $variant->cost_price);
        $this->assertTrue($variant->exists);
    }

    public function test_product_variant_sku_is_unique(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'sku' => 'TEST123'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'sku' => 'TEST123' // Same SKU should fail
        ]);
    }
}
