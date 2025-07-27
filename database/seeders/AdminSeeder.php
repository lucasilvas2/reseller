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
//        $adminUser = User::firstOrCreate(
//            ['email' => 'admin@admin.com'],
//            [
//                'name' => 'Administrator',
//                'email' => 'admin@admin.com',
//                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'AdminSecure123!')),
//                'email_verified_at' => now(),
//            ]
//        );

        // Garantir que o role admin existe
//        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Atribuir role admin ao usuário
//        if (!$adminUser->hasRole('admin')) {
//            $adminUser->assignRole('admin');
//        }

        // Avisar sobre a senha padrão em desenvolvimento
//        if (app()->environment('local') && env('ADMIN_DEFAULT_PASSWORD') === null) {
//            echo "\n⚠️  AVISO: Usando senha padrão para admin. Configure ADMIN_DEFAULT_PASSWORD no .env\n";
//            echo "📧 Email: admin@admin.com\n";
//            echo "🔑 Senha: AdminSecure123!\n\n";
//        }
    }
}
