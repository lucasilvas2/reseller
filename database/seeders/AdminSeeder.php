<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário admin se não existir
       $adminUser = User::firstOrCreate(
           ['email' => 'admin@example.com'],
           [
               'name' => 'Administrator',
               'email' => 'admin@example.com',
               'password' => Hash::make('admin'),
               'email_verified_at' => now(),
           ]
       );

        // Atribuir role admin ao usuário
       if (!$adminUser->hasRole('admin')) {
           $adminUser->assignRole('admin');
       }

       // Garantir que o admin tenha todas as permissões
       $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
       $allPermissions = \Spatie\Permission\Models\Permission::all();
       if ($adminRole) {
           $adminRole->syncPermissions($allPermissions);
       }
       $adminUser->syncPermissions($allPermissions);
    }
}
