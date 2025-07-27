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
        User::factory(10)->create();
        Store::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin'),
        ]);

        User::factory()->create([
            'name' => 'dealer',
            'email' => 'dealer@example.com',
            'password' => Hash::make('dealer'),
        ]);

        $dealer = User::where('name', 'dealer')->first();
        $dealer->update([
            'store_id' => Store::first()->id,
        ]);

        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
