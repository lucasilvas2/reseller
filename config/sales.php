<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sales Processing Configuration
    |--------------------------------------------------------------------------
    */

    'queues' => [
        'high_priority' => env('QUEUE_SALES_HIGH_PRIORITY', 'sales-high-priority'),
        'retry' => env('QUEUE_SALES_RETRY', 'sales-retry'),
        'recovery' => env('QUEUE_SALES_RECOVERY', 'sales-recovery'),
    ],

    'timeouts' => [
        'processing' => env('SALES_PROCESSING_TIMEOUT', 300), // 5 minutos
        'orphaned_minutes' => env('SALES_ORPHANED_MINUTES', 10),
        'recovery_delay_seconds' => env('SALES_RECOVERY_DELAY', 5),
    ],

    'retry_policy' => [
        'max_attempts' => env('SALES_MAX_ATTEMPTS', 3),
        'backoff_seconds' => env('SALES_BACKOFF_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Start Workers Configuration
    |--------------------------------------------------------------------------
    */
    'workers' => [
        'sales-high' => [
            'connection' => env('QUEUE_CONNECTION', 'sqs'),
            'queue' => env('QUEUE_SALES_HIGH_PRIORITY', 'sales-high-priority'),
            'sleep' => 1,
            'tries' => 3,
            'max_jobs' => 1000,
            'timeout' => 300,
        ],
        'sales-retry' => [
            'connection' => env('QUEUE_CONNECTION', 'sqs'),
            'queue' => env('QUEUE_SALES_RETRY', 'sales-retry'),
            'sleep' => 3,
            'tries' => 3,
            'max_jobs' => 500,
            'timeout' => 300,
        ],
        'sales-recovery' => [
            'connection' => env('QUEUE_CONNECTION', 'sqs'),
            'queue' => env('QUEUE_SALES_RECOVERY', 'sales-recovery'),
            'sleep' => 5,
            'tries' => 2,
            'max_jobs' => 100,
            'timeout' => 300,
        ],
    ],
];
