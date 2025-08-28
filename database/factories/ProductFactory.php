<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Electronics', 'Clothing', 'Books', 'Home', 'Sports']),
            'store_id' => 1,
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{6}'),
            'barcode' => $this->faker->optional()->ean13(),
            'cost_price' => $this->faker->randomFloat(2, 5, 100),
            'sale_price' => $this->faker->randomFloat(2, 10, 200),
        ];
    }
}
