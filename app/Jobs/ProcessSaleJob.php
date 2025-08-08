<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Collection;

class ProcessSaleJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $timeout;
    public $tries;
    public $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $saleId
    ){
        $this->timeout = config('sales.timeouts.processing');
        $this->tries = config('sales.retry_policy.max_attempts');
        $this->backoff = config('sales.retry_policy.backoff_seconds');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sale  = Sale::with('orderItems.productSku')->find($this->saleId);

        if( !$sale ) {
            Log::error('Sale not found', ['sale_id' => $this->saleId]);
            return;
        }

        if(!in_array($sale->status, ['pending', 'processing'])){
            Log::info('Sale already processed or failed', [
                'sale_id' => $this->saleId,
                'status' => $sale->status
            ]);
            return;
        }

        try {
            $sale->update(['status' => 'processing']);

            // ✅ Transação otimizada para alta demanda
            if (config('sales.high_demand.batch_processing_enabled', true)) {
                $this->processSaleWithBatching($sale);
            } else {
                $this->processSaleSequential($sale);
            }

            // Atualizar status da venda baseado nos itens
            $finalStatus = $sale->updateStatusFromItems();

            Log::info('Sale processed successfully via queue', [
                'sale_id' => $sale->id,
                'final_status' => $finalStatus,
                'items_summary' => $sale->getItemsStatusSummary(),
                'queue' => config('sales.queues.high_priority')
            ]);

        } catch (\Exception $e) {
            $sale->update(['status' => 'failed']);

            Log::error('Sale processing failed via queue', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        $sale = Sale::find($this->saleId);

        if ($sale) {
            $sale->update(['status' => 'failed']);
        }

        Log::critical('Sale processing failed permanently', [
            'sale_id' => $this->saleId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    private function processOrderItem(OrderItem $item): void
    {
        try {
            $item->update(['status' => 'processing']);

            // 🔒 LOCK no ProductSku para prevenir race conditions
            $productVariant = ProductVariant::where('id', $item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$productVariant) {
                throw new \Exception('Product SKU not found for item ID: ' . $item->id);
            }

            // ✅ Calcular estoque atual baseado em StockMovements
            $availableStock = $productVariant->getCurrentStock();
            if ($availableStock < $item->quantity) {
                throw new \Exception("Estoque insuficiente. Disponível: {$availableStock}, Solicitado: {$item->quantity}");
            }

            // ✅ Criar movimento de estoque de saída (estrutura correta)
            StockMovement::create([
                'product_variant_id' => $item->product_variant_id,
                'store_id' => $item->sale->store_id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'description' => "Venda #{$item->sale_id} - Item #{$item->id}",
                'user_id' => $item->sale->user->id ?? 1,
                'sale_id' => $item->sale_id,
                'order_item_id' => $item->id,
            ]);

            $item->update(['status' => 'completed']);

            Log::info('OrderItem processed successfully in queue', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity
            ]);

        } catch (\Exception $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('OrderItem processing failed in queue', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🚀 Processamento com batching para alta demanda
     */
    private function processSaleWithBatching(Sale $sale): void
    {
        $pendingItems = $sale->orderItems()->pending()->get();

        // Agrupar itens por produto para reduzir locks
        $itemsByProduct = $pendingItems->groupBy('product_variant_id');

        foreach ($itemsByProduct as $productVariantId => $productItems) {
            $this->processProductBatch($productVariantId, $productItems);
        }
    }

    /**
     * 🔒 Processar lote de itens do mesmo produto (um lock por produto)
     */
    private function processProductBatch(int $productVariantId, $productItems): void
    {
        // Verificar circuit breaker
        if ($this->isCircuitBreakerOpen($productVariantId)) {
            $this->deferProductItems($productItems, 'Circuit breaker open');
            return;
        }

        try {
            DB::transaction(function () use ($productVariantId, $productItems) {
                // Configurar timeout de lock específico para alta demanda
                $lockTimeout = config('sales.high_demand.lock_timeout_seconds', 5);
                DB::statement("SET innodb_lock_wait_timeout = ?", [$lockTimeout]);

                // Um lock para todos os itens do mesmo produto
                $productVariant = ProductVariant::where('id', $productVariantId)
                    ->lockForUpdate()
                    ->first();

                if (!$productVariant) {
                    throw new \Exception("Product SKU {$productVariantId} not found");
                }

                // Calcular quantidade total necessária
                $totalQuantityNeeded = $productItems->sum('quantity');
                $availableStock = $productVariant->getCurrentStock();

                Log::info('Processing product batch', [
                    'product_variant_id' => $productVariantId,
                    'items_count' => $productItems->count(),
                    'total_quantity_needed' => $totalQuantityNeeded,
                    'available_stock' => $availableStock
                ]);

                if ($availableStock >= $totalQuantityNeeded) {
                    // Processar todos os itens do produto
                    foreach ($productItems as $item) {
                        $this->processSingleItemInBatch($item, $productVariant);
                    }
                } else {
                    // Processar parcialmente (FIFO) até o estoque acabar
                    $this->processPartialStock($productItems, $productVariant, $availableStock);
                }
            }, 3); // Menos tentativas para alta demanda

            // Reset circuit breaker em caso de sucesso
            $this->resetCircuitBreaker($productVariantId);

        } catch (\Exception $e) {
            // Registrar falha no circuit breaker
            $this->recordCircuitBreakerFailure($productVariantId);

            // Falhar todos os itens do produto
            foreach ($productItems as $item) {
                $this->failOrderItem($item, "Batch processing failed: " . $e->getMessage());
            }

            Log::error('Product batch processing failed', [
                'product_variant_id' => $productVariantId,
                'items_count' => $productItems->count(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 🔄 Processamento sequencial (método original)
     */
    private function processSaleSequential(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->orderItems()->pending()->get() as $item) {
                $this->processOrderItem($item);
            }
        }, 5);
    }

    /**
     * ⚡ Processar item individual dentro do batch
     */
    private function processSingleItemInBatch(OrderItem $item, ProductVariant $productVariant): void
    {
        $item->update(['status' => 'processing']);

        // Criar movimento de estoque
        StockMovement::create([
            'product_variant_id' => $item->product_variant_id,
            'store_id' => $item->sale->store_id,
            'type' => 'out',
            'quantity' => $item->quantity,
            'description' => "Venda #{$item->sale_id} - Item #{$item->id} (Batch)",
            'user_id' => $item->sale->user->id ?? 1,
            'sale_id' => $item->sale_id,
            'order_item_id' => $item->id,
        ]);

        $item->update(['status' => 'completed']);

        Log::debug('Item processed in batch', [
            'order_item_id' => $item->id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity
        ]);
    }

    /**
     * 📦 Processar estoque parcial (FIFO)
     */
    private function processPartialStock($productItems, ProductVariant $productVariant, int $availableStock): void
    {
        $remainingStock = $availableStock;

        foreach ($productItems as $item) {
            if ($remainingStock >= $item->quantity) {
                // Processar item completo
                $this->processSingleItemInBatch($item, $productVariant);
                $remainingStock -= $item->quantity;
            } else {
                // Falhar item por falta de estoque
                $this->failOrderItem($item, "Estoque insuficiente. Disponível: {$remainingStock}, Solicitado: {$item->quantity}");
            }
        }

        Log::info('Partial stock processing completed', [
            'product_variant_id' => $productVariant->id,
            'initial_stock' => $availableStock,
            'remaining_stock' => $remainingStock,
            'items_processed' => $productItems->count()
        ]);
    }

    /**
     * ❌ Falhar item individual
     */
    private function failOrderItem(OrderItem $item, string $reason): void
    {
        $item->update([
            'status' => 'failed',
            'error_message' => $reason
        ]);

        Log::warning('OrderItem failed in batch processing', [
            'order_item_id' => $item->id,
            'sale_id' => $item->sale_id,
            'reason' => $reason
        ]);
    }

    /**
     * 🔥 Circuit Breaker - Verificar se produto está em alta contenção
     */
    private function isCircuitBreakerOpen(int $productVariantId): bool
    {
        if (!config('sales.high_demand.circuit_breaker.enabled', true)) {
            return false;
        }

        $failures = Cache::get("circuit_breaker_failures:{$productVariantId}", 0);
        $threshold = config('sales.high_demand.circuit_breaker.failure_threshold', 5);

        return $failures >= $threshold;
    }

    /**
     * 📈 Registrar falha no circuit breaker
     */
    private function recordCircuitBreakerFailure(int $productVariantId): void
    {
        if (!config('sales.high_demand.circuit_breaker.enabled', true)) {
            return;
        }

        $key = "circuit_breaker_failures:{$productVariantId}";
        $failures = Cache::get($key, 0) + 1;
        $ttl = config('sales.high_demand.circuit_breaker.recovery_time', 300);

        Cache::put($key, $failures, $ttl);

        if ($failures >= config('sales.high_demand.circuit_breaker.failure_threshold', 5)) {
            Log::warning('Circuit breaker opened for product', [
                'product_variant_id' => $productVariantId,
                'failures' => $failures,
                'recovery_time' => $ttl
            ]);
        }
    }

    /**
     * ✅ Reset circuit breaker em caso de sucesso
     */
    private function resetCircuitBreaker(int $productVariantId): void
    {
        if (!config('sales.high_demand.circuit_breaker.enabled', true)) {
            return;
        }

        Cache::forget("circuit_breaker_failures:{$productVariantId}");
    }

    /**
     * ⏰ Adiar itens para processamento posterior
     */
    private function deferProductItems($productItems, string $reason): void
    {
        foreach ($productItems as $item) {
            // Adiar para fila de retry com delay
            ProcessSaleJob::dispatch($item->sale_id)
                ->onQueue(config('sales.queues.retry'))
                ->delay(now()->addMinutes(5));
        }

        Log::info('Product items deferred', [
            'items_count' => $productItems->count(),
            'reason' => $reason
        ]);
    }
}
