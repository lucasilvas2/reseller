<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, create permissions and admin
        $this->call([
            PermissionSeeder::class,
            StoreSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            StockSeeder::class,
            ClientSeeder::class,
            SaleSeeder::class
        ]);
    }
}
