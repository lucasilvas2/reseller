<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMovementCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = StockMovementResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => $this->getMetaData(),
            'summary' => $this->getSummaryData(),
        ];
    }

    /**
     * Get additional meta data
     */
    private function getMetaData(): array
    {
        return [
            'total_movements' => $this->collection->count(),
            'movement_types' => $this->getMovementTypeCounts(),
            'date_range' => $this->getDateRange(),
        ];
    }

    /**
     * Get movement type counts
     */
    private function getMovementTypeCounts(): array
    {
        $movements = $this->collection;

        return [
            'total_in' => $movements->where('type', 'in')->count(),
            'total_out' => $movements->where('type', 'out')->count(),
            'quantity_in' => $movements->where('type', 'in')->sum('quantity'),
            'quantity_out' => $movements->where('type', 'out')->sum('quantity'),
        ];
    }

    /**
     * Get date range of movements
     */
    private function getDateRange(): array
    {
        if ($this->collection->isEmpty()) {
            return [
                'earliest' => null,
                'latest' => null,
            ];
        }

        $dates = $this->collection->pluck('created_at')->filter();

        return [
            'earliest' => $dates->min(),
            'latest' => $dates->max(),
        ];
    }

    /**
     * Get summary data for the collection
     */
    private function getSummaryData(): array
    {
        $movements = $this->collection;

        if ($movements->isEmpty()) {
            return [
                'total_value' => 0,
                'total_quantity' => 0,
                'net_quantity_change' => 0,
            ];
        }

        $totalValue = 0;
        $totalQuantityIn = 0;
        $totalQuantityOut = 0;

        foreach ($movements as $movement) {
            if ($movement->type === 'in') {
                $totalQuantityIn += $movement->quantity;
            } else {
                $totalQuantityOut += $movement->quantity;
            }

            // Calculate value if product SKU is loaded
            if ($movement->relationLoaded('productSku')) {
                $price = $movement->type === 'in'
                    ? ($movement->productSku->cost_price ?? 0)
                    : ($movement->productSku->sale_price ?? 0);
                $totalValue += $price * $movement->quantity;
            }
        }

        return [
            'total_value' => $totalValue,
            'total_value_formatted' => 'R$ ' . number_format($totalValue, 2, ',', '.'),
            'total_quantity_in' => $totalQuantityIn,
            'total_quantity_out' => $totalQuantityOut,
            'net_quantity_change' => $totalQuantityIn - $totalQuantityOut,
        ];
    }
}
