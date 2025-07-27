<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreStockMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasRole('dealer');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'sku' => 'nullable|string|max:255|unique:products_skus,sku,NULL,id,store_id,' . Auth::user()->store_id,
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0|gte:cost_price',
            'barcode' => 'nullable|string|max:255|unique:products_skus,barcode,NULL,id,store_id,' . Auth::user()->store_id,
            'type' => 'required|in:in,out',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório.',
            'product_id.exists' => 'O produto selecionado não existe.',
            'sku.unique' => 'Este SKU já existe para sua concessionária.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.min' => 'A quantidade deve ser maior que zero.',
            'cost_price.required' => 'O preço de custo é obrigatório.',
            'cost_price.min' => 'O preço de custo deve ser maior ou igual a zero.',
            'sale_price.required' => 'O preço de venda é obrigatório.',
            'sale_price.min' => 'O preço de venda deve ser maior ou igual a zero.',
            'sale_price.gte' => 'O preço de venda deve ser maior ou igual ao preço de custo.',
            'barcode.unique' => 'Este código de barras já existe para sua concessionária.',
            'type.required' => 'O tipo de movimentação é obrigatório.',
            'type.in' => 'O tipo de movimentação deve ser "entrada" ou "saída".',
            'description.max' => 'A descrição não pode ter mais que 500 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produto',
            'sku' => 'SKU',
            'quantity' => 'quantidade',
            'cost_price' => 'preço de custo',
            'sale_price' => 'preço de venda',
            'barcode' => 'código de barras',
            'type' => 'tipo de movimentação',
            'description' => 'descrição',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure numeric fields are properly formatted
        if ($this->has('cost_price')) {
            $this->merge([
                'cost_price' => (float) str_replace(',', '.', $this->cost_price)
            ]);
        }

        if ($this->has('sale_price')) {
            $this->merge([
                'sale_price' => (float) str_replace(',', '.', $this->sale_price)
            ]);
        }

        // Convert type labels to values if needed
        if ($this->has('type')) {
            $typeMap = [
                'entrada' => 'in',
                'saida' => 'out',
                'saída' => 'out',
            ];

            $type = strtolower($this->type);
            if (isset($typeMap[$type])) {
                $this->merge(['type' => $typeMap[$type]]);
            }
        }
    }
}
