<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected User $userModel;
    protected Role $roleModel;
    protected Store $storeModel;

    /**
     * @param User $user
     * @param Role $role
     */
    public function __construct(User $user, Role $role, Store $store)
    {
        $this->userModel = $user;
        $this->roleModel = $role;
        $this->storeModel = $store;
    }

    public function index(Request $request): \Inertia\Response
    {
        $query = $this->userModel->role('reseller');

        if($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        if($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        if($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $sortKey = $request->get('sort', 'created_at');
        $sortOrder = strtolower($request->get('order', 'desc'));
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        if(in_array($sortKey, ['name', 'email', 'phone_number'])) {
            $query->orderBy($sortKey, $sortOrder);
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        $perPage = $request->get('perPage', 10);
        $users = $query->paginate($perPage);

        $transformedData = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'role' => $user->getRoleNames()->first(),
                'store' => $user->store ? $user->store->name : null,
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'data' => $transformedData,
            'filters' => $request->all(['search', 'name', 'email', 'date_from', 'date_to', 'sort', 'order']),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'links' => [],
            ],
        ]);
    }

    public function create(): \Inertia\Response
    {
        $roles = $this->roleModel->all();
        $stores = $this->storeModel->all();
        return Inertia::render('Admin/Users/Create',[
            'roles' => $roles,
            'stores' => $stores,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,id',
            'store' => 'required|exists:stores,id',
            'password' => 'sometimes|min:5|confirmed',
        ]);

        // Gerar senha aleatória se não fornecida
        $password = $request->get('password', \Illuminate\Support\Str::random(16));

        $user = $this->userModel->create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($password),
            'store_id' => $request->get('store'),
        ]);

        $role = $this->roleModel->findById($request->get('role'));
        $user->assignRole($role);

        // TODO: Enviar email com a senha para o usuário

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    public function edit(int $id): \Inertia\Response
    {
        $user = $this->userModel->findOrFail($id);
        $user->role = $user->roles->first();

        $roles = $this->roleModel->all();
        $stores = $this->storeModel->all();
        return Inertia::render('Admin/Users/Edit',[
            'roles' => $roles,
            'user' => $user,
            'stores' => $stores,
        ]);
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|exists:roles,id',
            'store' => 'required|exists:stores,id',
        ]);

        $user = $this->userModel->findOrFail($id);

        $user->update([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'store_id' => $request->get('store'),
        ]);

        // Sincronizar roles (remove os antigos e adiciona o novo)
        $role = $this->roleModel->findById($request->get('role'));
        $user->syncRoles([$role]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }
}
