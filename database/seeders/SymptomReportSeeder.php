<?php

namespace Database\Seeders;

use App\Models\SymptomReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class SymptomReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->activeSubscribers()
            ->whereNotNull('location_id')
            ->limit(10)
            ->get();

        $symptomSets = [
            ['fever', 'headache'],
            ['diarrhea', 'vomiting'],
            ['cough', 'breathing_difficulty'],
            ['fever', 'body_pain'],
        ];

        foreach ($users as $index => $user) {
            $createdAt = now()->startOfDay()->subDays($index % 5);
            SymptomReport::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'location_id' => $user->location_id,
                    'created_at' => $createdAt,
                ],
                [
                    'symptoms' => $symptomSets[$index % count($symptomSets)],
                    'notes' => 'Seeded report for surveillance dashboard validation.',
                    'channel' => $user->channel === 'ussd' ? 'ussd' : ($user->channel === 'sms' ? 'dashboard' : 'whatsapp'),
                    'updated_at' => $createdAt->copy()->addHour(),
                ]
            );
        }
    }
}
