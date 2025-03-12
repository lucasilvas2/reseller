<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Response;
use Laravel\Jetstream\Features;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_method_return_reponse_correct(): void{
        $role = Role::create(['name' => 'dealer']);

        $users = User::factory()->count(3)->create();
        foreach($users as $user){
            $user->assignRole($role->name);
        }

        $response = $this->get(route('admin.users.index'));

        $response->assertSuccessful();

        $response->assertInertia(function ($page) use ($users){
           $page->component('Admin/Users/Index')
               ->has('users', 3)
               ->where('users.0.id', $users[0]->id)
               ->where('users.0.roles.0.name', 'dealer')
               ->where('users.1.id', $users[1]->id)
               ->where('users.1.roles.0.name', 'dealer')
               ->where('users.2.id', $users[2]->id)
               ->where('users.2.roles.0.name', 'dealer');
        });
    }

    public function test_store_method_create_user_and_assigns_role()
    {
        $role = Role::create(['name' => 'dealer']);

        $requestData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => $role->id,
        ];

        $response = $this->post(route('admin.users.store'), $requestData);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();

        $this->assertTrue($user->hasRole('dealer'));

        $this->assertTrue(Hash::check('12345678', $user->password));
    }

    public function test_store_method_validates_request_data()
    {
        $response = $this->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'role']);
    }
}
