<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BrandsController extends Controller
{
    protected Brands $brandsModel;

    public function __construct()
    {
        $this->brandsModel = new Brands();
    }

    public function index(): \Inertia\Response
    {
        $brands = $this->brandsModel->where('dealership_id', Auth::user()->dealership_id)->get();
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
            'dealership_id' => Auth::user()->dealership_id,
        ]);

        $brand = $this->brandsModel->create($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('image', 's3');
            $brand->update(['image_url' => $path]);
        }

        return redirect()->route('admin.brands.index');
    }

    public function edit(int $id): \Inertia\Response
    {
        $brand = $this->brandsModel->where('dealership_id', Auth::user()->dealership_id)->findOrFail($id);
        return Inertia::render('Admin/Brands/Edit', compact('brand'));
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $brand = $this->brandsModel->where('dealership_id', Auth::user()->dealership_id)->findOrFail($id);
        $brand->update($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('image', 'public');
            $brand->update(['image_url' => $path]);
        }

        return redirect()->route('admin.brands.index');
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $brand = $this->brandsModel->where('dealership_id', Auth::user()->dealership_id)->findOrFail($id);
        $brand->delete();

        return redirect()->route('admin.brands.index');
    }

}
