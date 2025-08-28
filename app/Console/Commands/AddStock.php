<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Console\Command;

class AddStock extends Command
{
    protected $signature = 'stock:add {product_id} {quantity} {--description=Adição de estoque via comando}';
    protected $description = 'Adicionar estoque a um produto';

    public function handle()
    {
        $productId = $this->argument('product_id');
        $quantity = (int) $this->argument('quantity');
        $description = $this->option('description');

        $product= Product::find($productId);

        if (!$productId) {
            $this->error("❌ Produto ID {$productId} não encontrado!");
            return 1;
        }

        $currentStock = $product->getCurrentStock();

        // Criar movimento de entrada
        StockMovement::create([
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => $quantity,
            'user_id' => 1,
            'store_id' => $product->store_id ?? 1,
            'description' => $description,
        ]);

        $newStock = $product->getCurrentStock();

        $this->info("✅ Estoque adicionado com sucesso!");
        $this->table(['Info', 'Valor'], [
            ['Produto SKU', $product->sku],
            ['Estoque Anterior', $currentStock],
            ['Quantidade Adicionada', $quantity],
            ['Estoque Atual', $newStock],
        ]);

        return 0;
    }
}
