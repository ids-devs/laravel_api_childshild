<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\User;
use Illuminate\Database\Seeder;

class AlertDeliverySeeder extends Seeder
{
    public function run(): void
    {
        $alerts = Alert::query()->limit(5)->get();
        $users = User::query()->activeSubscribers()->limit(12)->get();

        if ($alerts->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($alerts as $alert) {
            $selectedUsers = $users->where('location_id', $alert->location_id)->take(4);

            if ($selectedUsers->isEmpty()) {
                $selectedUsers = $users->take(4);
            }

            foreach ($selectedUsers as $idx => $user) {
                $status = $idx === 0 ? 'failed' : ($idx % 2 === 0 ? 'delivered' : 'sent');
                AlertDelivery::query()->updateOrCreate(
                    [
                        'alert_id' => $alert->id,
                        'user_id' => $user->id,
                        'channel' => $user->channel === 'whatsapp' ? 'whatsapp' : 'sms',
                    ],
                    [
                        'status' => $status,
                        'provider_message_id' => 'seed-msg-' . $alert->id . '-' . $user->id,
                        'failure_reason' => $status === 'failed' ? 'Simulated provider timeout' : null,
                        'sent_at' => now()->subMinutes(rand(10, 120)),
                        'delivered_at' => $status === 'delivered' ? now()->subMinutes(rand(1, 9)) : null,
                    ]
                );
            }
        }
    }
}
