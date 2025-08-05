<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ✅ Binding do SaleProcessor com formatação correta
        $this->app->bind(\App\Contracts\SaleProcessor::class, function ($app) {
            return config('app.env') === 'local'
                ? new \App\Services\SyncSaleProcessor()
                : new \App\Services\QueuedSaleProcessor();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Monitoring otimizado para produção
        if (app()->environment('production')) {
            // Só logs críticos em produção
            Queue::failing(function (JobFailed $event) {
                Log::error('Job failed permanently', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                    'attempts' => $event->job->attempts(),
                    'exception' => $event->exception->getMessage(),
                    'payload' => $event->job->payload(),
                ]);
            });
        }

        // ✅ Logs detalhados para desenvolvimento
        if (app()->environment('local', 'testing')) {
            Queue::before(function (JobProcessing $event) {
                Log::info('Processing job', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                    'attempts' => $event->job->attempts(),
                ]);
            });

            Queue::after(function (JobProcessed $event) {
                Log::info('Job completed', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                ]);
            });

            Queue::failing(function (JobFailed $event) {
                Log::error('Job failed in development', [
                    'job_id' => $event->job->getJobId(),
                    'queue' => $event->job->getQueue(),
                    'exception' => $event->exception->getMessage(),
                ]);
            });
        }
    }
}
