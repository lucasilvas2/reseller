<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category ?? 'Uncategorized',
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'store_id' => $this->store_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Brand information
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),

            // Stock calculations
            'stock_info' => $this->getStockInfo(),

            // Financial calculations
            'financial_info' => $this->getFinancialInfo(),

            // Status information
            'status_info' => $this->getStatusInfo(),

            // Display fields
            'display_name' => $this->getDisplayName(),
            'full_description' => $this->getFullDescription(),

            // Last movement information
            'last_movement' => $this->getLastMovement(),

            // Profit margin
            'profit_margin' => $this->getProfitMargin(),
            'profit_margin_percentage' => $this->getProfitMarginPercentage(),
        ];
    }

    /**
     * Get stock information with calculations
     */
    private function getStockInfo(): array
    {
        if (!$this->relationLoaded('stockMovements')) {
            return [
                'current_stock' => 0,
                'total_movements_in' => 0,
                'total_movements_out' => 0,
                'movement_count' => 0,
            ];
        }

        $totalIn = $this->stockMovements->where('type', 'in')->sum('quantity');
        $totalOut = $this->stockMovements->where('type', 'out')->sum('quantity');
        $currentStock = $totalIn - $totalOut;

        return [
            'current_stock' => $currentStock,
            'total_movements_in' => $totalIn,
            'total_movements_out' => $totalOut,
            'movement_count' => $this->stockMovements->count(),
        ];
    }

    /**
     * Get financial information
     */
    private function getFinancialInfo(): array
    {
        $stockInfo = $this->getStockInfo();
        $currentStock = $stockInfo['current_stock'];

        return [
            'stock_value' => $currentStock * ($this->cost_price ?? 0),
            'potential_revenue' => $currentStock * ($this->sale_price ?? 0),
            'cost_price_formatted' => 'R$ ' . number_format($this->cost_price ?? 0, 2, ',', '.'),
            'sale_price_formatted' => 'R$ ' . number_format($this->sale_price ?? 0, 2, ',', '.'),
            'stock_value_formatted' => 'R$ ' . number_format(
                $currentStock * ($this->cost_price ?? 0),
                2, ',', '.'
            ),
            'potential_revenue_formatted' => 'R$ ' . number_format(
                $currentStock * ($this->sale_price ?? 0),
                2, ',', '.'
            ),
        ];
    }

    /**
     * Get status information
     */
    private function getStatusInfo(): array
    {
        $stockInfo = $this->getStockInfo();
        $currentStock = $stockInfo['current_stock'];

        $status = 'in-stock';
        $statusLabel = 'Em Estoque';
        $statusColor = 'green';

        if ($currentStock <= 0) {
            $status = 'out-of-stock';
            $statusLabel = 'Sem Estoque';
            $statusColor = 'red';
        } elseif ($currentStock <= 10) {
            $status = 'low-stock';
            $statusLabel = 'Estoque Baixo';
            $statusColor = 'yellow';
        }

        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'needs_restock' => $currentStock <= 10,
            'is_available' => $currentStock > 0,
        ];
    }

    /**
     * Get display name
     */
    private function getDisplayName(): string
    {
        if (!$this->relationLoaded('product')) {
            return $this->sku ?? "Item #{$this->id}";
        }

        $productName = $this->product->name ?? 'Produto Desconhecido';
        return "{$productName} - {$this->sku}";
    }

    /**
     * Get full description
     */
    private function getFullDescription(): string
    {
        $displayName = $this->getDisplayName();
        $stockInfo = $this->getStockInfo();

        return "{$displayName} (Estoque: {$stockInfo['current_stock']} unidades)";
    }

    /**
     * Get last movement information
     */
    private function getLastMovement(): ?array
    {
        if (!$this->relationLoaded('stockMovements')) {
            return null;
        }

        $lastMovement = $this->stockMovements->sortByDesc('created_at')->first();

        if (!$lastMovement) {
            return null;
        }

        return [
            'id' => $lastMovement->id,
            'type' => $lastMovement->type,
            'type_label' => $lastMovement->type === 'in' ? 'Entrada' : 'Saída',
            'quantity' => $lastMovement->quantity,
            'created_at' => $lastMovement->created_at,
            'created_at_formatted' => $lastMovement->created_at->format('M d, Y H:i'),
            'created_at_human' => $lastMovement->created_at->diffForHumans(),
        ];
    }

    /**
     * Get profit margin in absolute value
     */
    private function getProfitMargin(): float
    {
        return ($this->sale_price ?? 0) - ($this->cost_price ?? 0);
    }

    /**
     * Get profit margin percentage
     */
    private function getProfitMarginPercentage(): float
    {
        if (($this->cost_price ?? 0) <= 0) {
            return 0;
        }

        return (($this->getProfitMargin() / $this->cost_price) * 100);
    }
}
