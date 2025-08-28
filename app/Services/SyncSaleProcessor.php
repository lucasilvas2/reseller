<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSaleProcessor implements \App\Contracts\SaleProcessor
{
    /**
     * Processar uma venda
     *
     * @param \App\Models\Sale $sale
     * @return \App\Enums\SaleEnum Status da venda após processamento
     */
    public function process(\App\Models\Sale $sale): string
    {
        try {
            // ✅ Transação com timeout para evitar locks longos
            DB::transaction(function () use ($sale) {
                // Processar cada OrderItem individualmente (sem status granular)
                foreach ($sale->orderItems as $item) {
                    $this->processOrderItem($item);
                }
            }, 5); // 5 tentativas máximo

            // Verificar se houve falhas usando abordagem híbrida
            if ($sale->hasUnresolvedFailures()) {
                $finalStatus = 'failed';
            } else {
                $finalStatus = 'completed';
            }

            $sale->update(['status' => $finalStatus]);

            Log::info('Sale processed synchronously', [
                'sale_id' => $sale->id,
                'final_status' => $finalStatus,
                'processing_summary' => $sale->getProcessingSummary()
            ]);

            return $finalStatus;
        } catch (\Exception $e) {
            Log::error('Synchronous sale processing failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);

            $sale->update(['status' => 'failed']);
            return 'failed';
        }
    }

    public function retry(int $saleId): bool
    {
        $sale = Sale::find($saleId);

        if (!$sale || !$sale->canRetry()) {
            return false;
        }

        // Reset failed items para pending
        $sale->orderItems()->failed()->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        $result = $this->process($sale);
        return $result === 'completed';
    }

    public function recoverOrphanedSales(): array
    {
        $pendingSales = Sale::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        $recoveredIds = [];

        foreach ($pendingSales as $sale) {
            if ($this->process($sale) === 'completed') {
                $recoveredIds[] = $sale->id;
            }
        }

        return $recoveredIds;
    }


    private function processOrderItem(OrderItem $item): void
    {
        try {
            // 🔒 LOCK no Product para prevenir race conditions
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception('Product not found for item ID: ' . $item->id);
            }

            // ✅ Calcular estoque atual baseado em StockMovements
            $availableStock = $product->getCurrentStock();
            if ($availableStock < $item->quantity) {
                // Create failure record using hybrid approach
                $item->sale->createFailure(
                    $item,
                    'insufficient_stock',
                    "Estoque insuficiente. Disponível: {$availableStock}, Solicitado: {$item->quantity}",
                    ['available' => $availableStock, 'requested' => $item->quantity]
                );

                // Set sale to failed
                $item->sale->update(['status' => 'failed']);
                return;
            }

            // ✅ Criar movimento de estoque de saída (estrutura consolidada)
            StockMovement::create([
                'product_id' => $item->product_id,
                'store_id' => $item->sale->store_id,
                'type' => 'out',
                'quantity' => $item->quantity,
                'description' => "Venda #{$item->sale_id} - Item #{$item->id}",
                'user_id' => $item->sale->user->id ?? 1,
                'sale_id' => $item->sale_id,
                'order_item_id' => $item->id,
            ]);

            Log::info('OrderItem processed successfully', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity
            ]);

        }
        catch (\Exception $e) {
            // Create failure record using hybrid approach
            $item->sale->createFailure(
                $item,
                'processing_error',
                $e->getMessage(),
                ['error_code' => $e->getCode()]
            );

            // Set sale to failed
            $item->sale->update(['status' => 'failed']);

            Log::error('OrderItem processing failed', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
