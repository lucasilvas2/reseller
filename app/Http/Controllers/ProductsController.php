<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use App\Models\Products;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    protected Products $productsModel;
    protected Brands $brandsModel;
    public function __construct()
    {
        $this->productsModel = new Products();
    }

    public function index(): \Inertia\Response
    {
        $products = $this->productsModel->all();
        return inertia('App/Products/Index', compact('products'));
    }

    public function create(): \Inertia\Response
    {
        $brands = Brands::all();
        return inertia('App/Products/Create', compact('brands'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $request->merge([
            'dealership_id' => auth()->user()->dealership_id,
        ]);

        $product = $this->productsModel->create($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 's3');
            $product->update(['image_url' => $path]);
        }

        return redirect()->route('products.index');
    }

    public function edit(int $id): \Inertia\Response
    {
        $product = $this->productsModel->findOrFail($id);
        $brands = Brands::all();
        return inertia('App/Products/Edit', compact('product', 'brands'));
    }

    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|exists:brands,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $product = $this->productsModel->findOrFail($id);
        $product->update($request->all());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 's3');
            $product->update(['image_url' => $path]);
        }

        return redirect()->route('products.index');
    }
}
