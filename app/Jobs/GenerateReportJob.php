<?php

namespace App\Jobs;

use App\Models\ClinicUser;
use App\Services\DashboardAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Generates and stores a PDF/Excel report for a clinic user.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        private ClinicUser $user,
        private string     $format, // 'pdf' | 'xlsx'
        private array      $params,
    ) {}

    public function handle(DashboardAnalyticsService $analytics): void
    {
        $locationId = $this->user->canAccessAllZones() ? null : $this->user->location_id;
        $data       = $analytics->getOverview($locationId);

        $filename = "reports/childshield_report_{$this->user->id}_" . now()->format('Ymd_His') . ".{$this->format}";

        if ($this->format === 'xlsx') {
            $this->generateExcel($filename, $data);
        } else {
            $this->generatePdf($filename, $data);
        }

        // Notify user via email (if configured)
        Log::info("[GenerateReportJob] Report generated: {$filename}");
    }

    private function generateExcel(string $filename, array $data): void
    {
        // Uses Maatwebsite/Excel — implement Export class per module
        Storage::put($filename, json_encode($data)); // placeholder
    }

    private function generatePdf(string $filename, array $data): void
    {
        Storage::put($filename, json_encode($data)); // placeholder — use barryvdh/dompdf
    }
}
