<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserHouseholdSeeder extends Seeder
{
    public function run(): void
    {
        $locations = Location::query()->inRandomOrder()->limit(8)->get();

        if ($locations->isEmpty()) {
            $this->command?->warn('No locations found for UserHouseholdSeeder.');
            return;
        }

        $profiles = [
            ['channel' => 'ussd', 'language' => 'pt', 'children' => ['0-1', '1-5'], 'pregnant' => true, 'weeks' => '13-24'],
            ['channel' => 'sms', 'language' => 'changane', 'children' => ['1-5', '6-12'], 'pregnant' => false, 'weeks' => null],
            ['channel' => 'whatsapp', 'language' => 'sena', 'children' => ['0-1'], 'pregnant' => false, 'weeks' => null],
            ['channel' => 'sms', 'language' => 'macua', 'children' => ['6-12'], 'pregnant' => false, 'weeks' => null],
            ['channel' => 'whatsapp', 'language' => 'ndau', 'children' => ['1-5'], 'pregnant' => true, 'weeks' => '25-40'],
        ];

        for ($i = 1; $i <= 15; $i++) {
            $profile = $profiles[($i - 1) % count($profiles)];
            $location = $locations[($i - 1) % $locations->count()];
            $phone = '25884' . str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT);

            $user = User::query()->updateOrCreate(
                ['email' => "subscriber{$i}@demo.childshield.mz"],
                [
                    'name' => "Household Subscriber {$i}",
                    'password' => Hash::make('Subscriber@2026!'),
                    'phone_number_encrypted' => User::encryptPhone($phone),
                    'phone_hash' => User::hashPhone($phone),
                    'channel' => $profile['channel'],
                    'language' => $profile['language'],
                    'location_id' => $location->id,
                    'consent_status' => true,
                    'subscription_active' => true,
                    'consent_given_at' => now()->subDays(rand(15, 120)),
                    'last_interaction_at' => now()->subDays(rand(0, 7)),
                    'email_verified_at' => now(),
                ]
            );

            $household = Household::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'number_of_children' => count($profile['children']),
                    'children_age_groups' => $profile['children'],
                    'pregnant_woman' => $profile['pregnant'],
                    'weeks_pregnant' => $profile['weeks'],
                ]
            );

            $household->update([
                'vulnerability_score' => $household->calculateVulnerability(),
            ]);
        }
    }
}
