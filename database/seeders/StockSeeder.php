<?php

namespace Database\Seeders;

use App\Models\Products;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use App\Models\Brands;
use App\Models\Dealership;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $dealership = Dealership::first();

        if (!$dealership) {
            $this->command->warn('No dealership found. Creating one...');
            $dealership = Dealership::factory()->create();
        }

        // Criar algumas marcas
        $brands = Brands::factory(5)->create();

        // Categorias de produtos
        $categories = ['Electronics', 'Automotive', 'Tools', 'Parts', 'Accessories'];

        // Criar produtos com categorias
        $products = [];
        foreach ($categories as $category) {
            for ($i = 1; $i <= 4; $i++) {
                $products[] = Products::create([
                    'name' => $category . ' Product ' . $i,
                    'description' => 'Description for ' . $category . ' Product ' . $i,
                    'category' => $category,
                    'brand_id' => $brands->random()->id,
                    'dealership_id' => $dealership->id,
                ]);
            }
        }

        // Criar SKUs e movimentos de estoque
        foreach ($products as $index => $product) {
            $sku = ProductsSku::create([
                'product_id' => $product->id,
                'sku' => 'SKU-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'barcode' => 'BAR' . str_pad($index + 1, 10, '0', STR_PAD_LEFT),
                'cost_price' => rand(50, 500),
                'sale_price' => rand(80, 800),
                'dealership_id' => $dealership->id,
            ]);

            // Criar movimentos de entrada (últimos 30 dias)
            for ($day = 30; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);

                // Movimento de entrada
                if (rand(1, 3) === 1) { // 33% chance por dia
                    StockMovement::create([
                        'product_sku_id' => $sku->id,
                        'type' => 'in',
                        'quantity' => rand(10, 100),
                        'dealership_id' => $dealership->id,
                        'user_id' => 1,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Movimento de saída
                if (rand(1, 4) === 1) { // 25% chance por dia
                    StockMovement::create([
                        'product_sku_id' => $sku->id,
                        'type' => 'out',
                        'quantity' => rand(1, 30),
                        'dealership_id' => $dealership->id,
                        'user_id' => 1,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        $this->command->info('Stock data seeded successfully!');
        $this->command->info('Total Products: ' . count($products));
        $this->command->info('Total Categories: ' . count($categories));
        $this->command->info('Dealership ID: ' . $dealership->id);
    }
}
