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
        $roleAdmin =  Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);
        Role::create(['name' => 'dealer', 'guard_name' => 'web']);

        Permission::create(['name' => 'admin.home', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.users.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.roles.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.permissions.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.categories.index', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.categories.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'admin.categories.edit', 'guard_name' => 'web']);

        $roleAdmin->givePermissionTo([
            'admin.home',
            'admin.users.index',
            'admin.roles.index',
            'admin.permissions.index',
            'admin.categories.index',
            'admin.categories.create',
            'admin.categories.edit',
        ]);

        User::where('name', 'admin')->first()->assignRole('admin');

        $users = User::whereNot('name', 'admin')->get();
        foreach ($users as $user) {
            $user->assignRole('dealer');
        }
    }
}
