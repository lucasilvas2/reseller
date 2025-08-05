<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageQueueWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:workers {action : start|stop|restart|status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage queue workers (start|stop|restart|status)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'start':
                return $this->startWorkers();
            case 'stop':
                return $this->stopWorkers();
            case 'restart':
                return $this->restartWorkers();
            case 'status':
                return $this->checkStatus();
            default:
                $this->error("Invalid action. Use: start|stop|restart|status");
                return 1;
        }
    }

    /**
     * Iniciar workers
     */
    private function startWorkers(): int
    {
        if ($this->workersRunning()) {
            $this->warn('Workers are already running!');
            return 0;
        }

        $this->info('Starting queue workers...');

        $workersConfig = config('sales.workers', []);

        foreach ($workersConfig as $workerName => $config) {
            $this->startWorker($workerName, $config);
            $this->line("✓ Started worker: {$workerName}");
        }

        $this->info('All workers started successfully!');
        return 0;
    }

    /**
     * Parar workers
     */
    private function stopWorkers(): int
    {
        $this->info('Stopping queue workers...');

        // Graceful shutdown primeiro
        $this->call('queue:restart');
        sleep(2);

        // Force kill se necessário
        $processes = $this->getWorkerProcesses();
        if (!empty($processes)) {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows: usar taskkill
                exec('taskkill /F /IM php.exe /FI "WINDOWTITLE eq *queue:work*" 2>nul');
            } else {
                // Linux/Unix: usar pkill
                exec('pkill -f "queue:work"');
            }
            $this->line('✓ Force stopped remaining workers');
        }

        $this->info('All workers stopped!');
        return 0;
    }

    /**
     * Reiniciar workers
     */
    private function restartWorkers(): int
    {
        $this->info('Restarting queue workers...');

        $this->stopWorkers();
        sleep(1);
        $this->startWorkers();

        $this->info('Workers restarted successfully!');
        return 0;
    }

    /**
     * Verificar status dos workers
     */
    private function checkStatus(): int
    {
        $processes = $this->getWorkerProcesses();

        if (empty($processes)) {
            $this->warn('No queue workers are running');
            return 0;
        }

        $this->info('Running queue workers:');
        $this->table(
            ['PID', 'Queue', 'Status', 'Started'],
            collect($processes)->map(function ($process) {
                preg_match('/--queue=([^\s]+)/', $process, $queueMatches);
                preg_match('/\s+(\d+)\s+/', $process, $pidMatches);

                return [
                    'pid' => $pidMatches[1] ?? 'N/A',
                    'queue' => $queueMatches[1] ?? 'default',
                    'status' => 'Running',
                    'started' => 'N/A'
                ];
            })->toArray()
        );

        return 0;
    }

    /**
     * Verificar se workers estão rodando
     */
    private function workersRunning(): bool
    {
        return !empty($this->getWorkerProcesses());
    }

    /**
     * Obter processos dos workers
     */
    private function getWorkerProcesses(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: usar tasklist
            $output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV 2>nul') ?: '';
            $lines = explode("\n", trim($output));

            $workers = [];
            foreach ($lines as $line) {
                if (str_contains($line, 'queue:work')) {
                    $workers[] = $line;
                }
            }
            return $workers;
        } else {
            // Linux/Unix: usar ps
            $output = shell_exec('ps aux | grep "queue:work" | grep -v grep') ?: '';
            return array_filter(explode("\n", trim($output)));
        }
    }

    /**
     * Iniciar um worker específico
     */
    private function startWorker(string $name, array $config): void
    {
        $logFile = storage_path("logs/worker-{$name}.log");

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: usar start /B para background
            $command = sprintf(
                'start /B php %s queue:work %s --queue=%s --sleep=%d --tries=%d --max-jobs=%d --timeout=%d > %s 2>&1',
                base_path('artisan'),
                $config['connection'] ?? 'sqs',
                $config['queue'],
                $config['sleep'] ?? 3,
                $config['tries'] ?? 3,
                $config['max_jobs'] ?? 1000,
                $config['timeout'] ?? 300,
                $logFile
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
                $logFile
            );
        }

        exec($command);

        Log::info("Worker '{$name}' started manually", [
            'queue' => $config['queue'],
            'log_file' => $logFile
        ]);
    }
}
