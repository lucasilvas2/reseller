<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class CheckSaleStatus extends Command
{
    protected $signature = 'sale:status {sale_id}';
    protected $description = 'Verificar status de uma venda específica';

    public function handle()
    {
        $saleId = $this->argument('sale_id');

        $sale = Sale::with(['orderItems.productSku', 'client.user'])->find($saleId);

        if (!$sale) {
            $this->error("❌ Venda ID {$saleId} não encontrada!");
            return 1;
        }

        $this->info("📊 Status da Venda #{$sale->id}");

        // Informações básicas da venda
        $this->table(['Campo', 'Valor'], [
            ['ID da Venda', $sale->id],
            ['Status', $sale->status],
            ['Cliente', $sale->client->user->name ?? 'N/A'],
            ['Valor Total', 'R$ ' . number_format($sale->total_amount, 2, ',', '.')],
            ['Data Criação', $sale->created_at->format('d/m/Y H:i:s')],
            ['Última Atualização', $sale->updated_at->format('d/m/Y H:i:s')],
        ]);

        // Status dos itens
        $itemsSummary = $sale->getItemsStatusSummary();
        $this->info("🛒 Resumo dos Itens:");
        $this->table(['Status', 'Quantidade'], [
            ['Total', $itemsSummary['total']],
            ['Pendentes', $itemsSummary['pending']],
            ['Processando', $itemsSummary['processing']],
            ['Completados', $itemsSummary['completed']],
            ['Falharam', $itemsSummary['failed']],
        ]);

        // Detalhes dos itens
        if ($sale->orderItems->isNotEmpty()) {
            $this->info("📋 Detalhes dos Itens:");
            $itemsData = [];
            foreach ($sale->orderItems as $item) {
                $itemsData[] = [
                    $item->id,
                    $item->productSku->sku ?? 'N/A',
                    $item->quantity,
                    'R$ ' . number_format($item->unit_price, 2, ',', '.'),
                    $item->status,
                    $item->error_message ?: '-'
                ];
            }

            $this->table(['Item ID', 'SKU', 'Qtd', 'Preço Unit.', 'Status', 'Erro'], $itemsData);
        }

        // Movimentos de estoque relacionados
        $stockMovements = StockMovement::where('sale_id', $saleId)->get();
        if ($stockMovements->isNotEmpty()) {
            $this->info("📦 Movimentos de Estoque:");
            $movementsData = [];
            foreach ($stockMovements as $movement) {
                $movementsData[] = [
                    $movement->id,
                    $movement->productSku->sku ?? 'N/A',
                    $movement->type,
                    $movement->quantity,
                    $movement->description,
                    $movement->created_at->format('d/m/Y H:i:s')
                ];
            }

            $this->table(['Mov. ID', 'SKU', 'Tipo', 'Qtd', 'Descrição', 'Data'], $movementsData);
        }

        return 0;
    }
}
