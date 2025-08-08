<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QueueHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue workers health and restart if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking queue workers health...');

        $checks = [
            'workers_running' => $this->checkWorkersRunning(),
            'queue_depth' => $this->checkQueueDepth(),
            'failed_jobs' => $this->checkFailedJobs(),
            'old_jobs' => $this->checkOldJobs(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['healthy']);

        if ($allHealthy) {
            $this->info('✅ All queue workers are healthy!');
            return 0;
        }

        $this->warn('⚠️  Issues detected with queue workers:');
        
        foreach ($checks as $checkName => $result) {
            if (!$result['healthy']) {
                $this->error("❌ {$checkName}: {$result['message']}");
                
                if (isset($result['action'])) {
                    $this->line("   🔧 Action: {$result['action']}");
                }
            } else {
                $this->info("✅ {$checkName}: {$result['message']}");
            }
        }

        // Auto-repair se configurado
        if (config('sales.auto_repair', false)) {
            $this->attemptAutoRepair($checks);
        }

        return $allHealthy ? 0 : 1;
    }

    /**
     * Verificar se workers estão rodando
     */
    private function checkWorkersRunning(): array
    {
        $processes = shell_exec('ps aux | grep "queue:work" | grep -v grep') ?: '';
        $runningWorkers = count(array_filter(explode("\n", trim($processes))));
        
        $expectedWorkers = count(config('sales.workers', []));
        
        if ($runningWorkers >= $expectedWorkers) {
            return [
                'healthy' => true,
                'message' => "Workers running: {$runningWorkers}/{$expectedWorkers}"
            ];
        }

        return [
            'healthy' => false,
            'message' => "Only {$runningWorkers}/{$expectedWorkers} workers running",
            'action' => 'Run: php artisan queue:workers start'
        ];
    }

    /**
     * Verificar profundidade das filas
     */
    private function checkQueueDepth(): array
    {
        try {
            // Para SQS, verificamos através de jobs table se existe
            $pendingJobs = DB::table('jobs')->count();
            
            if ($pendingJobs > 1000) {
                return [
                    'healthy' => false,
                    'message' => "High queue depth: {$pendingJobs} pending jobs",
                    'action' => 'Consider scaling workers or investigate bottlenecks'
                ];
            }

            return [
                'healthy' => true,
                'message' => "Queue depth normal: {$pendingJobs} pending jobs"
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => true,
                'message' => 'Queue depth check skipped (SQS or no jobs table)'
            ];
        }
    }

    /**
     * Verificar jobs falhados
     */
    private function checkFailedJobs(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>', now()->subHour())
                ->count();
            
            if ($failedJobs > 10) {
                return [
                    'healthy' => false,
                    'message' => "High recent failures: {$failedJobs} failed jobs in last hour",
                    'action' => 'Check logs and investigate failing jobs'
                ];
            }

            return [
                'healthy' => true,
                'message' => "Failed jobs normal: {$failedJobs} in last hour"
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => true,
                'message' => 'Failed jobs check skipped (no failed_jobs table)'
            ];
        }
    }

    /**
     * Verificar jobs antigos
     */
    private function checkOldJobs(): array
    {
        try {
            $oldJobs = DB::table('jobs')
                ->where('created_at', '<', now()->subHours(6))
                ->count();
            
            if ($oldJobs > 0) {
                return [
                    'healthy' => false,
                    'message' => "Old jobs detected: {$oldJobs} jobs older than 6 hours",
                    'action' => 'Investigate stuck jobs or worker performance'
                ];
            }

            return [
                'healthy' => true,
                'message' => 'No old jobs detected'
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => true,
                'message' => 'Old jobs check skipped (no jobs table)'
            ];
        }
    }

    /**
     * Tentar reparação automática
     */
    private function attemptAutoRepair(array $checks): void
    {
        $this->info('🔧 Attempting auto-repair...');

        if (!$checks['workers_running']['healthy']) {
            $this->line('   Restarting workers...');
            $this->call('queue:workers', ['action' => 'restart']);
        }

        Log::info('Queue health check completed with auto-repair', [
            'checks' => $checks,
            'timestamp' => now()
        ]);
    }
}
