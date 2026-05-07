<?php

namespace Tests\Unit;

use App\Jobs\CalculateRiskScoresJob;
use App\Jobs\DispatchCampaignJob;
use App\Jobs\FetchClimateDataJob;
use App\Jobs\SendSmsAlertJob;
use App\Jobs\SendWhatsappAlertJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class QueueJobsContractTest extends TestCase
{
    public function test_core_jobs_implement_should_queue(): void
    {
        $jobs = [
            FetchClimateDataJob::class,
            CalculateRiskScoresJob::class,
            SendSmsAlertJob::class,
            SendWhatsappAlertJob::class,
            DispatchCampaignJob::class,
        ];

        foreach ($jobs as $jobClass) {
            $reflection = new ReflectionClass($jobClass);
            $this->assertTrue($reflection->implementsInterface(ShouldQueue::class), "{$jobClass} must implement ShouldQueue");
        }
    }
}
