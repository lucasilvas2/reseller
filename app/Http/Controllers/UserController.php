<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): \Inertia\Response
    {
        $users =  User::role('dealer')->get();
        $users->map(function ($user) {
           $user->roles = $user->getRoleNames();
        });
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): \Inertia\Response
    {
        $roles = Role::all();
        return Inertia::render('Admin/Users/Create',[
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'role' => 'required',
        ]);

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make('12345678')
        ]);

        $role = Role::findById($request->get('role'));
        $user->assignRole($role);
        return redirect()->route('admin.users.index');
    }
}
