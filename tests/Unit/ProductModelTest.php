<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_product_can_be_created(): void
    {
        $store = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'brand_id' => $brand->id,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($store->id, $product->store_id);
        $this->assertEquals($brand->id, $product->brand_id);
    }

    public function test_product_belongs_to_brand(): void
    {
        $store = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'brand_id' => $brand->id,
        ]);

        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertEquals($brand->name, $product->brand->name);
    }

    public function test_product_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $product->store);
        $this->assertEquals($store->id, $product->store->id);
    }

    public function test_product_factory_creates_valid_data(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $store->id]);

        $this->assertNotEmpty($product->name);
        $this->assertNotEmpty($product->description);
        $this->assertIsInt($product->store_id);
        $this->assertTrue($product->exists);
    }
}
