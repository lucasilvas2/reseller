<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateStockMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasRole('reseller');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
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
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.min' => 'A quantidade deve ser maior que zero.',
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
            'quantity' => 'quantidade',
            'type' => 'tipo de movimentação',
            'description' => 'descrição',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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
