<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueWorkerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Só iniciar workers em produção e se não estiver rodando via CLI/console
        if ($this->shouldStartWorkers()) {
            $this->startQueueWorkers();
        }
    }

    /**
     * Determinar se deve iniciar workers
     */
    private function shouldStartWorkers(): bool
    {
        return app()->environment('production') &&
               !app()->runningInConsole() &&
               !$this->workersAlreadyRunning();
    }

    /**
     * Verificar se workers já estão rodando
     */
    private function workersAlreadyRunning(): bool
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows: usar tasklist
                $processes = shell_exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul');
                return !empty(trim($processes)) && str_contains($processes, 'queue:work');
            } else {
                // Linux/Unix: usar ps
                $processes = shell_exec('ps aux | grep "queue:work" | grep -v grep');
                return !empty(trim($processes));
            }
        } catch (\Exception $e) {
            Log::warning('Could not check running workers', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Iniciar workers em background
     */
    private function startQueueWorkers(): void
    {
        try {
            $workersConfig = config('sales.workers', []);

            foreach ($workersConfig as $workerName => $config) {
                $this->startWorker($workerName, $config);
            }

            Log::info('Queue workers auto-started successfully');
        } catch (\Exception $e) {
            Log::error('Failed to auto-start queue workers', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Iniciar um worker específico
     */
    private function startWorker(string $name, array $config): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: usar start /B
            $command = sprintf(
                'start /B php %s queue:work %s --queue=%s --sleep=%d --tries=%d --max-jobs=%d --timeout=%d > %s 2>&1',
                base_path('artisan'),
                $config['connection'] ?? 'sqs',
                $config['queue'],
                $config['sleep'] ?? 3,
                $config['tries'] ?? 3,
                $config['max_jobs'] ?? 1000,
                $config['timeout'] ?? 300,
                storage_path("logs/worker-{$name}.log")
            );
        } else {
            // Linux/Unix: usar nohup
            $command = sprintf(
                'nohup php %s queue:work %s --queue=%s --sleep=%d --tries=%d --max-jobs=%d --timeout=%d > %s 2>&1 &',
                base_path('artisan'),
                $config['connection'] ?? 'sqs',
                $config['queue'],
                $config['sleep'] ?? 3,
                $config['tries'] ?? 3,
                $config['max_jobs'] ?? 1000,
                $config['timeout'] ?? 300,
                storage_path("logs/worker-{$name}.log")
            );
        }

        exec($command);

        Log::info("Worker '{$name}' started", [
            'queue' => $config['queue'],
            'command' => $command
        ]);
    }
}
