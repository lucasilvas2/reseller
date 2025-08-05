<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Health check dos workers a cada 5 minutos (só em produção)
if (app()->environment('production')) {
    Schedule::command('queue:health-check')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
