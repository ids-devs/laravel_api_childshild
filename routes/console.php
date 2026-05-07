<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchClimateDataJob;
use App\Jobs\CalculateRiskScoresJob;

/*
|--------------------------------------------------------------------------
| ChildShield Climate AI — Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Fetch climate data every hour (e.g. :00 minutes)
Schedule::job(new FetchClimateDataJob, 'default')
    ->hourly()
    ->withoutOverlapping()
    ->name('fetch-climate-data')
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('FetchClimateDataJob scheduled run failed'));

// Calculate risk scores every hour, 5 minutes after climate fetch
Schedule::job(new CalculateRiskScoresJob, 'default')
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->name('calculate-risk-scores');

// Clean up expired USSD sessions daily
Schedule::command('ussd:cleanup-sessions')
    ->daily()
    ->name('cleanup-ussd-sessions');

// Generate daily summary report (optional)
Schedule::command('reports:daily-summary')
    ->dailyAt('07:00')
    ->timezone('Africa/Maputo')
    ->name('daily-summary-report');
