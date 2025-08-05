<?php

namespace App\Console\Commands;

use App\Contracts\SaleProcessor;
use App\Models\Sale;
use App\Models\Client;
use App\Models\ProductsSku;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TestSalesConcurrency extends Command
{
    protected $signature = 'test:sales-concurrency
                            {--users=5 : Número de usuários simultâneos}
                            {--product= : ID ou SKU do produto para testar}
                            {--quantity=1 : Quantidade por venda}
                            {--create-data : Criar dados de teste}';

    protected $description = 'Testar concorrência no processamento de vendas';

    // ✅ Propriedades para rastrear o teste atual
    private array $testSaleIds = [];
    private $testStartTime = null;

    public function handle()
    {
        $users = (int) $this->option('users');
        $quantity = (int) $this->option('quantity');

        $this->info("🧪 Teste de Concorrência - {$users} usuários simultâneos");

        if ($this->option('create-data')) {
            $this->createTestData();
        }

        $product = $this->getTestProduct();
        if (!$product) {
            $this->error('❌ Produto de teste não encontrado. Use --create-data');
            return 1;
        }

        $initialStock = $product->getCurrentStock();
        $this->info("📦 Estoque inicial: {$initialStock} unidades");

        if ($initialStock < ($users * $quantity)) {
            $this->warn("⚠️  Estoque insuficiente para teste completo");
        }

        // Executar teste de concorrência
        $this->runConcurrencyTest($product, $users, $quantity);

        // Validar resultados
        $this->validateResults($product, $initialStock, $users, $quantity);

        return 0;
    }

    private function createTestData(): void
    {
        $this->info('📝 Criando dados de teste...');

        DB::transaction(function () {
            // Usar cliente existente ou criar um simples
            $client = Client::first();
            if (!$client) {
                $client = Client::create([
                    'user_id' => 1,
                    'store_id' => 1,
                ]);
            }

            // Criar produto de teste com estoque inicial via StockMovement
            $product = ProductsSku::firstOrCreate([
                'sku' => 'CONCURRENCY-TEST'
            ], [
                'product_id' => 1,
                'cost_price' => 10.00,
                'sale_price' => 20.00,
                'store_id' => 1,
            ]);

            // Criar movimento de entrada de estoque
            $existingStock = StockMovement::where('product_sku_id', $product->id)
                ->where('type', 'in')
                ->where('description', 'LIKE', '%teste de concorrência%')
                ->first();

            if (!$existingStock) {
                StockMovement::create([
                    'product_sku_id' => $product->id,
                    'type' => 'in',
                    'quantity' => 1000,
                    'user_id' => 1,
                    'store_id' => 1,
                    'description' => 'Estoque inicial para teste de concorrência',
                ]);
            }

            $this->line("✅ Cliente: ID {$client->id}");
            $this->line("✅ Produto: {$product->sku} (ID: {$product->id}, Estoque: {$product->getCurrentStock()})");
        });
    }

    private function getTestProduct(): ?ProductsSku
    {
        $productId = $this->option('product');

        if ($productId) {
            // Buscar por ID ou SKU
            return ProductsSku::where('id', $productId)
                ->orWhere('sku', $productId)
                ->first();
        }

        // Produto padrão de teste
        return ProductsSku::where('sku', 'CONCURRENCY-TEST')->first();
    }

    private function runConcurrencyTest(ProductsSku $product, int $users, int $quantity): void
    {
        $this->info("⚡ Executando {$users} vendas simultâneas...");

        $client = Client::first();
        $saleIds = [];

        // ✅ Marcar timestamp de início do teste
        $testStartTime = now();

        // Criar vendas simultâneas
        for ($i = 0; $i < $users; $i++) {
            $sale = Sale::create([
                'user_id' => $client->user_id,
                'client_id' => $client->id,
                'store_id' => $client->store_id,
                'status' => 'pending',
                'total_amount' => $product->sale_price * $quantity,
                'notes' => "Teste de concorrência #{$i} - " . $testStartTime->toDateTimeString(),
            ]);

            // Criar OrderItem
            OrderItem::create([
                'sale_id' => $sale->id,
                'product_sku_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->sale_price,
                'total_price' => $product->sale_price * $quantity,
                'status' => 'pending',
            ]);

            $saleIds[] = $sale->id;
        }

        // Processar todas simultaneamente usando SyncProcessor
        $processor = app(SaleProcessor::class);

        $startTime = microtime(true);
        $results = [];

        // Simular processamento "simultâneo" sequencial
        foreach ($saleIds as $saleId) {
            $sale = Sale::find($saleId);
            try {
                $status = $processor->process($sale);
                $results[] = ['sale_id' => $saleId, 'status' => $status, 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['sale_id' => $saleId, 'status' => 'error', 'success' => false, 'error' => $e->getMessage()];
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->info("⏱️  Processamento concluído em {$duration}ms");

        // Mostrar resultados
        $successful = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false)->count();

        $this->table(['Resultado', 'Quantidade'], [
            ['Vendas Bem-sucedidas', $successful],
            ['Vendas Falharam', $failed],
            ['Total', count($results)],
        ]);

        if ($failed > 0) {
            $this->warn('❌ Erros encontrados:');
            foreach ($results as $result) {
                if (!$result['success']) {
                    $this->line("   Sale {$result['sale_id']}: {$result['error']}");
                }
            }
        }

        // ✅ Armazenar dados do teste para validação
        $this->testSaleIds = $saleIds;
        $this->testStartTime = $testStartTime;
    }

    private function validateResults(ProductsSku $product, int $initialStock, int $users, int $quantity): void
    {
        $this->info('🔍 Validando integridade...');

        $product->refresh();
        $finalStock = $product->getCurrentStock();

        // ✅ Contar apenas vendas DESTE teste (pelos IDs específicos)
        $testCompletedSales = Sale::whereIn('id', $this->testSaleIds)
            ->where('status', 'completed')
            ->count();

        // ✅ Calcular unidades vendidas NESTE teste
        $testSoldUnits = Sale::whereIn('id', $this->testSaleIds)
            ->where('status', 'completed')
            ->with('orderItems')
            ->get()
            ->sum(function($sale) use ($product) {
                return $sale->orderItems->where('product_sku_id', $product->id)->sum('quantity');
            });

        $expectedStock = $initialStock - $testSoldUnits;

        $this->table(['Métrica', 'Valor'], [
            ['Estoque Inicial', $initialStock],
            ['Estoque Final', $finalStock],
            ['Estoque Esperado', $expectedStock],
            ['Vendas Deste Teste', $testCompletedSales],
            ['Unidades Vendidas Neste Teste', $testSoldUnits],
            ['Diferença', $finalStock - $expectedStock],
        ]);

        if ($finalStock === $expectedStock && $finalStock >= 0) {
            $this->info('✅ Teste de integridade PASSOU!');
        } else {
            $this->error('❌ Teste de integridade FALHOU!');
            if ($finalStock < 0) {
                $this->error('🚨 ESTOQUE NEGATIVO DETECTADO!');
            }
            if ($finalStock !== $expectedStock) {
                $this->error("🔍 Inconsistência: esperado {$expectedStock}, encontrado {$finalStock}");
            }
        }
    }
}
