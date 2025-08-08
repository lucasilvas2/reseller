<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\ProductVariant;
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
                // Processar cada OrderItem individualmente
                foreach ($sale->orderItems()->pending()->get() as $item) {
                    $this->processOrderItem($item);
                }
            }, 5); // 5 tentativas máximo

            // Atualizar status da venda baseado nos itens
            $finalStatus = $sale->updateStatusFromItems();

            Log::info('Sale processed synchronously', [
                'sale_id' => $sale->id,
                'final_status' => $finalStatus,
                'items_summary' => $sale->getItemsStatusSummary()
            ]);

            return $finalStatus;
        } catch (\Exception $e) {
            Log::error('Synchronous sale processing failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);

            $sale->update(['status' => 'failed']);  // ✅ Usar 'failed' em vez de 'canceled'
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
            $item->update(['status' => 'processing']);

            // 🔒 LOCK no ProductVariant para prevenir race conditions
            $productVariant = ProductVariant::where('id', $item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$productVariant) {
                throw new \Exception('Product Variant not found for item ID: ' . $item->id);
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

            Log::info('OrderItem processed successfully', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity
            ]);

        }
        catch (\Exception $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('OrderItem processing failed', [
                'order_item_id' => $item->id,
                'sale_id' => $item->sale_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
