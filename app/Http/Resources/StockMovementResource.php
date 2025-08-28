<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'description' => $this->description,
            'user_id' => $this->user_id,
            'store_id' => $this->store_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Formatted dates
            'created_at_formatted' => $this->created_at->format('M d, Y H:i'),
            'created_at_human' => $this->created_at->diffForHumans(),

            // Relationships
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'description' => $this->product->description,
                    'category' => $this->product->category,
                    'sku' => $this->product->sku,
                    'barcode' => $this->product->barcode,
                    'cost_price' => $this->product->cost_price,
                    'sale_price' => $this->product->sale_price,
                    'brand' => $this->whenLoaded('product.brand', function () {
                        return [
                            'id' => $this->product->brand->id,
                            'name' => $this->product->brand->name,
                        ];
                    }),
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            // Calculated fields
            'total_value' => $this->getTotalValue(),
            'movement_impact' => $this->getMovementImpact(),

            // Display fields
            'display_name' => $this->getDisplayName(),
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * Get the type label in Portuguese
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'in' => 'Entrada',
            'out' => 'Saída',
            default => ucfirst($this->type),
        };
    }

    /**
     * Calculate the total value of the movement
     */
    private function getTotalValue(): float
    {
        if (!$this->relationLoaded('productSku')) {
            return 0;
        }

        $price = $this->type === 'in'
            ? ($this->productSku->cost_price ?? 0)
            : ($this->productSku->sale_price ?? 0);

        return $price * $this->quantity;
    }

    /**
     * Get movement impact (positive for in, negative for out)
     */
    private function getMovementImpact(): int
    {
        return $this->type === 'in' ? $this->quantity : -$this->quantity;
    }

    /**
     * Get display name for the movement
     */
    private function getDisplayName(): string
    {
        if (!$this->relationLoaded('productSku') || !$this->productSku->relationLoaded('product')) {
            return "Movimentação #{$this->id}";
        }

        $productName = $this->productSku->product->name ?? 'Produto Desconhecido';
        $sku = $this->productSku->sku ?? 'N/A';

        return "{$productName} ({$sku})";
    }

    /**
     * Get movement summary
     */
    private function getSummary(): string
    {
        $typeLabel = $this->getTypeLabel();
        $displayName = $this->getDisplayName();

        return "{$typeLabel} de {$this->quantity} unidades - {$displayName}";
    }
}
