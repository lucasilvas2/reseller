<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryItemCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = InventoryItemResource::class;

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
            'insights' => $this->getInsights(),
        ];
    }

    /**
     * Get additional meta data
     */
    private function getMetaData(): array
    {
        return [
            'total_items' => $this->collection->count(),
            'stock_status_distribution' => $this->getStockStatusDistribution(),
            'category_distribution' => $this->getCategoryDistribution(),
        ];
    }

    /**
     * Get stock status distribution
     */
    private function getStockStatusDistribution(): array
    {
        $items = $this->collection;

        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;

        foreach ($items as $item) {
            $stockInfo = $item->getStockInfo();
            $currentStock = $stockInfo['current_stock'];

            if ($currentStock <= 0) {
                $outOfStock++;
            } elseif ($currentStock <= 10) {
                $lowStock++;
            } else {
                $inStock++;
            }
        }

        return [
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'in_stock_percentage' => $this->calculatePercentage($inStock, $items->count()),
            'low_stock_percentage' => $this->calculatePercentage($lowStock, $items->count()),
            'out_of_stock_percentage' => $this->calculatePercentage($outOfStock, $items->count()),
        ];
    }

    /**
     * Get category distribution
     */
    private function getCategoryDistribution(): array
    {
        $categories = [];

        foreach ($this->collection as $item) {
            if ($item->relationLoaded('products')) {
                $category = $item->product->category ?? 'Uncategorized';
                $categories[$category] = ($categories[$category] ?? 0) + 1;
            }
        }

        return $categories;
    }

    /**
     * Get summary data for the inventory
     */
    private function getSummaryData(): array
    {
        $items = $this->collection;

        if ($items->isEmpty()) {
            return [
                'total_stock_value' => 0,
                'total_potential_revenue' => 0,
                'total_units' => 0,
                'average_profit_margin' => 0,
            ];
        }

        $totalStockValue = 0;
        $totalPotentialRevenue = 0;
        $totalUnits = 0;
        $totalProfitMargin = 0;
        $itemsWithMargin = 0;

        foreach ($items as $item) {
            $stockInfo = $item->getStockInfo();
            $currentStock = $stockInfo['current_stock'];

            $totalUnits += $currentStock;
            $totalStockValue += $currentStock * ($item->cost_price ?? 0);
            $totalPotentialRevenue += $currentStock * ($item->sale_price ?? 0);

            if (($item->cost_price ?? 0) > 0) {
                $totalProfitMargin += $item->getProfitMarginPercentage();
                $itemsWithMargin++;
            }
        }

        $averageProfitMargin = $itemsWithMargin > 0 ? $totalProfitMargin / $itemsWithMargin : 0;

        return [
            'total_stock_value' => $totalStockValue,
            'total_stock_value_formatted' => 'R$ ' . number_format($totalStockValue, 2, ',', '.'),
            'total_potential_revenue' => $totalPotentialRevenue,
            'total_potential_revenue_formatted' => 'R$ ' . number_format($totalPotentialRevenue, 2, ',', '.'),
            'total_units' => $totalUnits,
            'total_potential_profit' => $totalPotentialRevenue - $totalStockValue,
            'total_potential_profit_formatted' => 'R$ ' . number_format($totalPotentialRevenue - $totalStockValue, 2, ',', '.'),
            'average_profit_margin' => round($averageProfitMargin, 2),
            'average_profit_margin_formatted' => number_format($averageProfitMargin, 2, ',', '.') . '%',
        ];
    }

    /**
     * Get insights about the inventory
     */
    private function getInsights(): array
    {
        $items = $this->collection;
        $summary = $this->getSummaryData();
        $statusDistribution = $this->getStockStatusDistribution();

        $insights = [];

        // Stock alerts
        if ($statusDistribution['out_of_stock'] > 0) {
            $insights[] = [
                'type' => 'alert',
                'level' => 'danger',
                'message' => "Atenção: {$statusDistribution['out_of_stock']} produtos sem estoque",
                'action' => 'Reabastecer produtos em falta',
            ];
        }

        if ($statusDistribution['low_stock'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'level' => 'warning',
                'message' => "Aviso: {$statusDistribution['low_stock']} produtos com estoque baixo",
                'action' => 'Verificar necessidade de reabastecimento',
            ];
        }

        // Profit margin insights
        if ($summary['average_profit_margin'] < 20) {
            $insights[] = [
                'type' => 'business',
                'level' => 'info',
                'message' => "Margem de lucro média baixa: {$summary['average_profit_margin_formatted']}",
                'action' => 'Revisar estratégia de preços',
            ];
        }

        // High value inventory
        if ($summary['total_stock_value'] > 100000) {
            $insights[] = [
                'type' => 'financial',
                'level' => 'info',
                'message' => "Alto valor em estoque: {$summary['total_stock_value_formatted']}",
                'action' => 'Monitorar giro do estoque',
            ];
        }

        return $insights;
    }

    /**
     * Calculate percentage
     */
    private function calculatePercentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0;
    }
}
