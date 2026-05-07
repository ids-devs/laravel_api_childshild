<?php

namespace App\Jobs;

use App\Services\RiskEngineService;
use App\Services\AlertDispatchService;
use App\Models\RiskScore;
use App\Models\Alert;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Calculates risk scores for all locations and auto-triggers alerts for
 * high/critical risks that haven't been alerted in the last 24h.
 * Scheduled: every hour (after FetchClimateDataJob completes).
 */
class CalculateRiskScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 2;

    public function handle(RiskEngineService $engine, AlertDispatchService $dispatcher): void
    {
        Log::info('[CalculateRiskScoresJob] Starting risk calculation');

        $engine->calculateAllLocations();

        // Auto-dispatch alerts for high/critical risks
        $this->autoDispatchAlerts($dispatcher);

        Log::info('[CalculateRiskScoresJob] Risk calculation completed');
    }

    private function autoDispatchAlerts(AlertDispatchService $dispatcher): void
    {
        // Find high/critical risks without recent alerts
        $criticalScores = RiskScore::latestPerLocation()
            ->whereIn('risk_level', ['high', 'critical'])
            ->whereDoesntHave('alerts', function ($q) {
                $q->where('created_at', '>=', now()->subHours(
                    // Critical: alert every 12h, High: alert every 24h
                    RiskScore::where('risk_level', 'critical')->exists() ? 12 : 24
                ));
            })
            ->with('location')
            ->get();

        foreach ($criticalScores as $score) {
            // Auto-create and dispatch alert
            $alert = Alert::create([
                'location_id'   => $score->location_id,
                'risk_score_id' => $score->id,
                'risk_type_id'  => $score->risk_type_id,
                'risk_level'    => $score->risk_level,
                'message_pt'    => $score->recommendation,
                'channel'       => 'both',
                'status'        => 'pending',
            ]);

            $dispatcher->dispatch($alert);

            Log::info("[CalculateRiskScoresJob] Auto-alert for risk_type_id={$score->risk_type_id}/{$score->risk_level} @ location #{$score->location_id}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[CalculateRiskScoresJob] Failed: ' . $exception->getMessage());
    }
}
