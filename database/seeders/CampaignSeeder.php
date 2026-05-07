<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\ClinicUser;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $creator = ClinicUser::query()->first();

        if (! $creator) {
            $this->command?->warn('No clinic users found for CampaignSeeder.');
            return;
        }

        $campaigns = [
            [
                'title' => 'Prevencao de calor extremo',
                'message' => 'Evite exposicao ao sol nas horas quentes e mantenha hidratacao das criancas.',
                'channel' => 'both',
                'target_risk_level' => 'high',
                'status' => 'scheduled',
                'scheduled_at' => now()->addHour(),
            ],
            [
                'title' => 'Prevencao de malaria',
                'message' => 'Use redes mosquiteiras e elimine aguas paradas perto de casa.',
                'channel' => 'sms',
                'target_risk_level' => 'critical',
                'status' => 'draft',
                'scheduled_at' => null,
            ],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::query()->updateOrCreate(
                ['title' => $campaign['title']],
                [
                    ...$campaign,
                    'target_provinces' => null,
                    'target_districts' => null,
                    'recipients_total' => 0,
                    'recipients_sent' => 0,
                    'sent_at' => null,
                    'created_by' => $creator->id,
                ]
            );
        }
    }
}
