<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItemFailure>
 */
class SaleItemFailureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => 1,
            'order_item_id' => 1,
            'product_id' => 1,
            'failure_type' => $this->faker->randomElement([
                'insufficient_stock',
                'payment_error',
                'validation_error',
                'processing_error',
                'network_error'
            ]),
            'error_message' => $this->faker->sentence(),
            'error_context' => [
                'timestamp' => now()->toISOString(),
                'details' => $this->faker->words(5, true)
            ],
            'attempted_at' => $this->faker->dateTimeThisMonth(),
            'attempt_number' => $this->faker->numberBetween(1, 3),
            'is_retry' => $this->faker->boolean(30),
            'is_resolved' => $this->faker->boolean(20),
            'resolved_at' => $this->faker->optional(0.2)->dateTimeThisMonth(),
            'resolution_notes' => $this->faker->optional(0.2)->sentence(),
            'store_id' => 1,
        ];
    }
}
