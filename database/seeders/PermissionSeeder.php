<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);

        // Criar permissões
        $permissions = [
            'admin.home',
            'admin.users.index',
            'admin.users.create',
            'admin.users.store',
            'admin.users.edit',
            'admin.users.update',
            'admin.users.destroy',
            'admin.roles.index',
            'admin.permissions.index',
            'admin.categories.index',
            'admin.categories.create',
            'admin.categories.edit',
            'admin.categories.update',
            'admin.categories.destroy',
            'admin.stores.index',
            'admin.stores.create',
            'admin.stores.edit',
            'admin.stores.update',
            'admin.stores.destroy',
            'admin.brands.index',
            'admin.brands.create',
            'admin.brands.edit',
            'admin.brands.update',
            'admin.brands.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
