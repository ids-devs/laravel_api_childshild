<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerDefinitionTest extends TestCase
{
    public function test_expected_scheduled_events_are_registered(): void
    {
        $schedule = app(Schedule::class);
        $names = collect($schedule->events())->map(fn ($event) => $event->description)->filter()->values();

        $this->assertTrue($names->contains('fetch-climate-data'));
        $this->assertTrue($names->contains('calculate-risk-scores'));
        $this->assertTrue($names->contains('cleanup-ussd-sessions'));
        $this->assertTrue($names->contains('daily-summary-report'));
    }
}
