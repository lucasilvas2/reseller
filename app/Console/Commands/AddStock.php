<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class AddStock extends Command
{
    protected $signature = 'stock:add {product_sku_id} {quantity} {--description=Adição de estoque via comando}';
    protected $description = 'Adicionar estoque a um produto';

    public function handle()
    {
        $productSkuId = $this->argument('product_sku_id');
        $quantity = (int) $this->argument('quantity');
        $description = $this->option('description');

        $productSku = ProductVariant::find($productSkuId);

        if (!$productSku) {
            $this->error("❌ Produto SKU ID {$productSkuId} não encontrado!");
            return 1;
        }

        $currentStock = $productSku->getCurrentStock();

        // Criar movimento de entrada
        StockMovement::create([
            'product_sku_id' => $productSkuId,
            'type' => 'in',
            'quantity' => $quantity,
            'user_id' => 1,
            'store_id' => $productSku->store_id ?? 1,
            'description' => $description,
        ]);

        $newStock = $productSku->getCurrentStock();

        $this->info("✅ Estoque adicionado com sucesso!");
        $this->table(['Info', 'Valor'], [
            ['Produto SKU', $productSku->sku],
            ['Estoque Anterior', $currentStock],
            ['Quantidade Adicionada', $quantity],
            ['Estoque Atual', $newStock],
        ]);

        return 0;
    }
}
