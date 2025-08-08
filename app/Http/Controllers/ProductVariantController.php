<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function index()
    {
        $variants = ProductVariant::with(['product.brand'])
            ->whereHas('product', function($query) {
                $query->where('store_id', Auth::user()->store_id);
            })
            ->paginate(20);

        return Inertia::render('ProductVariants/Index', [
            'variants' => $variants
        ]);
    }

    public function show(ProductVariant $productVariant)
    {
        $productVariant->load(['product.brand']);

        return Inertia::render('ProductVariants/Show', [
            'variant' => $productVariant,
            'currentStock' => $productVariant->getCurrentStock(),
            'hasStock' => $productVariant->hasStock()
        ]);
    }
}
