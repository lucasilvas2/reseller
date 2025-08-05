<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(5)->create();
        $store = Store::first();

        foreach ($users as $user) {
            $user->assignRole('user');
            Client::factory()->create([
                'user_id' => $user->id,
                'store_id' => $store ? $store->id : 1,
            ]);
        }


    }
}
