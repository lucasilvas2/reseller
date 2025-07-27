<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            StockSeeder::class
        ]);
    }
}
