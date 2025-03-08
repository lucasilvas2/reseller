<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected User $userModel;
    protected Role $roleModel;

    /**
     * @param User $user
     * @param Role $role
     */
    public function __construct(User $user, Role $role)
    {
        $this->userModel = $user;
        $this->roleModel = $role;
    }

    public function index(): \Inertia\Response
    {
        $users =  $this->userModel->role('dealer')->get();
        $users->map(function ($user) {
           $user->roles = $user->getRoleNames();
        });
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): \Inertia\Response
    {
        $roles = $this->roleModel->all();
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

        $user = $this->userModel->create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make('12345678')
        ]);

        $role = $this->roleModel->findById($request->get('role'));
        $user->assignRole($role);
        return redirect()->route('admin.users.index');
    }

    public function edit(int $id): \Inertia\Response
    {
        $user = $this->userModel->findOrFail($id);
        $user->role = $user->roles->first();

        $roles = $this->roleModel->all();
        return Inertia::render('Admin/Users/Edit',[
            'roles' => $roles,
            'user' => $user,
        ]);
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'role' => 'required',
        ]);

        $user = $this->userModel->find($id);
        $user->update([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
        ]);
        $role = $this->roleModel->findById($request->get('role'));
        $user->assignRole($role);

        info('Package installed successfully.');
        return redirect()->route('admin.users.index');
    }
}
