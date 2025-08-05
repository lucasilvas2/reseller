<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\ProductsSku;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateSalesImplementation extends Command
{
    protected $signature = 'sales:validate-implementation {--fix : Corrigir inconsistências encontradas}';
    protected $description = 'Validar integridade da implementação de vendas';

    public function handle()
    {
        $this->info('🔍 Validando implementação de vendas...');
        $issues = [];

        // 1. Verificar consistência de status
        $issues = array_merge($issues, $this->checkStatusConsistency());

        // 2. Verificar integridade do estoque
        $issues = array_merge($issues, $this->checkStockIntegrity());

        // 3. Verificar orphaned records
        $issues = array_merge($issues, $this->checkOrphanedRecords());

        // 4. Verificar performance
        $issues = array_merge($issues, $this->checkPerformanceIssues());

        if (empty($issues)) {
            $this->info('✅ Implementação está íntegra!');
            return 0;
        }

        $this->error('🚨 Encontrados ' . count($issues) . ' problemas:');
        foreach ($issues as $issue) {
            $this->line("   • {$issue}");
        }

        if ($this->option('fix')) {
            $this->info('🔧 Corrigindo problemas...');
            $this->fixIssues();
        }

        return count($issues);
    }

    private function checkStatusConsistency(): array
    {
        $issues = [];

        // Vendas com status inconsistente
        $inconsistentSales = Sale::whereHas('orderItems', function($q) {
            $q->where('status', 'completed');
        })->where('status', '!=', 'completed')->count();

        if ($inconsistentSales > 0) {
            $issues[] = "{$inconsistentSales} vendas com status inconsistente";
        }

        // OrderItems órfãos (sem venda)
        $orphanedItems = OrderItem::whereDoesntHave('sale')->count();
        if ($orphanedItems > 0) {
            $issues[] = "{$orphanedItems} itens órfãos encontrados";
        }

        return $issues;
    }

    private function checkStockIntegrity(): array
    {
        $issues = [];

        // Produtos com estoque negativo (baseado em StockMovements)
        $negativeStockQuery = "
            SELECT product_sku_id
            FROM stock_movements
            GROUP BY product_sku_id
            HAVING SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) < 0
        ";

        $negativeStockProducts = DB::table('products_skus')
            ->whereIn('id', function($query) {
                $query->select('product_sku_id')
                      ->from('stock_movements')
                      ->groupBy('product_sku_id')
                      ->havingRaw('SUM(CASE WHEN type = "in" THEN quantity ELSE -quantity END) < 0');
            })->count();

        if ($negativeStockProducts > 0) {
            $issues[] = "{$negativeStockProducts} produtos com estoque negativo";
        }

        // StockMovements órfãos (com sale_id mas sem Sale válida)
        $orphanedMovements = StockMovement::whereNotNull('sale_id')
            ->whereDoesntHave('sale')
            ->count();

        if ($orphanedMovements > 0) {
            $issues[] = "{$orphanedMovements} movimentos de estoque órfãos";
        }

        return $issues;
    }

    private function checkOrphanedRecords(): array
    {
        $issues = [];

        // Vendas pendentes há muito tempo
        $oldPendingSales = Sale::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        if ($oldPendingSales > 0) {
            $issues[] = "{$oldPendingSales} vendas pendentes há mais de 24h";
        }

        return $issues;
    }

    private function checkPerformanceIssues(): array
    {
        $issues = [];

        // Vendas com muitos itens (possível problema de performance)
        $heavySales = Sale::withCount('orderItems')
            ->having('order_items_count', '>', 100)
            ->count();

        if ($heavySales > 0) {
            $issues[] = "{$heavySales} vendas com mais de 100 itens (possível problema de performance)";
        }

        return $issues;
    }

    private function fixIssues(): void
    {
        // Corrigir status inconsistentes
        $this->line('   📊 Corrigindo status inconsistentes...');
        $sales = Sale::with('orderItems')->get();
        foreach ($sales as $sale) {
            $sale->updateStatusFromItems();
        }

        // Limpar registros órfãos
        $this->line('   🧹 Limpando registros órfãos...');
        OrderItem::whereDoesntHave('sale')->delete();

        StockMovement::whereNotNull('sale_id')
            ->whereDoesntHave('sale')
            ->delete();

        $this->info('✅ Correções aplicadas!');
    }
}
