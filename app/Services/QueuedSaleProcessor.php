<?php

namespace App\Services;

use App\Contracts\SaleProcessor;
use App\Enums\SaleEnum;
use App\Jobs\ProcessSaleJob;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class QueuedSaleProcessor implements SaleProcessor
{
    public function process(Sale $sale): string
    {
        try {
            ProcessSaleJob::dispatch($sale->id)
                ->onQueue(config('sales.queues.high_priority'));

            Log::info('Sale dispatched to queue', [
                'sale_id' => $sale->id,
                'queue' => config('sales.queues.high_priority')
            ]);

            return 'processing';

        } catch (\Exception $e) {
            Log::error('Failed to dispatch sale', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);

            $sale->update(['status' => 'failed']);  // ✅ Usar 'failed' em vez de 'canceled'
            return 'failed';
        }
    }

    public function retry(int $saleId): bool
    {
        try{
            $sale = Sale::find($saleId);

            if (!$sale || !$sale->canRetry()) {
                return false;
            }

            // Reset failed items para pending
            $sale->orderItems()->failed()->update([
                'status' => 'pending',
                'error_message' => null
            ]);

            ProcessSaleJob::dispatch($saleId)
                ->onQueue(config('sales.queues.retry'));

            $sale->update(['status' => 'processing']);

            Log::info('Sale retry dispatched', [
                'sale_id' => $saleId,
                'queue' => config('sales.queues.retry')
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry sale', [
                'sale_id' => $saleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function recoverOrphanedSales(): array
    {
        $orphanedMinutes = config('sales.timeouts.orphaned_minutes');
        $orphanedSales = Sale::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($orphanedMinutes))
            ->pluck('id')
            ->toArray();

        $delaySeconds = config('sales.timeouts.recovery_delay_seconds');

        foreach ($orphanedSales as $saleId) {
            ProcessSaleJob::dispatch($saleId)
                ->onQueue(config('sales.queues.recovery'))
                ->delay(now()->addSeconds($delaySeconds));
        }

        Log::info('Orphaned sales recovered', [
            'count' => count($orphanedSales),
            'sale_ids' => $orphanedSales,
            'queue' => config('sales.queues.recovery')
        ]);

        return $orphanedSales;
    }
}
