<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)->create();
        User::factory()->create([
            'name' => 'reseller',
            'email' => 'reseller@example.com',
            'password' => Hash::make('reseller'),
        ]);

        $reseller = User::where('name', 'reseller')->first();
        if ($reseller && Store::first()) {
            $reseller->update([
                'store_id' => Store::first()->id,
            ]);
        }

        $resellerUsers = User::whereNot('email', 'admin@example.com')->get();
        foreach ($resellerUsers as $user) {
            if (!$user->hasRole('admin')) {
                $user->assignRole('reseller');
            }
        }
    }
}
