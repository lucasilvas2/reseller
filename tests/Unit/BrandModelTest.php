<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_brand_can_be_created(): void
    {
        $store = Store::factory()->create();

        $brand = Brand::factory()->create([
            'store_id' => $store->id,
        ]);

        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertEquals($store->id, $brand->store_id);
    }

    public function test_brand_belongs_to_store(): void
    {
        $store = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        $this->assertInstanceOf(Store::class, $brand->store);
        $this->assertEquals($store->id, $brand->store->id);
    }

    public function test_brand_has_many_products(): void
    {
        $store = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        Product::factory()
            ->count(3)
            ->create([
                'brand_id' => $brand->id,
                'store_id' => $store->id
            ]);

        $this->assertCount(3, $brand->products);
        $this->assertInstanceOf(Product::class, $brand->products->first());
    }

    public function test_brand_factory_creates_valid_data(): void
    {
        $store = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $store->id]);

        $this->assertNotEmpty($brand->name);
        $this->assertIsInt($brand->store_id);
        $this->assertTrue($brand->exists);
    }

    public function test_brand_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Brand::create([
            'store_id' => 1,
            'name' => null
        ]);
    }
}
