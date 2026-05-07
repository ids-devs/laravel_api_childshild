<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\FetchClimateDataJob;
use App\Jobs\CalculateRiskScoresJob;
use App\Models\Alert;
use App\Models\UssdSession;
use Illuminate\Support\Facades\Log;

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

// Clean up stale USSD sessions daily
Schedule::call(function (): void {
    $deleted = UssdSession::query()
        ->where('is_active', false)
        ->orWhere('updated_at', '<', now()->subHours(12))
        ->delete();

    Log::info('[Scheduler] USSD sessions cleanup finished', ['deleted' => $deleted]);
})
    ->daily()
    ->name('cleanup-ussd-sessions');

// Generate daily summary report snapshot in logs (optional)
Schedule::call(function (): void {
    $summary = [
        'alerts_created_24h' => Alert::query()->where('created_at', '>=', now()->subDay())->count(),
        'alerts_sent_24h' => Alert::query()
            ->where('created_at', '>=', now()->subDay())
            ->where('status', 'sent')
            ->count(),
        'active_ussd_sessions' => UssdSession::query()->where('is_active', true)->count(),
    ];

    Log::info('[Scheduler] Daily summary', $summary);
})
    ->dailyAt('07:00')
    ->timezone('Africa/Maputo')
    ->name('daily-summary-report');
