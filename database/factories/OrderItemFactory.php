<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => 1, // Will be overridden in tests
            'product_variant_id' => 1, // Will be overridden in tests
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'total_price' => fn(array $attributes) => $attributes['quantity'] * $attributes['unit_price'],
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
        ];
    }
}
