<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoresController extends Controller
{
    protected Store $storeModel;

    public function __construct(Store $storeModel)
    {
        $this->storeModel = $storeModel;
    }

    public function index(): \Inertia\Response
    {
        $stores = $this->storeModel->all();

        return Inertia::render('Admin/Stores/Index', [
            'stores' => $stores,
        ]);
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Stores/Create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required',
        ]);

        $this->storeModel->create($request->all());

        return redirect()->route('admin.stores.index');
    }

    public function edit(int $id)
    {
        $store = $this->storeModel->findOrFail($id);

        return Inertia::render('Admin/Stores/Edit', [
            'store' => $store,
        ]);
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
        ]);

        $store = $this->storeModel->find($id);

        $store->update([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone_number' => $request->get('phone_number'),
        ]);

        return redirect()->route('admin.stores.index');
    }
}
