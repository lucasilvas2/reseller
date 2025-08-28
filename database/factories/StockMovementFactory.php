<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => 1,
            'type' => $this->faker->randomElement(['in', 'out']),
            'quantity' => $this->faker->numberBetween(1, 100),
            'store_id' => 1,
            'user_id' => 1,
            'description' => $this->faker->sentence(),
        ];
    }
}
