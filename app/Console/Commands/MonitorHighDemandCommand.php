<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonitorHighDemandCommand extends Command
{
    protected $signature = 'sales:monitor-high-demand
                            {--interval=5 : Interval in seconds for monitoring}
                            {--duration=300 : Duration in seconds to monitor}';

    protected $description = 'Monitor system performance during high demand periods';

    public function handle(): int
    {
        $interval = $this->option('interval');
        $duration = $this->option('duration');
        $endTime = time() + $duration;

        $this->info("🚀 Starting high demand monitoring for {$duration} seconds...");
        $this->newLine();

        while (time() < $endTime) {
            $this->displayMetrics();
            sleep($interval);
        }

        $this->info('✅ Monitoring completed');
        return 0;
    }

    private function displayMetrics(): void
    {
        $timestamp = now()->format('H:i:s');

        // Sales metrics
        $pendingSales = Sale::where('status', 'pending')->count();
        $processingSales = Sale::where('status', 'processing')->count();
        $completedLastMinute = Sale::where('status', 'completed')
            ->where('updated_at', '>', now()->subMinute())
            ->count();
        $failedLastMinute = Sale::where('status', 'failed')
            ->where('updated_at', '>', now()->subMinute())
            ->count();

        // OrderItem metrics
        $pendingItems = OrderItem::where('status', 'pending')->count();
        $processingItems = OrderItem::where('status', 'processing')->count();
        $failedItems = OrderItem::where('status', 'failed')
            ->where('updated_at', '>', now()->subMinute())
            ->count();

        // Database metrics
        $activeConnections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 'N/A';
        $runningQueries = DB::select("SHOW STATUS LIKE 'Threads_running'")[0]->Value ?? 'N/A';

        // Circuit breaker status
        $circuitBreakerActive = $this->getCircuitBreakerStatus();

        // Queue metrics (approximate)
        $queueDepth = $pendingSales + $processingItems;

        $this->table(['Metric', 'Value'], [
            ['⏰ Time', $timestamp],
            ['📈 Sales/min (completed)', $completedLastMinute],
            ['❌ Sales/min (failed)', $failedLastMinute],
            ['⏳ Pending Sales', $pendingSales],
            ['🔄 Processing Sales', $processingSales],
            ['⏳ Pending Items', $pendingItems],
            ['🔄 Processing Items', $processingItems],
            ['❌ Failed Items/min', $failedItems],
            ['🔗 DB Connections', $activeConnections],
            ['⚡ Running Queries', $runningQueries],
            ['🚨 Circuit Breakers', $circuitBreakerActive],
            ['📊 Queue Depth', $queueDepth],
        ]);

        $this->newLine();
    }

    private function getCircuitBreakerStatus(): string
    {
        if (!config('sales.high_demand.circuit_breaker.enabled', true)) {
            return 'Disabled';
        }

        // Get all product SKUs with active circuit breakers
        $activeBreakers = 0;
        $threshold = config('sales.high_demand.circuit_breaker.failure_threshold', 5);

        $productSkus = ProductVariant::pluck('id');

        foreach ($productSkus as $skuId) {
            $failures = Cache::get("circuit_breaker_failures:{$skuId}", 0);
            if ($failures >= $threshold) {
                $activeBreakers++;
            }
        }

        return $activeBreakers > 0 ? "🚨 {$activeBreakers} active" : '✅ All OK';
    }
}
