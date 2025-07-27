<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Response;
use Laravel\Jetstream\Features;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_method_return_reponse_correct(): void{
        $role = Role::create(['name' => 'dealer']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create permissions
        $indexPermission = Permission::create(['name' => 'admin.users.index']);
        $adminRole->givePermissionTo($indexPermission);

        // Create a store
        $store = Store::factory()->create();

        // Create admin user and authenticate
        $adminUser = User::factory()->create([
            'store_id' => $store->id
        ]);
        $adminUser->assignRole($adminRole->name);
        $this->actingAs($adminUser);

        $users = User::factory()->count(3)->create([
            'store_id' => $store->id
        ]);
        foreach($users as $user){
            $user->assignRole($role->name);
        }

        $response = $this->get(route('admin.users.index'));

        $response->assertSuccessful();

        $response->assertInertia(function ($page) use ($adminRole){
           $page->component('Admin/Users/Index')
               ->has('users', 3) // Ajustado para 3 usuários (apenas os dealers criados)
               ->where('users.0.roles.0.name', 'dealer')
               ->where('users.1.roles.0.name', 'dealer')
               ->where('users.2.roles.0.name', 'dealer');
        });
    }

    public function test_store_method_create_user_and_assigns_role()
    {
        $role = Role::create(['name' => 'dealer']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create permissions
        $storePermission = Permission::create(['name' => 'admin.users.store']);
        $adminRole->givePermissionTo($storePermission);

        // Create a store
        $store = Store::factory()->create();

        // Create admin user and authenticate
        $adminUser = User::factory()->create([
            'store_id' => $store->id
        ]);
        $adminUser->assignRole($adminRole->name);
        $this->actingAs($adminUser);

        $requestData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => $role->id,
            'store' => $store->id,
        ];

        $response = $this->post(route('admin.users.store'), $requestData);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'store_id' => $store->id,
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();

        $this->assertTrue($user->hasRole('dealer'));
    }

    public function test_store_method_validates_request_data()
    {
        $role = Role::create(['name' => 'dealer']);
        $adminRole = Role::create(['name' => 'admin']);

        // Create permissions
        $storePermission = Permission::create(['name' => 'admin.users.store']);
        $adminRole->givePermissionTo($storePermission);

        // Create a store
        $store = Store::factory()->create();

        // Create admin user and authenticate
        $adminUser = User::factory()->create([
            'store_id' => $store->id
        ]);
        $adminUser->assignRole($adminRole->name);
        $this->actingAs($adminUser);

        $response = $this->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'role', 'store']);
    }
}
