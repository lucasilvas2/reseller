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
use Illuminate\Support\Facades\Log;

class TestSalesQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sales-queue {--create-data} {--processor=queued}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sales queue processing end-to-end';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->checkConfiguration()) {
            return 1;
        }

        if ($this->option('create-data')) {
            $this->createTestData();
        }

        // ✅ Forçar processador baseado no parâmetro --processor
        $processorType = $this->option('processor');

        if ($processorType === 'queued') {
            // Forçar QueuedSaleProcessor
            app()->bind(\App\Contracts\SaleProcessor::class, function ($app) {
                return new \App\Services\QueuedSaleProcessor();
            });
            $this->info('🔄 Forçando uso do QueuedSaleProcessor...');
        } elseif ($processorType === 'sync') {
            // Forçar SyncSaleProcessor
            app()->bind(\App\Contracts\SaleProcessor::class, function ($app) {
                return new \App\Services\SyncSaleProcessor();
            });
            $this->info('🔄 Forçando uso do SyncSaleProcessor...');
        }

        $saleProcessor = app(\App\Contracts\SaleProcessor::class);

        $this->line('📋 Processador em uso: ' . get_class($saleProcessor));

        $this->runSalesTest($saleProcessor);

        return 0;
    }

    /**
     * Verificar configuração
     */
    private function checkConfiguration(): bool
    {
        $this->info('🔍 Verificando configuração...');

        // Verificar queue connection
        $queueConnection = config('queue.default');
        $this->line("Queue Connection: {$queueConnection}");

        // ✅ Permitir database queue para testes também
        if (!in_array($queueConnection, ['sqs', 'database'])) {
            $this->error('❌ Queue connection deve ser "sqs" ou "database" para este teste');
            return false;
        }

        // Verificar AWS endpoint (LocalStack)
        $endpoint = config('queue.connections.sqs.endpoint');
        $this->line("AWS Endpoint: " . ($endpoint ?: 'padrão'));

        // Verificar filas configuradas
        $queues = config('sales.queues');
        $this->line("Filas configuradas:");
        foreach ($queues as $name => $queue) {
            $this->line("  - {$name}: {$queue}");
        }

        $this->info('✅ Configuração verificada');
        return true;
    }

    /**
     * Criar dados de teste
     */
    private function createTestData(): void
    {
        $this->info('📝 Criando dados de teste...');

        DB::transaction(function () {
            // Verificar se existe store
            $store = \App\Models\Store::first();
            if (!$store) {
                $store = \App\Models\Store::create([
                    'name' => 'Loja Teste',
                    'email' => 'loja@teste.com',
                    'phone_number' => '11999999999',
                ]);
            }

            // Verificar se existe user
            $user = \App\Models\User::first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => 'Usuário Teste',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                    'store_id' => $store->id,
                ]);
            }

            // Criar cliente se não existir
            $client = Client::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ]
            );

            // Criar produto SKU se não existir
            $product = \App\Models\Products::firstOrCreate(
                ['name' => 'Produto Teste'],
                [
                    'name' => 'Produto Teste',
                    'description' => 'Produto para teste',
                    'brand_id' => 1,
                    'store_id' => $store->id,
                ]
            );

            $productSku = ProductsSku::firstOrCreate(
                ['sku' => 'TEST-001'],
                [
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'cost_price' => 10.00,
                    'sale_price' => 20.00,
                ]
            );

            // ✅ Garantir que há estoque através de StockMovement
            $currentStock = $productSku->getCurrentStock();
            if ($currentStock < 100) {
                StockMovement::create([
                    'product_sku_id' => $productSku->id,
                    'type' => 'in',
                    'quantity' => 100,
                    'user_id' => $user->id,
                    'store_id' => $store->id,
                    'description' => 'Estoque inicial para teste de queue',
                ]);
            }

            $this->line("✅ Cliente criado: {$client->user->name} (ID: {$client->id})");
            $this->line("✅ Produto SKU criado: {$productSku->sku} (ID: {$productSku->id})");
        });
    }

    /**
     * Executar teste de vendas
     */
    private function runSalesTest(SaleProcessor $saleProcessor): void
    {
        $this->info('🧪 Executando teste de venda...');

        try {
            // Criar venda de teste
            $sale = $this->createTestSale();
            $this->line("✅ Venda criada: ID {$sale->id}");

            // Processar usando o processador configurado
            $this->info('⚡ Processando venda...');
            $status = $saleProcessor->process($sale);
            $this->line("📊 Status inicial: {$status}");

            // Se for processamento em fila, aguardar e verificar status
            if ($status === 'processing') {
                $this->monitorSaleProcessing($sale->id);
            }

            // Verificar resultado final
            $this->checkFinalResult($sale->id);

        } catch (\Exception $e) {
            $this->error("❌ Erro durante o teste: {$e->getMessage()}");
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
        }
    }

    /**
     * Criar venda de teste
     */
    private function createTestSale(): Sale
    {
        // Buscar qualquer cliente disponível
        $client = Client::with('user')->first();
        $productSku = ProductsSku::where('sku', 'TEST-001')->first();

        if (!$client || !$productSku) {
            throw new \Exception('Dados de teste não encontrados. Use --create-data');
        }

        // Criar venda
        $sale = Sale::create([
            'user_id' => $client->user_id,
            'client_id' => $client->id,
            'store_id' => $client->store_id,
            'status' => 'pending',
            'total_amount' => 40.00,
            'notes' => 'Venda de teste - ' . now(),
        ]);        // Criar itens da venda
        OrderItem::create([
            'sale_id' => $sale->id,
            'product_sku_id' => $productSku->id,
            'quantity' => 2,
            'unit_price' => 20.00,
            'total_price' => 40.00,
            'status' => 'pending',
        ]);

        return $sale;
    }

    /**
     * Monitorar processamento da venda
     */
    private function monitorSaleProcessing(int $saleId): void
    {
        $this->info('👀 Monitorando processamento...');

        $maxAttempts = 30; // 30 * 2 = 60 segundos max
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            sleep(2);

            $sale = Sale::find($saleId);
            $this->line("   Tentativa {$attempt}: Status = {$sale->status}");

            if (in_array($sale->status, ['completed', 'failed'])) {
                break;
            }

            // Mostrar progresso
            $this->output->write('.');
        }

        $this->newLine();
    }

    /**
     * Verificar resultado final
     */
    private function checkFinalResult(int $saleId): void
    {
        $this->info('🔍 Verificando resultado final...');

        $sale = Sale::with('orderItems')->find($saleId);

        $this->table(
            ['Campo', 'Valor'],
            [
                ['Sale ID', $sale->id],
                ['Status', $sale->status],
                ['Total Amount', 'R$ ' . number_format($sale->total_amount, 2, ',', '.')],
                ['Order Items', $sale->orderItems->count()],
                ['Items Completed', $sale->orderItems->where('status', 'completed')->count()],
                ['Items Canceled', $sale->orderItems->where('status', 'canceled')->count()],
                ['Items Pending', $sale->orderItems->where('status', 'pending')->count()],
            ]
        );

        // Status final
        if ($sale->status === 'completed') {
            $this->info('🎉 TESTE CONCLUÍDO COM SUCESSO!');
        } else {
            $this->warn("⚠️  Teste finalizado com status: {$sale->status}");

            // Mostrar erros se houver
            $canceledItems = $sale->orderItems->where('status', 'canceled');
            if ($canceledItems->count() > 0) {
                $this->warn('⚠️  Itens cancelados:');
                foreach ($canceledItems as $item) {
                    $this->line("   - Item {$item->id}: {$item->error_message}");
                }
            }
        }

        // Logs recentes
        $this->info('📝 Logs recentes (últimas 10 linhas):');
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = explode("\n", file_get_contents($logFile));
            $recentLogs = array_slice($logs, -10);
            foreach ($recentLogs as $log) {
                if (trim($log)) {
                    $this->line("   " . trim($log));
                }
            }
        }
    }
}
