<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'client_id' => 1,
            'store_id' => 1,
            'total_amount' => $this->faker->randomFloat(2, 50, 1000),
            'notes' => $this->faker->optional()->text(200),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'canceled']),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }
}
