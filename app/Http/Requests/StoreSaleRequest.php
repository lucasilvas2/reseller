<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar se o usuário está autenticado e tem uma loja associada
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
            // Validação do cliente
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) use ($storeId) {
                    $query->where('store_id', $storeId)
                          ->whereNull('deleted_at');
                }),
            ],

            // Validação dos itens da venda
            'items' => [
                'required',
                'array',
                'min:1',
                'max:50', // Limite máximo de 50 itens por venda
            ],

            // Validação de cada item
            'items.*.product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
                Rule::exists('product_variants', 'id')->where(function ($query) use ($storeId) {
                    $query->where('store_id', $storeId);
                }),
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:9999', // Limite máximo de quantidade
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99', // Limite máximo de preço
            ],

            // Validação de campos opcionais
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'status' => [
                'required',
                'string',
                'in:pending,paid,canceled',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Mensagens para client_id
            'client_id.required' => 'Selecione um cliente para a venda.',
            'client_id.exists' => 'O cliente selecionado não existe ou não pertence à sua loja.',

            // Mensagens para items
            'items.required' => 'Adicione pelo menos um produto à venda.',
            'items.min' => 'A venda deve ter pelo menos um produto.',
            'items.max' => 'Uma venda pode ter no máximo 50 produtos.',

            // Mensagens para product_variant_id
            'items.*.product_variant_id.required' => 'Selecione um produto válido.',
            'items.*.product_variant_id.exists' => 'Um dos produtos selecionados não existe ou não pertence à sua loja.',

            // Mensagens para quantity
            'items.*.quantity.required' => 'Informe a quantidade do produto.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'items.*.quantity.max' => 'A quantidade máxima por item é 9999.',

            // Mensagens para unit_price
            'items.*.unit_price.required' => 'Informe o preço unitário do produto.',
            'items.*.unit_price.numeric' => 'O preço deve ser um valor numérico.',
            'items.*.unit_price.min' => 'O preço não pode ser negativo.',
            'items.*.unit_price.max' => 'O preço máximo por item é R$ 999.999,99.',

            // Mensagens para notes
            'notes.max' => 'As observações não podem ter mais de 1000 caracteres.',

            // Mensagens para status
            'status.required' => 'Selecione o status da venda.',
            'status.in' => 'O status deve ser: pendente, pago ou cancelado.',
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
            'client_id' => 'cliente',
            'items' => 'produtos',
            'items.*.product_variant_id' => 'produto',
            'items.*.quantity' => 'quantidade',
            'items.*.unit_price' => 'preço unitário',
            'notes' => 'observações',
            'status' => 'status',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateStockAvailability($validator);
            $this->validateUniqueProductSkus($validator);
            $this->validateTotalAmount($validator);
        });
    }

    /**
     * Validar disponibilidade de estoque para cada item
     */
    protected function validateStockAvailability($validator)
    {
        if (!$this->has('items')) {
            return;
        }

        foreach ($this->input('items', []) as $index => $item) {
            if (!isset($item['product_variant_id']) || !isset($item['quantity'])) {
                continue;
            }

            $productVariant = ProductVariant::find($item['product_variant_id']);

            if (!$productVariant) {
                continue;
            }

            $currentStock = $productVariant->getCurrentStock();
            $requestedQuantity = (int) $item['quantity'];

            if ($currentStock < $requestedQuantity) {
                $productName = $productSku->product->name ?? 'Produto desconhecido';
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "Estoque insuficiente para '{$productName}'. Disponível: {$currentStock}, Solicitado: {$requestedQuantity}."
                );
            }
        }
    }

    /**
     * Validar se não há SKUs duplicados na venda
     */
    protected function validateUniqueProductSkus($validator)
    {
        if (!$this->has('items')) {
            return;
        }

        $skuIds = collect($this->input('items', []))
            ->pluck('product_variant_id')
            ->filter()
            ->toArray();

        $duplicates = array_diff_assoc($skuIds, array_unique($skuIds));

        if (!empty($duplicates)) {
            $validator->errors()->add(
                'items',
                'Não é possível adicionar o mesmo produto mais de uma vez. Ajuste as quantidades se necessário.'
            );
        }
    }

    /**
     * Validar se o valor total não excede limites
     */
    protected function validateTotalAmount($validator)
    {
        if (!$this->has('items')) {
            return;
        }

        $totalAmount = collect($this->input('items', []))
            ->sum(function ($item) {
                return ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
            });

        // Limite máximo de venda: R$ 999.999,99
        if ($totalAmount > 999999.99) {
            $validator->errors()->add(
                'items',
                'O valor total da venda não pode exceder R$ 999.999,99.'
            );
        }

        // Valor mínimo de venda: R$ 0,01
        if ($totalAmount <= 0) {
            $validator->errors()->add(
                'items',
                'O valor total da venda deve ser maior que zero.'
            );
        }
    }

    /**
     * Get the validated data from the request with additional processing.
     */
    public function validatedWithProcessing(): array
    {
        $validated = $this->validated();

        // Adicionar store_id automaticamente
        $validated['store_id'] = Auth::user()->store_id;

        // Calcular total_price para cada item
        if (isset($validated['items'])) {
            $validated['items'] = collect($validated['items'])->map(function ($item) {
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
                return $item;
            })->toArray();
        }

        // Calcular valor total da venda
        $validated['total_amount'] = collect($validated['items'])
            ->sum('total_price');

        return $validated;
    }
}
