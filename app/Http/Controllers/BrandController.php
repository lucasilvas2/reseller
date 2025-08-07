<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BrandController extends Controller
{
    protected Brand $brandModel;

    public function __construct()
    {
        $this->brandModel = new Brand();
    }

    public function index(): \Inertia\Response
    {
        $brands = $this->brandModel->where('store_id', Auth::user()->store_id)->get();
        return Inertia::render('Admin/Brands/Index', compact('brands'));
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('Admin/Brands/Create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $request->merge([
            'store_id' => Auth::user()->store_id,
        ]);

        $brand = $this->brandModel->create($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('image', 's3');
            $brand->update(['image_url' => $path]);
        }

        return redirect()->route('admin.brands.index');
    }

    public function edit(int $id): \Inertia\Response
    {
        $brand = $this->brandModel->where('store_id', Auth::user()->store_id)->findOrFail($id);
        return Inertia::render('Admin/Brands/Edit', compact('brand'));
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $brand = $this->brandModel->where('store_id', Auth::user()->store_id)->findOrFail($id);
        $brand->update($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('image', 'public');
            $brand->update(['image_url' => $path]);
        }

        return redirect()->route('admin.brands.index');
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $brand = $this->brandModel->where('store_id', Auth::user()->store_id)->findOrFail($id);
        $brand->delete();

        return redirect()->route('admin.brands.index');
    }

}
