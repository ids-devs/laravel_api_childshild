<?php

namespace App\Jobs;

use App\Services\ClimateDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fetches climate data from OpenWeather, Tomorrow.io, INAM for all locations.
 * Scheduled: every hour via Laravel Scheduler.
 */
class FetchClimateDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    public function handle(ClimateDataService $service): void
    {
        Log::info('[FetchClimateDataJob] Starting climate data fetch');

        try {
            $service->fetchAllLocations();
            Log::info('[FetchClimateDataJob] Climate data fetch completed');
        } catch (\Throwable $e) {
            Log::error('[FetchClimateDataJob] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[FetchClimateDataJob] Failed permanently: ' . $exception->getMessage());
    }
}
