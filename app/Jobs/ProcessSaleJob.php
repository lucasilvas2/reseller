<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Models\ProductsSku;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            // ✅ Transação com timeout para evitar locks longos
            DB::transaction(function () use ($sale) {
                foreach ($sale->orderItems()->pending()->get() as $item) {
                    $this->processOrderItem($item);
                }
            }, 5); // 5 tentativas máximo

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
            $productSku = ProductsSku::where('id', $item->product_sku_id)
                ->lockForUpdate()
                ->first();

            if (!$productSku) {
                throw new \Exception('Product SKU not found for item ID: ' . $item->id);
            }

            // ✅ Calcular estoque atual baseado em StockMovements
            $availableStock = $productSku->getCurrentStock();
            if ($availableStock < $item->quantity) {
                throw new \Exception("Estoque insuficiente. Disponível: {$availableStock}, Solicitado: {$item->quantity}");
            }

            // ✅ Criar movimento de estoque de saída (estrutura correta)
            StockMovement::create([
                'product_sku_id' => $item->product_sku_id,
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
                'product_sku_id' => $item->product_sku_id,
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
}
