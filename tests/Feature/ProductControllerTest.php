<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

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

        // Assign role to user
        $this->user->assignRole('reseller');
    }

    public function test_authenticated_user_can_view_products_index(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);
        Product::factory()
            ->count(3)
            ->create([
                'store_id' => $this->store->id,
                'brand_id' => $brand->id
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Products/Index')
            ->has('data', 3)
        );
    }

    public function test_authenticated_user_can_create_product(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'brand_id' => $brand->id,
            'category' => 'Electronics'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('products.store'), $productData);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'store_id' => $this->store->id,
            'brand_id' => $brand->id
        ]);
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $response = $this->get(route('products.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_only_see_products_from_their_store(): void
    {
        // Create another store and products
        $otherStore = Store::factory()->create();
        $otherBrand = Brand::factory()->create(['store_id' => $otherStore->id]);

        Product::factory()->create([
            'store_id' => $otherStore->id,
            'brand_id' => $otherBrand->id,
            'name' => 'Other Store Product'
        ]);

        // Create product for current user's store
        $userBrand = Brand::factory()->create(['store_id' => $this->store->id]);
        Product::factory()->create([
            'store_id' => $this->store->id,
            'brand_id' => $userBrand->id,
            'name' => 'User Store Product'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('data', 1)
            ->where('data.0.name', 'User Store Product')
        );
    }

    public function test_product_search_works(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        Product::factory()->create([
            'store_id' => $this->store->id,
            'brand_id' => $brand->id,
            'name' => 'iPhone 15'
        ]);

        Product::factory()->create([
            'store_id' => $this->store->id,
            'brand_id' => $brand->id,
            'name' => 'Samsung Galaxy'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('products.index', ['search' => 'iPhone']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('data', 1)
            ->where('data.0.name', 'iPhone 15')
        );
    }
}
