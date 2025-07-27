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

    public function index(Request $request): \Inertia\Response
    {
        $query = $this->storeModel->newQuery();

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Apply name filter
        if ($request->filled('name')) {
            $query->where('name', 'like', "%" . $request->name . "%");
        }

        // Apply email filter
        if ($request->filled('email')) {
            $query->where('email', 'like', "%" . $request->email . "%");
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Apply sorting
        $sortKey = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        if( in_array($sortKey, ['name', 'email', 'created_at'])) {
            $query->orderBy($sortKey, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 10);
        $stores = $query->paginate($perPage);

        $transformedData = $stores->getCollection()->transform(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
                'created_at' => $store->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Admin/Stores/Index', [
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'from' => $stores->firstItem(),
                'to' => $stores->lastItem(),
                'links' => [],
            ],
            'filters' => $request->only(['search', 'name', 'email', 'date_from', 'date_to', 'sort', 'order']),
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
