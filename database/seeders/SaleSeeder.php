<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sales = \App\Models\Sale::factory(50)->create();
        $this->command->info('50 sales created successfully.');

        // Optionally, you can associate sales with clients and stores
        foreach ($sales as $sale) {
            $client = \App\Models\Client::inRandomOrder()->first();
            $store = \App\Models\Store::inRandomOrder()->first();
            if ($client) {
                $sale->client()->associate($client);
            }
            if ($store) {
                $sale->store()->associate($store);
            }
            $sale->save();
        }
    }
}
