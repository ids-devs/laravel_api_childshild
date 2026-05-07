<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\ClinicUser;
use App\Models\RiskScore;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = ClinicUser::query()->value('id');

        $riskScores = RiskScore::query()
            ->with(['riskType'])
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        foreach ($riskScores as $riskScore) {
            Alert::query()->updateOrCreate(
                [
                    'location_id' => $riskScore->location_id,
                    'risk_score_id' => $riskScore->id,
                    'risk_type_id' => $riskScore->risk_type_id,
                ],
                [
                    'scheduled_at' => now()->startOfDay()->setHour(14),
                    'risk_level' => $riskScore->risk_level,
                    'message_pt' => "Alerta {$riskScore->riskType?->name}: risco {$riskScore->risk_level} na sua zona. Siga as orientacoes de protecao.",
                    'message_changane' => null,
                    'message_sena' => null,
                    'message_macua' => null,
                    'message_ndau' => null,
                    'channel' => 'both',
                    'status' => 'pending',
                    'recipients_total' => 0,
                    'recipients_sent' => 0,
                    'recipients_failed' => 0,
                    'delivery_report' => null,
                    'created_by' => $creatorId,
                ]
            );
        }
    }
}
