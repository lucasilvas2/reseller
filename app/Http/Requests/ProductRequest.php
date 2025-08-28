<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->store_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $storeId = Auth::user()->store_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric'],
            'sale_price' => ['required', 'numeric'],
            'sku' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand_id' => [
                'required',
                'integer',
                'exists:brands,id',
                Rule::exists('brands', 'id')->where(function ($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                }),
            ],
            'image_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            //name
            'name.required' => 'Product name is required.',
            'name.string' => 'Product name must be a string.',
            'name.max' => 'Product name is too long.',
            //description
            'description.max' => 'Product description is too long.',
            'description.string' => 'Product description must be a string.',
            //values
            'cost_price.required' => 'Product cost price is required.',
            'cost_price.numeric' => 'Product cost price must be a number.',
            'sale_price.required' => 'Product sale price is required.',
            'sale_price.numeric' => 'Product sale price must be a number.',
            //infos
            'barcode.required' => 'Product barcode is required.',
            'barcode.string' => 'Product barcode must be a string.',
            'sku.required' => 'Sku is required.',
            'sku.string' => 'Sku must be a string.',
            'category.required' => 'Category is required.',
            'category.integer' => 'Category must be an integer.',
            //relationships
            'brand_id.required' => 'Brand is required.',
            'brand_id.integer' => 'Brand must be an integer.',
            'store_id.required' => 'Store is required.',
            'store_id.integer' => 'Store must be an integer.',
            //image
            'image_url.required' => 'Image is required.',
            'image_url.string' => 'Image must be a string.',
        ];
    }
}
