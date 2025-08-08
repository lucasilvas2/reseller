<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seeders
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // Create test data
        $this->store = Store::factory()->create();
        $this->user = User::factory()->create([
            'store_id' => $this->store->id
        ]);
        $this->product = Product::factory()->create([
            'store_id' => $this->store->id
        ]);

        // Assign role to user
        $this->user->assignRole('reseller');
    }

    public function test_authenticated_user_can_view_product_variants_index(): void
    {
        ProductVariant::factory()
            ->count(3)
            ->create([
                'product_id' => $this->product->id,
                'store_id' => $this->store->id
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('product-variants.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/ProductVariants/Index')
        );
    }

    public function test_authenticated_user_can_create_product_variant(): void
    {
        $variantData = [
            'product_id' => $this->product->id,
            'sku' => 'TEST-SKU-001',
            'barcode' => '1234567890123',
            'cost_price' => 50.00,
            'sale_price' => 75.00
        ];

        $response = $this->actingAs($this->user)
            ->post(route('product-variants.store'), $variantData);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TEST-SKU-001',
            'product_id' => $this->product->id,
            'store_id' => $this->store->id
        ]);
    }

    public function test_nested_route_for_product_variants_works(): void
    {
        $variantData = [
            'sku' => 'NESTED-SKU-001',
            'barcode' => '1234567890124',
            'cost_price' => 30.00,
            'sale_price' => 45.00
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.variants.store', $this->product), $variantData);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_variants', [
            'sku' => 'NESTED-SKU-001',
            'product_id' => $this->product->id,
            'store_id' => $this->store->id
        ]);
    }

    public function test_user_can_only_see_variants_from_their_store(): void
    {
        // Create variant for other store
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);
        ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'store_id' => $otherStore->id,
            'sku' => 'OTHER-STORE-SKU'
        ]);

        // Create variant for current user's store
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'sku' => 'USER-STORE-SKU'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('product-variants.index'));

        $response->assertStatus(200);
        // Should only see variants from user's store
    }

    public function test_sku_must_be_unique_within_store(): void
    {
        // Create first variant
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'sku' => 'UNIQUE-SKU'
        ]);

        // Try to create second variant with same SKU
        $variantData = [
            'product_id' => $this->product->id,
            'sku' => 'UNIQUE-SKU',
            'cost_price' => 50.00,
            'sale_price' => 75.00
        ];

        $response = $this->actingAs($this->user)
            ->post(route('product-variants.store'), $variantData);

        // Should fail validation
        $response->assertSessionHasErrors('sku');
    }

    public function test_sale_price_must_be_greater_than_cost_price(): void
    {
        $variantData = [
            'product_id' => $this->product->id,
            'sku' => 'PRICE-TEST-SKU',
            'cost_price' => 100.00,
            'sale_price' => 50.00 // Less than cost price
        ];

        $response = $this->actingAs($this->user)
            ->post(route('product-variants.store'), $variantData);

        // Should fail validation
        $response->assertSessionHasErrors('sale_price');
    }

    public function test_unauthenticated_user_cannot_access_product_variants(): void
    {
        $response = $this->get(route('product-variants.index'));
        $response->assertRedirect(route('login'));
    }
}
