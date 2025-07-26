<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InventoryFilterRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'stock_status' => 'nullable|in:in-stock,low-stock,out-of-stock',
            'sort_by' => 'nullable|in:current_stock,product_name,stock_value,last_movement',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:5|max:100',
            'page' => 'nullable|integer|min:1',
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
            'search.max' => 'O termo de busca não pode ter mais que 255 caracteres.',
            'stock_status.in' => 'Status de estoque inválido.',
            'sort_by.in' => 'Campo de ordenação inválido.',
            'sort_order.in' => 'Ordem de classificação deve ser "asc" ou "desc".',
            'per_page.integer' => 'Itens por página deve ser um número.',
            'per_page.min' => 'Mínimo de 5 itens por página.',
            'per_page.max' => 'Máximo de 100 itens por página.',
            'page.integer' => 'Número da página deve ser um número.',
            'page.min' => 'Número da página deve ser maior que zero.',
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
            'search' => 'busca',
            'stock_status' => 'status do estoque',
            'sort_by' => 'campo de ordenação',
            'sort_order' => 'ordem de classificação',
            'per_page' => 'itens por página',
            'page' => 'página',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'per_page' => $this->per_page ?? 25,
            'page' => $this->page ?? 1,
            'sort_by' => $this->sort_by ?? 'current_stock',
            'sort_order' => $this->sort_order ?? 'desc',
        ]);

        // Clean search term
        if ($this->has('search')) {
            $this->merge([
                'search' => trim($this->search)
            ]);
        }
    }
}
