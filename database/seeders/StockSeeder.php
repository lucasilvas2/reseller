<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $store = Store::first();

        if (!$store) {
            $this->command->warn('No store found. Creating one...');
            $store = Store::factory()->create();
        }

        // Criar algumas marcas
        $brands = Brand::factory(5)->create(['store_id' => $store->id]);

        // Categorias de produtos
        $categories = ['Electronics', 'Automotive', 'Tools', 'Parts', 'Accessories'];

        // Criar produtos com categorias
        $products = [];
        $skuCounter = 1;
        foreach ($categories as $category) {
            for ($i = 1; $i <= 4; $i++) {
                $products[] = Product::create([
                    'name' => $category . ' Product ' . $i,
                    'description' => 'Description for ' . $category . ' Product ' . $i,
                    'category' => $category,
                    'brand_id' => $brands->random()->id,
                    'store_id' => $store->id,
                    'sku' => 'SKU-' . str_pad($skuCounter, 4, '0', STR_PAD_LEFT), // SKU único
                    'barcode' => 'BAR' . str_pad($skuCounter, 10, '0', STR_PAD_LEFT), // Barcode único
                    'cost_price' => rand(50, 500),
                    'sale_price' => rand(80, 800),
                ]);
                $skuCounter++;
            }
        }

        // Criar SKUs e movimentos de estoque
        foreach ($products as $index => $product) {
            // Criar movimentos de entrada (últimos 30 dias)
            for ($day = 30; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);

                // Movimento de entrada
                if (rand(1, 3) === 1) { // 33% chance por dia
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => rand(10, 100),
                        'store_id' => $store->id,
                        'user_id' => 1,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Movimento de saída
                if (rand(1, 4) === 1) { // 25% chance por dia
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => rand(1, 30),
                        'store_id' => $store->id,
                        'user_id' => 1,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        $this->command->info('Stock movements created successfully!');
        $this->command->info("Total Products: {$product->count()}");
        $this->command->info("Store ID: {$store->id}");
    }
}
